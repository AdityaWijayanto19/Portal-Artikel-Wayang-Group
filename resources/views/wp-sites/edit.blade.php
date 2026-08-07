@extends('layouts.app')
@section('title', 'Edit WP Site: ' .  $wpSite->site_name )
@section('subtitle', 'Perbarui koneksi WordPress dan penempatan perusahaan jika diperlukan')
@section('back_url', route('wp-sites.index'))

@section('content')
<form action="{{ route('wp-sites.update', $wpSite) }}" method="POST">
    @csrf
    @method('PUT')
    @include('wp-sites.partials._form')
</form>
@endsection
