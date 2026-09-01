@extends('layouts.error')

@section('error-code')
    <span class="digit digit-1">5</span>
    <span class="digit digit-2">0</span>
    <span class="digit digit-3">0</span>
@endsection


@section('title')
    Terjadi Kesalahan Server
@endsection


@section('description')
    Server mengalami gangguan dan tidak dapat memproses permintaan Anda saat ini.
    Tim teknis telah diberitahu. Silakan coba lagi dalam beberapa saat.
@endsection


@section('action')
    <a href="{{ url()->previous() }}" class="btn-home">
        Kembali
    </a>
@endsection
