<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ForgotPasswordController extends Controller
{
    // Fungsi untuk mengirim OTP
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email|exists:users,email']);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Email tidak terdaftar.'], 404);
        }

        $user = User::where('email', $request->email)->first();

        // Hapus OTP lama jika ada
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        // Buat OTP
        $otp = rand(100000, 999999);

        // Simpan OTP ke database
        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $otp, // Simpan OTP (unhashed, untuk verifikasi mudah)
            'created_at' => Carbon::now()
        ]);

        try {
            // Kirim email
            Mail::to($request->email)->send(new SendOtpMail($otp, $user->name));
            return response()->json(['status' => 'success', 'message' => 'OTP telah dikirim ke email Anda.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Gagal mengirim email. Coba lagi nanti.'], 500);
        }
    }

    // Fungsi untuk verifikasi OTP
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Data tidak valid.'], 422);
        }

        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        // Cek jika OTP tidak ada
        if (!$resetRequest) {
            return response()->json(['status' => 'error', 'message' => 'OTP salah.'], 400);
        }

        // Cek jika OTP kadaluarsa (misal > 10 menit)
        if (Carbon::parse($resetRequest->created_at)->addMinutes(10)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['status' => 'error', 'message' => 'OTP telah kadaluarsa. Silakan kirim ulang.'], 400);
        }

        // OTP valid
        return response()->json(['status' => 'success', 'message' => 'OTP berhasil diverifikasi.'], 200);
    }

    // Fungsi untuk reset password baru
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric|digits:6',
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validasi gagal.', 'errors' => $validator->errors()], 422);
        }

        // Verifikasi ulang OTP
        $resetRequest = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->where('token', $request->otp)
            ->first();

        if (!$resetRequest || Carbon::parse($resetRequest->created_at)->addMinutes(10)->isPast()) {
            return response()->json(['status' => 'error', 'message' => 'OTP tidak valid atau kadaluarsa.'], 400);
        }

        // Update password user
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Hapus OTP setelah berhasil
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['status' => 'success', 'message' => 'Password berhasil direset. Silakan login.'], 200);
    }
}