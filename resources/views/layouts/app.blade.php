<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'KosKu')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('css/kosku.css') }}">
</head>
<body>
    @php
        $role = $user['role'] ?? 'penyewa';
        $name = $user['nama'] ?? 'Pengguna';
        $initial = strtoupper(substr($name, 0, 1));

        $navByRole = [
            'pemilik' => [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
                ['route' => 'kamar', 'label' => 'Manajemen Kamar', 'icon' => 'bi-door-open'],
                ['route' => 'penyewa', 'label' => 'Manajemen Penyewa', 'icon' => 'bi-people'],
                ['route' => 'pembayaran', 'label' => 'Pembayaran', 'icon' => 'bi-credit-card'],
                ['route' => 'komplain', 'label' => 'Komplain', 'icon' => 'bi-chat-left-text'],
                ['route' => 'laporan', 'label' => 'Laporan Keuangan', 'icon' => 'bi-graph-up-arrow'],
            ],
            'pengelola' => [
                ['route' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'bi-speedometer2'],
                ['route' => 'kamar', 'label' => 'Manajemen Kamar', 'icon' => 'bi-door-open'],
                ['route' => 'penyewa', 'label' => 'Manajemen Penyewa', 'icon' => 'bi-people'],
                ['route' => 'pembayaran', 'label' => 'Pembayaran', 'icon' => 'bi-credit-card'],
                ['route' => 'komplain', 'label' => 'Komplain', 'icon' => 'bi-chat-left-text'],
            ],
            'penyewa' => [
                ['route' => 'dashboard', 'label' => 'Dashboard Saya', 'icon' => 'bi-house-door'],
            ],
        ];

        $navItems = $navByRole[$role] ?? $navByRole['penyewa'];
    @endphp

    <div class="app-shell">
        <aside class="sidebar" id="sidebar">
            <div class="brand">
                <div class="brand-icon"><i class="bi bi-building"></i></div>
                <div>
                    <h1>KosKu</h1>
                    <p>Manajemen Kost</p>
                </div>
                <button type="button" class="mobile-close" data-close-sidebar>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <nav class="nav-list">
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="sidebar-footer">
                <div class="user-chip">
                    <div class="avatar">{{ $initial }}</div>
                    <div>
                        <p>{{ $name }}</p>
                        <span>{{ ucfirst($role) }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        Keluar
                    </button>
                </form>
            </div>
        </aside>

        <div class="mobile-backdrop" id="mobileBackdrop" data-close-sidebar></div>

        <div class="app-main">
            <header class="topbar">
                <button type="button" class="menu-btn" data-open-sidebar>
                    <i class="bi bi-list"></i>
                </button>

                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <span>Cari sesuatu...</span>
                </div>

                <div class="topbar-right">
                    <div class="notif-wrap">
                        <button type="button" class="icon-btn" data-toggle-notif>
                            <i class="bi bi-bell"></i>
                            <span class="dot"></span>
                        </button>
                        <div class="notif-panel" id="notifPanel">
                            <h3>Notifikasi</h3>
                            <div class="notif-item">
                                <strong>Budi Santoso</strong> belum membayar tagihan bulan ini.
                            </div>
                            <div class="notif-item">
                                Komplain AC dari Ahmad Wijaya - A101.
                            </div>
                            <div class="notif-item">
                                Kamar B102 selesai maintenance.
                            </div>
                        </div>
                    </div>

                    <div class="top-user">
                        <div>
                            <p>{{ $name }}</p>
                            <span>{{ ucfirst($role) }}</span>
                        </div>
                        <div class="avatar sm">{{ $initial }}</div>
                    </div>
                </div>
            </header>

            <main class="content">
                @yield('content')
            </main>
        </div>
    </div>

    <script src="{{ asset('js/kosku.js') }}"></script>
</body>
</html>
