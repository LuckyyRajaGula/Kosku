@extends('layouts.auth')

@section('title', 'Login - KosKu')

@section('content')
<div class="login-scene">
    <div class="house-pattern" aria-hidden="true"></div>

    <div class="login-card">
        <h1>Selamat Datang!</h1>
        <p>Masuk untuk mengelola properti kost Anda</p>

        @if (session('error'))
            <div class="alert error">{{ session('error') }}</div>
        @endif

        @if (session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('login.submit') }}">
            @csrf

            <div class="field">
                <label for="username">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="masukkan username" required>
                @error('username')
                    <small class="text-error">{{ $message }}</small>
                @enderror
            </div>

            <div class="field">
                <div class="field-head">
                    <label for="password">Password</label>
                    <span class="muted-link">Lupa password?</span>
                </div>
                <div class="password-wrap">
                    <input id="password" type="password" name="password" placeholder="Masukkan password" required>
                    <button type="button" class="toggle-password" data-toggle-password="#password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
                @error('password')
                    <small class="text-error">{{ $message }}</small>
                @enderror
            </div>

            <button type="submit" class="primary-btn">Masuk -></button>
        </form>

        <div class="demo-box">
            <h3>Akun Seeder</h3>
            @foreach ($demoAccounts as $account)
                <div class="demo-item">
                    <span>{{ ucfirst($account['role']) }}</span>
                    <code>{{ $account['username'] }} / {{ $account['password'] }}</code>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
