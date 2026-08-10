@extends('layouts.app')
@section('title', 'Profile Saya')
@section('subtitle', 'Kelola informasi akun dan kredensial login Anda.')

@section('content')
@php
    $isAuthor = $user->isAuthor();
    $currentRole = $user->roles->first()?->name ?? '-';
    $companyName = $user->company?->name ?? ($user->companies->first()?->name ?? '-');
@endphp

<div class="max-w-6xl">
    <form action="{{ route('profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <div class="lg:col-span-7 space-y-5">
                <x-input name="name" label="Nama Lengkap" placeholder="Contoh: Budi Santoso"
                    value="{{ old('name', $user->name) }}" required />

                <x-input type="email" name="email" label="Alamat Email" placeholder="budi@wayanggroup.com"
                    value="{{ old('email', $user->email) }}" :required="!$isAuthor" :disabled="$isAuthor" />

                <x-input name="username" label="Username WordPress" placeholder="budisantoso"
                    value="{{ old('username', $user->username) }}" :required="!$isAuthor" :disabled="$isAuthor" />

                @if ($isAuthor)
                    <p class="text-[11px] text-slate-500">Email dan username dikelola oleh admin perusahaan. Hubungi admin jika ingin mengubahnya.</p>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input type="password" name="password" label="Password Baru"
                            placeholder="Kosongkan jika tidak diubah" />
                    </div>
                    <div>
                        <x-input type="password" name="password_confirmation" label="Konfirmasi Password"
                            placeholder="Ulangi password baru" />
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-200 flex items-center justify-start gap-2">
                    <x-button href="{{ route('dashboard') }}" variant="secondary">
                        Batal
                    </x-button>
                    <x-button type="submit" variant="primary">
                        Simpan Profil
                    </x-button>
                </div>
            </div>

            <div class="lg:col-span-5 space-y-5">
                <div class="bg-slate-50/70 p-5 rounded-2xl border border-slate-200/80 space-y-4">
                    <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Informasi Akun</h3>

                    <x-input name="company_display" label="Perusahaan / Tenant" value="{{ $companyName }}"
                        disabled />

                    <x-input name="role_display" label="Role / Peran" value="{{ ucwords(str_replace('_', ' ', $currentRole)) }}"
                        disabled />

                    <p class="text-[11px] text-slate-500">Data ini dikelola oleh admin dan tidak dapat diubah dari halaman ini.</p>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection
