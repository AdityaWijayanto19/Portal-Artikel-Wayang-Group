@extends('layouts.app')
@section('title', 'Tambah Pengguna Baru')
@section('subtitle', 'Buat akun pengguna baru untuk mengelola artikel dan publikasi.')
@section('back_url', route('users.index'))

@section('content')
<div class="max-w-6xl">
    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        @include('users.partials._form')
    </form>
</div>
@endsection
