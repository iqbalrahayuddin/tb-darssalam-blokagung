<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator; // Pastikan ini ada

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
        
        // --- PERUBAHAN BARU: Dapatkan id_saas user yang login ---
        $userSaas = $user->id_saas;
        // --- BATAS PERUBAHAN ---

        // 1. Validasi input dari Flutter
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
            // Tambahkan validasi untuk filter toko (filter_saas)
            'filter_saas' => 'nullable|string|max:100', 
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Format input salah.', 'errors' => $validator->errors()], 422);
        }

        // 2. Tentukan rentang tanggal dari input
        $startDateQuery = Carbon::parse($request->input('start_date'))->startOfDay();
        $endDateQuery = Carbon::parse($request->input('end_date'))->endOfDay();
        
        // --- PERUBAHAN BARU: Baca filter toko dari request ---
        // Jika tidak ada, defaultnya adalah 'Semua Toko'
        $filterSaas = $request->input('filter_saas', 'Semua Toko');
        // --- BATAS PERUBAHAN ---

        
        // --- PERUBAHAN BARU: Logika Query Dinamis ---

        // 3. Buat query builder Pemasukan
        $pemasukanQuery = Pemasukan::query();

        // 4. Buat query builder Pengeluaran
        $pengeluaranQuery = Pengeluaran::query();

        // Terapkan filter id_saas berdasarkan role user
        if ($userSaas == 'TB Pusat') {
            // User adalah TB Pusat, dia boleh memfilter
            // Sesuai Req #2 & #3: Jika 'Semua Toko' JANGAN filter,
            // jika toko spesifik, BARU filter.
            if ($filterSaas != 'Semua Toko') {
                $pemasukanQuery->where('id_saas', $filterSaas);
                $pengeluaranQuery->where('id_saas', $filterSaas);
            }
            // Jika $filterSaas == 'Semua Toko', maka tidak ada 'where'
            // yang ditambahkan, sehingga mengambil SEMUA id_saas.
            
        } else {
            // User BUKAN TB Pusat (misal: TB Mart)
            // Sesuai Req #4: Paksa filter HANYA untuk toko dia sendiri.
            // Abaikan input $filterSaas dari request demi keamanan.
            $pemasukanQuery->where('id_saas', $userSaas);
            $pengeluaranQuery->where('id_saas', $userSaas);
        }
        
        // --- BATAS PERUBAHAN ---

        // --- PERUBAHAN BARU: Clone query untuk mengambil data detail ---
        // Kita clone SEBELUM di-groupBy
        $pemasukanDetailQuery = (clone $pemasukanQuery);
        $pengeluaranDetailQuery = (clone $pengeluaranQuery);
        // --- BATAS PERUBAHAN ---


        // Lanjutkan query dengan filter tanggal dan group by (untuk Chart)
        $pemasukanPerHari = $pemasukanQuery
            ->whereBetween('tanggal', [$startDateQuery, $endDateQuery]) 
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->select(
                DB::raw('DATE(tanggal) as tanggal_grup'),
                DB::raw('SUM(jumlah) as total_harian')
            )
            ->pluck('total_harian', 'tanggal_grup')
            ->map(fn($total) => (float)$total);

        $pengeluaranPerHari = $pengeluaranQuery
            ->whereBetween('tanggal', [$startDateQuery, $endDateQuery]) 
            ->groupBy(DB::raw('DATE(tanggal)'))
            ->select(
                DB::raw('DATE(tanggal) as tanggal_grup'),
                DB::raw('SUM(jumlah) as total_harian')
            )
            ->pluck('total_harian', 'tanggal_grup')
            ->map(fn($total) => (float)$total);

        
        // --- PERUBAHAN BARU: Ambil data detail untuk tabel SaldoDetailPage ---
        $pemasukanDetail = $pemasukanDetailQuery
            ->whereBetween('tanggal', [$startDateQuery, $endDateQuery])
            ->select('tanggal', 'keterangan', 'jumlah')
            ->get()
            ->map(function ($item) {
                return [
                    // Format tanggal agar konsisten
                    'tanggal' => Carbon::parse($item->tanggal)->format('Y-m-d'), 
                    'keterangan' => $item->keterangan ?? '-', // Beri default
                    'pemasukan' => (float)$item->jumlah,
                    'pengeluaran' => 0.0,
                ];
            });

        $pengeluaranDetail = $pengeluaranDetailQuery
            ->whereBetween('tanggal', [$startDateQuery, $endDateQuery])
            ->select('tanggal', 'keterangan', 'jumlah')
            ->get()
            ->map(function ($item) {
                return [
                    'tanggal' => Carbon::parse($item->tanggal)->format('Y-m-d'),
                    'keterangan' => $item->keterangan ?? '-', // Beri default
                    'pemasukan' => 0.0,
                    'pengeluaran' => (float)$item->jumlah,
                ];
            });

        // Gabungkan dan urutkan berdasarkan tanggal
        $detailData = $pemasukanDetail->merge($pengeluaranDetail)
                                      ->sortBy('tanggal') // Urutkan berdasarkan tanggal
                                      ->values() // Reset keys agar menjadi array
                                      ->all();
        // --- BATAS PERUBAHAN ---


        // 5. Buat array rentang tanggal penuh (untuk label chart)
        $dates = [];
        $chartIncome = [];
        $chartExpense = [];

        // Gunakan rentang tanggal dari request untuk label chart
        $periodStartDate = Carbon::parse($request->input('start_date'));
        $periodEndDate = Carbon::parse($request->input('end_date'));
        
        $period = CarbonPeriod::create($periodStartDate, $periodEndDate);

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');
            $dates[] = $dateString;
            $chartIncome[] = $pemasukanPerHari->get($dateString, 0.0);
            $chartExpense[] = $pengeluaranPerHari->get($dateString, 0.0);
        }

        // 6. Hitung Total Pemasukan/Pengeluaran
        $totalPemasukan = array_sum($chartIncome);
        $totalPengeluaran = array_sum($chartExpense);

        // 7. Kembalikan Respon JSON
        return response()->json([
            'data' => [
                'total_pemasukan' => $totalPemasukan,
                'total_pengeluaran' => $totalPengeluaran,
                
                'chart_data' => [
                    'dates' => $dates,
                    'income' => $chartIncome,
                    'expense' => $chartExpense,
                ],

                // --- PERUBAHAN BARU: Tambahkan detail data ke response ---
                'detail_data' => $detailData
                // --- BATAS PERUBAHAN ---
            ]
        ]);
    }
}