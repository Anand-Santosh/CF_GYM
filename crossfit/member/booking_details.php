<?php
require_once '../includes/auth.php';
require_once '../includes/header.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

$booking_id = $_GET['id'] ?? null;

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, p.name as package_name, p.description, p.duration_months, p.price, p.features,
           t.full_name as trainer_name, t.specialization, t.image as trainer_image
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    LEFT JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.booking_id = ?
    AND b.member_id = (SELECT member_id FROM members WHERE user_id = ?)
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - CrossFit Revolution</title>
    <style>
        :root {
            --primary: #FF5A1F; /* Vibrant orange */
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
            color: var(--text-dark);
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .card {
            background-color: var(--darker);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-header.bg-info {
            background: linear-gradient(135deg, #17a2b8, #138496) !important;
        }

        .card-header.bg-success {
            background: linear-gradient(135deg, #28a745, #20c997) !important;
        }

        .card-body {
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 10px 25px;
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
            color: white;
        }

        .btn-outline-secondary {
            background-color: transparent;
            border: 2px solid var(--text-dark);
            color: var(--text-dark);
        }

        .btn-outline-secondary:hover {
            background-color: var(--text-dark);
            color: var(--dark);
        }

        .btn-outline-success {
            background-color: transparent;
            border: 2px solid #28a745;
            color: #28a745;
        }

        .btn-outline-success:hover {
            background-color: #28a745;
            color: white;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .btn-success:hover {
            background-color: #218838;
            color: white;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .bg-warning {
            background-color: #ffc107 !important;
            color: #212529;
        }

        .lead {
            font-size: 1.2rem;
            color: var(--primary);
            font-weight: 600;
        }

        ul {
            list-style-type: none;
            padding-left: 0;
        }

        ul li {
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            padding-left: 25px;
        }

        ul li:before {
            content: "✓";
            color: var(--primary);
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        ul li:last-child {
            border-bottom: none;
        }

        .rounded-circle {
            border-radius: 50% !important;
            object-fit: cover;
        }

        .text-muted {
            color: #6c757d !important;
        }

        .d-flex {
            display: flex;
        }

        .align-items-center {
            align-items: center;
        }

        .justify-content-between {
            justify-content: space-between;
        }

        .mb-0 {
            margin-bottom: 0 !important;
        }

        .mb-2 {
            margin-bottom: 0.5rem !important;
        }

        .mb-3 {
            margin-bottom: 1rem !important;
        }

        .mb-4 {
            margin-bottom: 1.5rem !important;
        }

        .mt-4 {
            margin-top: 1.5rem !important;
        }

        .me-2 {
            margin-right: 0.5rem !important;
        }

        .w-100 {
            width: 100% !important;
        }

        .py-4 {
            padding-top: 1.5rem !important;
            padding-bottom: 1.5rem !important;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin-right: -15px;
            margin-left: -15px;
        }

        .col-lg-4, .col-lg-8 {
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }

        @media (min-width: 992px) {
            .col-lg-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
            .col-lg-8 {
                flex: 0 0 66.666667%;
                max-width: 66.666667%;
            }
        }

        small {
            font-size: 0.875em;
        }

        .bi {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Booking Details</h2>
            <a href="dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
        </div>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Package Information</h5>
                    </div>
                    <div class="card-body">
                        <h4><?= htmlspecialchars($booking['package_name']) ?></h4>
                        <p class="lead">₹<?= number_format($booking['price'], 2) ?> for <?= $booking['duration_months'] ?> month<?= $booking['duration_months'] > 1 ? 's' : '' ?></p>
                        
                        <h5 class="mt-4">Description</h5>
                        <p><?= htmlspecialchars($booking['description']) ?></p>
                        
                        <h5 class="mt-4">Package Features</h5>
                        <ul>
                            <?php 
                            $features = explode(',', $booking['features']);
                            foreach($features as $feature): 
                            ?>
                                <li><?= trim($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-info">
                        <h5 class="mb-0">Booking Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6>Status</h6>
                            <span class="badge bg-<?= 
                                $booking['status'] == 'active' ? 'success' : 
                                ($booking['status'] == 'completed' ? 'info' : 'warning') 
                            ?>">
                                <?= ucfirst($booking['status']) ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <h6>Start Date</h6>
                            <p><?= date('M j, Y', strtotime($booking['start_date'])) ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h6>End Date</h6>
                            <p><?= date('M j, Y', strtotime($booking['end_date'])) ?></p>
                        </div>
                        
                        <?php if($booking['trainer_name']): ?>
                            <div class="mb-3">
                                <h6>Assigned Trainer</h6>
                                <div class="d-flex align-items-center">
                                    <img src="../assets/images/trainers/<?= $booking['trainer_image'] ?? 'default.jpg' ?>" 
                                         class="rounded-circle me-2" width="40" height="40">
                                    <div>
                                        <p class="mb-0"><?= htmlspecialchars($booking['trainer_name']) ?></p>
                                        <small class="text-muted"><?= htmlspecialchars($booking['specialization']) ?></small>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if($booking['status'] == 'active'): ?>
                            <a href="book_package.php?package_id=<?= $booking['package_id'] ?>" class="btn btn-primary w-100">
                                <i class="bi bi-arrow-repeat"></i> Renew Package
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if($booking['trainer_name']): ?>
                    <div class="card">
                        <div class="card-header bg-success">
                            <h5 class="mb-0">Trainer Contact</h5>
                        </div>
                        <div class="card-body">
                            <a href="message.php?trainer_id=<?= $booking['trainer_id'] ?>" class="btn btn-outline-success w-100 mb-2">
                                <i class="bi bi-chat"></i> Send Message
                            </a>
                            <a href="schedule_session.php" class="btn btn-success w-100">
                                <i class="bi bi-calendar-plus"></i> Schedule Session
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

<?php include '../includes/footer.php'; ?>