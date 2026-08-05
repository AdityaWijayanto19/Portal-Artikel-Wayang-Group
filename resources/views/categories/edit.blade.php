@extends('layouts.app')

@section('title', 'Edit Kategori: ' . $category->name)
@section('subtitle', 'Perbarui data kategori dan penempatan perusahaan jika diperlukan.')
@section('back_url', route('categories.index'))

@section('content')
<div class="max-w-6xl">
    <form action="{{ route('categories.update', $category) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('categories.partials._form')
    </form>
</div>
@endsection
