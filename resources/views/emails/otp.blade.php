<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <div style="max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
        <h2 style="color: #333;">Halo, {{ $userName }}!</h2>
        <p>Kami menerima permintaan untuk mereset password akun Anda.</p>
        <p>Gunakan Kode OTP di bawah ini untuk melanjutkan. Kode ini hanya berlaku selama 10 menit.</p>
        <p style="text-align: center; font-size: 24px; font-weight: bold; background-color: #f4f4f4; padding: 15px; border-radius: 5px; letter-spacing: 2px;">
            {{ $otp }}
        </p>
        <p>Jika Anda tidak merasa meminta reset password, abaikan email ini.</p>
        <p>Terima kasih.</p>
    </div>
</body>
</html>