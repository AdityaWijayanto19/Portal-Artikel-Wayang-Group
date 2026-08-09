@extends('layouts.app')

@section('title', 'Tulis Artikel Baru · ' . $company->name)
@section('subtitle', 'Buat artikel dengan analisis SEO realtime, lalu publikasikan ke WordPress.')
@section('back_url', route('articles.index'))

@section('content')
    <div class="space-y-6">
        <form action="{{ route('articles.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="company_id" value="{{ $company->id }}">
            @include('articles.partials._form')
        </form>
    </div>
@endsection
