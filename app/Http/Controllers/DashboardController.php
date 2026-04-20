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
    public function dashboard(Request $request): View
    {
        $this->syncRoomStatusesByActiveTenants();

        $user = $this->currentUser($request);
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

        return view('dashboard.index', [
            'user' => $user,
            'propertyCards' => $propertyCards,
            'totalKamar' => $totalKamar,
            'totalTerisi' => $totalTerisi,
            'totalKosong' => $totalKosong,
            'totalMaintenance' => $totalMaintenance,
            'tingkatHunian' => $tingkatHunian,
            'pendapatanBulan' => $totalTerisi * 1750000,
        ]);
    }

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

    public function penyewa(Request $request): View
    {
        $this->ensureCanManageTenants($request);
        $this->syncRoomStatusesByActiveTenants();

        $tenants = DB::table('penyewa')
            ->leftJoin('kamar', 'kamar.id_kamar', '=', 'penyewa.id_kamar')
            ->select(
                'penyewa.id_penyewa',
                'penyewa.nama',
                'penyewa.ktp',
                'penyewa.kontrak',
                'penyewa.tanggal_masuk',
                'penyewa.tanggal_keluar',
                'penyewa.dokumen_kontrak',
                'kamar.no_kamar',
                'kamar.status_ketersediaan'
            )
            ->orderByDesc('penyewa.id_penyewa')
            ->get();

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

        DB::transaction(function () use ($validated, $kontrakPath): void {
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

    public function storeKamar(Request $request): RedirectResponse
    {
        $this->ensureCanManageRooms($request);

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

    public function placeholder(Request $request, string $module): View
    {
        $map = [
            'penyewa' => ['title' => 'Manajemen Penyewa', 'description' => 'Kelola data penyewa dan kontrak digital.'],
            'pembayaran' => ['title' => 'Manajemen Pembayaran', 'description' => 'Catat dan pantau pembayaran sewa kost.'],
            'komplain' => ['title' => 'Manajemen Komplain', 'description' => 'Kelola komplain dan laporan dari penyewa.'],
            'laporan' => ['title' => 'Laporan Keuangan', 'description' => 'Analisis pendapatan dan pengeluaran usaha kost.'],
        ];

        abort_unless(array_key_exists($module, $map), 404);

        return view('placeholder', [
            'user' => $this->currentUser($request),
            'title' => $map[$module]['title'],
            'description' => $map[$module]['description'],
            'module' => $module,
        ]);
    }

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
