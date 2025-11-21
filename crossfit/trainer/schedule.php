<?php
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'trainer') {
    header("Location: ../index.php");
    exit();
}

// Get trainer info
$stmt = $conn->prepare("SELECT * FROM trainers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$trainer = $stmt->fetch();

// Get assigned members with schedule
$scheduled_sessions = $conn->query("
    SELECT m.full_name, b.start_date, b.end_date, b.status 
    FROM bookings b
    JOIN members m ON b.member_id = m.member_id
    WHERE b.trainer_id = {$trainer['trainer_id']}
    ORDER BY b.start_date
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Schedule - CrossFit Revolution</title>
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

        .container {
            padding: 30px;
            max-width: 1200px;
            margin: 0 auto;
            min-height: 100vh;
        }

        .card {
            background-color: var(--dark);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .card-body {
            padding: 20px;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.4);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.6);
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

        .table {
            width: 100%;
            color: var(--text-light);
            border-collapse: collapse;
        }

        .table th {
            background-color: rgba(255,255,255,0.05);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid var(--primary);
        }

        .table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .table tr:hover {
            background-color: rgba(255,255,255,0.02);
        }

        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .bg-success {
            background-color: var(--success) !important;
        }

        .bg-warning {
            background-color: var(--warning) !important;
            color: #000;
        }

        .bg-secondary {
            background-color: #6c757d !important;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--text-dark);
        }

        .empty-state p {
            margin-bottom: 20px;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                min-width: 600px;
            }
            
            .table th,
            .table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>Your Training Schedule</h2>
            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">Scheduled Sessions</h3>
            </div>
            <div class="card-body">
                <?php if(empty($scheduled_sessions)): ?>
                    <div class="empty-state">
                        <p>You don't have any scheduled sessions yet.</p>
                        <a href="#" class="btn">View Available Members</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Member Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($scheduled_sessions as $session): ?>
                                <tr>
                                    <td><?= htmlspecialchars($session['full_name']) ?></td>
                                    <td><?= date('M j, Y', strtotime($session['start_date'])) ?></td>
                                    <td><?= date('M j, Y', strtotime($session['end_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $session['status'] == 'active' ? 'success' : 
                                            ($session['status'] == 'completed' ? 'secondary' : 'warning') 
                                        ?>">
                                            <?= ucfirst($session['status']) ?>
                                        </span>
                                    </td>
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
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.table tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.backgroundColor = 'rgba(255, 90, 31, 0.05)';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.backgroundColor = '';
                });
            });
            
            // Add animation to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>