<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    public function getLatestVersion()
    {
        // GANTI PENGATURAN INI UNTUK TESTING
        $versiServer = '1.0.2';
        $buildNumberServer = '2';
        $isCriticalServer = true; // <-- GANTI JADI true UNTUK TES KRITIS, false UNTUK TES BIASA
        
        // PASTIKAN NAMA FILE APK SESUAI DENGAN YANG DI-UPLOAD
        $namaFileApk = 'tb_pusat_v1.0.1.apk'; 

        // INI ADALAH URL YANG BENAR SETELAH 'php artisan storage:link'
        $downloadUrl = "https://download.nandradigital.net/" . $namaFileApk;

        return response()->json([
            'version' => $versiServer,
            'build_number' => $buildNumberServer,
            'download_url' => $downloadUrl, // URL yang sudah diperbaiki
            'release_notes' => "• Perbaikan bug kritis.\n• Peningkatan keamanan data.\n• Mohon segera update.",
            'is_critical' => $isCriticalServer,
            'minimum_required_version' => '1.0.2'
        ]);
    }
}