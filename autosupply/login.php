<?php
session_start();
include 'db_connect.php';
@include_once 'activity_logger.php';
if (!function_exists('log_activity')) {
    function log_activity($conn, $user_id, $action_type, $description, $reference_id = null) {}
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {

        if (isset($user['status']) && $user['status'] !== 'Active') {
            $error = "This account has been deactivated. Contact an administrator.";
        } else {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['full_name'];

            log_activity($conn, $user['user_id'], 'LOGIN', "{$user['full_name']} logged in.");

            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = "Invalid Username or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | GonPreaks AutoSupply</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/all.min.css">
    <style>
        :root {
            --accent-blue: #38bdf8;
            --glass-bg: rgba(15, 23, 42, 0.8);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body, html { height: 100%; margin: 0; font-family: 'Inter', sans-serif; overflow: hidden; background: #020617; }

        .bg-image {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: url('images/gon_background.jpg');
            background-size: cover; background-position: center; z-index: 1;
        }

        .bg-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(2, 6, 23, 0.5); backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px); z-index: 2;
        }

        .login-container { position: relative; display: flex; justify-content: center; align-items: center; height: 100vh; z-index: 10; }

        .login-box {
            width: 400px; padding: 50px 40px; text-align: center;
            background: var(--glass-bg); border: 1px solid var(--glass-border);
            border-radius: 30px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
        }

        .brand-logo {
            font-size: 2.4rem; font-weight: 900;
            background: linear-gradient(to right, #fff, var(--accent-blue));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 5px; letter-spacing: -1px;
        }

        .sub-text { color: #94a3b8; font-size: 0.8rem; margin-bottom: 35px; text-transform: uppercase; letter-spacing: 2px; }

        .error-alert {
            background: rgba(239, 68, 68, 0.2); color: #fb7185; padding: 12px;
            border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(239, 68, 68, 0.3);
            font-size: 0.85rem; font-weight: 600;
        }

        .input-group { position: relative; margin-bottom: 20px; }
        .input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #475569; }

        input {
            width: 100%; padding: 14px 14px 14px 45px;
            background: rgba(255, 255, 255, 0.05); border: 1px solid var(--glass-border);
            color: white; border-radius: 12px; box-sizing: border-box; font-size: 1rem;
        }

        input:focus { outline: none; border-color: var(--accent-blue); background: rgba(56, 189, 248, 0.08); }

        .btn-signin {
            width: 100%; margin-top: 15px; padding: 15px; cursor: pointer; border: none;
            border-radius: 12px; background: var(--accent-blue); color: #020617;
            font-weight: 800; text-transform: uppercase; transition: 0.3s;
        }

        .btn-signin:hover { transform: translateY(-2px); filter: brightness(1.1); }
    </style>
</head>
<body>
    <div class="bg-image"></div>
    <div class="bg-overlay"></div>

    <div class="login-container">
        <div class="login-box">
            <div class="brand-logo">GONPREAKS</div>
            <div class="sub-text">AutoSupply Enterprise</div>

            <?php if (isset($error)): ?>
                <div class="error-alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="text" name="username" placeholder="Username" required autofocus>
                    <i class="fas fa-user"></i>
                </div>

                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                    <i class="fas fa-lock"></i>
                </div>

                <button type="submit" class="btn-signin">Authorize & Sign In</button>
            </form>

            <p style="margin-top: 30px; font-size: 0.65rem; color: #475569; letter-spacing: 1.5px;">
                SECURED TERMINAL | VERSION 1.0.5
            </p>
        </div>
    </div>
</body>
</html>