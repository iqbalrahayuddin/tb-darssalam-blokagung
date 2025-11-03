<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_saas',
        'tanggal',
        'nota',
        'keterangan',
        'jumlah',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal' => 'date:Y-m-d', // Sesuaikan format jika perlu
        'jumlah' => 'double', // Cast ke double agar JSON konsisten
    ];
}