<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sesi Berakhir - E-RANDIS</title>
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
            background: radial-gradient(circle at 10% 20%, rgb(20, 35, 75) 0%, rgb(10, 15, 30) 90.2%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Premium Background Elements */
        body::before {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(30, 64, 175, 0.12);
            border-radius: 50%;
            filter: blur(80px);
            top: 15%;
            left: 20%;
            z-index: 0;
        }
        body::after {
            content: '';
            position: absolute;
            width: 300px;
            height: 300px;
            background: rgba(14, 165, 233, 0.08); /* Blue-sky accents */
            border-radius: 50%;
            filter: blur(80px);
            bottom: 15%;
            right: 20%;
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
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 4rem 3rem;
            box-shadow: 0 24px 64px rgba(0, 0, 0, 0.4);
            max-width: 540px;
            width: 100%;
            text-align: center;
            color: #f8fafc;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .error-icon-wrapper {
            margin-bottom: 1.5rem;
            animation: float 4s ease-in-out infinite;
            display: inline-block;
        }

        .error-icon {
            font-size: 6.5rem;
            background: linear-gradient(135deg, #38bdf8 0%, #0284c7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .error-code {
            font-size: 2.2rem;
            font-weight: 800;
            color: #38bdf8;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .error-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffffff;
        }

        .error-desc {
            font-size: 1rem;
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 2.5rem;
            font-weight: 400;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 14px;
            border: none;
            box-shadow: 0 8px 24px rgba(30, 64, 175, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(37, 99, 235, 0.4);
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
                rgba(255, 255, 255, 0.25),
                transparent
            );
            transition: all 0.6s ease;
        }

        .btn-back:hover::after {
            left: 100%;
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
            .error-icon {
                font-size: 5rem;
            }
            .error-title {
                font-size: 1.5rem;
            }
            .error-desc {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon-wrapper">
                <i class="bi bi-hourglass-split error-icon"></i>
            </div>
            <div class="error-code">419 EXPIRED</div>
            <h1 class="error-title">Sesi Halaman Berakhir</h1>
            <p class="error-desc">Maaf, halaman ini telah kedaluwarsa karena token keamanan (CSRF Token) Anda sudah tidak aktif. Hal ini biasanya terjadi jika halaman didiamkan terlalu lama tanpa ada aktivitas.</p>
            <a href="javascript:window.location.reload();" class="btn-back">
                <i class="bi bi-arrow-clockwise"></i>
                <span>Segarkan Halaman</span>
            </a>
        </div>
    </div>
</body>
</html>
