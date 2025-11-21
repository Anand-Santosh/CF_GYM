<?php
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member info
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Get all supplement orders for this member
$orders = $conn->query("
    SELECT o.*, s.name as supplement_name, s.price 
    FROM supplement_orders o
    JOIN supplements s ON o.supplement_id = s.supplement_id
    WHERE o.member_id = {$member['member_id']}
    ORDER BY o.order_date DESC
")->fetchAll();

$pageTitle = "My Supplement Orders";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --primary-light: rgba(255, 90, 31, 0.1);
            --dark: #121212;
            --darker: #0A0A0A;
            --card-dark: #1A1A1A;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
            --success: #10B981;
            --border: rgba(255,255,255,0.1);
            --gradient: linear-gradient(135deg, #FF5A1F 0%, #E04A14 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--darker);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
        }

        .background-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255, 90, 31, 0.03) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 90, 31, 0.03) 0%, transparent 50%);
            z-index: -1;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 3rem;
            position: relative;
        }

        .header-content h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .header-content p {
            color: var(--text-dark);
            font-size: 1.1rem;
            font-weight: 400;
        }

        .header-actions {
            display: flex;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--border);
            color: var(--text-light);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline:hover {
            background: var(--primary-light);
            border-color: var(--primary);
        }

        .card {
            background-color: var(--dark);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid var(--border);
            margin-bottom: 2rem;
        }

        .card-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }

        .card-header .icon {
            width: 50px;
            height: 50px;
            background: var(--primary-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--primary);
        }

        .card-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-light);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--dark);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: var(--dark);
        }

        .table th {
            background-color: rgba(255,255,255,0.1);
            padding: 1.25rem;
            text-align: left;
            font-weight: 600;
            color: var(--text-light);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--primary);
        }

        .table td {
            padding: 1.25rem;
            border-bottom: 1px solid var(--border);
            color: var(--text-light);
            transition: background-color 0.2s ease;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }

        .supplement-name {
            font-weight: 600;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quantity-badge {
            background: var(--primary-light);
            color: var(--primary);
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .total-amount {
            font-weight: 700;
            color: var(--primary);
            font-size: 1.1rem;
        }

        .date-cell {
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-light);
        }

        .empty-state .icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-light);
        }

        .empty-state p {
            color: var(--text-dark);
            margin-bottom: 2rem;
            font-size: 1rem;
        }

        .stats-card {
            background: var(--dark);
            border-radius: 16px;
            padding: 2rem;
            color: white;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .stats-card h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
        }

        .stats-card .number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            .page-header {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .header-content h1 {
                font-size: 2rem;
            }

            .header-actions {
                width: 100%;
                justify-content: center;
            }

            .card {
                padding: 1.5rem;
            }

            .table th,
            .table td {
                padding: 1rem 0.75rem;
                font-size: 0.85rem;
            }

            .btn {
                padding: 10px 20px;
            }
        }

        @media (max-width: 480px) {
            .header-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .table-container {
                border-radius: 8px;
            }
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card, .stats-card {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body>
    <div class="background-pattern"></div>
    
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <h1>My Supplement Orders</h1>
                <p>Track my supplement purchases</p>
            </div>
            <div class="header-actions">
                <a href="supplements.php" class="btn">
                    <i class="bi bi-capsule"></i> Order Supplements
                </a>
                <a href="dashboard.php" class="btn">
                    <i class="bi bi-speedometer2"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php if(!empty($orders)): ?>
        <div class="stats-card">
            <h3>Total Orders</h3>
            <div class="number"><?= count($orders) ?></div>
        </div>
        <?php endif; ?>

        <!-- Orders Card -->
        <div class="card">
            <div class="card-header">
                <div class="icon">
                    <i class="bi bi-list-check"></i>
                </div>
                <h2>Order History</h2>
            </div>
            
            <div>
                <?php if(empty($orders)): ?>
                    <div class="empty-state">
                        <div class="icon">
                            <i class="bi bi-cart-x"></i>
                        </div>
                        <h3>No Orders Yet</h3>
                        <p>Start your fitness journey with our premium supplements</p>
                        <a href="supplements.php" class="btn" style="margin-top: 1rem;">
                            <i class="bi bi-capsule"></i> Explore Supplements
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Supplement</th>
                                    <th>Quantity</th>
                                    <th>Total Amount</th>
                                    <th>Order Date</th>
                                    <th>Pickup Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($orders as $order): 
                                    $total = $order['price'] * $order['quantity'];
                                ?>
                                <tr>
                                    <td>
                                        <div class="supplement-name">
                                            <i class="bi bi-capsule"></i>
                                            <?= htmlspecialchars($order['supplement_name']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="quantity-badge">
                                            <?= $order['quantity'] ?> units
                                        </span>
                                    </td>
                                    <td class="total-amount">₹<?= number_format($total, 2) ?></td>
                                    <td class="date-cell"><?= date('M j, Y', strtotime($order['order_date'])) ?></td>
                                    <td class="date-cell"><?= date('M j, Y', strtotime($order['pickup_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add subtle animations
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach((row, index) => {
                row.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>