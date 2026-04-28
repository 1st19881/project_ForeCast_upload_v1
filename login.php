<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SAIC Forecast System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #e30613;
            --secondary: #0f172a;
            --bg: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            min-height: 100vh;
            min-height: 100svh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 1rem;
        }

        .login-container {
            width: 100%;
            max-width: 450px;
            padding: 2.5rem;
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.06);
            border: 1px solid rgba(0,0,0,0.02);
            text-align: center;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .logo-box {
            width: 60px;
            height: 60px;
            background: var(--primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 20px rgba(227, 6, 19, 0.2);
        }

        .logo-box i {
            font-size: 2rem;
            color: white;
        }

        h2 {
            font-weight: 800;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }

        p.subtitle {
            color: #64748b;
            margin-bottom: 2.5rem;
            font-size: 0.95rem;
        }

        .form-label {
            display: block;
            text-align: left;
            font-weight: 600;
            color: #475569;
            font-size: 0.85rem;
            margin-bottom: 0.5rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 12px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            font-weight: 500;
            transition: 0.2s;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(227, 6, 19, 0.1);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .form-control {
            padding-right: 3rem;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 0.85rem;
            transform: translateY(-50%);
            width: 2rem;
            height: 2rem;
            border: 0;
            background: transparent;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: 0.2s;
        }

        .toggle-password:hover,
        .toggle-password:focus {
            color: var(--primary);
            background: rgba(227, 6, 19, 0.08);
            outline: none;
        }

        .btn-login {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.85rem;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1.1rem;
            margin-top: 1rem;
            transition: 0.2s;
        }

        .btn-login:hover {
            background: #b3050f;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(227, 6, 19, 0.2);
        }

        .copyright {
            margin-top: 3rem;
            color: #94a3b8;
            font-size: 0.8rem;
        }

        @media (max-width: 575.98px) {
            body {
                align-items: flex-start;
                padding: 1rem .85rem;
            }

            .login-container {
                padding: 1.5rem;
                border-radius: 18px;
                margin: 1rem 0;
            }

            p.subtitle {
                margin-bottom: 1.75rem;
            }

            .copyright {
                margin-top: 2rem;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo-box">
        <i class="fas fa-chart-line"></i>
    </div>
    <h2>Welcome Back</h2>
    <p class="subtitle">Please sign in to access Forecast System</p>

    <form id="loginForm">
        <div class="mb-3">
            <label class="form-label">USERNAME</label>
            <input type="text" class="form-control" name="username" id="username" placeholder="Employee ID" required>
        </div>
        <div class="mb-4">
            <label class="form-label">PASSWORD</label>
            <div class="password-wrap">
                <input type="password" class="form-control" name="password" id="password" placeholder="••••••••" required>
                <button type="button" class="toggle-password" id="togglePassword" aria-label="Show password" aria-pressed="false">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
            SIGN IN
        </button>
    </form>

    <div class="copyright">
        &copy; 2026 SAIC Motor Thailand. All rights reserved.
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('#togglePassword').on('click', function() {
            const passwordInput = $('#password');
            const icon = $(this).find('i');
            const isHidden = passwordInput.attr('type') === 'password';

            passwordInput.attr('type', isHidden ? 'text' : 'password');
            icon.toggleClass('fa-eye', !isHidden).toggleClass('fa-eye-slash', isHidden);
            $(this)
                .attr('aria-label', isHidden ? 'Hide password' : 'Show password')
                .attr('aria-pressed', isHidden ? 'true' : 'false');
        });

        $('#loginForm').submit(function(e) {
            e.preventDefault();
            
            const btn = $('#btnLogin');
            const originalText = btn.text();
            
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Authenticating...');

            $.ajax({
                url: 'api/auth_login.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(res) {
                    if (res.status === 'success') {
                        window.location.href = 'index.php';
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Access Denied',
                            text: res.message
                        });
                        btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: 'Could not connect to authentication services.'
                    });
                    btn.prop('disabled', false).text(originalText);
                }
            });
        });
    });
</script>

</body>
</html>
