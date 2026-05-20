@extends('layouts.auth')

@section('title', 'Login - KosKu')

@section('content')
<div class="login-scene">
    <!-- Orbs for ambient background glow -->
    <div class="ambient-orb" aria-hidden="true"></div>
    <div class="ambient-orb-2" aria-hidden="true"></div>

    <div class="login-wrapper">
        <!-- Left Visual branding pane -->
        <div class="login-left">
            <div class="login-left-brand">
                <i class="bi bi-houses-fill" style="color: #ffffff;"></i>
                <h2>KosKu</h2>
            </div>
            
            <div class="login-left-body">
                <h1>Sistem Manajemen Kost Modern</h1>
                <p>Kelola hunian, pantau tagihan, dan layani komplain penyewa secara digital, cepat, dan otomatis dalam satu genggaman premium.</p>
                
                <div class="features-list">
                    <div class="feature-item">
                        <i class="bi bi-shield-check" style="color: #ffffff;"></i>
                        <span>Keamanan Data & Role Terproteksi</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-cash-coin" style="color: #ffffff;"></i>
                        <span>Pencatatan & Tagihan Otomatis</span>
                    </div>
                    <div class="feature-item">
                        <i class="bi bi-chat-heart" style="color: #ffffff;"></i>
                        <span>Penanganan Komplain Terpusat</span>
                    </div>
                </div>
            </div>
            
            <div class="login-left-footer">
                <p>© 2026 KosKu Premium. All rights reserved.</p>
            </div>
        </div>

        <!-- Right interactive Form pane -->
        <div class="login-right">
            <div class="login-right-header">
                <h1>Selamat Datang!</h1>
                <p>Masuk untuk mengelola properti kost Anda</p>
            </div>

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
                    <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="Masukkan username" required>
                    @error('username')
                        <small class="text-error" style="font-size: 11px; margin-top: 4px; display: block;">{{ $message }}</small>
                    @enderror
                </div>

                <div class="field">
                    <div class="field-head">
                        <label for="password">Password</label>
                        <span class="muted-link" style="cursor: pointer;">Lupa password?</span>
                    </div>
                    <div class="password-wrap">
                        <input id="password" type="password" name="password" placeholder="Masukkan password" required>
                        <button type="button" class="toggle-password" data-toggle-password="#password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <small class="text-error" style="font-size: 11px; margin-top: 4px; display: block;">{{ $message }}</small>
                    @enderror
                </div>

                <button type="submit" class="primary-btn">
                    Masuk <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <div class="quick-demo-box">
                <div class="quick-demo-title">
                    <i class="bi bi-lightning-charge-fill"></i> Autologin Demo Cepat
                </div>
                <div class="demo-chips-grid">
                    @foreach ($demoAccounts as $account)
                        @php
                            $userNick = 'User';
                            if ($account['role'] === 'pemilik') $userNick = 'Budi';
                            elseif ($account['role'] === 'pengelola') $userNick = 'Siti';
                            elseif ($account['role'] === 'penyewa') $userNick = 'Ahmad';
                        @endphp
                        <button type="button" class="demo-chip" data-username="{{ $account['username'] }}" data-password="{{ $account['password'] }}">
                            <span class="chip-role {{ $account['role'] }}">{{ $account['role'] }}</span>
                            <span class="chip-user">{{ $userNick }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
