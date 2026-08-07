@props([
    'name' => 'file',
    'label' => 'Upload File',
    'previewUrl' => null,
    'accept' => 'image/*', // BISA DIISI: 'image/*', '.pdf', '.csv', '.pdf,.csv,image/*'
    'fileType' => 'image'   // OPTS: 'image' atau 'document'
])

<div x-data="{
        isDragging: false,
        preview: '{{ $previewUrl }}',
        fileName: '',
        fileSize: '',
        isModalOpen: false,

        init() {
            if (this.preview) {
                // Mengambil nama file dari URL jika ada
                this.fileName = this.preview.split('/').pop().split('?')[0];
            }
        },
        handleDrop(e) {
            let file = e.dataTransfer.files[0];
            if (file) {
                this.$refs.fileInput.files = e.dataTransfer.files;
                this.processFile(file);
            }
        },
        handleFileSelect(e) {
            let file = e.target.files[0];
            if (file) this.processFile(file);
        },
        processFile(file) {
            this.fileName = file.name;
            this.fileSize = (file.size / 1024).toFixed(1) + ' KB';

            if (file.type.startsWith('image/')) {
                this.processImage(file);
            } else {
                // Jika file non-gambar (PDF/CSV)
                this.preview = 'document';
                this.replaceInputFile(file);
            }
        },
        async processImage(file) {
            this.origSize = (file.size / 1024).toFixed(1) + ' KB';

            try {
                const compressed = await this.compressImage(file, 1600, 0.82);
                this.fileName = compressed.name;
                this.fileSize = (compressed.size / 1024).toFixed(1) + ' KB';
                this.showPreview(URL.createObjectURL(compressed));
                this.replaceInputFile(compressed);
            } catch (e) {
                console.warn('Kompresi gambar gagal, gunakan file asli:', e);
                let reader = new FileReader();
                reader.onload = (ev) => { this.showPreview(ev.target.result); };
                reader.readAsDataURL(file);
                this.replaceInputFile(file);
            }
        },
        showPreview(url) {
            if (this.preview && this.preview.startsWith('blob:')) URL.revokeObjectURL(this.preview);
            this.preview = url;
        },
        // Kompres/resize gambar di browser (canvas) → server hanya menerima gambar kecil.
        compressImage(file, maxWidth, quality) {
            return new Promise((resolve, reject) => {
                const url = URL.createObjectURL(file);
                const img = new Image();
                img.onload = () => {
                    URL.revokeObjectURL(url);
                    const { naturalWidth, naturalHeight } = img;

                    // Kirim asli bila gambar sudah kecil & ringan — hindari kehilangan kualitas.
                    if (naturalWidth <= maxWidth && file.size <= 400 * 1024) {
                        resolve(file);
                        return;
                    }

                    let width = naturalWidth;
                    let height = naturalHeight;
                    if (width > maxWidth) {
                        height = Math.round(height * maxWidth / width);
                        width = maxWidth;
                    }

                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    canvas.getContext('2d').drawImage(img, 0, 0, width, height);

                    canvas.toBlob((blob) => {
                        if (!blob) { reject(new Error('Canvas toBlob gagal')); return; }
                        const ext = blob.type === 'image/webp' ? 'webp' : (blob.type === 'image/png' ? 'png' : 'jpg');
                        const base = (file.name || 'image').replace(/\.[^.]+$/, '');
                        resolve(new File([blob], `${base}.${ext}`, { type: blob.type }));
                    }, 'image/webp', quality);
                };
                img.onerror = () => { URL.revokeObjectURL(url); reject(new Error('Gagal memuat gambar')); };
                img.src = url;
            });
        },
        replaceInputFile(file) {
            try {
                const dt = new DataTransfer();
                dt.items.add(file);
                this.$refs.fileInput.files = dt.files;
            } catch (e) {
                console.warn('DataTransfer tidak didukung, file asli tetap dipakai:', e);
            }
        },
        clearFile() {
            this.showPreview(null);
            this.fileName = '';
            this.fileSize = '';
            this.$refs.fileInput.value = '';
        },
        triggerBrowse() {
            this.$refs.fileInput.click();
        }
     }"
     class="space-y-1.5 h-full flex flex-col relative">

    @if($label)
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }}
        </label>
    @endif

    <!-- Dropzone Area -->
    <div @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="isDragging = false; handleDrop($event)"
         :class="isDragging ? 'border-[#C59B27] bg-amber-50/50' : 'border-slate-200 bg-slate-50/50 hover:border-[#C59B27]/60 hover:bg-white'"
         class="relative border-2 border-dashed rounded-2xl p-4 transition-all duration-200 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]">

        <!-- Hidden Input File -->
        <input type="file"
               name="{{ $name }}"
               x-ref="fileInput"
               @change="handleFileSelect($event)"
               accept="{{ $accept }}"
               class="hidden">

        <!-- STATE 1: Kosong (Belum ada file) -->
        <template x-if="!preview">
            <div @click="triggerBrowse()" class="w-full h-full flex flex-col items-center justify-center cursor-pointer space-y-2 py-4">
                <div class="w-12 h-12 rounded-full bg-white border border-slate-200 shadow-xs flex items-center justify-center text-[#C59B27]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-xs text-slate-700 font-semibold">
                    Tarik file ke sini, atau <span class="text-[#C59B27] underline">pilih file</span>
                </p>
                <p class="text-[10px] text-slate-400">Format yang didukung: {{ strtoupper(str_replace(['image/', '.'], '', $accept)) }}</p>
            </div>
        </template>

        <!-- STATE 2: Ada File Gambar -->
        <template x-if="preview && preview !== 'document'">
            <div class="relative w-full h-full min-h-[160px] rounded-xl overflow-hidden group flex flex-col items-center justify-center">
                <!-- Preview Thumbnail (Klik untuk Zoom/Pop-up) -->
                <img :src="preview" 
                     @click="isModalOpen = true"
                     title="Klik untuk memperbesar"
                     class="w-full h-40 object-contain rounded-xl cursor-zoom-in transition-transform duration-200 group-hover:scale-105">

                <!-- Action Floating Overlay Bar -->
                <div class="absolute bottom-2 flex items-center gap-2 bg-slate-900/80 backdrop-blur-md px-3 py-1.5 rounded-full text-white text-xs opacity-90 group-hover:opacity-100 transition">
                    <!-- Tombol Preview Modal -->
                    <button type="button" @click="isModalOpen = true" title="Pratinjau" class="p-1 hover:text-amber-400 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                    <span class="w-[1px] h-3 bg-slate-600"></span>
                    <!-- Tombol Ganti File -->
                    <button type="button" @click="triggerBrowse()" title="Ganti File" class="p-1 hover:text-amber-400 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                    </button>
                    <span class="w-[1px] h-3 bg-slate-600"></span>
                    <!-- Tombol Hapus File -->
                    <button type="button" @click="clearFile()" title="Hapus File" class="p-1 hover:text-rose-400 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>

        <!-- STATE 3: Ada File Dokumen (PDF / CSV / Docx) -->
        <template x-if="preview === 'document'">
            <div class="w-full flex items-center justify-between p-3 bg-white rounded-xl border border-slate-200 shadow-sm">
                <div class="flex items-center space-x-3 truncate">
                    <div class="p-2 bg-amber-50 text-[#C59B27] rounded-lg">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div class="text-left truncate">
                        <p class="text-xs font-semibold text-slate-800 truncate" x-text="fileName"></p>
                        <p class="text-[10px] text-slate-400" x-text="fileSize"></p>
                    </div>
                </div>
                <div class="flex items-center space-x-1">
                    <button type="button" @click="triggerBrowse()" title="Ganti File" class="p-1.5 text-slate-500 hover:text-[#C59B27] rounded-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                        </svg>
                    </button>
                    <button type="button" @click="clearFile()" title="Hapus File" class="p-1.5 text-slate-500 hover:text-rose-500 rounded-md transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                </div>
            </div>
        </template>
    </div>

    <!-- MODAL POPUP PREVIEW GAMBAR FULL-SIZE -->
    <template x-teleport="body">
        <div x-show="isModalOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @keydown.escape.window="isModalOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/80 backdrop-blur-sm"
             style="display: none;">
            
            <!-- Backdrop Overlay (Klik di luar untuk close) -->
            <div class="absolute inset-0" @click="isModalOpen = false"></div>

            <!-- Modal Content -->
            <div class="relative max-w-4xl max-h-[90vh] bg-white rounded-2xl shadow-2xl overflow-hidden z-10 flex flex-col">
                <!-- Header Modal -->
                <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/50">
                    <span class="text-xs font-semibold text-slate-700 truncate" x-text="fileName || 'Pratinjau Gambar'"></span>
                    <button @click="isModalOpen = false" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                
                <!-- Body Modal (Gambar Asli Tidak Terpotong) -->
                <div class="p-2 overflow-auto flex items-center justify-center max-h-[80vh] bg-slate-950/5">
                    <img :src="preview" class="max-w-full max-h-[75vh] object-contain rounded-lg">
                </div>
            </div>
        </div>
    </template>

    @error($name)
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>