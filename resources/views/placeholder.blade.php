@extends('layouts.app')

@section('title', $title . ' - KosKu')

@section('content')
<section class="page-header">
    <div>
        <h2>{{ $title }}</h2>
        <p>{{ $description }}</p>
    </div>
</section>

<section class="placeholder-box">
    <div class="icon"><i class="bi bi-tools"></i></div>
    <h3>Halaman dalam pengembangan</h3>
    <p>Modul {{ $title }} akan diimplementasikan penuh pada sprint berikutnya di Laravel.</p>
    <a href="{{ route('dashboard') }}" class="primary-btn">Kembali ke Dashboard</a>
</section>
@endsection
