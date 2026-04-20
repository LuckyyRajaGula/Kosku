<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function dashboard(Request $request): View
    {
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

        $validated = $request->validate([
            'no_kamar' => ['required', 'string', 'max:10', 'unique:kamar,no_kamar'],
            'tipe_kamar' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0'],
            'status_ketersediaan' => ['required', 'in:Kosong,Terisi,Maintenance'],
            'fasilitias' => ['nullable', 'string'],
            'luas_kamar' => ['nullable', 'string', 'max:20'],
        ]);

        DB::table('kamar')->insert($validated);

        return redirect()->route('kamar')->with('success', 'Data kamar berhasil ditambahkan.');
    }

    public function updateKamar(Request $request, int $idKamar): RedirectResponse
    {
        $this->ensureCanManageRooms($request);

        $validated = $request->validate([
            'no_kamar' => ['required', 'string', 'max:10', 'unique:kamar,no_kamar,'.$idKamar.',id_kamar'],
            'tipe_kamar' => ['nullable', 'string', 'max:50'],
            'harga' => ['required', 'numeric', 'min:0'],
            'status_ketersediaan' => ['required', 'in:Kosong,Terisi,Maintenance'],
            'fasilitias' => ['nullable', 'string'],
            'luas_kamar' => ['nullable', 'string', 'max:20'],
        ]);

        DB::table('kamar')->where('id_kamar', $idKamar)->update($validated);

        return redirect()->route('kamar')->with('success', 'Data kamar berhasil diperbarui.');
    }

    public function deleteKamar(Request $request, int $idKamar): RedirectResponse
    {
        $this->ensureCanManageRooms($request);

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

    private function currentUser(Request $request): array
    {
        return $request->session()->get('kosku_user', []);
    }

    private function ensureCanManageRooms(Request $request): void
    {
        $user = $this->currentUser($request);

        abort_unless(in_array($user['role'] ?? null, ['pemilik', 'pengelola'], true), 403);
    }
}
