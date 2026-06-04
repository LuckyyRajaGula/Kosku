<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardDataController extends Controller
{
    public function ownerSummary(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || $user->role !== 'pemilik') {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $totalRooms = DB::table('kamar')->count();
        $occupiedRooms = DB::table('kamar')->where('status_ketersediaan', 'Terisi')->count();
        $occupancyRate = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 2) : 0.0;

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
        $monthlyRevenue = DB::table('tagihan_pembayaran')
            ->where('status', 'Lunas')
            ->whereBetween('tanggal_bayar', [$startOfMonth, $endOfMonth])
            ->sum('nominal');

        return response()->json([
            'property' => [
                'name' => 'KosKu Utama',
                'total_rooms' => $totalRooms,
                'occupied_rooms' => $occupiedRooms,
                'occupancy_rate' => $occupancyRate,
                'monthly_revenue' => (float) $monthlyRevenue,
                'month' => Carbon::now()->format('F Y'),
            ],
            'as_of' => Carbon::now()->toDateString(),
        ]);
    }

    public function managerSummary(): JsonResponse
    {
        $user = Auth::guard('api')->user();

        if (!$user || !in_array($user->role, ['pemilik', 'pengelola'], true)) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $emptyRooms = DB::table('kamar')->where('status_ketersediaan', 'Kosong')->count();
        $activeTenants = DB::table('penyewa')->whereNull('tanggal_selesai')->count();
        $unpaidBills = DB::table('tagihan_pembayaran')->where('status', '!=', 'Lunas')->count();
        $activeComplaints = DB::table('komplain')
            ->where('status_penanganan', '!=', 'Selesai')
            ->count();

        return response()->json([
            'empty_rooms' => $emptyRooms,
            'active_tenants' => $activeTenants,
            'unpaid_bills' => $unpaidBills,
            'active_complaints' => $activeComplaints,
            'as_of' => Carbon::now()->toDateString(),
        ]);
    }
}
