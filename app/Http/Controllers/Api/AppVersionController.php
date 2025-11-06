<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function getLatestVersion()
    {
        // GANTI INI DENGAN VERSI TERBARU ANDA
        // Pastikan build_number adalah INTEGER (atau string angka)
        return response()->json([
            'version' => '1.0.1', 
            'build_number' => '2', 
            'download_url' => 'https://tb.nandradigital.net/storage/app/tb_pusat_v1.0.1.apk',
            'release_notes' => "• Perbaikan bug pada halaman laporan.\n• Peningkatan performa aplikasi.\n• Penambahan fitur A dan B.",
            'is_critical' => false,
            'minimum_required_version' => '1.0.0' // Opsional
        ]);
    }
}