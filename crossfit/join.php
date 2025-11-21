<?php
ob_start();
session_start();
require_once 'config/database.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = 'member'; // Set default role to 'member'
    
    try {
        // Validate inputs
        if (empty($full_name)) throw new Exception("Full name is required");
        if (empty($email)) throw new Exception("Email is required");
        if (empty($password)) throw new Exception("Password is required");
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }
        
        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters");
        }
        
        // Check for existing email
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            // Email exists - provide helpful message with options
            $error = "This email is already registered. Please <a href='login.php' style='color: #FF5A1F; font-weight: 600;'>login here</a> or <a href='forgot_password.php' style='color: #FF5A1F; font-weight: 600;'>reset your password</a> if you've forgotten it.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Begin transaction
            $conn->beginTransaction();
            
            // Insert user with default role
            $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, ?)");
            $stmt->execute([$email, $hashed_password, $role]);
            $user_id = $conn->lastInsertId();
            
            // Insert member without join_date
            $stmt = $conn->prepare("INSERT INTO members (user_id, full_name) VALUES (?, ?)");
            $stmt->execute([$user_id, $full_name]);
            
            $conn->commit();
            
            $success = "Registration successful! You can now <a href='login.php' style='color: #28a745; font-weight: 600;'>login here</a>.";
        }
        
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        
        // Check for duplicate entry error (MySQL error code 1062)
        if ($e->errorInfo[1] == 1062) {
            $error = "This email is already registered. Please <a href='login.php' style='color: #FF5A1F; font-weight: 600;'>login here</a> or <a href='forgot_password.php' style='color: #FF5A1F; font-weight: 600;'>reset your password</a> if you've forgotten it.";
        } else {
            $error = "Registration error. Please try again later.";
            // Log the actual error for debugging: error_log($e->getMessage());
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join CrossFit Revolution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--darker);
            color: var(--text-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
        }
        
        .join-container {
            width: 100%;
            max-width: 500px;
        }
        
        .join-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .logo h1 {
            color: var(--primary);
            font-size: 2.2rem;
            margin-bottom: 5px;
        }
        
        .logo p {
            color: var(--text-dark);
            font-size: 1rem;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-size: 0.95rem;
        }
        
        .alert-danger {
            background-color: rgba(220,53,69,0.2);
            border: 1px solid rgba(220,53,69,0.3);
            color: var(--danger);
        }
        
        .alert-success {
            background-color: rgba(40,167,69,0.2);
            border: 1px solid rgba(40,167,69,0.3);
            color: var(--success);
        }
        
        .alert a {
            color: inherit;
            font-weight: 600;
            text-decoration: underline;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-light);
            font-weight: 600;
        }
        
        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 5px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255,90,31,0.3);
        }
        
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23E0E0E0' viewBox='0 0 16 16'%3E%3Cpath d='M8 12L2 6h12L8 12z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
            padding-right: 40px;
        }
        
        .btn {
            background-color: var(--primary);
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,90,31,0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 25px;
            color: var(--text-dark);
        }
        
        .login-link a {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 0.8rem;
            color: var(--text-dark);
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="join-container">
        <div class="join-card">
            <div class="logo">
                <h1><i class="bi bi-lightning-charge"></i> CrossFit Revolution</h1>
                <p>Create your account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php elseif ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" id="registrationForm">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" class="form-control" name="full_name" 
                           value="<?= isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : '' ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" class="form-control" name="email" 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" 
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" class="form-control" name="password" required minlength="8">
                    <div class="password-requirements">Must be at least 8 characters long</div>
                </div>
                
                <button type="submit" class="btn">Create Account</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>

    <script>
        // Simple form validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>