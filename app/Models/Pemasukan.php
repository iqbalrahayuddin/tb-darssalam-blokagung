<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\Storage; // <-- Tidak perlu lagi

class Pemasukan extends Model
{
    use HasFactory;
    protected $table = 'pemasukan';

    /**
     * Kolom yang boleh diisi
     */
    protected $fillable = [
        'id_saas',
        'nota',
        'keterangan',
        'jumlah',
        'tanggal',
        // 'bukti_path', // <-- DIHAPUS
    ];

    /**
     * Casts untuk tipe data
     */
    protected $casts = [
        'tanggal' => 'date',
        'jumlah' => 'decimal:2',
        'id_saas' => 'string',
    ];

    // protected $appends = ['bukti_url']; // <-- DIHAPUS
    // public function getBuktiUrlAttribute(): ?string { ... } // <-- DIHAPUS
}