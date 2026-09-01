<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Request Tidak Valid - Portal Artikel</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            color: #334155;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.08);
            padding: 48px;
            max-width: 480px;
            text-align: center;
        }
        .icon {
            width: 64px; height: 64px;
            margin: 0 auto 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        .icon-blue { background: #dbeafe; }
        .code { font-size: 13px; font-weight: 700; color: #94a3b8; margin-bottom: 8px; letter-spacing: 2px; }
        h1 { font-size: 20px; font-weight: 700; margin-bottom: 12px; color: #0f172a; }
        p { font-size: 14px; line-height: 1.6; color: #64748b; margin-bottom: 24px; }
        .btn {
            display: inline-block;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.15s;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-outline {
            background: transparent;
            color: #64748b;
            border: 1px solid #e2e8f0;
            margin-left: 8px;
        }
        .btn-outline:hover { background: #f8fafc; }
    </style>
</head>

<body>
    <div class="container">
        <div class="icon icon-blue">⚠️</div>
        <div class="code">ERROR 400</div>
        <h1>Request Tidak Valid</h1>
        <p>
            Permintaan yang Anda kirim tidak dapat diproses oleh server.
            Kemungkinan ada data yang tidak lengkap atau format yang salah.
        </p>
        <a href="javascript:window.location.reload()" class="btn btn-primary">Muat Ulang</a>
        <a href="javascript:history.back()" class="btn btn-outline">Kembali</a>
    </div>
</body>

</html>
