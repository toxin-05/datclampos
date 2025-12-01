<?php
require_once 'includes/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid. Please try again.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            redirect('dashboard.php');
        } else {
            $error = "Invalid username or password!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Datclam Hardware POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="login-body">
    <!-- Animated Background Elements -->
    <div class="login-background">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
        <div class="floating-shape shape-4"></div>
        <div class="floating-tools">
            <i class="floating-tool tool-1 fas fa-hammer"></i>
            <i class="floating-tool tool-2 fas fa-screwdriver"></i>
            <i class="floating-tool tool-3 fas fa-wrench"></i>
            <i class="floating-tool tool-4 fas fa-toolbox"></i>
            <i class="floating-tool tool-5 fas fa-bolt"></i>
        </div>
    </div>

    <!-- Centered Login Container -->
    <div class="login-center-container">
        <div class="login-wrapper">
            <div class="login-card">
                <!-- Card Header with Animated Logo -->
                <div class="login-header">
                    <div class="logo-container">
                        <div class="logo-circle">
                            <i class="fas fa-tools logo-icon"></i>
                        </div>
                        <div class="logo-rings">
                            <div class="ring ring-1"></div>
                            <div class="ring ring-2"></div>
                            <div class="ring ring-3"></div>
                        </div>
                    </div>
                    <h1 class="login-title">
                        <span class="title-main">Datclam Hardware</span>
                        <span class="title-sub">POS System</span>
                    </h1>
                    <p class="login-subtitle">Secure Access to Your Store Management</p>
                </div>

                <!-- Login Form -->
                <div class="login-form-container">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger alert-login">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="login-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        
                        <!-- Username Field -->
                        <div class="form-group-login">
                            <div class="input-container">
                                <div class="input-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <input type="text" 
                                       class="form-control login-input" 
                                       name="username" 
                                       placeholder="Enter your username"
                                       required 
                                       autofocus>
                                <div class="input-highlight"></div>
                            </div>
                            <label class="input-label">Username</label>
                        </div>

                        <!-- Password Field -->
                        <div class="form-group-login">
                            <div class="input-container">
                                <div class="input-icon">
                                    <i class="fas fa-lock"></i>
                                </div>
                                <input type="password" 
                                       class="form-control login-input" 
                                       name="password" 
                                       placeholder="Enter your password"
                                       required>
                                <div class="input-highlight"></div>
                                <button type="button" class="password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <label class="input-label">Password</label>
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="form-options">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rememberMe">
                                <label class="form-check-label" for="rememberMe">
                                    Remember me
                                </label>
                            </div>
                            <a href="#" class="forgot-password">
                                Forgot Password?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-login">
                            <span class="btn-content">
                                <i class="fas fa-sign-in-alt btn-icon"></i>
                                <span class="btn-text">Sign In to Dashboard</span>
                            </span>
                            <div class="btn-shine"></div>
                        </button>
                    </form>

                    <!-- Security Badge -->
                    <div class="security-badge">
                        <i class="fas fa-shield-alt security-icon"></i>
                        <span>256-bit SSL Encrypted</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Animation -->
    <div class="login-loading" id="loading">
        <div class="loading-spinner">
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-ring"></div>
            <div class="spinner-center">
                <i class="fas fa-tools"></i>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password visibility toggle
        function togglePassword() {
            const passwordInput = document.querySelector('input[name="password"]');
            const toggleIcon = document.querySelector('.password-toggle i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                toggleIcon.className = 'fas fa-eye';
            }
        }

        // Input focus effects
        document.querySelectorAll('.login-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });

            // Check if input has value on page load
            if (input.value) {
                input.parentElement.classList.add('focused');
            }
        });

        // Form submission loading
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const loading = document.getElementById('loading');
            loading.style.display = 'flex';
            
            // Simulate loading for demo
            setTimeout(() => {
                loading.style.display = 'none';
            }, 2000);
        });

        // Add ripple effect to button
        document.querySelector('.btn-login').addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });

        // Add floating animation to tools
        document.addEventListener('DOMContentLoaded', function() {
            const tools = document.querySelectorAll('.floating-tool');
            tools.forEach((tool, index) => {
                tool.style.animationDelay = `${index * 0.5}s`;
            });
        });

        // Auto-focus on username field
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
        });
    </script>
</body>
</html>