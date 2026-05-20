<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /* ────────────────────────────────────────────────────────────
     *  DASHBOARD
     * ──────────────────────────────────────────────────────────── */

    public function dashboard(Request $request): View
    {
        $this->syncRoomStatusesByActiveTenants();

        $user = $this->currentUser($request);
        $role = $user['role'] ?? 'penyewa';
        $rooms = DB::table('kamar')->get();

        $totalKamar = $rooms->count();
        $totalTerisi = $rooms->where('status_ketersediaan', 'Terisi')->count();
        $totalKosong = $rooms->where('status_ketersediaan', 'Kosong')->count();
        $totalMaintenance = $rooms->where('status_ketersediaan', 'Maintenance')->count();
        $tingkatHunian = $totalKamar > 0 ? (int) round(($totalTerisi / $totalKamar) * 100) : 0;

        $propertyCards = collect([
            [
                'nama' => 'KosKu Utama',
                'hunian' => $tingkatHunian,
                'terisi' => $totalTerisi,
                'kosong' => $totalKosong,
                'maintenance' => $totalMaintenance,
            ],
        ]);

        // Data dinamis untuk penyewa
        $tenantData = null;
        $tenantBill = null;
        $tenantKomplainCount = 0;

        if ($role === 'penyewa') {
            $tenantData = DB::table('penyewa')
                ->leftJoin('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
                ->where('penyewa.id_user', $user['id'])
                ->whereNull('penyewa.tanggal_selesai')
                ->select(
                    'penyewa.id_penyewa',
                    'penyewa.nama',
                    'penyewa.kontrak',
                    'penyewa.tanggal_masuk',
                    'penyewa.tanggal_keluar',
                    'kamar.no_kamar',
                    'kamar.harga',
                    'kamar.tipe_kamar'
                )
                ->first();

            if ($tenantData) {
                $tenantBill = DB::table('tagihan_pembayaran')
                    ->where('id_penyewa', $tenantData->id_penyewa)
                    ->orderByDesc('tanggal_jatuh_tempo')
                    ->first();

                $tenantKomplainCount = DB::table('komplain')
                    ->where('id_penyewa', $tenantData->id_penyewa)
                    ->where('status_penanganan', '!=', 'Selesai')
                    ->count();
            }
        }

        // Hitung jumlah komplain aktif & pembayaran pending untuk pengelola/pemilik
        $komplainAktif = DB::table('komplain')
            ->where('status_penanganan', '!=', 'Selesai')
            ->count();
        $pembayaranPending = DB::table('tagihan_pembayaran')
            ->where('status', 'Belum Bayar')
            ->count();

        return view('dashboard.index', [
            'user' => $user,
            'propertyCards' => $propertyCards,
            'totalKamar' => $totalKamar,
            'totalTerisi' => $totalTerisi,
            'totalKosong' => $totalKosong,
            'totalMaintenance' => $totalMaintenance,
            'tingkatHunian' => $tingkatHunian,
            'pendapatanBulan' => $totalTerisi * 1750000,
            'tenantData' => $tenantData,
            'tenantBill' => $tenantBill,
            'tenantKomplainCount' => $tenantKomplainCount,
            'komplainAktif' => $komplainAktif,
            'pembayaranPending' => $pembayaranPending,
        ]);
    }

    /* ────────────────────────────────────────────────────────────
     *  KAMAR
     * ──────────────────────────────────────────────────────────── */

    public function kamar(Request $request): View
    {
        $this->syncRoomStatusesByActiveTenants();

        $user = $this->currentUser($request);
        $canEdit = in_array($user['role'], ['pemilik', 'pengelola'], true);
        $query = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');

        $rooms = DB::table('kamar')
            ->when($query !== '', function ($builder) use ($query) {
                $builder->where(function ($inner) use ($query) {
                    $inner->where('no_kamar', 'like', '%'.$query.'%')
                        ->orWhere('tipe_kamar', 'like', '%'.$query.'%');
                });
            })
            ->when($status !== 'all', fn ($builder) => $builder->where('status_ketersediaan', $status))
            ->orderBy('no_kamar')
            ->get()
            ->map(function ($room) {
                return [
                    'id_kamar' => $room->id_kamar,
                    'no_kamar' => $room->no_kamar,
                    'tipe_kamar' => $room->tipe_kamar,
                    'harga' => $room->harga,
                    'status_ketersediaan' => $room->status_ketersediaan,
                    'fasilitias' => $room->fasilitias,
                    'luas_kamar' => $room->luas_kamar,
                    'fasilitas_list' => collect(explode(',', (string) $room->fasilitias))
                        ->map(fn ($item) => trim($item))
                        ->filter()
                        ->values(),
                ];
            });

        return view('kamar.index', [
            'user' => $user,
            'rooms' => $rooms,
            'q' => $query,
            'status' => $status,
            'stats' => [
                'total' => $rooms->count(),
                'terisi' => $rooms->where('status_ketersediaan', 'Terisi')->count(),
                'kosong' => $rooms->where('status_ketersediaan', 'Kosong')->count(),
                'maintenance' => $rooms->where('status_ketersediaan', 'Maintenance')->count(),
            ],
            'canEdit' => $canEdit,
        ]);
    }

    public function storeKamar(Request $request): RedirectResponse
    {
        $this->ensureCanManageRooms($request);

        $fasilitas = $request->input('fasilitias');
        if (is_array($fasilitas)) {
            $request->merge(['fasilitias' => implode(', ', $fasilitas)]);
        }

        $validated = $request->validate([
            'no_kamar' => ['required', 'string', 'max:10', 'unique:kamar,no_kamar'],
            'tipe_kamar' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0'],
            'status_ketersediaan' => ['required', 'in:Kosong,Terisi,Maintenance'],
            'fasilitias' => ['nullable', 'string'],
            'luas_kamar' => ['nullable', 'string', 'max:20'],
        ]);

        // Kamar baru tidak bisa langsung "Terisi" tanpa penyewa aktif.
        if ($validated['status_ketersediaan'] === 'Terisi') {
            $validated['status_ketersediaan'] = 'Kosong';
        }

        DB::table('kamar')->insert($validated);

        return redirect()->route('kamar')->with('success', 'Data kamar berhasil ditambahkan.');
    }

    public function updateKamar(Request $request, int $idKamar): RedirectResponse
    {
        $this->ensureCanManageRooms($request);

        $roomExists = DB::table('kamar')->where('id_kamar', $idKamar)->exists();
        abort_unless($roomExists, 404);

        $fasilitas = $request->input('fasilitias');
        if (is_array($fasilitas)) {
            $request->merge(['fasilitias' => implode(', ', $fasilitas)]);
        } else if ($request->has('fasilitias') && is_null($fasilitas)) {
            // Jika dikirim tapi kosong (tidak ada checkbox terpilih)
            $request->merge(['fasilitias' => '']);
        }

        $validated = $request->validate([
            'no_kamar' => ['required', 'string', 'max:10', 'unique:kamar,no_kamar,'.$idKamar.',id_kamar'],
            'tipe_kamar' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0'],
            'status_ketersediaan' => ['required', 'in:Kosong,Terisi,Maintenance'],
            'fasilitias' => ['nullable', 'string'],
            'luas_kamar' => ['nullable', 'string', 'max:20'],
        ]);

        $activeTenant = $this->hasActiveTenantForRoom($idKamar);

        if ($validated['status_ketersediaan'] === 'Maintenance' && $activeTenant) {
            return redirect()->route('kamar')->with('error', 'Kamar yang sedang ditempati tidak bisa diubah ke Maintenance.');
        }

        $validated['status_ketersediaan'] = $this->resolveRoomStatus(
            $validated['status_ketersediaan'],
            $activeTenant
        );

        DB::table('kamar')->where('id_kamar', $idKamar)->update($validated);

        return redirect()->route('kamar')->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function deleteKamar(Request $request, int $idKamar): RedirectResponse
    {
        $this->ensureCanManageRooms($request);

        if ($this->hasActiveTenantForRoom($idKamar)) {
            return redirect()->route('kamar')->with('error', 'Kamar yang sedang ditempati tidak boleh dihapus.');
        }

        DB::table('kamar')->where('id_kamar', $idKamar)->delete();

        return redirect()->route('kamar')->with('success', 'Data kamar berhasil dihapus.');
    }

    /* ────────────────────────────────────────────────────────────
     *  PENYEWA
     * ──────────────────────────────────────────────────────────── */

    public function penyewa(Request $request): View
    {
        $this->ensureCanManageTenants($request);
        $this->syncRoomStatusesByActiveTenants();

        $today = now()->toDateString();

        $tenants = DB::table('penyewa')
            ->leftJoin('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
            ->select(
                'penyewa.id_penyewa',
                'penyewa.nama',
                'penyewa.ktp',
                'penyewa.dokumen_ktp',
                'penyewa.kontrak',
                'penyewa.tanggal_masuk',
                'penyewa.tanggal_keluar',
                'penyewa.tanggal_selesai',
                'penyewa.dokumen_kontrak',
                'penyewa.id_kamar',
                'kamar.no_kamar',
                'kamar.status_ketersediaan'
            )
            ->orderByDesc('penyewa.id_penyewa')
            ->get()
            ->map(function ($t) use ($today) {
                $t->is_active = is_null($t->tanggal_selesai)
                    && ($t->tanggal_masuk === null || $t->tanggal_masuk <= $today)
                    && ($t->tanggal_keluar === null || $t->tanggal_keluar >= $today);
                return $t;
            });

        $availableRooms = DB::table('kamar')
            ->where('status_ketersediaan', 'Kosong')
            ->orderBy('no_kamar')
            ->get(['id_kamar', 'no_kamar', 'tipe_kamar', 'harga']);

        return view('penyewa.index', [
            'user' => $this->currentUser($request),
            'tenants' => $tenants,
            'availableRooms' => $availableRooms,
        ]);
    }

    public function storePenyewa(Request $request): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'ktp' => ['nullable', 'string', 'max:20', 'unique:penyewa,ktp'],
            'kontrak' => ['nullable', 'string', 'max:50'],
            'dokumen_kontrak' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'dokumen_ktp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'tanggal_masuk' => ['required', 'date'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'id_kamar' => ['required', 'integer', 'exists:kamar,id_kamar'],
            'username' => ['required', 'string', 'max:50', 'unique:user,username'],
            'email' => ['required', 'email', 'max:100', 'unique:user,email'],
            'password' => ['required', 'string', 'min:8'],
            'no_telpon' => ['nullable', 'string', 'max:15'],
        ]);

        $kontrakPath = null;
        if ($request->hasFile('dokumen_kontrak')) {
            $kontrakPath = $request->file('dokumen_kontrak')->store('kontrak', 'public');
        }

        $ktpPath = null;
        if ($request->hasFile('dokumen_ktp')) {
            $ktpPath = $request->file('dokumen_ktp')->store('ktp', 'public');
        }

        DB::transaction(function () use ($validated, $kontrakPath, $ktpPath): void {
            $room = DB::table('kamar')->where('id_kamar', $validated['id_kamar'])->lockForUpdate()->first();

            if (!$room || $room->status_ketersediaan !== 'Kosong') {
                abort(422, 'Kamar tidak tersedia untuk check-in.');
            }

            $newUserId = DB::table('user')->insertGetId([
                'nama' => $validated['nama'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'no_telpon' => $validated['no_telpon'] ?? null,
                'role' => 'penyewa',
                'status_akun' => 'Aktif',
            ], 'id_user');

            DB::table('penyewa')->insert([
                'id_user' => $newUserId,
                'id_kamar' => $validated['id_kamar'],
                'nama' => $validated['nama'],
                'ktp' => $validated['ktp'] ?? null,
                'dokumen_ktp' => $ktpPath,
                'kontrak' => $validated['kontrak'] ?? null,
                'dokumen_kontrak' => $kontrakPath,
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'tanggal_keluar' => $validated['tanggal_keluar'] ?? null,
                'tanggal_selesai' => null,
            ]);

            DB::table('kamar')
                ->where('id_kamar', $validated['id_kamar'])
                ->update(['status_ketersediaan' => 'Terisi']);
        });

        return redirect()->route('penyewa')->with('success', 'Check-in penyewa berhasil. Kamar otomatis menjadi Terisi.');
    }

    public function updatePenyewa(Request $request, int $idPenyewa): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        $tenant = DB::table('penyewa')->where('id_penyewa', $idPenyewa)->first();
        abort_unless($tenant, 404);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'ktp' => ['nullable', 'string', 'max:20', 'unique:penyewa,ktp,'.$idPenyewa.',id_penyewa'],
            'kontrak' => ['nullable', 'string', 'max:50'],
            'tanggal_masuk' => ['required', 'date'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
        ]);

        DB::table('penyewa')->where('id_penyewa', $idPenyewa)->update($validated);

        // Juga update nama di tabel user jika terhubung
        if ($tenant->id_user) {
            DB::table('user')->where('id_user', $tenant->id_user)->update(['nama' => $validated['nama']]);
        }

        return redirect()->route('penyewa')->with('success', 'Data penyewa berhasil diperbarui.');
    }

    public function checkoutPenyewa(Request $request, int $idPenyewa): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        $tenant = DB::table('penyewa')->where('id_penyewa', $idPenyewa)->first();
        abort_unless($tenant, 404);

        if ($tenant->tanggal_selesai !== null) {
            return redirect()->route('penyewa')->with('error', 'Penyewa sudah checkout.');
        }

        DB::transaction(function () use ($tenant, $idPenyewa): void {
            DB::table('penyewa')
                ->where('id_penyewa', $idPenyewa)
                ->update(['tanggal_selesai' => now()->toDateString()]);

            // Bebaskan kamar jika ada
            if ($tenant->id_kamar) {
                // Cek apakah masih ada penyewa aktif lain di kamar ini
                $otherActive = DB::table('penyewa')
                    ->where('id_kamar', $tenant->id_kamar)
                    ->where('id_penyewa', '!=', $idPenyewa)
                    ->whereNull('tanggal_selesai')
                    ->exists();

                if (!$otherActive) {
                    DB::table('kamar')
                        ->where('id_kamar', $tenant->id_kamar)
                        ->update(['status_ketersediaan' => 'Kosong']);
                }
            }
        });

        return redirect()->route('penyewa')->with('success', 'Checkout berhasil. Kamar telah dibebaskan.');
    }

    /* ────────────────────────────────────────────────────────────
     *  PEMBAYARAN / TAGIHAN
     * ──────────────────────────────────────────────────────────── */

    public function pembayaran(Request $request): View
    {
        $user = $this->currentUser($request);
        $role = $user['role'] ?? 'penyewa';
        $filterStatus = (string) $request->query('status', 'all');

        $query = DB::table('tagihan_pembayaran')
            ->join('penyewa', 'penyewa.id_penyewa', '=', 'tagihan_pembayaran.id_penyewa')
            ->leftJoin('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
            ->select(
                'tagihan_pembayaran.*',
                'penyewa.nama as nama_penyewa',
                'kamar.no_kamar'
            );

        // Penyewa hanya lihat tagihannya sendiri
        if ($role === 'penyewa') {
            $penyewaId = DB::table('penyewa')->where('id_user', $user['id'])->value('id_penyewa');
            $query->where('tagihan_pembayaran.id_penyewa', $penyewaId ?? 0);
        }

        if ($filterStatus !== 'all') {
            $query->where('tagihan_pembayaran.status', $filterStatus);
        }

        $tagihan = $query->orderByDesc('tagihan_pembayaran.tanggal_jatuh_tempo')->get();

        // Ambil daftar penyewa aktif untuk form buat tagihan (hanya pengelola/pemilik)
        $penyewaAktif = collect();
        if (in_array($role, ['pemilik', 'pengelola'], true)) {
            $penyewaAktif = DB::table('penyewa')
                ->leftJoin('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
                ->whereNull('penyewa.tanggal_selesai')
                ->select('penyewa.id_penyewa', 'penyewa.nama', 'kamar.no_kamar', 'kamar.harga')
                ->orderBy('penyewa.nama')
                ->get();
        }

        $stats = [
            'total' => $tagihan->count(),
            'lunas' => $tagihan->where('status', 'Lunas')->count(),
            'belum' => $tagihan->where('status', 'Belum Bayar')->count(),
            'telat' => $tagihan->where('status', 'Telat')->count(),
            'totalNominal' => $tagihan->where('status', 'Lunas')->sum('nominal'),
        ];

        return view('pembayaran.index', [
            'user' => $user,
            'tagihan' => $tagihan,
            'penyewaAktif' => $penyewaAktif,
            'filterStatus' => $filterStatus,
            'stats' => $stats,
        ]);
    }

    public function storeTagihan(Request $request): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        $validated = $request->validate([
            'id_penyewa' => ['required', 'integer', 'exists:penyewa,id_penyewa'],
            'periode' => ['required', 'string', 'max:50'],
            'nominal' => ['required', 'numeric', 'min:0'],
            'tanggal_jatuh_tempo' => ['required', 'date'],
        ]);

        DB::table('tagihan_pembayaran')->insert([
            'id_penyewa' => $validated['id_penyewa'],
            'periode' => $validated['periode'],
            'nominal' => $validated['nominal'],
            'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
            'status' => 'Belum Bayar',
        ]);

        return redirect()->route('pembayaran')->with('success', 'Tagihan berhasil dibuat.');
    }

    public function updateTagihan(Request $request, int $idTagihan): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        $tagihan = DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->first();
        abort_unless($tagihan, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:Belum Bayar,Lunas,Telat,Menunggu Verifikasi'],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode_pembayaran' => ['nullable', 'string', 'max:50'],
            'bukti_bayar' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        $updateData = [
            'status' => $validated['status'],
            'tanggal_bayar' => $validated['tanggal_bayar'] ?? null,
            'metode_pembayaran' => $validated['metode_pembayaran'] ?? null,
        ];

        if ($validated['status'] === 'Lunas' && empty($updateData['tanggal_bayar'])) {
            $updateData['tanggal_bayar'] = now()->toDateString();
        }

        if ($request->hasFile('bukti_bayar')) {
            // Hapus bukti lama jika ada
            if ($tagihan->bukti_bayar) {
                Storage::disk('public')->delete($tagihan->bukti_bayar);
            }
            $updateData['bukti_bayar'] = $request->file('bukti_bayar')->store('bukti_bayar', 'public');
        }

        DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->update($updateData);

        return redirect()->route('pembayaran')->with('success', 'Tagihan berhasil diperbarui.');
    }

    public function deleteTagihan(Request $request, int $idTagihan): RedirectResponse
    {
        $this->ensureOwner($request);

        $tagihan = DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->first();
        abort_unless($tagihan, 404);

        if ($tagihan->bukti_bayar) {
            Storage::disk('public')->delete($tagihan->bukti_bayar);
        }

        DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->delete();

        return redirect()->route('pembayaran')->with('success', 'Tagihan berhasil dihapus.');
    }

    /**
     * Penyewa upload bukti pembayaran.
     * Status otomatis berubah menjadi 'Menunggu Verifikasi'.
     */
    public function uploadBuktiBayar(Request $request, int $idTagihan): RedirectResponse
    {
        $user = $this->currentUser($request);
        abort_unless(($user['role'] ?? null) === 'penyewa', 403);

        $tagihan = DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->first();
        abort_unless($tagihan, 404);

        // Pastikan tagihan milik penyewa yang login
        $penyewaId = DB::table('penyewa')->where('id_user', $user['id'])->value('id_penyewa');
        abort_unless($penyewaId && $tagihan->id_penyewa == $penyewaId, 403);

        // Hanya bisa upload jika status belum lunas
        if ($tagihan->status === 'Lunas') {
            return redirect()->route('pembayaran')->with('error', 'Tagihan ini sudah lunas.');
        }

        $validated = $request->validate([
            'bukti_bayar' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);

        // Hapus bukti lama jika ada
        if ($tagihan->bukti_bayar) {
            Storage::disk('public')->delete($tagihan->bukti_bayar);
        }

        $buktiPath = $request->file('bukti_bayar')->store('bukti_bayar', 'public');

        DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->update([
            'bukti_bayar' => $buktiPath,
            'status' => 'Menunggu Verifikasi',
        ]);

        return redirect()->route('pembayaran')->with('success', 'Bukti pembayaran berhasil diunggah. Menunggu verifikasi pengelola.');
    }

    /**
     * Pengelola/Pemilik tandai lunas dengan satu klik.
     */
    public function tandaiLunas(Request $request, int $idTagihan): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        $tagihan = DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->first();
        abort_unless($tagihan, 404);

        if ($tagihan->status === 'Lunas') {
            return redirect()->route('pembayaran')->with('error', 'Tagihan ini sudah lunas.');
        }

        DB::table('tagihan_pembayaran')->where('id_tagihan', $idTagihan)->update([
            'status' => 'Lunas',
            'tanggal_bayar' => now()->toDateString(),
        ]);

        return redirect()->route('pembayaran')->with('success', 'Tagihan berhasil ditandai lunas.');
    }

    /* ────────────────────────────────────────────────────────────
     *  KOMPLAIN
     * ──────────────────────────────────────────────────────────── */

    public function komplain(Request $request): View
    {
        $user = $this->currentUser($request);
        $role = $user['role'] ?? 'penyewa';
        $filterStatus = (string) $request->query('status', 'all');

        $query = DB::table('komplain')
            ->join('penyewa', 'penyewa.id_penyewa', '=', 'komplain.id_penyewa')
            ->leftJoin('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
            ->select(
                'komplain.*',
                'penyewa.nama as nama_penyewa',
                'kamar.no_kamar'
            );

        // Penyewa hanya lihat komplainnya sendiri
        if ($role === 'penyewa') {
            $penyewaId = DB::table('penyewa')->where('id_user', $user['id'])->value('id_penyewa');
            $query->where('komplain.id_penyewa', $penyewaId ?? 0);
        }

        if ($filterStatus !== 'all') {
            $query->where('komplain.status_penanganan', $filterStatus);
        }

        $komplainList = $query->orderByDesc('komplain.tanggal')->get();

        // Untuk penyewa: ambil id_penyewa
        $penyewaId = null;
        if ($role === 'penyewa') {
            $penyewaId = DB::table('penyewa')->where('id_user', $user['id'])->value('id_penyewa');
        }

        $stats = [
            'total' => $komplainList->count(),
            'diajukan' => $komplainList->where('status_penanganan', 'Diajukan')->count(),
            'diproses' => $komplainList->where('status_penanganan', 'Diproses')->count(),
            'selesai' => $komplainList->where('status_penanganan', 'Selesai')->count(),
        ];

        return view('komplain.index', [
            'user' => $user,
            'komplainList' => $komplainList,
            'penyewaId' => $penyewaId,
            'filterStatus' => $filterStatus,
            'stats' => $stats,
        ]);
    }

    public function storeKomplain(Request $request): RedirectResponse
    {
        $user = $this->currentUser($request);

        $validated = $request->validate([
            'jenis_komplain' => ['required', 'string', 'max:100'],
            'deskripsi' => ['required', 'string'],
        ]);

        // Tentukan id_penyewa
        if ($user['role'] === 'penyewa') {
            $penyewaId = DB::table('penyewa')->where('id_user', $user['id'])->value('id_penyewa');
            abort_unless($penyewaId, 403, 'Data penyewa tidak ditemukan.');
        } else {
            // Pengelola/pemilik bisa mewakili penyewa
            $penyewaId = $request->validate(['id_penyewa' => ['required', 'integer', 'exists:penyewa,id_penyewa']])['id_penyewa'];
        }

        DB::table('komplain')->insert([
            'id_penyewa' => $penyewaId,
            'jenis_komplain' => $validated['jenis_komplain'],
            'deskripsi' => $validated['deskripsi'],
            'tanggal' => now()->toDateString(),
            'status_penanganan' => 'Diajukan',
        ]);

        return redirect()->route('komplain')->with('success', 'Komplain berhasil diajukan.');
    }

    public function updateKomplain(Request $request, int $idKomplain): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        $komplain = DB::table('komplain')->where('id_komplain', $idKomplain)->first();
        abort_unless($komplain, 404);

        $validated = $request->validate([
            'status_penanganan' => ['required', 'in:Diajukan,Diproses,Selesai'],
            'respon' => ['nullable', 'string'],
        ]);

        $updateData = [
            'status_penanganan' => $validated['status_penanganan'],
            'respon' => $validated['respon'] ?? null,
        ];

        if ($validated['status_penanganan'] === 'Selesai') {
            $updateData['tanggal_selesai'] = now()->toDateString();
        } else {
            $updateData['tanggal_selesai'] = null;
        }

        DB::table('komplain')->where('id_komplain', $idKomplain)->update($updateData);

        return redirect()->route('komplain')->with('success', 'Status komplain berhasil diperbarui.');
    }

    public function deleteKomplain(Request $request, int $idKomplain): RedirectResponse
    {
        $this->ensureCanManageTenants($request);

        DB::table('komplain')->where('id_komplain', $idKomplain)->delete();

        return redirect()->route('komplain')->with('success', 'Komplain berhasil dihapus.');
    }

    /* ────────────────────────────────────────────────────────────
     *  LAPORAN KEUANGAN
     * ──────────────────────────────────────────────────────────── */

    public function laporan(Request $request): View
    {
        $this->ensureOwner($request);

        $filterTahun = (string) $request->query('tahun', date('Y'));

        // Ringkasan per bulan
        $bulanan = DB::table('tagihan_pembayaran')
            ->selectRaw("
                strftime('%Y-%m', tanggal_jatuh_tempo) as periode_bulan,
                COUNT(*) as total_tagihan,
                SUM(CASE WHEN status = 'Lunas' THEN 1 ELSE 0 END) as jumlah_lunas,
                SUM(CASE WHEN status = 'Belum Bayar' THEN 1 ELSE 0 END) as jumlah_belum,
                SUM(CASE WHEN status = 'Telat' THEN 1 ELSE 0 END) as jumlah_telat,
                SUM(CASE WHEN status = 'Lunas' THEN nominal ELSE 0 END) as pendapatan,
                SUM(nominal) as total_nominal
            ")
            ->whereRaw("strftime('%Y', tanggal_jatuh_tempo) = ?", [$filterTahun])
            ->groupByRaw("strftime('%Y-%m', tanggal_jatuh_tempo)")
            ->orderBy('periode_bulan')
            ->get();

        // Statistik keseluruhan tahun ini
        $statsQuery = DB::table('tagihan_pembayaran')
            ->whereRaw("strftime('%Y', tanggal_jatuh_tempo) = ?", [$filterTahun]);

        $totalPendapatan = (clone $statsQuery)->where('status', 'Lunas')->sum('nominal');
        $totalTagihan = (clone $statsQuery)->count();
        $totalLunas = (clone $statsQuery)->where('status', 'Lunas')->count();
        $totalBelum = (clone $statsQuery)->where('status', 'Belum Bayar')->count();

        // Tahun-tahun yang tersedia
        $tahunList = DB::table('tagihan_pembayaran')
            ->selectRaw("DISTINCT strftime('%Y', tanggal_jatuh_tempo) as tahun")
            ->orderByDesc('tahun')
            ->pluck('tahun');

        if ($tahunList->isEmpty()) {
            $tahunList = collect([date('Y')]);
        }

        return view('laporan.index', [
            'user' => $this->currentUser($request),
            'bulanan' => $bulanan,
            'filterTahun' => $filterTahun,
            'tahunList' => $tahunList,
            'totalPendapatan' => $totalPendapatan,
            'totalTagihan' => $totalTagihan,
            'totalLunas' => $totalLunas,
            'totalBelum' => $totalBelum,
        ]);
    }

    /* ────────────────────────────────────────────────────────────
     *  MANAJEMEN PENGGUNA (PENGELOLA)
     * ──────────────────────────────────────────────────────────── */

    public function pengguna(Request $request): View
    {
        $this->ensureOwner($request);

        $pengelola = User::query()
            ->where('role', 'pengelola')
            ->orderBy('nama')
            ->get(['id_user', 'nama', 'username', 'email', 'no_telpon', 'status_akun']);

        return view('pengguna.index', [
            'user' => $this->currentUser($request),
            'pengelola' => $pengelola,
        ]);
    }

    public function storePengelola(Request $request): RedirectResponse
    {
        $this->ensureOwner($request);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:user,username'],
            'email' => ['required', 'email', 'max:100', 'unique:user,email'],
            'password' => ['required', 'string', 'min:8'],
            'no_telpon' => ['nullable', 'string', 'max:15'],
        ]);

        User::query()->create([
            'nama' => $validated['nama'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'no_telpon' => $validated['no_telpon'] ?? null,
            'role' => 'pengelola',
            'status_akun' => 'Aktif',
        ]);

        return redirect()->route('pengguna')->with('success', 'Akun pengelola berhasil ditambahkan.');
    }

    public function updatePengelola(Request $request, int $idUser): RedirectResponse
    {
        $this->ensureOwner($request);

        $manager = User::query()->where('id_user', $idUser)->where('role', 'pengelola')->firstOrFail();

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'unique:user,username,'.$manager->id_user.',id_user'],
            'email' => ['required', 'email', 'max:100', 'unique:user,email,'.$manager->id_user.',id_user'],
            'password' => ['nullable', 'string', 'min:8'],
            'no_telpon' => ['nullable', 'string', 'max:15'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $manager->fill($validated);
        $manager->save();

        return redirect()->route('pengguna')->with('success', 'Akun pengelola berhasil diperbarui.');
    }

    public function updateStatusPengelola(Request $request, int $idUser): RedirectResponse
    {
        $this->ensureOwner($request);

        $validated = $request->validate([
            'status_akun' => ['required', 'in:Aktif,Nonaktif'],
        ]);

        $manager = User::query()->where('id_user', $idUser)->where('role', 'pengelola')->firstOrFail();
        $manager->status_akun = $validated['status_akun'];
        $manager->save();

        return redirect()->route('pengguna')->with('success', 'Status akun pengelola berhasil diperbarui.');
    }

    /* ────────────────────────────────────────────────────────────
     *  HELPERS
     * ──────────────────────────────────────────────────────────── */

    private function currentUser(Request $request): array
    {
        return $request->session()->get('kosku_user', []);
    }

    private function ensureCanManageRooms(Request $request): void
    {
        $user = $this->currentUser($request);

        abort_unless(in_array($user['role'] ?? null, ['pemilik', 'pengelola'], true), 403);
    }

    private function ensureOwner(Request $request): void
    {
        $user = $this->currentUser($request);

        abort_unless(($user['role'] ?? null) === 'pemilik', 403);
    }

    private function ensureCanManageTenants(Request $request): void
    {
        $user = $this->currentUser($request);

        abort_unless(in_array($user['role'] ?? null, ['pemilik', 'pengelola'], true), 403);
    }

    private function syncRoomStatusesByActiveTenants(): void
    {
        $rooms = DB::table('kamar')->select('id_kamar', 'status_ketersediaan')->get();

        foreach ($rooms as $room) {
            $hasActiveTenant = $this->hasActiveTenantForRoom((int) $room->id_kamar);

            if ($hasActiveTenant && $room->status_ketersediaan !== 'Terisi') {
                DB::table('kamar')
                    ->where('id_kamar', $room->id_kamar)
                    ->update(['status_ketersediaan' => 'Terisi']);

                continue;
            }

            if (!$hasActiveTenant && $room->status_ketersediaan === 'Terisi') {
                DB::table('kamar')
                    ->where('id_kamar', $room->id_kamar)
                    ->update(['status_ketersediaan' => 'Kosong']);
            }
        }
    }

    private function resolveRoomStatus(string $requestedStatus, bool $hasActiveTenant): string
    {
        if ($hasActiveTenant) {
            return 'Terisi';
        }

        if ($requestedStatus === 'Terisi') {
            return 'Kosong';
        }

        return $requestedStatus;
    }

    private function hasActiveTenantForRoom(int $idKamar): bool
    {
        $today = now()->toDateString();

        return DB::table('penyewa')
            ->where('id_kamar', $idKamar)
            ->whereNull('tanggal_selesai')
            ->where(function ($query) use ($today) {
                $query->whereNull('tanggal_masuk')
                    ->orWhereDate('tanggal_masuk', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('tanggal_keluar')
                    ->orWhereDate('tanggal_keluar', '>=', $today);
            })
            ->exists();
    }
}
