<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengeluarans', function (Blueprint $table) {
            $table->id();
            $table->string('id_saas'); // Sesuai dengan kode Flutter
            $table->date('tanggal');
            $table->string('keterangan');
            $table->decimal('jumlah', 15, 2); // Gunakan decimal untuk uang
            $table->timestamps();
            
            // Asumsi: Anda mungkin ingin menambahkan foreign key ke tabel users atau saas
            // $table->foreign('id_saas')->references('id_saas')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluarans');
    }
};