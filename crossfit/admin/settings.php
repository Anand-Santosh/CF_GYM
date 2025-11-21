<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/crossfit/includes/auth.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Handle form submissions
$success_message = '';
$error_message = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    
    try {
        // Update session data with new full name
        $_SESSION['full_name'] = $full_name;
        
        $success_message = "Profile updated successfully!";
        
    } catch(PDOException $e) {
        $error_message = "Error updating profile: " . $e->getMessage();
    }
}

// Check for success messages from redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'profile_updated') {
        $success_message = "Profile updated successfully!";
    }
}

// Get current user details
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_details = $stmt->fetch();

// Get some gym stats for admin dashboard
$stats = [
    'total_members' => 0,
    'today_bookings' => 0,
    'total_trainers' => 0
];

try {
    // Total members count
    $stmt = $conn->query("SELECT COUNT(*) as total_members FROM users WHERE role = 'member'");
    $result = $stmt->fetch();
    $stats['total_members'] = $result ? $result['total_members'] : 0;
    
} catch(PDOException $e) {
    $stats['total_members'] = 0;
}

try {
    // Today's bookings
    $stmt = $conn->query("SELECT COUNT(*) as today_bookings FROM bookings WHERE DATE(created_at) = CURDATE()");
    $result = $stmt->fetch();
    $stats['today_bookings'] = $result ? $result['today_bookings'] : 0;
    
} catch(PDOException $e) {
    $stats['today_bookings'] = 0;
}

try {
    // Total trainers - count all trainers regardless of verification status
    $stmt = $conn->query("SELECT COUNT(*) as total_trainers FROM trainers");
    $result = $stmt->fetch();
    $stats['total_trainers'] = $result ? $result['total_trainers'] : 0;
    
} catch(PDOException $e) {
    // If trainers table doesn't exist, try users table as fallback
    try {
        $stmt = $conn->query("SELECT COUNT(*) as total_trainers FROM users WHERE role = 'trainer'");
        $result = $stmt->fetch();
        $stats['total_trainers'] = $result ? $result['total_trainers'] : 0;
    } catch(PDOException $e2) {
        $stats['total_trainers'] = 0;
    }
}

// Default values - use session data for profile
$email = $_SESSION['email'] ?? $user_details['email'] ?? '';
$full_name = $_SESSION['full_name'] ?? 'Administrator';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - CrossFit Revolution</title>
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
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, var(--dark) 0%, var(--darker) 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 20px 0;
            border-bottom: 2px solid var(--primary);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.4);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.6);
        }

        .form-container {
            background: linear-gradient(145deg, var(--darker) 0%, #1a1a1a 100%);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 90, 31, 0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: var(--text-light);
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.25);
            background: rgba(255,255,255,0.08);
        }

        .form-control:disabled {
            background: rgba(255,255,255,0.03);
            color: #888;
            cursor: not-allowed;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border-left: 4px solid;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.1);
            border-left-color: var(--success);
            color: #28a745;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            border-left-color: var(--danger);
            color: #dc3545;
        }

        .settings-section {
            margin-bottom: 40px;
        }

        .section-title {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title::before {
            content: "⚙️";
            font-size: 1.2em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(145deg, var(--darker) 0%, #1a1a1a 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(255, 90, 31, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            font-family: 'Oswald', sans-serif;
        }

        .stat-label {
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .admin-info {
            background: linear-gradient(145deg, var(--darker) 0%, #1a1a1a 100%);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border-left: 4px solid var(--primary);
        }

        .admin-info h4 {
            color: var(--primary);
            margin-bottom: 15px;
        }

        .admin-info ul {
            list-style: none;
            padding-left: 0;
        }

        .admin-info li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-info li:last-child {
            border-bottom: none;
        }

        .info-text {
            color: #888;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 0.8rem;
            }
            
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Settings</h1>
            <a href="dashboard.php" class="btn">← Back to Dashboard</a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= htmlspecialchars($stats['total_members']) ?></div>
                <div class="stat-label">Total Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= htmlspecialchars($stats['today_bookings']) ?></div>
                <div class="stat-label">Today's Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= htmlspecialchars($stats['total_trainers']) ?></div>
                <div class="stat-label">Total Trainers</div>
            </div>
        </div>

        <div class="settings-section">
            <h3 class="section-title">Profile Settings</h3>
            <div class="form-container">
                <form method="POST">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($full_name) ?>" required>
                        
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($email) ?>" disabled>
                       
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Admin Role</label>
                        <input type="text" class="form-control" value="Administrator" disabled>
                        
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn">Update Profile</button>
                </form>
            </div>
        </div>

        <div class="admin-info">
            <h4>🔧 Admin Quick Actions</h4>
            <ul>
                <li><strong>Manage Members:</strong> View and manage all member accounts</li>
                <li><strong>Manage Trainer:</strong> Create and manage all trainers</li>
                <li><strong>Manage Supplements:</strong> Create and manage supplements</li>
                <li><strong>Update Stock:</strong>Update and create new stocks if needed </li>
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formContainers = document.querySelectorAll('.form-container, .stat-card, .admin-info');
            
            formContainers.forEach((container, index) => {
                container.style.opacity = '0';
                container.style.transform = 'translateY(20px)';
                container.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    container.style.opacity = '1';
                    container.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>