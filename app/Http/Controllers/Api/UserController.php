<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserRegisteredNotification;

class UserController extends Controller
{
    /**
     * Menampilkan daftar semua user.
     * Logika disesuaikan berdasarkan id_saas admin.
     */
    public function index(Request $request)
    {
        $admin = $request->user();

        // Mulai query
        $query = User::query();

        // Jika admin BUKAN 'TB Pusat', filter HANYA data diri sendiri
        if ($admin->id_saas !== 'TB Pusat') {
            $query->where('id', $admin->id);
        } else {
            // Jika admin 'TB Pusat', tampilkan semua KECUALI diri sendiri
            $query->where('id', '!=', $admin->id);
        }

        $users = $query->latest()->get();

        return response()->json([
            'status' => 'success',
            'admin_id_saas' => $admin->id_saas,
            'data' => $users,
        ], 200);
    }

    /**
     * Menyimpan user baru yang dibuat oleh Admin.
     * (Hanya bisa diakses oleh TB Pusat)
     */
    public function store(Request $request)
    {
        // --- PERUBAHAN VALIDASI ---
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            // Username wajib, unik, hanya huruf, dan huruf kecil
            'username' => 'required|string|max:255|unique:users|alpha|lowercase',
             // Email opsional, tapi jika diisi, harus valid dan unik
            'email' => 'nullable|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'nama_toko' => ['required', 'string', Rule::in([
                'TB Pusat',
                'TB mart', 'TB kantin', 'TB kitab', 'TB martabak',
                'TB warung', 'TB farm', 'TB putri', 'TB londry Gus rozin',
                'TB pentol', 'TB minuman kekinian', 'TB londry Ning wida'
            ])],
        ], [
            // Pesan kustom untuk username
            'username.alpha' => 'Username hanya boleh berisi huruf.',
            'username.lowercase' => 'Username hanya boleh berisi huruf kecil.',
            'username.unique' => 'Username ini sudah digunakan.',
        ]);
        // --- SELESAI PERUBAHAN ---

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plaintextPassword = $request->password;

            // --- PERUBAHAN CREATE ---
            $user = User::create([
                'name' => $request->name,
                'username' => $request->username, // <-- TAMBAHKAN
                'email' => $request->email, // <-- Akan 'null' jika tidak diisi
                'password' => Hash::make($plaintextPassword),
                'nama_toko' => $request->nama_toko,
                'id_saas' => $request->nama_toko,
            ]);
            // --- SELESAI PERUBAHAN ---

            // Kirim email notifikasi HANYA JIKA email diisi
            try {
                 // --- PERUBAHAN LOGIKA EMAIL ---
                 if ($user->email) {
                    Mail::to($user->email)->send(new UserRegisteredNotification($user, $plaintextPassword));
                 }
                 // --- SELESAI PERUBAHAN ---
            } catch (\Exception $mailException) {
                // Opsional: Catat log jika email gagal terkirim
                // \Log::error('Gagal kirim email registrasi: ' . $mailException->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'User berhasil ditambahkan',
                'data' => $user,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menambahkan user.',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui data user.
     */
    public function update(Request $request, string $id)
    {
        $admin = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }

        $isAdminPusat = ($admin->id_saas == 'TB Pusat');
        $isSameSaas = ($user->id_saas == $admin->id_saas);
        $isSelf = ($admin->id == $user->id);

        // Admin non-pusat hanya bisa edit diri sendiri
        if (!$isAdminPusat && !$isSelf) {
             return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengedit user ini.'], 403);
        }
        
        // Admin pusat bisa edit siapa saja
        // Admin non-pusat bisa edit diri sendiri
        // (Logika $isSameSaas tidak relevan jika admin non-pusat HANYA bisa edit diri sendiri)
        // if (!$isAdminPusat && !$isSameSaas && !$isSelf) {
        //      return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengedit user ini.'], 403);
        // }


        // --- Logika Validasi berdasarkan Peran Admin ---
        $rules = [
            'name' => 'required|string|max:255',
            // --- PERUBAHAN VALIDASI ---
            'username' => ['required', 'string', 'max:255', 'alpha', 'lowercase', Rule::unique('users')->ignore($user->id)],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // --- SELESAI PERUBAHAN ---
            'password' => 'nullable|string|min:8',
        ];

        if ($isAdminPusat) {
            $rules['nama_toko'] = ['required', 'string', Rule::in([
                'TB Pusat',
                'TB mart', 'TB kantin', 'TB kitab', 'TB martabak',
                'TB warung', 'TB farm', 'TB putri', 'TB londry Gus rozin',
                'TB pentol', 'TB minuman kekinian', 'TB londry Ning wida'
            ])];
        }

        // --- PERUBAHAN PESAN KUSTOM ---
        $messages = [
            'username.alpha' => 'Username hanya boleh berisi huruf.',
            'username.lowercase' => 'Username hanya boleh berisi huruf kecil.',
            'username.unique' => 'Username ini sudah digunakan.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        // --- SELESAI PERUBAHAN ---

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            // --- PERUBAHAN UPDATE ---
            $user->name = $request->name;
            $user->username = $request->username; // <-- TAMBAHKAN
            $user->email = $request->email; // <-- Akan 'null' jika dikirim null
            // --- SELESAI PERUBAHAN ---

            if ($isAdminPusat) {
                $user->nama_toko = $request->nama_toko;
                $user->id_saas = $request->nama_toko;
            }

            if ($request->filled('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return response()->json(['status' => 'success', 'message' => 'User berhasil diupdate', 'data' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal mengupdate user.', 'errors' => $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus user.
     * (Hanya bisa diakses oleh TB Pusat)
     */
    public function destroy(Request $request, string $id)
    {
        $admin = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }

        $isAdminPusat = ($admin->id_saas == 'TB Pusat');
        
        if (!$isAdminPusat) {
             return response()->json(['status' => 'error', 'message' => 'Hanya Admin Pusat yang dapat menghapus user.'], 403);
        }


        try {
            $user->delete();
            return response()->json(['status' => 'success', 'message' => 'User berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal menghapus user.', 'errors' => $e->getMessage()], 500);
        }
    }
}