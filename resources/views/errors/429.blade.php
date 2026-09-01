@extends('layouts.error')

@section('error-code')
    <span class="digit digit-1">4</span>
    <span class="digit digit-2">2</span>
    <span class="digit digit-3">9</span>
@endsection


@section('title')
    Terlalu Banyak Percobaan
@endsection


@section('description')
    Anda melakukan terlalu banyak percobaan dalam waktu singkat.
    Silakan tunggu beberapa saat sebelum mencoba lagi.
@endsection


@section('action')
    <a href="{{ url()->previous() }}" class="btn-home">
        Kembali
    </a>
@endsection
