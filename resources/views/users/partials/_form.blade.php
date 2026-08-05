<div class="space-y-6" x-data="{
    name: '{{ old('name', $user->name ?? '') }}',
    username: '{{ old('username', $user->username ?? '') }}',
    slugify(text) {
        return text.toString().toLowerCase()
            .trim()
            .replace(/\s+/g, '')
            .replace(/[^\w\-]+/g, '');
    }
}">

    <!-- Grid 2 Kolom -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Kolom Kiri: Form Fields Utama -->
        <div class="lg:col-span-7 space-y-5">

            <!-- Nama Lengkap -->
            <x-input name="name" label="Nama Lengkap" placeholder="Contoh: Budi Santoso" x-model="name"
                @input="if(!'{{ isset($user) }}') username = slugify(name)" required />

            <!-- Email -->
            <x-input type="email" name="email" label="Alamat Email" placeholder="budi@wayanggroup.com"
                value="{{ old('email', $user->email ?? '') }}" required />

            <!-- Username WP / System -->
            <x-input name="username" label="Username WordPress" placeholder="budisantoso" x-model="username" required />

            <!-- Password & Konfirmasi (Optional saat Edit) -->
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

            <!-- Action Buttons -->
            <div class="pt-4 border-t border-slate-200 flex items-center justify-start gap-2">
                <x-button href="{{ route('users.index') }}" variant="secondary">
                    Batal
                </x-button>
                <x-button type="submit" variant="primary">
                    {{ isset($user) ? 'Perbarui User' : 'Simpan User' }}
                </x-button>
            </div>
        </div>

        <!-- Kolom Kanan: Perusahaan & Role -->
        <div class="lg:col-span-5 space-y-5">
            <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4">
    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Akses & Otorisasi</h3>

    @if (auth()->user()->hasRole('super_admin'))
        {{-- AKUTAN/EDITABLE KHUSUS SUPER ADMIN --}}

        <!-- Perusahaan Select -->
        <x-select
            name="company_id"
            label="Perusahaan / Tenant"
            :options="$companies->pluck('name', 'id')"
            :value="old('company_id', $user->company_id ?? '')"
            placeholder="Pilih Perusahaan..."
            searchable
            required
        />

        <!-- Role Select -->
        @php
            $currentRole = old('role', isset($user) ? $user->roles->first()?->name : 'author');
        @endphp

        <x-select
            name="role"
            label="Role / Peran"
            :options="[
                'author' => 'Author (Penulis Artikel)',
                'admin' => 'Admin Perusahaan',
                'super_admin' => 'Super Admin'
            ]"
            :value="$currentRole"
            placeholder="Pilih Role..."
            required
        />

    @else
        {{-- TERKUNCI KHUSUS ADMIN TENANT --}}

        @php
            $activeCompany = $companies->firstWhere('id', session('active_company_id', auth()->user()->company_id))
                ?? $companies->first();
        @endphp

        <!-- Perusahaan (Disabled + Hidden Input) -->
        <x-input
            name="company_display"
            label="Perusahaan / Tenant"
            :value="$activeCompany->name ?? 'Perusahaan Aktif'"
            disabled
        />
        <input type="hidden" name="company_id" value="{{ $activeCompany->id ?? auth()->user()->company_id }}">

        <!-- Role (Disabled + Hidden Input) -->
        <x-input
            name="role_display"
            label="Role / Peran"
            value="Author (Penulis Artikel)"
            disabled
        />
        <input type="hidden" name="role" value="author">
    @endif
</div>
        </div>

    </div>
</div>
