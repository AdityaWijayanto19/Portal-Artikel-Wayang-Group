@extends('layouts.app')
@section('title', 'Edit User: ' .  $user->name )
@section('subtitle', 'Perbarui informasi profil, kredensial, atau otorisasi akses user.')
@section('back_url', route('users.index'))

@section('content')
<div class="max-w-6xl">
    <form action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')
        @include('users.partials._form')
    </form>
</div>
@endsection
