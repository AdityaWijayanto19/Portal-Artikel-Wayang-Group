@extends('layouts.error')

@section('error-code')

    <span class="digit digit-1">4</span>
    <span class="digit digit-2">1</span>
    <span class="digit digit-3">9</span>

@endsection


@section('title')
    Halaman Kedaluwarsa
@endsection


@section('description')
    Sesi Anda telah berakhir. Silakan muat ulang halaman dan coba kembali.
@endsection


@section('action')

    <a href="{{ url()->previous() }}" class="btn-home">
        Kembali
    </a>

@endsection
