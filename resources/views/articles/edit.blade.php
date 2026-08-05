@extends('layouts.app')

@section('title', 'Edit Artikel · ' . $article->title)
@section('subtitle', 'Perbarui artikel dan evaluasi SEO sebelum publikasi ulang.')
@section('back_url', route('articles.index'))

@section('content')
    <div class="space-y-6">
        @if ($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-700 text-xs px-4 py-3 rounded-xl shadow-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('articles.update', $article) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="company_id" value="{{ $article->company_id }}">
            @include('articles.partials._form')
        </form>
    </div>
@endsection
