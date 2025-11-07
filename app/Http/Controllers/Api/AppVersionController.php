<?php

namespace App\Http\Controllers\Api; // <-- INI DIA PERBAIKANNYA

use App\Http\Controllers\Controller; // <-- TAMBAHKAN 'use' INI
use Illuminate\Http\Request;

class AppVersionController extends Controller // <-- Tambahkan 'extends Controller'
{
    public function getLatestVersion()
    {
        // GANTI PENGATURAN INI UNTUK TESTING
        $versiServer = '1.0.2';
        $buildNumberServer = '2';
        $isCriticalServer = true; // <-- Atur ke 'true' untuk Wajib, 'false' untuk Opsional

        // Pastikan nama file ini benar dan ada di 'public/storage/'
        $namaFileApk = 'tb_pusat_v1.0.1.apk'; 

        // Pastikan URL ini bisa diakses (http atau https)
        $downloadUrl = "http://download.nandradigital.net/" . $namaFileApk;

        return response()->json([
            'version' => $versiServer,
            'build_number' => $buildNumberServer,
            'download_url' => $downloadUrl,
            'release_notes' => "• Perbaikan bug kritis (testing URL baru).\n• Peningkatan keamanan data.\n• Mohon segera update.",
            'is_critical' => $isCriticalServer,
            'minimum_required_version' => '1.0.2'
        ]);
    }
}