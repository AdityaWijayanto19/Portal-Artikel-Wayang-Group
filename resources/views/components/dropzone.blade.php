@props([
    'name' => 'logo',
    'label' => 'Logo Perusahaan',
    'previewUrl' => null
])

<div x-data="{
        isDragging: false,
        preview: '{{ $previewUrl }}',
        handleDrop(e) {
            let file = e.dataTransfer.files[0];
            if (file && file.type.startsWith('image/')) {
                this.$refs.fileInput.files = e.dataTransfer.files;
                this.updatePreview(file);
            }
        },
        handleFileSelect(e) {
            let file = e.target.files[0];
            if (file) this.updatePreview(file);
        },
        updatePreview(file) {
            let reader = new FileReader();
            reader.onload = (e) => { this.preview = e.target.result; };
            reader.readAsDataURL(file);
        }
     }"
     class="space-y-1.5 h-full flex flex-col">

    @if($label)
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
            {{ $label }}
        </label>
    @endif

    <div @dragover.prevent="isDragging = true"
         @dragleave.prevent="isDragging = false"
         @drop.prevent="isDragging = false; handleDrop($event)"
         :class="isDragging ? 'border-[#C59B27] bg-amber-50/50' : 'border-slate-200 bg-slate-50/50 hover:border-[#C59B27]/60 hover:bg-white'"
         class="relative border-2 border-dashed rounded-2xl p-6 transition-all duration-200 text-center cursor-pointer flex-1 flex flex-col items-center justify-center min-h-[220px]">

        <input type="file"
               name="{{ $name }}"
               x-ref="fileInput"
               @change="handleFileSelect($event)"
               accept="image/*"
               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10">

        <!-- Default Prompt -->
        <template x-if="!preview">
            <div class="space-y-2 pointer-events-none flex flex-col items-center">
                <div class="w-12 h-12 rounded-full bg-white border border-slate-200 shadow-xs flex items-center justify-center text-[#C59B27]">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <p class="text-xs text-slate-700 font-semibold">
                    Tarik gambar ke sini, atau <span class="text-[#C59B27] underline">pilih file</span>
                </p>
                <p class="text-[10px] text-slate-400">Format WEBP, PNG, JPG (Otomatis Kompres WebP)</p>
            </div>
        </template>

        <!-- Preview Gambar -->
        <template x-if="preview">
            <div class="relative w-full h-full min-h-[160px] rounded-xl overflow-hidden group">
                <img :src="preview" class="w-full h-full object-contain rounded-xl">
                <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition flex items-center justify-center text-xs text-white font-medium">
                    Klik atau drop untuk mengganti logo
                </div>
            </div>
        </template>
    </div>

    @error($name)
        <p class="text-[11px] text-rose-500 font-medium mt-1">{{ $message }}</p>
    @enderror
</div>
