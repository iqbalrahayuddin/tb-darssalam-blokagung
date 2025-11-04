<?php

namespace App\Http\Controllers\Api;

use App\Models\Pemasukan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PemasukanController extends Controller
{
    /**
     * READ
     */
    public function index(Request $request)
    {
        $idSaas = Auth::user()->id_saas;
        $pemasukan = Pemasukan::where('id_saas', $idSaas)
                            ->orderBy('tanggal', 'desc')
                            ->get();

        // Mengembalikan format Objek {'data': ...}
        return response()->json([
            'data' => $pemasukan
        ], 200);
    }

    /**
     * CREATE
     */
    public function store(Request $request)
    {
        $userSaasId = Auth::user()->id_saas;

        $validator = Validator::make($request->all(), [
            // id_saas diambil dari Auth, bukan input
            'keterangan' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'tanggal' => 'required|date_format:Y-m-d',
            // 'nota' => 'required|string|max:100', // <-- DIHAPUS
        ]);

        if ($validator->fails()) {
            // Format error disamakan
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // Ambil data yang divalidasi dan tambahkan id_saas secara paksa
        $dataToCreate = $validator->validated();
        $dataToCreate['id_saas'] = $userSaasId; // <-- Keamanan Multi-tenant

        $pemasukan = Pemasukan::create($dataToCreate);

        // Format response disamakan
        return response()->json([
            'message' => 'Pemasukan berhasil ditambahkan',
            'data' => $pemasukan
        ], 201);
    }

    /**
     * READ (Single)
     */
    public function show(Request $request, Pemasukan $pemasukan)
    {
        if (Auth::user()->id_saas != $pemasukan->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Format response disamakan
        return response()->json([
            'data' => $pemasukan
        ], 200);
    }

    /**
     * UPDATE
     */
    public function update(Request $request, Pemasukan $pemasukan)
    {
        if (Auth::user()->id_saas != $pemasukan->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'keterangan' => 'sometimes|required|string|max:255',
            'jumlah' => 'sometimes|required|numeric|min:0',
            'tanggal' => 'sometimes|required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            // Format error disamakan
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $pemasukan->update($validator->validated());

        // Format response disamakan
        return response()->json([
            'message' => 'Pemasukan berhasil diupdate',
            'data' => $pemasukan
        ], 200);
    }

    /**
     * DELETE
     */
    public function destroy(Request $request, Pemasukan $pemasukan)
    {
        if (Auth::user()->id_saas != $pemasukan->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $pemasukan->delete();

        // Format response disamakan (200 OK dengan message)
        return response()->json([
            'message' => 'Pemasukan berhasil dihapus'
        ], 200);
    }
}