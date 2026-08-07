@extends('layouts.app')
@section('title', 'Tambah WP Site')
@section('subtitle', 'Hubungkan target WordPress ke perusahaan tertentu untuk publikasi artikel.')
@section('back_url', route('wp-sites.index'))

@section('content')
<form action="{{ route('wp-sites.store') }}" method="POST">
    @csrf
    @include('wp-sites.partials._form')
</form>
@endsection
