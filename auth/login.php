<?php
// login.php - Halaman Login dengan Tema Modern (Mendukung Admin)
// File disimpan di folder auth, sehingga path ke folder lain perlu naik satu level
session_start();
require_once('../config/db.php');

// Jika sudah login, redirect ke dashboard atau admin sesuai role
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ../admin/index.php');
    } else {
        header('Location: ../users/dashboard.php');
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];
    
    // Validasi
    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        // Cek user di database
        $query = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];
                
                // Update last login
                $update_query = "UPDATE users SET last_login = NOW() WHERE id = " . $user['id'];
                mysqli_query($conn, $update_query);
                
                // Redirect berdasarkan role
                if ($user['role'] == 'admin') {
                    header('Location: ../admin/index.php');
                } else {
                    header('Location: ../users/dashboard.php');
                }
                exit();
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username/email tidak ditemukan!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - e-RT Digital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
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

        /* Overlay dengan gradasi tema */
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

        /* Container login dengan efek glass */
        .login-container {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 50px 40px;
            width: 100%;
            max-width: 450px;
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

        /* Pesan sukses/error */
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

        /* Form group */
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

        /* Password toggle */
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: white;
            font-size: 20px;
            opacity: 0.7;
            transition: 0.3s;
        }

        .toggle-password:hover {
            opacity: 1;
        }

        /* Tombol login */
        .btn-login {
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

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(36,158,148,0.5);
        }

        /* Extra options */
        .extra-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            font-size: 14px;
            color: rgba(255,255,255,0.8);
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-me input {
            width: 18px;
            height: 18px;
            accent-color: var(--accent);
        }

        .forgot-password {
            color: white;
            text-decoration: none;
            border-bottom: 1px dashed rgba(255,255,255,0.3);
            padding-bottom: 2px;
        }

        .forgot-password:hover {
            border-bottom-color: var(--accent);
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.8);
            font-size: 14px;
        }

        .login-footer a {
            color: white;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid var(--accent);
        }

        .login-footer a:hover {
            color: var(--accent);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            .logo h1 {
                font-size: 24px;
            }
            .extra-options {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon"><i class="fas fa-hands-helping"></i></div>
            <h1>e-RT Digital</h1>
            <p>Akses layanan warga digital Anda</p>
        </div>

        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="message success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Username atau Email</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       placeholder="Masukkan username atau email">
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required 
                           placeholder="Masukkan password">
                    <button type="button" class="toggle-password" id="togglePassword">
                        <i class="far fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="extra-options">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Ingat saya</label>
                </div>
                <a href="#" class="forgot-password">Lupa password?</a>
            </div>
            
            <button type="submit" class="btn-login"><i class="fas fa-sign-in-alt"></i> Masuk ke Dashboard</button>
        </form>
        
        <div class="login-footer">
            Belum punya akun? <a href="register.php">Daftar akun baru</a>
        </div>
    </div>

    <script>
        // Toggle show/hide password
        const togglePassword = document.getElementById('togglePassword');
        const password = document.getElementById('password');
        
        togglePassword.addEventListener('click', function() {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
        });

        // Form validation
        const form = document.getElementById('loginForm');
        
        form.addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value.trim();
            
            if (!username || !password) {
                e.preventDefault();
                alert('Username dan password harus diisi!');
                return false;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('.btn-login');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            submitBtn.disabled = true;
            
            // Re-enable button after 3 seconds if something goes wrong
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });

        // Remember me functionality
        document.addEventListener('DOMContentLoaded', function() {
            const rememberedUsername = localStorage.getItem('rememberedUsername');
            const rememberCheckbox = document.getElementById('remember');
            
            if (rememberedUsername) {
                document.getElementById('username').value = rememberedUsername;
                rememberCheckbox.checked = true;
            }
            
            rememberCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    const username = document.getElementById('username').value;
                    localStorage.setItem('rememberedUsername', username);
                } else {
                    localStorage.removeItem('rememberedUsername');
                }
            });
        });

        // Auto-focus on username field
        window.onload = function() {
            document.getElementById('username').focus();
        };
    </script>
</body>
</html>