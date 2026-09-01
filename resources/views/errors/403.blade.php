@extends('layouts.error')

@section('error-code')
    <span class="digit digit-1">4</span>
    <span class="digit digit-2">0</span>
    <span class="digit digit-3">3</span>
@endsection


@section('title')
    Akses Ditolak
@endsection


@section('description')
    Anda tidak memiliki izin untuk mengakses halaman atau sumber daya ini.
@endsection


@section('action')
    <a href="{{ url('/') }}" class="btn-home">
        Kembali
    </a>
@endsection
