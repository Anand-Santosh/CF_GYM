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

// Get assigned members list (removed problematic columns)
$assignedMembers = $conn->query("
    SELECT DISTINCT m.member_id, m.full_name, m.join_date, u.created_at
    FROM members m 
    JOIN users u ON m.user_id = u.user_id
    JOIN bookings b ON m.member_id = b.member_id 
    WHERE b.trainer_id = {$trainer['trainer_id']}
    ORDER BY m.full_name
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Clients - CrossFit Revolution</title>
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--darker) 0%, #1a1a1a 100%);
            color: var(--text-light);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: var(--dark);
            border-radius: 15px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        }

        .header-actions {
            display: flex;
            gap: 15px;
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
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 4px 15px rgba(255, 90, 31, 0.3);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 90, 31, 0.4);
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

        .btn-back {
            background-color: var(--primary);
            border: 2px solid var(--primary);
            color: white;
        }

        .btn-back:hover {
            background-color: var(--primary-dark);
            color: white;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
            margin-top: 1rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--dark) 0%, #1a1a1a 100%);
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
            border-color: rgba(255,90,31,0.3);
        }

        .stat-card.members {
            border-left: 4px solid var(--primary);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-card h3 {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--text-light), #cccccc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            color: var(--text-dark);
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
            display: block;
        }

        /* Client Cards */
        .clients-section {
            background: var(--dark);
            border-radius: 15px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .section-header h2 {
            font-size: 1.8rem;
            background: linear-gradient(135deg, var(--text-light), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .clients-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .client-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.05) 0%, rgba(255,255,255,0.02) 100%);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.4s ease;
            border-left: 4px solid var(--primary);
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .client-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255,90,31,0.1) 0%, transparent 50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .client-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(255,90,31,0.2);
            border-color: rgba(255,90,31,0.3);
        }

        .client-card:hover::before {
            opacity: 1;
        }

        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }

        .client-name {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        .client-id {
            color: var(--primary);
            font-size: 0.9rem;
            font-weight: 600;
            background: rgba(255,90,31,0.1);
            padding: 4px 12px;
            border-radius: 20px;
            display: inline-block;
        }

        .client-detail {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 2;
        }

        .detail-label {
            color: var(--primary);
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-value {
            color: var(--text-light);
            font-size: 1rem;
            font-weight: 500;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-dark);
            grid-column: 1 / -1;
            background: rgba(255,255,255,0.02);
            border-radius: 15px;
            border: 2px dashed rgba(255,255,255,0.1);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-light);
        }

        .empty-state p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
                padding: 1.5rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
                flex-wrap: wrap;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .clients-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .clients-section {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div>
                <h1><i class="bi bi-people-fill"></i> My Clients</h1>
                <p style="color: var(--text-dark); font-size: 1.1rem;">Manage your assigned clients and track their progress</p>
            </div>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-back">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
                <a href="../index.php" class="btn btn-home">
                    <i class="bi bi-house"></i> Home
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card members">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <h3><?= count($assignedMembers) ?></h3>
                <span class="stat-label">Total Clients</span>
            </div>
        </div>

        <!-- Clients Section -->
        <div class="clients-section">
            <div class="section-header">
                <i class="bi bi-list-ul" style="font-size: 2rem; color: var(--primary);"></i>
                <h2>Client Portfolio</h2>
            </div>
            
            <?php if(count($assignedMembers) > 0): ?>
                <div class="clients-grid">
                    <?php foreach($assignedMembers as $member): ?>
                        <div class="client-card">
                            <div class="client-header">
                                <div>
                                    <div class="client-name"><?= htmlspecialchars($member['full_name']) ?></div>
                                    <div class="client-id">ID: <?= $member['member_id'] ?></div>
                                </div>
                                <i class="bi bi-person-check" style="color: var(--primary); font-size: 1.5rem;"></i>
                            </div>
                            
                            <div class="client-detail">
                                <span class="detail-label">Member Since</span>
                                <span class="detail-value">
                                    <?php
                                    // Determine which date to use for "Member Since"
                                    $joinDate = null;
                                    
                                    // First try the join_date from members table
                                    if (!empty($member['join_date']) && $member['join_date'] != '0000-00-00') {
                                        $joinDate = date('M j, Y', strtotime($member['join_date']));
                                    } 
                                    // If not available, try created_at from users table
                                    elseif (!empty($member['created_at']) && $member['created_at'] != '0000-00-00') {
                                        $joinDate = date('M j, Y', strtotime($member['created_at']));
                                    } 
                                    // If neither is available, show "Not available"
                                    else {
                                        $joinDate = 'Not available';
                                    }
                                    
                                    echo $joinDate;
                                    ?>
                                </span>
                            </div>
                            
                            <div class="client-detail">
                                <span class="detail-label">Status</span>
                                <span class="detail-value" style="color: var(--success);">
                                    <i class="bi bi-check-circle-fill"></i> Active Member
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="bi bi-people"></i>
                    <h3>No Clients Assigned</h3>
                    <p>You don't have any clients assigned to you yet.</p>
                    <p style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.6;">
                        Clients will appear here once they are assigned to your training schedule.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add hover effects to stat cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
                this.style.boxShadow = '0 15px 40px rgba(0,0,0,0.4)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 8px 32px rgba(0,0,0,0.2)';
            });
        });

        // Add hover effects to client cards
        document.querySelectorAll('.client-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>