<?php
// register.php - Halaman Registrasi dengan Tema Glass Modern
// File disimpan di folder auth, sehingga path ke folder lain perlu naik satu level
session_start();
require_once('../config/db.php');

// Jika sudah login, redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    // Jika sudah login, arahkan ke halaman sesuai role
    if ($_SESSION['role'] == 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../users/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

// Cek koneksi database
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($nama) || empty($email) || empty($username) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } elseif ($password !== $confirm_password) {
        $error = "Password dan konfirmasi password tidak cocok!";
    } elseif (strlen($password) < 8) {
        $error = "Password minimal 8 karakter!";
    } else {
        // Cek apakah username/email sudah terdaftar
        $check_query = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (!$check_result) {
            $error = "Error query: " . mysqli_error($conn);
        } elseif (mysqli_num_rows($check_result) > 0) {
            $error = "Username atau email sudah terdaftar!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Query insert (role default 'warga')
            $query = "INSERT INTO users (nama, email, username, password, role, created_at) 
                     VALUES ('$nama', '$email', '$username', '$hashed_password', 'warga', NOW())";
            
            if (mysqli_query($conn, $query)) {
                // Ambil ID user yang baru dibuat
                $user_id = mysqli_insert_id($conn);
                
                // Set session untuk otomatis login
                $_SESSION['user_id'] = $user_id;
                $_SESSION['nama'] = $nama;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'warga';
                
                // Redirect ke dashboard user
                header('Location: ../users/dashboard.php');
                exit();
                
            } else {
                $error = "Terjadi kesalahan: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
        }

        :root {
            --primary: #005461;
            --primary-light: #0C7779;
            --secondary: #249E94;
            --accent: #3BC1A8;
            --light: #F8F9FA;
            --dark: #1A1A2E;
            --gray: #6C757D;
            --danger: #EF476F;
            --warning: #FFD166;
            --success: #06D6A0;
        }

        body {
            background: url('https://images.unsplash.com/photo-1506905925346-21bda4d32df4?q=80&w=2070&auto=format&fit=crop') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(145deg, rgba(0,84,97,0.7) 0%, rgba(36,158,148,0.6) 100%);
            backdrop-filter: blur(3px);
            z-index: -1;
        }

        .register-container {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 50px 40px;
            width: 100%;
            max-width: 500px;
            border: 1px solid rgba(255,255,255,0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-icon {
            background: var(--accent);
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            color: white;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .logo h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin: 10px 0;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .logo p {
            color: rgba(255,255,255,0.9);
            font-size: 14px;
        }

        .message {
            padding: 12px 18px;
            border-radius: 30px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 500;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .success {
            background: rgba(6,214,160,0.2);
            color: white;
        }

        .error {
            background: rgba(239,71,111,0.2);
            color: white;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .form-group input {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 30px;
            font-size: 16px;
            transition: all 0.3s;
            background: rgba(255,255,255,0.1);
            color: white;
            backdrop-filter: blur(5px);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent);
            background: rgba(255,255,255,0.2);
            box-shadow: 0 0 0 3px rgba(59,193,168,0.2);
        }

        .form-group input::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .password-info {
            font-size: 12px;
            color: rgba(255,255,255,0.6);
            margin-top: 5px;
        }

        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            background: rgba(255,255,255,0.1);
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--secondary), var(--accent));
            color: white;
            border: none;
            padding: 16px;
            width: 100%;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(36,158,148,0.3);
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(36,158,148,0.5);
        }

        .login-link {
            text-align: center;
            margin-top: 25px;
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }

        .login-link a {
            color: white;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid var(--accent);
        }

        .login-link a:hover {
            color: var(--accent);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .register-container {
                padding: 30px 20px;
            }
            .logo h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-user-plus"></i></div>
            <h1>e-RT Digital</h1>
            <p>Buat akun untuk akses dashboard digital</p>
        </div>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="registerForm">
            <div class="form-group">
                <label for="nama"><i class="fas fa-user"></i> Nama Lengkap *</label>
                <input type="text" id="nama" name="nama" required 
                       value="<?php echo isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : ''; ?>"
                       placeholder="Masukkan nama lengkap">
            </div>
            
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                <input type="email" id="email" name="email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                       placeholder="contoh@email.com">
            </div>
            
            <div class="form-group">
                <label for="username"><i class="fas fa-at"></i> Username *</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="Pilih username">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password *</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Minimal 8 karakter">
                <div class="password-info">Password minimal 8 karakter</div>
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Konfirmasi Password *</label>
                <input type="password" id="confirm_password" name="confirm_password" required 
                       placeholder="Ulangi password">
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Daftar Akun
            </button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="../auth/login.php"><i class="fas fa-sign-in-alt"></i> Login disini</a>
        </div>
    </div>

    <script>
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strengthBar');
        const form = document.getElementById('registerForm');
        
        function validatePassword() {
            const passwordValue = password.value;
            let strength = 0;
            
            if (passwordValue.length >= 8) strength += 25;
            if (passwordValue.length >= 12) strength += 10;
            if (/[a-z]/.test(passwordValue) && /[A-Z]/.test(passwordValue)) strength += 25;
            if (/\d/.test(passwordValue)) strength += 25;
            if (/[^A-Za-z0-9]/.test(passwordValue)) strength += 25;
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#EF476F';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#FFD166';
            } else {
                strengthBar.style.backgroundColor = '#06D6A0';
            }
            
            if (passwordValue !== confirmPassword.value) {
                confirmPassword.style.borderColor = '#EF476F';
                confirmPassword.style.boxShadow = '0 0 0 3px rgba(239,71,111,0.2)';
                return false;
            } else {
                confirmPassword.style.borderColor = '#06D6A0';
                confirmPassword.style.boxShadow = '0 0 0 3px rgba(6,214,160,0.2)';
                return true;
            }
        }
        
        password.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
        
        form.addEventListener('submit', function(e) {
            const nama = document.getElementById('nama').value.trim();
            const email = document.getElementById('email').value.trim();
            const username = document.getElementById('username').value.trim();
            const passwordValue = document.getElementById('password').value;
            
            if (nama.length < 3) {
                e.preventDefault();
                alert('Nama minimal 3 karakter!');
                return false;
            }
            
            if (!validateEmail(email)) {
                e.preventDefault();
                alert('Format email tidak valid!');
                return false;
            }
            
            if (username.length < 4) {
                e.preventDefault();
                alert('Username minimal 4 karakter!');
                return false;
            }
            
            if (passwordValue.length < 8) {
                e.preventDefault();
                alert('Password minimal 8 karakter!');
                return false;
            }
            
            if (!validatePassword()) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
            
            const btn = document.querySelector('.btn-register');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mendaftarkan...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-user-plus"></i> Daftar Akun';
                btn.disabled = false;
            }, 3000);
        });
        
        function validateEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        window.onload = function() {
            document.getElementById('nama').focus();
        };
    </script>
</body>
</html>