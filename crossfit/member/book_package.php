<?php
ob_start();
session_start();
require_once '../config/database.php';

// Verify member access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member info
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Check if member has active booking
$activeBooking = $conn->prepare("
    SELECT b.*, p.name as package_name, p.duration_months, p.price, p.description, p.features
    FROM bookings b 
    JOIN packages p ON b.package_id = p.package_id 
    WHERE b.member_id = ? AND b.status = 'active'
    ORDER BY b.end_date DESC 
    LIMIT 1
");
$activeBooking->execute([$member['member_id']]);
$currentPackage = $activeBooking->fetch();

// Handle package booking only if no active booking exists
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$currentPackage) {
    $package_id = $_POST['package_id'];
    $start_date = $_POST['start_date'];
    
    try {
        // Get package details for success message
        $stmt = $conn->prepare("SELECT name, duration_months, price FROM packages WHERE package_id = ?");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        
        $end_date = date('Y-m-d', strtotime($start_date . " + " . $package['duration_months'] . " months"));
        
        // Create booking
        $stmt = $conn->prepare("INSERT INTO bookings (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
        $stmt->execute([$member['member_id'], $package_id, $start_date, $end_date]);
        
        $_SESSION['success'] = "Package booked successfully! You have subscribed to " . $package['name'] . " for " . $package['duration_months'] . " months.";
        header("Location: book_package.php");
        exit();
    } catch(PDOException $e) {
        $error = "Booking failed: " . $e->getMessage();
    }
}

// Get the specific package if package_id is set and no active booking
$package = null;
if (isset($_GET['package_id']) && !$currentPackage) {
    $package_id = $_GET['package_id'];
    $stmt = $conn->prepare("SELECT * FROM packages WHERE package_id = ?");
    $stmt->execute([$package_id]);
    $package = $stmt->fetch();
}

// Get all packages if no specific package is selected and no active booking
if (!$package && !$currentPackage) {
    $packages = $conn->query("SELECT * FROM packages")->fetchAll();
}

$pageTitle = "Book a Package";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
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
            --danger: #dc3545;
            --info: #17a2b8;
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
            padding: 20px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        .booking-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
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

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #218838;
        }

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

        .current-package-card {
            border-left: 4px solid var(--success);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--text-light);
        }

        .form-control {
            width: 100%;
            padding: 14px 16px;
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.2);
            background-color: rgba(255,255,255,0.08);
        }

        .alert {
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
            border: 2px solid transparent;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            border-color: rgba(220, 53, 69, 0.3);
            color: #f8d7da;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            border-color: rgba(40, 167, 69, 0.3);
            color: #d4edda;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.15);
            border-color: rgba(23, 162, 184, 0.3);
            color: #d1ecf1;
        }

        .alert-icon {
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .list-group {
            list-style: none;
            margin: 1.5rem 0;
        }

        .list-group-item {
            background-color: var(--darker);
            color: var(--text-light);
            padding: 1rem;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            margin-bottom: 0.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-group-item:last-child {
            margin-bottom: 0;
        }

        .package-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .package-card {
            background-color: var(--dark);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .package-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 90, 31, 0.2);
            border-color: var(--primary);
        }

        .package-header {
            margin-bottom: 1.5rem;
        }

        .package-price {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--primary);
            margin: 1rem 0;
        }

        .package-features {
            flex-grow: 1;
            margin-bottom: 1.5rem;
        }

        .package-footer {
            margin-top: auto;
            text-align: center;
        }

        .feature-list {
            list-style: none;
            margin: 1rem 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li i {
            color: var(--primary);
        }

        .form-actions {
            display: grid;
            gap: 1rem;
            margin-top: 2rem;
        }

        .status-badge {
            background-color: var(--success);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .package-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .package-detail {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--text-dark);
            margin-bottom: 0.2rem;
        }

        .detail-value {
            color: var(--text-light);
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .package-grid {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .package-card {
                padding: 1.5rem;
            }
            
            .alert {
                padding: 1.2rem;
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .booking-container {
                padding: 0;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        .text-muted {
            color: var(--text-dark) !important;
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="bi bi-box-seam"></i> Book a Package</h1>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <!-- Alerts -->
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <div class="alert-icon">⚠️</div>
                <div>
                    <strong>Booking Failed</strong><br>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <div class="alert-icon">✅</div>
                <div>
                    <strong>Success!</strong><br>
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Current Active Package -->
        <?php if($currentPackage): ?>
            <div class="card current-package-card">
                <span class="status-badge">
                    <i class="bi bi-check-circle"></i> Active Membership
                </span>
                <div class="package-header">
                    <h2><?= htmlspecialchars($currentPackage['package_name']) ?></h2>
                    <p class="text-muted"><?= htmlspecialchars($currentPackage['description']) ?></p>
                </div>
                
                <div class="package-details">
                    <div class="package-detail">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value"><?= $currentPackage['duration_months'] ?> month<?= $currentPackage['duration_months'] > 1 ? 's' : '' ?></span>
                    </div>
                    <div class="package-detail">
                        <span class="detail-label">Price</span>
                        <span class="detail-value">₹<?= number_format($currentPackage['price'], 2) ?></span>
                    </div>
                    <div class="package-detail">
                        <span class="detail-label">Start Date</span>
                        <span class="detail-value"><?= date('M j, Y', strtotime($currentPackage['start_date'])) ?></span>
                    </div>
                    <div class="package-detail">
                        <span class="detail-label">End Date</span>
                        <span class="detail-value"><?= date('M j, Y', strtotime($currentPackage['end_date'])) ?></span>
                    </div>
                </div>

                <div class="package-features" style="margin-top: 1.5rem;">
                    <h4>Package Features</h4>
                    <p class="text-muted"><?= htmlspecialchars($currentPackage['features']) ?></p>
                </div>

                <div class="alert alert-info" style="margin-top: 1.5rem;">
                    <div class="alert-icon">ℹ️</div>
                    <div>
                        <strong>Active Membership</strong><br>
                        You currently have an active package. You can book a new package only after your current membership expires on <?= date('F j, Y', strtotime($currentPackage['end_date'])) ?>.
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Package Booking Form or Package List -->
            <?php if($package): ?>
                <!-- Single Package Booking Form -->
                <form method="POST">
                    <input type="hidden" name="package_id" value="<?= $package['package_id'] ?>">
                    
                    <div class="card">
                        <div class="package-header">
                            <h2><?= htmlspecialchars($package['name']) ?></h2>
                            <p class="text-muted"><?= htmlspecialchars($package['description']) ?></p>
                        </div>
                        
                        <div class="package-features">
                            <ul class="list-group">
                                <li class="list-group-item">
                                    <span><i class="bi bi-calendar"></i> Duration</span>
                                    <strong><?= $package['duration_months'] ?> month<?= $package['duration_months'] > 1 ? 's' : '' ?></strong>
                                </li>
                                <li class="list-group-item">
                                    <span><i class="bi bi-currency-rupee"></i> Price</span>
                                    <strong>₹<?= number_format($package['price'], 2) ?></strong>
                                </li>
                                <li class="list-group-item">
                                    <span><i class="bi bi-star"></i> Features</span>
                                    <span><?= htmlspecialchars($package['features']) ?></span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="form-group">
                            <label for="start_date" class="form-label">
                                <i class="bi bi-calendar-check"></i> Start Date
                            </label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   min="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn">
                                <i class="bi bi-check-circle"></i> Confirm Booking
                            </button>
                            <a href="book_package.php" class="btn btn-outline">
                                <i class="bi bi-arrow-left"></i> Back to Packages
                            </a>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <!-- Package List -->
                <div class="package-grid">
                    <?php foreach($packages as $p): ?>
                    <div class="package-card">
                        <div class="package-header">
                            <h3><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="text-muted"><?= htmlspecialchars($p['description']) ?></p>
                            <div class="package-price">₹<?= number_format($p['price'], 2) ?></div>
                        </div>
                        
                        <div class="package-features">
                            <ul class="feature-list">
                                <li><i class="bi bi-check-circle"></i> Duration: <?= $p['duration_months'] ?> month<?= $p['duration_months'] > 1 ? 's' : '' ?></li>
                                <li><i class="bi bi-check-circle"></i> <?= htmlspecialchars($p['features']) ?></li>
                            </ul>
                        </div>
                        
                        <div class="package-footer">
                            <a href="book_package.php?package_id=<?= $p['package_id'] ?>" class="btn">
                                <i class="bi bi-bookmark-check"></i> Book Now
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // Add hover effects to cards
        document.querySelectorAll('.card, .package-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 25px rgba(255, 90, 31, 0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
            });
        });

        // Set minimum date for date input
        document.addEventListener('DOMContentLoaded', function() {
            const startDateInput = document.getElementById('start_date');
            if (startDateInput) {
                startDateInput.min = new Date().toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>