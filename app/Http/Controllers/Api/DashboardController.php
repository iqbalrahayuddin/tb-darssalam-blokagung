<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Mengambil data dashboard gabungan untuk homepage Flutter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDashboardData(Request $request)
    {
        $user = $request->user();
        if (!$user || !isset($user->id_saas)) {
            return response()->json(['message' => 'User tidak terautentikasi atau id_saas tidak ditemukan.'], 401);
        }
        $idSaas = $user->id_saas;

        // --- PERBAIKAN LOGIKA WAKTU ---
        // 1. Tentukan rentang tanggal (30 hari terakhir)
        // Gunakan Carbon::now() agar mencakup transaksi hari ini
        $endDateQuery = Carbon::now(); 
        // Ambil 29 hari lalu dan set ke awal hari (00:00:00)
        $startDateQuery = Carbon::now()->subDays(29)->startOfDay(); 
        // --- AKHIR PERBAIKAN ---

        // 2. Ambil data Pemasukan harian (Logika Poin 2 Anda)
        $pemasukanPerHari = Pemasukan::where('id_saas', $idSaas)
            // Gunakan rentang tanggal yang sudah diperbaiki
            ->whereBetween('tanggal', [$startDateQuery, $endDateQuery]) 
            ->groupBy(DB::raw('DATE(tanggal)')) // Group by hari
            ->select(
                DB::raw('DATE(tanggal) as tanggal_grup'),
                DB::raw('SUM(jumlah) as total_harian')
            )
            ->pluck('total_harian', 'tanggal_grup')
            ->map(fn($total) => (float)$total);

        // 3. Ambil data Pengeluaran harian (Logika Poin 2 Anda)
        $pengeluaranPerHari = Pengeluaran::where('id_saas', $idSaas)
            // Gunakan rentang tanggal yang sudah diperbaiki
            ->whereBetween('tanggal', [$startDateQuery, $endDateQuery]) 
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->select(
                DB::raw('DATE(tanggal) as tanggal_grup'),
                DB::raw('SUM(jumlah) as total_harian')
            )
            ->pluck('total_harian', 'tanggal_grup')
            ->map(fn($total) => (float)$total);

        // 4. Buat array 30 hari penuh (untuk label chart)
        $dates = [];
        $chartIncome = [];
        $chartExpense = [];

        // Buat rentang tanggal untuk label (dari awal hari 29 hari lalu, sampai awal hari ini)
        $periodEndDate = Carbon::today();
        $periodStartDate = Carbon::today()->subDays(29);
        $period = CarbonPeriod::create($periodStartDate, $periodEndDate);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dates[] = $dateString;
            $chartIncome[] = $pemasukanPerHari->get($dateString, 0.0);
            $chartExpense[] = $pengeluaranPerHari->get($dateString, 0.0);
        }

        // 5. Hitung Total Pemasukan/Pengeluaran (Logika Poin 3 Anda)
        $totalPemasukan30Hari = array_sum($chartIncome);
        $totalPengeluaran30Hari = array_sum($chartExpense);

        // 6. Kembalikan Respon JSON
        return response()->json([
            'data' => [
                'total_pemasukan' => $totalPemasukan30Hari,
                'total_pengeluaran' => $totalPengeluaran30Hari,
                
                'chart_data' => [
                    'dates' => $dates,
                    'income' => $chartIncome, // Ini akan naik-turun
                    'expense' => $chartExpense, // Ini akan naik-turun
                ]
            ]
        ]);
    }
}