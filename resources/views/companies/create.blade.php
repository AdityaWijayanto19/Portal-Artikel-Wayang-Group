@extends('layouts.app')

@section('title', 'Tambah Perusahaan Baru')
@section('subtitle', 'Daftarkan tenant baru ke dalam sistem Wayang Group.')
@section('back_url', route('companies.index'))

@section('content')
<div class="max-w-6xl">
    <form action="{{ route('companies.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('companies.partials._form')
    </form>
</div>
@endsection
