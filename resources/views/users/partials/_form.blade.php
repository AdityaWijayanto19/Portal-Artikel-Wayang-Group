@php
    $currentRole = old('role', isset($user) ? $user->roles->first()?->name : 'author');
@endphp

<div class="space-y-6" x-data="{
    name: '{{ old('name', $user->name ?? '') }}',
    username: '{{ old('username', $user->username ?? '') }}',
    role: '{{ $currentRole }}',
    slugify(text) {
        return text.toString().trim()
            .replace(/[^\w\s\-\.]+/g, '');
    },
    onRoleChange(event) {
        const value = event.detail ?? event.target?.value;
        if (value) this.role = String(value);
    }
}">

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <div class="lg:col-span-7 space-y-5">

            <x-input name="name" label="Nama Lengkap" placeholder="Contoh: Budi Santoso" x-model="name"
                @input="if(!'{{ isset($user) }}') username = slugify(name)" required />

            <x-input type="email" name="email" label="Alamat Email" placeholder="budi@wayanggroup.com"
                value="{{ old('email', $user->email ?? '') }}" required />

            <x-input name="username" label="Username WordPress" placeholder="budisantoso" x-model="username" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input type="password" name="password" label="Password {{ isset($user) ? '(Opsional)' : '' }}"
                        placeholder="{{ isset($user) ? 'Kosongkan jika tidak diubah' : 'Minimal 8 karakter' }}"
                        :required="!isset($user)" />
                </div>
                <div>
                    <x-input type="password" name="password_confirmation" label="Konfirmasi Password"
                        placeholder="Ulangi password" :required="!isset($user)" />
                </div>
            </div>

            <div class="pt-4 border-t border-slate-200 flex items-center justify-start gap-2">
                <x-button href="{{ route('users.index') }}" variant="secondary">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    {{ isset($user) ? 'Perbarui User' : 'Simpan User' }}
                </x-button>
            </div>
        </div>

        <div class="lg:col-span-5 space-y-5">
            <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4">
                <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Akses & Otorisasi</h3>

                @if (auth()->user()->hasRole('super_admin'))
                    {{-- Role --}}
                    <div @change="onRoleChange($event)">
                        <x-select name="role" label="Peran" :options="[
                            'author' => 'Author (Penulis Artikel)',
                            'admin' => 'Admin Perusahaan',
                            'super_admin' => 'Super Admin',
                        ]" :value="$currentRole"
                            placeholder="Pilih Role..." required />
                    </div>

                    {{-- Perusahaan: Super Admin = Wayang Group (holding, bukan tenant DB) --}}
                    <template x-if="role === 'super_admin'">
                        <div>
                            <x-input name="company_holder" label="Perusahaan / Tenant" value="Wayang Group" disabled />
                            <p class="text-[11px] text-slate-500 mt-1">Super Admin mengelola holding Wayang Group (bukan perusahaan tenant).</p>
                            <input type="hidden" name="company_id" value="">
                        </div>
                    </template>

                    {{-- Perusahaan: Admin/Author = tenant nyata (Wayang Group tidak boleh) --}}
                    <template x-if="role !== 'super_admin'">
                        <div>
                            <x-select name="company_id" label="Perusahaan" :options="$companies->pluck('name', 'id')"
                                :value="old('company_id', isset($user) ? $user->company_id : '')"
                                placeholder="Pilih Perusahaan..." searchable required />
                        </div>
                    </template>
                @else
                    @php
                        $activeCompany =
                            $companies->firstWhere('id', session('active_company_id', auth()->user()->company_id)) ??
                            $companies->first();
                    @endphp

                    <x-input name="company_display" label="Perusahaan / Tenant" :value="$activeCompany->name ?? 'Perusahaan Aktif'" disabled />
                    <input type="hidden" name="company_id"
                        value="{{ $activeCompany->id ?? auth()->user()->company_id }}">

                    <x-input name="role_display" label="Role / Peran" value="Author (Penulis Artikel)" disabled />
                    <input type="hidden" name="role" value="author">
                @endif
            </div>
        </div>

    </div>
</div>
