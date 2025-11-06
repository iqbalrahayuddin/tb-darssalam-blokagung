<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Handle user registration.
     */
    public function register(Request $request): JsonResponse
    {
        // 1. Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // --- PERUBAHAN VALIDASI ---
            'username' => 'required|string|max:255|unique:users|alpha|lowercase', // Wajib, unik, hanya huruf, huruf kecil
            'email' => 'nullable|string|email|max:255|unique:users', // Opsional, tapi jika diisi harus unik
            'password' => 'required|string|min:8|confirmed',
            'nama_toko' => 'required|string|max:255',
        ], [
            // Pesan kustom untuk username
            'username.alpha' => 'Username hanya boleh berisi huruf.',
            'username.lowercase' => 'Username hanya boleh berisi huruf kecil.',
            'username.unique' => 'Username ini sudah digunakan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // 3. Buat user baru
        try {
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username, // <-- TAMBAHKAN
                'email' => $request->email, // <-- Email sekarang bisa null
                'password' => Hash::make($request->password),
                'nama_toko' => $request->nama_toko,
                'id_saas' => $request->nama_toko,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi berhasil',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registrasi gagal.',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Handle user login.
     */
    public function login(Request $request): JsonResponse
    {
        // --- PERUBAHAN VALIDASI ---
        $validator = Validator::make($request->all(), [
            'username' => 'required|string', // Menggunakan username
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        // --- PERUBAHAN AUTENTIKASI ---
        // Mencoba login dengan 'username' dan 'password'
        if (!Auth::attempt($request->only('username', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Username atau password salah.' // Pesan error diubah
            ], 401);
        }

        // --- PERUBAHAN LOOKUP USER ---
        $user = User::where('username', $request['username'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 200);
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil'
        ], 200);
    }
}