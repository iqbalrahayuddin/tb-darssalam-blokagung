<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akun Dibuat</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .header {
            font-size: 24px;
            font-weight: bold;
            color: #444;
        }
        .content {
            margin-top: 20px;
        }
        .credentials {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .credentials p {
            margin: 5px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Selamat Datang, {{ $user->name }}!
        </div>
        <div class="content">
            <p>Akun Anda untuk aplikasi manajemen toko telah berhasil dibuat.</p>
            <p>Anda dapat login menggunakan detail berikut:</p>

            <div class="credentials">
                <p><strong>Email:</strong> {{ $user->email }}</p>
                <p><strong>Password:</strong> {{ $plaintextPassword }}</p>
                <p><strong>Toko:</strong> {{ $user->nama_toko }}</p>
            </div>

            <p>Harap simpan informasi ini dengan aman dan segera ganti password Anda jika diperlukan.</p>
        </div>
        <div class="footer">
            <p>Ini adalah email yang dibuat secara otomatis. Mohon untuk tidak membalas.</p>
        </div>
    </div>
</body>
</html>