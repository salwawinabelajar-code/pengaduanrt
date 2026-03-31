<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>e-RT Digital - Sistem Pengaduan Warga</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #005461, #0C7779, #249E94, #3BC1A8);
            background-size: 300% 300%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            color: white;
            text-align: center;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        h1 {
            font-size: 48px;
            margin-bottom: 20px;
            animation: fadeIn 1s ease-out;
        }

        p {
            font-size: 18px;
            margin-bottom: 40px;
            opacity: 0.9;
            max-width: 600px;
            animation: fadeIn 1.2s ease-out;
        }

        .buttons {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            animation: fadeIn 1.5s ease-out;
        }

        .btn {
            padding: 15px 35px;
            font-size: 18px;
            font-weight: 600;
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-primary {
            background: white;
            color: #005461;
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .btn-primary:hover {
            background: #e0f2f1;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            font-size: 60px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .buttons {
                flex-direction: column;
                width: 100%;
                max-width: 300px;
            }
            
            .btn {
                justify-content: center;
            }
            
            h1 {
                font-size: 36px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🏠</div>
        <h1>e-RT Digital</h1>
        <p>Sistem Pengaduan dan Layanan Warga RT Digital yang Modern, Cepat, dan Transparan</p>
        
        <div class="buttons">
            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="users/dashboard.php" class="btn btn-primary">
                    <i class="fas fa-tachometer-alt"></i>
                    Ke Dashboard
                </a>
                <a href="users/logout.php" class="btn btn-secondary">
                    <i class="fas fa-sign-out-alt"></i>
                    Logout
                </a>
            <?php else: ?>
                <a href="users/login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Login ke Dashboard
                </a>
                <a href="users/register.php" class="btn btn-secondary">
                    <i class="fas fa-user-plus"></i>
                    Daftar Akun Baru
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>