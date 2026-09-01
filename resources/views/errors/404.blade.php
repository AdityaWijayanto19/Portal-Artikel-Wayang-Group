@extends('layouts.error')

@section('error-code')
    <span class="digit digit-1">4</span>
    <span class="digit digit-2">0</span>
    <span class="digit digit-3">4</span>
@endsection


@section('title')
    Halaman Tidak Ditemukan
@endsection


@section('description')
    Halaman yang Anda cari tidak tersedia atau mungkin telah dipindahkan.
    Silakan periksa kembali URL yang Anda masukkan.
@endsection


@section('action')
    <a href="{{ url('/') }}" class="btn-home"
        onclick="if (document.referrer) { event.preventDefault(); history.back(); }">
        Kembali
    </a>
@endsection
