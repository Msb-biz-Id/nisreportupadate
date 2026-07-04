<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kode OTP Login - {{ \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack')) }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            color: #333333;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 500px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #eef2f6;
        }
        .header {
            background-color: #09090b; /* Zinc 950 to match modern dark theme */
            padding: 24px 30px;
            text-align: center;
        }
        .logo-box {
            display: inline-block;
            background-color: #3b82f6; /* bg-primary blue */
            color: #ffffff;
            font-size: 20px;
            font-weight: 900;
            width: 36px;
            height: 36px;
            line-height: 36px;
            border-radius: 10px;
            text-align: center;
            vertical-align: middle;
            margin-right: 8px;
        }
        .logo-text {
            color: #ffffff;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            vertical-align: middle;
            display: inline-block;
            line-height: 36px;
        }
        .content {
            padding: 40px 30px;
            text-align: center;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #0f172a;
            text-align: left;
        }
        .intro-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.6;
            margin-bottom: 24px;
            text-align: left;
        }
        .otp-wrapper {
            margin: 30px 0;
            text-align: center;
        }
        .otp-container {
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 16px 28px;
            letter-spacing: 8px;
            font-size: 36px;
            font-weight: 800;
            color: #0f172a;
            display: inline-block;
            font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
        }
        .expiry-text {
            font-size: 13px;
            color: #ef4444;
            font-weight: 500;
            margin-top: 15px;
            margin-bottom: 30px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            border-top: 1px solid #eef2f6;
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.6;
        }
        .divider {
            height: 1px;
            background-color: #e2e8f0;
            margin: 24px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            @php 
                $dynamicName = \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack'));
                $dynamicLetter = strtoupper(substr($dynamicName, 0, 1));
            @endphp
            <span class="logo-box">{{ $dynamicLetter }}</span>
            <span class="logo-text">{{ strtoupper($dynamicName) }}</span>
        </div>
        <div class="content">
            <div class="greeting">Halo, {{ $user->name }}</div>
            <div class="intro-text">
                Kami menerima permintaan masuk ke akun Anda. Gunakan kode verifikasi (OTP) di bawah ini untuk menyelesaikan proses login:
            </div>
            
            <div class="otp-wrapper">
                <div class="otp-container">
                    {{ $otp }}
                </div>
            </div>
            
            <div class="expiry-text">
                *Kode OTP ini hanya berlaku selama 5 menit. Jangan bagikan kode ini kepada siapapun demi keamanan akun Anda.
            </div>
            
            <div class="divider"></div>
            
            <div class="intro-text" style="font-size: 12px; margin-bottom: 0; color: #64748b;">
                Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini. Akun Anda tetap aman selama tidak ada yang memiliki akses ke kode ini.
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} {{ \App\Models\Settings\SystemSetting::get('seo', 'site_name', config('app.name', 'ProTrack')) }}. All rights reserved.<br>
            Aplikasi Monitoring Laporan Produksi & Keuangan.
        </div>
    </div>
</body>
</html>
