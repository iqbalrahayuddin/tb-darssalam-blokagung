<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PengeluaranController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Ambil id_saas dari user yang terautentikasi
        $idSaas = $request->user()->id_saas;

        $pengeluaran = Pengeluaran::where('id_saas', $idSaas)
                                 ->orderBy('tanggal', 'desc')
                                 ->get();

        return response()->json([
            'data' => $pengeluaran
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_saas' => 'required|string',
            'keterangan' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'tanggal' => 'required|date',
            'nota' => 'required|string|unique:pengeluarans,nota',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Pastikan id_saas di request sama dengan id_saas user
        if ($request->user()->id_saas != $request->id_saas) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $pengeluaran = Pengeluaran::create($validator->validated());

        return response()->json([
            'message' => 'Pengeluaran berhasil ditambahkan',
            'data' => $pengeluaran
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pengeluaran = Pengeluaran::find($id);

        if (!$pengeluaran) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Cek otorisasi
        if ($pengeluaran->id_saas != Auth::user()->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json(['data' => $pengeluaran], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pengeluaran = Pengeluaran::find($id);

        if (!$pengeluaran) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Cek otorisasi
        if ($pengeluaran->id_saas != Auth::user()->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'keterangan' => 'sometimes|required|string|max:255',
            'jumlah' => 'sometimes|required|numeric|min:0',
            'tanggal' => 'sometimes|required|date',
            // Nota dan id_saas tidak diizinkan di-update
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $pengeluaran->update($validator->validated());

        return response()->json([
            'message' => 'Pengeluaran berhasil diupdate',
            'data' => $pengeluaran
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pengeluaran = Pengeluaran::find($id);

        if (!$pengeluaran) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        // Cek otorisasi
        if ($pengeluaran->id_saas != Auth::user()->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $pengeluaran->delete();

        // Flutter Anda mengharapkan 200 atau 204
        return response()->json(['message' => 'Pengeluaran berhasil dihapus'], 200);
    }
}