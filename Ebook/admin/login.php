<?php
session_start();

include_once '../includes/db.php';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUsername = $_POST['username'] ?? '';
    $inputPassword = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($inputUsername) || empty($inputPassword)) {
        $error = "Please fill in all fields.";
    } else {
        // Check credentials against database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND role = 'admin'");
        $stmt->execute([$inputUsername]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($inputPassword, $admin['password'])) {
            // Login successful
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_email'] = $admin['email'];
            
            // Redirect to dashboard
            header("Location: _layout.php");
            exit();
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ISUR-ORA Digital Library</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 20px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }
        
        .input-group-text {
            background: transparent;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
            padding-left: 10px;
        }
        
        .login-animation {
            animation: fadeInUp 0.6s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center min-vh-100 align-items-center">
            <div class="col-11 col-sm-8 col-md-6 col-lg-4">
                <div class="card login-card shadow-lg border-0 login-animation">
                    <div class="card-body p-5">
                        <!-- Logo and Title -->
                        <div class="text-center mb-4">
                            <div class="mb-3">
                                <i class="bi bi-shield-lock text-primary" style="font-size: 3rem;"></i>
                            </div>
                            <h3 class="fw-bold text-dark mb-2">Admin Portal</h3>
                            <p class="text-muted small mb-0">ISUR-ORA Digital Library</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="loginForm">
                            <!-- Username Field -->
                            <div class="mb-3">
                                <label for="username" class="form-label fw-semibold">
                                    <i class="bi bi-person me-1"></i>Username
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-person text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           placeholder="Enter your username" 
                                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                           required autocomplete="username">
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="mb-4">
                                <label for="password" class="form-label fw-semibold">
                                    <i class="bi bi-lock me-1"></i>Password
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">
                                        <i class="bi bi-lock text-muted"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter your password" required autocomplete="current-password">
                                    <span class="input-group-text bg-light password-toggle" id="togglePassword">
                                        <i class="bi bi-eye" id="toggleIcon"></i>
                                    </span>
                                </div>
                            </div>

                            <!-- Remember Me -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember_me">
                                    <label class="form-check-label small text-muted" for="rememberMe">
                                        Remember me on this device
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary w-100 mb-3" id="loginBtn">
                                <span class="btn-text">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                                </span>
                                <span class="spinner-border spinner-border-sm d-none" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </span>
                            </button>
                        </form>

                        <!-- Footer -->
                        <div class="text-center">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Contact IT support for login issues
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Password visibility toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'bi bi-eye';
            }
        });

        // Form submission with loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const loginBtn = document.getElementById('loginBtn');
            const btnText = loginBtn.querySelector('.btn-text');
            const spinner = loginBtn.querySelector('.spinner-border');
            
            btnText.classList.add('d-none');
            spinner.classList.remove('d-none');
            loginBtn.disabled = true;
        });

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });

        // Add shake animation on error
        <?php if ($error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const card = document.querySelector('.login-card');
            card.style.animation = 'shake 0.5s ease-in-out';
        });
        <?php endif; ?>
    </script>

    <style>
        @keyframes shake {
            0%, 20%, 40%, 60%, 80% {
                transform: translateX(-5px);
            }
            10%, 30%, 50%, 70% {
                transform: translateX(5px);
            }
            90%, 100% {
                transform: translateX(0);
            }
        }
    </style>
</body>
</html>