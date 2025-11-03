<?php

namespace App\Http\Controllers\Api;

use App\Models\Pemasukan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Storage; // <-- Tidak perlu lagi
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

        return response()->json(['data' => $pemasukan]);
    }

    /**
     * CREATE
     */
    public function store(Request $request)
    {
        $userSaasId = Auth::user()->id_saas;

        $validator = Validator::make($request->all(), [
            'id_saas' => ['required', 'uuid', Rule::in([$userSaasId])],
            'keterangan' => 'required|string|max:255',
            'jumlah' => 'required|numeric|min:0',
            'tanggal' => 'required|date_format:Y-m-d',
            'nota' => 'required|string|max:100',
            // 'bukti_file' => ... // <-- DIHAPUS
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }
        
        $pemasukan = Pemasukan::create($validator->validated());

        return response()->json($pemasukan, 201);
    }

    /**
     * READ (Single)
     */
    public function show(Request $request, Pemasukan $pemasukan)
    {
        if (Auth::user()->id_saas != $pemasukan->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($pemasukan);
    }

    /**
     * UPDATE
     * (Kembali menggunakan method 'update' standar dari apiResource)
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
            // 'bukti_file' => ... // <-- DIHAPUS
            // 'bukti_path' => ... // <-- DIHAPUS
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $pemasukan->update($validator->validated());

        return response()->json($pemasukan);
    }

    /**
     * DELETE
     */
    public function destroy(Request $request, Pemasukan $pemasukan)
    {
        if (Auth::user()->id_saas != $pemasukan->id_saas) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // if ($pemasukan->bukti_path) { ... } // <-- DIHAPUS
        
        $pemasukan->delete();

        return response()->json(null, 204);
    }
}