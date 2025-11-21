<?php
ob_start();
session_start();
require_once '../config/database.php';

// Only allow members
if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member info
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Get assigned trainer info for messaging - FIXED: Include email field
$trainer = null;
$trainer_stmt = $conn->prepare("
    SELECT t.*, u.email 
    FROM trainers t
    JOIN users u ON t.user_id = u.user_id
    JOIN bookings b ON t.trainer_id = b.trainer_id
    WHERE b.member_id = ? AND b.status = 'active'
    LIMIT 1
");
$trainer_stmt->execute([$member['member_id']]);
$trainer = $trainer_stmt->fetch();

// Get active package bookings (without trainer)
$activePackageBookings = $conn->query("
    SELECT b.*, p.name as package_name, p.duration_months, p.price
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    WHERE b.member_id = {$member['member_id']} 
    AND b.status = 'active'
    AND b.trainer_id IS NULL
    ORDER BY b.end_date DESC
")->fetchAll();

// Get active trainer bookings (with trainer) - UPDATED QUERY TO INCLUDE TIME SLOTS
$activeTrainerBookings = $conn->query("
    SELECT b.*, p.name as package_name, p.duration_months, p.price, 
           t.full_name as trainer_name, t.specialization,
           b.day_of_week, b.start_time, b.end_time, b.session_date
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.member_id = {$member['member_id']} 
    AND b.status = 'active'
    AND b.trainer_id IS NOT NULL
    ORDER BY b.day_of_week, b.start_time
")->fetchAll();

// Get past bookings
$pastBookings = $conn->query("
    SELECT b.*, p.name as package_name, p.duration_months, p.price, 
           t.full_name as trainer_name
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    LEFT JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.member_id = {$member['member_id']} 
    AND b.status != 'active'
    ORDER BY b.end_date DESC
    LIMIT 5
")->fetchAll();

// Get recent supplement orders
$supplementOrders = $conn->query("
    SELECT so.*, s.name as supplement_name, s.price
    FROM supplement_orders so
    JOIN supplements s ON so.supplement_id = s.supplement_id
    WHERE so.member_id = {$member['member_id']}
    ORDER BY so.order_date DESC
    LIMIT 5
")->fetchAll();

$pageTitle = "Member Dashboard";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
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
            --sidebar-width: 250px;
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
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background-color: var(--dark);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 20px 0;
            border-right: 1px solid rgba(255,255,255,0.1);
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 1.5rem;
            color: var(--primary);
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
        }

        .sidebar-nav {
            list-style: none;
        }

        .nav-item {
            margin-bottom: 5px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 90, 31, 0.1);
            color: var(--primary);
            border-left-color: var(--primary);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .nav-category {
            padding: 10px 20px;
            color: var(--text-dark);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
            margin-bottom: 10px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .member-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .membership-badge {
            background-color: var(--primary);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.3);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        .btn-home {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-home:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        }

        .stat-card.trainer {
            border-left-color: var(--warning);
        }

        .stat-card.supplements {
            border-left-color: var(--success);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.trainer .stat-icon {
            color: var(--warning);
        }

        .stat-card.supplements .stat-icon {
            color: var(--success);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 1rem;
            display: block;
        }

        .stat-card a {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
        }

        /* Quick Actions */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.3);
            border-color: var(--primary);
        }

        .action-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .action-card h5 {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        /* BMI Calculator Styles */
        .bmi-calculator {
            background-color: var(--dark);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .bmi-input-group {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .bmi-input {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            width: 100%;
        }

        .bmi-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 90, 31, 0.2);
        }

        .bmi-result {
            text-align: center;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1.5rem;
            display: none;
        }

        .bmi-value {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .bmi-category {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .bmi-underweight { background-color: rgba(23, 162, 184, 0.2); border: 1px solid var(--info); }
        .bmi-normal { background-color: rgba(40, 167, 69, 0.2); border: 1px solid var(--success); }
        .bmi-overweight { background-color: rgba(255, 193, 7, 0.2); border: 1px solid var(--warning); }
        .bmi-obese { background-color: rgba(220, 53, 69, 0.2); border: 1px solid var(--danger); }

        /* Card Styles */
        .card {
            background-color: var(--dark);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 90, 31, 0.2);
            border-color: var(--primary);
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .card-header h2 {
            margin-bottom: 0;
            color: var(--primary);
        }

        .table {
            width: 100%;
            color: var(--text-light);
            border-collapse: collapse;
        }

        .table th {
            background-color: rgba(255,255,255,0.05);
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .table tr:hover {
            background-color: rgba(255,255,255,0.03);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .bg-success {
            background-color: var(--success) !important;
        }

        .bg-warning {
            background-color: var(--warning) !important;
            color: #000;
        }

        .bg-info {
            background-color: var(--info) !important;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .alert {
            padding: 1.2rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 1px solid transparent;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.15);
            border-color: rgba(23, 162, 184, 0.2);
            color: #d4edda;
        }

        .table-responsive {
            overflow-x: auto;
            border-radius: 10px;
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .table-responsive::-webkit-scrollbar {
            height: 6px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 3px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* Trainer card styles */
        .trainer-card {
            margin-top: 0;
            border-left: 4px solid var(--primary);
        }
        
        .trainer-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .trainer-profile-img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .text-muted {
            color: var(--text-dark) !important;
        }

        /* Training Schedule Styles - NEW */
        .schedule-info {
            background: var(--success);
            color: white;
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
            border-left: 4px solid var(--success);
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .day-schedule {
            background: var(--darker);
            padding: 1.2rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .day-schedule h5 {
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .time-slot-item {
            background: var(--dark);
            padding: 1rem;
            margin-bottom: 0.8rem;
            border-radius: 6px;
            border-left: 3px solid var(--success);
        }

        .no-schedule {
            background: var(--warning);
            color: #000;
            padding: 1rem;
            border-radius: 5px;
            text-align: center;
            margin-top: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: var(--primary);
                color: white;
                border: none;
                border-radius: 5px;
                padding: 10px;
                cursor: pointer;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .bmi-input-group {
                grid-template-columns: 1fr 1fr;
            }
            
            .table th,
            .table td {
                padding: 0.8rem;
                font-size: 0.9rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }

            .trainer-actions {
                flex-direction: column;
            }
            
            .trainer-actions .btn {
                width: 100%;
            }

            .schedule-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 1.2rem;
            }
            
            .bmi-input-group {
                grid-template-columns: 1fr;
            }
        }

        .text-end {
            text-align: right;
        }

        .lead {
            color: var(--text-dark);
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
   <!-- Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo">CROSSFIT REVOLUTION</a>
        <p style="color: var(--text-dark); font-size: 0.9rem;">Member Panel</p>
    </div>
    
    <ul class="sidebar-nav">
        <li class="nav-item">
            <a href="dashboard.php" class="nav-link active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        
        <li class="nav-category">Bookings</li>
        
        <li class="nav-item">
            <a href="book_package.php" class="nav-link">
                <i class="bi bi-box-seam"></i> Book Package
            </a>
        </li>
        
        <li class="nav-item">
            <a href="book_trainer.php" class="nav-link">
                <i class="bi bi-people"></i> Book Trainer
            </a>
        </li>

        
        
        <li class="nav-category">Supplements</li>
        
        <li class="nav-item">
            <a href="supplements.php" class="nav-link">
                <i class="bi bi-capsule"></i> Order Supplements
            </a>
        </li>

        <!-- ADD THIS: View Orders Link -->
        <li class="nav-item">
            <a href="my_orders.php" class="nav-link">
                <i class="bi bi-cart-check"></i> My Orders
            </a>
        </li>
        
        <li class="nav-category">Communication</li>
        
        <?php if ($trainer): ?>
        <li class="nav-item">
            <a href="message.php?trainer_id=<?= $trainer['trainer_id'] ?>" class="nav-link">
                <i class="bi bi-chat-dots"></i> Message to Trainer
            </a>
        </li>
        <?php endif; ?>
        
        <li class="nav-category">Profile</li>
        
        <li class="nav-item">
            <a href="profile.php" class="nav-link">
                <i class="bi bi-person-gear"></i> Profile Settings
            </a>
        </li>
        
        <li class="nav-item">
            <a href="../logout.php" class="nav-link">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</aside>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="member-info">
                <div>
                    <h1><i class="bi bi-person-circle"></i> Member Dashboard</h1>
                    <p style="color: var(--text-dark);">Welcome back, <?= htmlspecialchars($member['full_name']) ?></p>
                    <div class="membership-badge">
                        <i class="bi bi-award"></i> Active Member
                    </div>
                </div>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="btn btn-home">
                    <i class="bi bi-house"></i> Home
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <h2 style="margin-bottom: 1.5rem;">Overview</h2>
        <div class="stats-grid">
            <div class="stat-card trainer">
                <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                <h3><?= $trainer ? 1 : 0 ?></h3>
                <span class="stat-label">Assigned Trainer</span>
            </div>
            
            <div class="stat-card supplements">
                <div class="stat-icon"><i class="bi bi-capsule"></i></div>
                <h3><?= count($supplementOrders) ?></h3>
                <span class="stat-label">Supplement Orders</span>
            </div>
        </div>

        <!-- BMI Calculator -->
        <div class="bmi-calculator">
            <div class="card-header">
                <i class="bi bi-calculator" style="color: var(--primary); font-size: 1.5rem;"></i>
                <h2>BMI Calculator</h2>
            </div>
            <div class="card-body">
                <div class="bmi-input-group">
                    <div>
                        <label for="age" class="form-label">Age</label>
                        <input type="number" class="bmi-input" id="age" placeholder="Enter your age" min="15" max="100">
                    </div>
                    <div>
                        <label for="gender" class="form-label">Gender</label>
                        <select class="bmi-input" id="gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label for="weight" class="form-label">Weight (kg)</label>
                        <input type="number" class="bmi-input" id="weight" placeholder="Enter weight in kg" step="0.1" min="20" max="300">
                    </div>
                    <div>
                        <label for="height" class="form-label">Height (cm)</label>
                        <input type="number" class="bmi-input" id="height" placeholder="Enter height in cm" step="0.1" min="100" max="250">
                    </div>
                </div>
                <button onclick="calculateBMI()" class="btn" style="width: 100%;">
                    <i class="bi bi-calculator"></i> Calculate BMI
                </button>
                
                <div id="bmiResult" class="bmi-result">
                    <div class="bmi-value" id="bmiValue">0.0</div>
                    <div class="bmi-category" id="bmiCategory">Category</div>
                    <div id="bmiDescription" style="color: var(--text-dark);">
                        Your BMI result will appear here
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 1.5rem;">Quick Actions</h2>
        <div class="actions-grid">
            <div class="action-card">
                <i class="bi bi-box-seam action-icon" style="color: var(--primary);"></i>
                <h5>Book a Package</h5>
                <a href="book_package.php" class="btn btn-outline">Go</a>
            </div>
            <!-- Add these to your existing actions-grid -->


<div class="action-card">
    <i class="bi bi-cart-check action-icon" style="color: var(--success);"></i>
    <h5>My Orders</h5>
    <a href="my_orders.php" class="btn btn-outline">Go</a>
</div>
            
            <div class="action-card">
                <i class="bi bi-people action-icon" style="color: var(--info);"></i>
                <h5>Book a Trainer</h5>
                <a href="book_trainer.php" class="btn btn-outline">Go</a>
            </div>
            
            <div class="action-card">
                <i class="bi bi-capsule action-icon" style="color: var(--success);"></i>
                <h5>Order Supplements</h5>
                <a href="supplements.php" class="btn btn-outline">Go</a>
            </div>

            <?php if ($trainer): ?>
            <div class="action-card">
                <i class="bi bi-chat-dots action-icon" style="color: var(--warning);"></i>
                <h5>Message Trainer</h5>
                <a href="message.php?trainer_id=<?= $trainer['trainer_id'] ?>" class="btn btn-outline">Go</a>
            </div>
            <?php endif; ?>
        </div>

        <!-- My Trainer Section -->
        <?php if ($trainer): ?>
        <div class="card trainer-card">
            <div class="card-header">
                <i class="bi bi-person-badge" style="color: var(--primary); font-size: 1.5rem;"></i>
                <h2>My Trainer</h2>
            </div>
            <div class="card-body">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 1rem;">
                    <?php
                    $trainerProfileImage = !empty($trainer['profile_photo']) ? 
                        '../uploads/profile_photos/' . $trainer['profile_photo'] : 
                        '../assets/default-trainer.jpg';
                    ?>
                    <img src="<?= $trainerProfileImage ?>" 
                         alt="<?= htmlspecialchars($trainer['full_name']) ?>" 
                         class="trainer-profile-img">
                    <div>
                        <h6><?= htmlspecialchars($trainer['full_name']) ?></h6>
                        <p class="text-muted"><?= htmlspecialchars($trainer['specialization']) ?></p>
                        <?php if(isset($trainer['email'])): ?>
                        <p class="text-muted" style="font-size: 0.9rem;">
                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($trainer['email']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="trainer-actions">
                    <a href="message.php?trainer_id=<?= $trainer['trainer_id'] ?>" class="btn">
                        <i class="bi bi-chat-dots"></i> Send Message
                    </a>
                    <a href="trainer.php" class="btn btn-outline">
                        <i class="bi bi-person-lines-fill"></i> View Profile
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Training Schedule Section - NEW -->
        <?php if (!empty($activeTrainerBookings)): ?>
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-calendar-week" style="color: var(--primary); font-size: 1.5rem;"></i>
                    <h2>My Training Schedule</h2>
                </div>
                <div class="card-body">
                    <?php
                    // Group bookings by day for schedule display
                    $scheduleByDay = [];
                    $hasScheduledSessions = false;
                    
                    foreach($activeTrainerBookings as $booking) {
                        if($booking['day_of_week'] && $booking['start_time']) {
                            $hasScheduledSessions = true;
                            $scheduleByDay[$booking['day_of_week']][] = $booking;
                        }
                    }
                    ?>

                    <?php if($hasScheduledSessions): ?>
                        <div class="schedule-grid">
                            <?php 
                            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            foreach($days as $day): 
                            ?>
                                <div class="day-schedule">
                                    <h5><?= ucfirst($day) ?></h5>
                                    <?php if(isset($scheduleByDay[$day])): ?>
                                        <?php foreach($scheduleByDay[$day] as $slot): ?>
                                            <div class="time-slot-item">
                                                <strong><?= htmlspecialchars($slot['trainer_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($slot['package_name']) ?></small><br>
                                                <i class="bi bi-clock"></i> 
                                                <?= date('g:i A', strtotime($slot['start_time'])) ?> - <?= date('g:i A', strtotime($slot['end_time'])) ?>
                                                <?php if($slot['session_date']): ?>
                                                    <br><small class="text-muted">
                                                        <i class="bi bi-calendar-date"></i> 
                                                        Next: <?= date('M j, Y', strtotime($slot['session_date'])) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-muted">No sessions scheduled</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="no-schedule">
                            <i class="bi bi-exclamation-triangle"></i> 
                            No training schedule set yet. Your trainer will assign your time slots soon.
                        </div>
                    <?php endif; ?>

                    <!-- Individual Session Details -->
                    <?php foreach($activeTrainerBookings as $booking): ?>
                        <?php if($booking['day_of_week'] && $booking['start_time']): ?>
                            <div class="schedule-info">
                                <h5><i class="bi bi-clock"></i> Your Training Session with <?= htmlspecialchars($booking['trainer_name']) ?></h5>
                                <p><strong>Day:</strong> <?= ucfirst($booking['day_of_week']) ?></p>
                                <p><strong>Time:</strong> <?= date('g:i A', strtotime($booking['start_time'])) ?> - <?= date('g:i A', strtotime($booking['end_time'])) ?></p>
                                <p><strong>Package:</strong> <?= htmlspecialchars($booking['package_name']) ?></p>
                                <?php if ($booking['session_date']): ?>
                                    <p><strong>Next Session Date:</strong> <?= date('M j, Y', strtotime($booking['session_date'])) ?></p>
                                <?php endif; ?>
                                <p><strong>Duration:</strong> <?= $booking['duration_months'] ?> months (Until <?= date('M j, Y', strtotime($booking['end_date'])) ?>)</p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Active Memberships -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-card-checklist" style="color: var(--primary); font-size: 1.5rem;"></i>
                <h2>Active Memberships</h2>
            </div>
            <div class="card-body">
                <?php if(empty($activePackageBookings) && empty($activeTrainerBookings)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> You don't have any active memberships. 
                        <a href="book_package.php" style="color: var(--primary); font-weight: 600;">Book a package now</a>.
                    </div>
                <?php else: ?>
                    <?php if(!empty($activePackageBookings)): ?>
                    <h4 style="margin-bottom: 1rem; color: var(--text-light);">Package Memberships</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($activePackageBookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['package_name']) ?></td>
                                    <td><?= $booking['duration_months'] ?> months</td>
                                    <td>₹<?= number_format($booking['price'], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($booking['start_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($booking['end_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>

                    <?php if(!empty($activeTrainerBookings)): ?>
                    <h4 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--text-light);">Trainer Sessions</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Package</th>
                                    <th>Duration</th>
                                    <th>Price</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Trainer</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($activeTrainerBookings as $booking): ?>
                                <tr>
                                    <td><?= htmlspecialchars($booking['package_name']) ?></td>
                                    <td><?= $booking['duration_months'] ?> months</td>
                                    <td>₹<?= number_format($booking['price'], 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($booking['start_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($booking['end_date'])) ?></td>
                                    <td>
                                        <?= htmlspecialchars($booking['trainer_name']) ?><br>
                                        <small class="text-muted"><?= htmlspecialchars($booking['specialization']) ?></small>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Supplement Orders -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-cart-check" style="color: var(--success); font-size: 1.5rem;"></i>
                <h2>Recent Supplement Orders</h2>
            </div>
            <div class="card-body">
                <?php if(empty($supplementOrders)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> You haven't ordered any supplements yet. 
                        <a href="supplements.php" style="color: var(--primary); font-weight: 600;">Browse our supplements</a>.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Supplement</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Total</th>
                                    <th>Order Date</th>
                                    <th>Pickup Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($supplementOrders as $order): 
                                    $total = $order['price'] * $order['quantity'];
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['supplement_name']) ?></td>
                                    <td><?= $order['quantity'] ?></td>
                                    <td>₹<?= number_format($order['price'], 2) ?></td>
                                    <td>₹<?= number_format($total, 2) ?></td>
                                    <td><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($order['pickup_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        // BMI Calculator Function with Age and Gender
        function calculateBMI() {
            const age = parseInt(document.getElementById('age').value);
            const gender = document.getElementById('gender').value;
            const weight = parseFloat(document.getElementById('weight').value);
            const height = parseFloat(document.getElementById('height').value) / 100; // Convert cm to meters
            
            // Validation
            if (!age || !gender || !weight || !height || age <= 0 || weight <= 0 || height <= 0) {
                alert('Please enter all required fields with valid values.');
                return;
            }
            
            if (age < 15 || age > 100) {
                alert('Please enter an age between 15 and 100 years.');
                return;
            }
            
            const bmi = weight / (height * height);
            const bmiValue = document.getElementById('bmiValue');
            const bmiCategory = document.getElementById('bmiCategory');
            const bmiDescription = document.getElementById('bmiDescription');
            const bmiResult = document.getElementById('bmiResult');
            
            // Format BMI to 1 decimal place
            const formattedBMI = bmi.toFixed(1);
            bmiValue.textContent = formattedBMI;
            
            // Determine BMI category with age and gender considerations
            let category = '';
            let description = '';
            let resultClass = '';
            
            // BMI categories with age and gender context
            if (bmi < 18.5) {
                category = 'Underweight';
                if (age < 25) {
                    description = gender === 'male' ? 
                        'As a young male, focus on strength training and calorie surplus. Consult your trainer for a muscle-building plan.' :
                        'As a young female, focus on balanced nutrition and strength training. Your trainer can help with a healthy weight gain plan.';
                } else if (age < 45) {
                    description = gender === 'male' ?
                        'Focus on compound exercises and protein-rich diet. Your trainer can create a mass-building program.' :
                        'Incorporate strength training and balanced nutrition. Consider consulting with our nutrition specialist.';
                } else {
                    description = gender === 'male' ?
                        'Focus on maintaining muscle mass with strength training. Your trainer can adjust exercises for your age group.' :
                        'Prioritize bone health and muscle maintenance. Your trainer can create an age-appropriate workout plan.';
                }
                resultClass = 'bmi-underweight';
            } else if (bmi >= 18.5 && bmi < 25) {
                category = 'Normal Weight';
                if (age < 25) {
                    description = gender === 'male' ?
                        'Great! Maintain your fitness with varied workouts. Focus on building healthy habits for the future.' :
                        'Excellent! Continue with balanced exercise and nutrition. Your trainer can help you set new fitness goals.';
                } else if (age < 45) {
                    description = gender === 'male' ?
                        'Well done! Maintain your weight with regular exercise. Consider challenging yourself with new fitness goals.' :
                        'Great work! Keep up with regular workouts and balanced nutrition. Your trainer can help maintain your progress.';
                } else {
                    description = gender === 'male' ?
                        'Excellent! Focus on maintaining muscle mass and flexibility. Your trainer can help with age-appropriate exercises.' :
                        'Wonderful! Continue with strength training and balance exercises. Your trainer can focus on long-term health.';
                }
                resultClass = 'bmi-normal';
            } else if (bmi >= 25 && bmi < 30) {
                category = 'Overweight';
                if (age < 25) {
                    description = gender === 'male' ?
                        'Focus on building healthy habits early. Your trainer can create a balanced workout and nutrition plan.' :
                        'Start with consistent cardio and strength training. Your trainer will design a sustainable fitness program.';
                } else if (age < 45) {
                    description = gender === 'male' ?
                        'Combine cardio with strength training. Your trainer can create a personalized weight management plan.' :
                        'Focus on consistent exercise and portion control. Your trainer will help you establish sustainable habits.';
                } else {
                    description = gender === 'male' ?
                        'Focus on low-impact cardio and strength training. Your trainer can create an age-appropriate program.' :
                        'Combine gentle cardio with strength exercises. Your trainer will focus on joint health and sustainable weight loss.';
                }
                resultClass = 'bmi-overweight';
            } else {
                category = 'Obese';
                if (age < 25) {
                    description = gender === 'male' ?
                        'Start with guided workouts and nutritional counseling. Your trainer will create a safe, effective plan.' :
                        'Begin with supervised exercises and dietary guidance. Your trainer will support your fitness journey.';
                } else if (age < 45) {
                    description = gender === 'male' ?
                        'Focus on sustainable lifestyle changes. Your trainer will design a comprehensive fitness and nutrition plan.' :
                        'Work with your trainer on gradual, sustainable changes. We\'ll focus on both exercise and nutrition.';
                } else {
                    description = gender === 'male' ?
                        'Start with low-impact exercises and dietary adjustments. Your trainer will prioritize joint health and safety.' :
                        'Begin with gentle exercises and nutritional guidance. Your trainer will create a safe, effective program.';
                }
                resultClass = 'bmi-obese';
            }
            
            bmiCategory.textContent = category;
            bmiDescription.textContent = description;
            
            // Apply styling and show result
            bmiResult.className = `bmi-result ${resultClass}`;
            bmiResult.style.display = 'block';
        }

        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 25px rgba(0,0,0,0.3)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Add click effect to action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('click', function(e) {
                if (e.target.tagName !== 'A' && e.target.tagName !== 'BUTTON') {
                    const link = this.querySelector('a');
                    if (link) {
                        window.location.href = link.href;
                    }
                }
            });
        });

        // Mobile menu toggle
        const menuToggle = document.createElement('button');
        menuToggle.className = 'menu-toggle';
        menuToggle.innerHTML = '<i class="bi bi-list"></i>';
        menuToggle.style.display = 'none';
        document.body.appendChild(menuToggle);

        menuToggle.addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Show menu toggle on mobile
        function checkWidth() {
            if (window.innerWidth <= 992) {
                menuToggle.style.display = 'block';
            } else {
                menuToggle.style.display = 'none';
                document.querySelector('.sidebar').classList.remove('active');
            }
        }

        window.addEventListener('resize', checkWidth);
        checkWidth();
    </script>
</body>
</html>
<?php ob_end_flush(); ?>