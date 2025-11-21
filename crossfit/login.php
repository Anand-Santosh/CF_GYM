<?php
ob_start();
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        switch($user['role']) {
            case 'admin':
                header("Location: admin/dashboard.php");
                break;
            case 'trainer':
                // Check if trainer is verified
                $stmt = $conn->prepare("SELECT is_verified FROM trainers WHERE user_id = ?");
                $stmt->execute([$user['user_id']]);
                $trainer = $stmt->fetch();
                
                if ($trainer && $trainer['is_verified']) {
                    header("Location: trainer/dashboard.php");
                } else {
                    // Trainer not verified yet
                    $_SESSION['info'] = "Your trainer account is pending verification. You'll have limited access until your certification is reviewed.";
                    header("Location: trainer/dashboard.php");
                }
                break;
            case 'member':
                header("Location: member/dashboard.php");
                break;
            default:
                header("Location: index.php");
        }
        exit();
    } else {
        $_SESSION['error'] = "Invalid email or password";
        header("Location: login.php");
        exit();
    }
}

$pageTitle = "Login";
$error = $_SESSION['error'] ?? null;
unset($_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--text-light);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: var(--darker);
            padding: 20px;
        }

        .login-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            width: 100%;
            max-width: 450px;
            transition: all 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header h2 {
            font-size: 2.2rem;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
            margin-bottom: 15px;
        }

        .login-header h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -10px;
            width: 60px;
            height: 4px;
            background: var(--primary);
            transform: translateX(-50%);
        }

        .login-header p {
            margin-bottom: 0;
            color: var(--text-dark);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
        }

        .form-control:focus {
            background-color: var(--darker);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 90, 31, 0.25);
            outline: none;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 14px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.4);
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .login-links {
            text-align: center;
            margin-top: 25px;
            font-size: 14px;
        }

        .login-links a {
            color: var(--primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .login-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 30px 20px;
            }
            
            .login-header h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>Login</h2>
                <p>Access your CrossFit account</p>
            </div>

            <?php if($error): ?>
                <div class="alert"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>

            <div class="login-links">
                <p>Don't have an account? <a href="join.php">Join us</a></p>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>