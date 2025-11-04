<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf as PDF; // Pastikan import ini benar
use Throwable;

class LaporanController extends Controller
{
    public function downloadLaporanKeuangan(Request $request)
    {
        try {
            // 1. Validasi Input (toko opsional)
            $request->validate([
                'dari_tanggal' => 'required|date_format:Y-m-d',
                'sampai_tanggal' => 'required|date_format:Y-m-d|after_or_equal:dari_tanggal',
                'toko' => 'nullable|string', 
            ]);

            // 2. Ambil Parameter & ID Tenant (id_saas)
            $dari_tanggal = $request->dari_tanggal;
            $sampai_tanggal = $request->sampai_tanggal;
            
            $user = auth()->user();
            if (!$user || !$user->id_saas) {
                return response()->json([
                    'message' => 'Akses ditolak. Akun Anda tidak terhubung dengan toko manapun.'
                ], 403);
            }
            
            $user_saas = $user->id_saas;
            $toko_filter = $request->input('toko'); // Ini bisa null

            // 3. Query Pemasukan & Pengeluaran
            $pemasukanQuery = Pemasukan::whereBetween('tanggal', [$dari_tanggal, $sampai_tanggal]);
            $pengeluaranQuery = Pengeluaran::whereBetween('tanggal', [$dari_tanggal, $sampai_tanggal]);


            if ($user_saas == 'TB Pusat') {
                // Skenario 1: User adalah Master Admin ('TB Pusat')
                if ($toko_filter && $toko_filter != 'Semua Toko') {
                    // Master Admin memilih toko spesifik
                    $pemasukanQuery->where('id_saas', $toko_filter);
                    $pengeluaranQuery->where('id_saas', $toko_filter);
                }
                // Jika $toko_filter == 'Semua Toko', jangan filter id_saas
            } else {
                // Skenario 2: User adalah Tenant Biasa
                // Filter paksa ke id_saas mereka
                $pemasukanQuery->where('id_saas', $user_saas);
                $pengeluaranQuery->where('id_saas', $user_saas);
            }

            // 5. Gabungkan hasil
            $pemasukan = $pemasukanQuery->select('tanggal', 'keterangan', DB::raw('jumlah as debit'), DB::raw('0 as kredit'));
            $pengeluaran = $pengeluaranQuery->select('tanggal', 'keterangan', DB::raw('0 as debit'), DB::raw('jumlah as kredit'));

            $laporan = $pemasukan->unionAll($pengeluaran)->orderBy('tanggal', 'asc')->get();

            // 6. Hitung total
            $totalDebit = $laporan->sum('debit');
            $totalKredit = $laporan->sum('kredit');
            $saldoAkhir = $totalDebit - $totalKredit;

            // 7. Siapkan data untuk Blade View
            $data = [
                'is_pdf' => true,
                'laporan' => $laporan,
                'totalDebit' => $totalDebit,
                'totalKredit' => $totalKredit,
                'saldoAkhir' => $saldoAkhir,
                'dari_tanggal' => $dari_tanggal,
                'sampai_tanggal' => $sampai_tanggal,
                'tanggal_cetak' => Carbon::now()->isoFormat('D MMMM YYYY, HH:mm'),
            ];

            // 8. Generate PDF
            $pdf = PDF::loadView('laporan.keuangan', $data);
            
            // 9. Kembalikan PDF
            $fileNameToko = $user_saas == 'TB Pusat' ? ($toko_filter ?? 'Semua_Toko') : $user_saas;
            $fileName = 'Laporan_' . str_replace(' ', '_', $fileNameToko) . '_' . $dari_tanggal . '_sd_' . $sampai_tanggal . '.pdf';
            
            return $pdf->download($fileName);

        } catch (Throwable $th) {
            // Blok debug
            Log::error('Error PDF Laporan Keuangan: ' . $th->getMessage(), [
                'file' => $th->getFile(),
                'line' => $th->getLine(),
                'user_id' => auth()->id(),
                'request' => $request->all()
            ]);

            return response()->json([
                'message' => 'Terjadi error di server: ' . $th->getMessage(),
                'file' => $th->getFile(),
                'line' => $th->getLine(),
            ], 500); 
        }
    }
}