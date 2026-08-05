@extends('layouts.app')

@section('title', 'Edit Perusahaan')
@section('subtitle', 'Perbarui informasi dan konfigurasi ' . $company->name)
@section('back_url', route('companies.index'))

@section('content')
<div class="max-w-6xl">
    <form action="{{ route('companies.update', $company) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        @include('companies.partials._form')
    </form>
</div>
@endsection
