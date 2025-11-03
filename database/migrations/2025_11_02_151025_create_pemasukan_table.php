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
        Schema::create('pemasukan', function (Blueprint $table) {
            $table->id();
            $table->uuid('id_saas'); 
            $table->string('nota');
            $table->string('keterangan');
            $table->decimal('jumlah', 15, 2); 
            $table->date('tanggal');
            // $table->string('bukti_path')->nullable(); // <-- DIHAPUS
            $table->timestamps();
            $table->index('id_saas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pemasukan');
    }
};