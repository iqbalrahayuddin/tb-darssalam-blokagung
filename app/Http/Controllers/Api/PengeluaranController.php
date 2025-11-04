<?php

namespace App\Http\Controllers\Api;

use App\Models\Pengeluaran; // Pastikan Model-nya benar
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PengeluaranController extends Controller
{
    /**
     * READ (Daftar)
     */
    public function index(Request $request)
    {
        $idSaas = Auth::user()->id_saas;
        $pengeluaran = Pengeluaran::where('id_saas', $idSaas)
            ->orderBy('tanggal', 'desc')
            ->get();

        // Mengembalikan format Objek {'data': ...} agar Flutter tidak error
        return response()->json([
            'data' => $pengeluaran
        ], 200);
    }

    /**
     * CREATE
     */
    public function store(Request $request)
    {
        $userSaasId = Auth::user()->id_saas;

        $validator = Validator::make($request->all(), [
            // id_saas tidak lagi divalidasi dari input, tapi diambil dari Auth
            'keterangan' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'tanggal' => 'required|date_format:Y-m-d', // Samakan formatnya
            
            // 'nota' telah dihapus dari validasi
        ]);

        if ($validator->fails()) {
            // Format error disamakan dengan PemasukanController
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // Ambil data yang divalidasi dan tambahkan id_saas secara paksa
        $dataToCreate = $validator->validated();
        $dataToCreate['id_saas'] = $userSaasId; // <-- Keamanan Multi-tenant

        $pengeluaran = Pengeluaran::create($dataToCreate);

        // Mengembalikan format JSON seperti kode asli
        return response()->json([
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => $pengeluaran
        ], 201);
    }

    /**
     * READ (Single)
     * Menggunakan Route Model Binding
     */
    public function show(Pengeluaran $pengeluaran)
    {
        // Cek otorisasi
        if (Auth::user()->id_saas != $pengeluaran->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Mengembalikan format JSON seperti kode asli
        return response()->json([
            'data' => $pengeluaran
        ], 200);
    }

    /**
     * UPDATE
     * Menggunakan Route Model Binding
     */
    public function update(Request $request, Pengeluaran $pengeluaran)
    {
        // Cek otorisasi
        if (Auth::user()->id_saas != $pengeluaran->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'keterangan' => 'sometimes|required|string|max:255',
            'jumlah' => 'sometimes|required|numeric|min:0',
            'tanggal' => 'sometimes|required|date_format:Y-m-d',
            // nota dan id_saas tidak diizinkan di-update
        ]);

        if ($validator->fails()) {
            // Format error disamakan dengan PemasukanController
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $pengeluaran->update($validator->validated());

        // Mengembalikan format JSON seperti kode asli
        return response()->json([
            'message' => 'Pengeluaran berhasil diupdate',
            'data' => $pengeluaran
        ], 200);
    }

    /**
     * DELETE
     * Menggunakan Route Model Binding
     */
    public function destroy(Pengeluaran $pengeluaran)
    {
        // Cek otorisasi
        if (Auth::user()->id_saas != $pengeluaran->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pengeluaran->delete();

        // Mengembalikan format JSON seperti kode asli (200 OK dengan message)
        return response()->json([
            'message' => 'Pengeluaran berhasil dihapus'
        ], 200);
    }
}