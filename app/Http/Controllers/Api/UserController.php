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
        $query = User::query(); // <-- PERUBAHAN DI SINI

        // Jika admin BUKAN 'TB Pusat', filter HANYA data diri sendiri
        if ($admin->id_saas !== 'TB Pusat') {
            $query->where('id', $admin->id); // <-- PERUBAHAN DI SINI
        } else {
            // Jika admin 'TB Pusat', tampilkan semua KECUALI diri sendiri
            $query->where('id', '!=', $admin->id); // <-- PERUBAHAN DI SINI
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
     * (Hanya bisa diakses oleh TB Pusat, UI Flutter akan menyembunyikan tombol)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'nama_toko' => ['required', 'string', Rule::in([
                'TB Pusat',
                // 'SEMUA TOKO DIATAS', // <-- DIHILANGKAN
                'TB mart', 'TB kantin', 'TB kitab', 'TB martabak',
                'TB warung', 'TB farm', 'TB putri', 'TB londry Gus rozin',
                'TB pentol', 'TB minuman kekinian', 'TB londry Ning wida'
            ])],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $plaintextPassword = $request->password;

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($plaintextPassword),
                'nama_toko' => $request->nama_toko,
                'id_saas' => $request->nama_toko,
            ]);

            // Kirim email notifikasi
            try {
                 Mail::to($user->email)->send(new UserRegisteredNotification($user, $plaintextPassword));
            } catch (\Exception $mailException) {
                // Opsional: Catat log jika email gagal terkirim
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
        $isSelf = ($admin->id == $user->id); // Cek apakah user mengedit diri sendiri

        // Admin non-pusat hanya bisa edit diri sendiri
        if (!$isAdminPusat && !$isSelf) {
             return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengedit user ini.'], 403);
        }
        
        // Admin pusat bisa edit siapa saja di saas manapun
        // Admin non-pusat bisa edit diri sendiri
        if (!$isAdminPusat && !$isSameSaas && !$isSelf) {
             return response()->json(['status' => 'error', 'message' => 'Anda tidak memiliki hak akses untuk mengedit user ini.'], 403);
        }


        // --- Logika Validasi berdasarkan Peran Admin ---
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
        ];

        // Hanya Admin Pusat yang bisa mengubah 'nama_toko'
        if ($isAdminPusat) {
            $rules['nama_toko'] = ['required', 'string', Rule::in([
                'TB Pusat',
                // 'SEMUA TOKO DIATAS', // <-- DIHILANGKAN
                'TB mart', 'TB kantin', 'TB kitab', 'TB martabak',
                'TB warung', 'TB farm', 'TB putri', 'TB londry Gus rozin',
                'TB pentol', 'TB minuman kekinian', 'TB londry Ning wida'
            ])];
        }
        // --- Selesai Logika Validasi ---

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        try {
            $user->name = $request->name;
            $user->email = $request->email;

            // Hanya Admin Pusat yang dapat mengupdate nama_toko dan id_saas
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
     * (Hanya bisa diakses oleh TB Pusat, UI Flutter akan menyembunyikan tombol)
     */
    public function destroy(Request $request, string $id)
    {
        $admin = $request->user();
        $user = User::find($id);

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'User tidak ditemukan.'], 404);
        }

        $isAdminPusat = ($admin->id_saas == 'TB Pusat');
        
        // Pengecekan: Hanya Admin Pusat yang dapat menghapus user.
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