@extends('layouts.app')

@section('title', 'Tambah Kategori')
@section('subtitle', 'Buat kategori Artikel yang terikat pada perusahaan ke website tertentu.')
@section('back_url', route('categories.index'))

@section('content')
<div class="max-w-6xl">
    <form action="{{ route('categories.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('categories.partials._form')
    </form>
</div>
@endsection
