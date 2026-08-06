<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Tidak Ditemukan - E-RANDIS</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('favicon.ico') }}">
    <!-- Google Fonts & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(-45deg, #ff007f, #7f00ff, #00f0ff, #00ff7f);
            background-size: 400% 400%;
            animation: gradientBG 12s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Floating Colorful Blobs */
        .blob-1 {
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(255, 0, 127, 0.35);
            border-radius: 50%;
            filter: blur(60px);
            top: 10%;
            left: 10%;
            animation: moveBlob1 8s ease-in-out infinite;
            z-index: 0;
        }
        .blob-2 {
            position: absolute;
            width: 350px;
            height: 350px;
            background: rgba(0, 240, 255, 0.3);
            border-radius: 50%;
            filter: blur(60px);
            bottom: 10%;
            right: 10%;
            animation: moveBlob2 8s ease-in-out infinite;
            z-index: 0;
        }

        .error-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            padding: 20px;
            z-index: 1;
        }

        .error-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.25);
            border-radius: 32px;
            padding: 4.5rem 3rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.25), 
                        0 0 50px rgba(255, 255, 255, 0.1);
            max-width: 540px;
            width: 100%;
            text-align: center;
            color: #ffffff;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .error-code {
            font-size: 8.5rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #ffffff 30%, #ffde59 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
            letter-spacing: -2px;
            animation: float 4s ease-in-out infinite;
            display: inline-block;
            text-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .error-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1.2rem;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .error-desc {
            font-size: 1.05rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            margin-bottom: 2.8rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            color: #7f00ff;
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            padding: 16px 40px;
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .btn-back:hover {
            transform: translateY(-4px);
            box-shadow: 0 15px 35px rgba(255, 255, 255, 0.35);
            color: #ff007f;
        }

        .btn-back:active {
            transform: translateY(-1px);
        }

        /* Sweep Glow Effect */
        .btn-back::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(127, 0, 255, 0.15),
                transparent
            );
            transition: all 0.6s ease;
        }

        .btn-back:hover::after {
            left: 100%;
        }

        @keyframes gradientBG {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes moveBlob1 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(40px, -30px) scale(1.1);
            }
        }

        @keyframes moveBlob2 {
            0%, 100% {
                transform: translate(0, 0) scale(1);
            }
            50% {
                transform: translate(-40px, 30px) scale(1.1);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-15px);
            }
        }

        @media (max-width: 576px) {
            .error-card {
                padding: 3rem 1.5rem;
            }
            .error-code {
                font-size: 6rem;
            }
            .error-title {
                font-size: 1.6rem;
            }
            .error-desc {
                font-size: 0.95rem;
            }
        }
    </style>
</head>
<body>
    <div class="blob-1"></div>
    <div class="blob-2"></div>
    <div class="error-container">
        <div class="error-card">
            <div class="error-code">404</div>
            <h1 class="error-title">Halaman Tidak Ditemukan</h1>
            <p class="error-desc">Maaf, halaman yang Anda cari tidak dapat ditemukan. Kemungkinan halaman telah dipindahkan, dihapus, atau tautan yang Anda masukkan salah.</p>
            <a href="{{ url('/home') }}" class="btn-back">
                <i class="bi bi-house-door-fill"></i>
                <span>Kembali ke Beranda</span>
            </a>
        </div>
    </div>
</body>
</html>
