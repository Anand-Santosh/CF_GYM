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

// Get assigned trainer
$trainer = $conn->query("
    SELECT t.* 
    FROM trainers t
    JOIN bookings b ON t.trainer_id = b.trainer_id
    WHERE b.member_id = {$member['member_id']}
    AND b.status = 'active'
    LIMIT 1
")->fetch();

// If no trainer is assigned, redirect to book trainer page
if (!$trainer) {
    header("Location: book_trainer.php");
    exit();
}

// Get trainer's active clients count
$activeClients = $conn->query("
    SELECT COUNT(DISTINCT member_id) as client_count 
    FROM bookings 
    WHERE trainer_id = {$trainer['trainer_id']} 
    AND status = 'active'
")->fetch()['client_count'];

// Get trainer's specialization details
$specialization = $trainer['specialization'] ?: 'Not specified';

$pageTitle = "My Trainer - " . htmlspecialchars($trainer['full_name']);
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

        .trainer-container {
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

        .btn-home {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-home:hover {
            background-color: var(--primary);
            color: white;
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

        .trainer-profile {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 900px) {
            .trainer-profile {
                grid-template-columns: 1fr;
            }
        }

        .profile-sidebar {
            text-align: center;
        }

        .profile-image {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 4px solid var(--primary);
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.3);
        }

        .specialization-badge {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
        }

        .stat-item {
            background-color: var(--darker);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .stat-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.3);
            border-color: var(--primary);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-dark);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .info-label {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-value {
            font-size: 1.1rem;
            color: var(--text-light);
        }

        .bio-content {
            color: var(--text-dark);
            line-height: 1.6;
            background-color: rgba(255,255,255,0.05);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .card {
                padding: 1.5rem;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .trainer-container {
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

        .lead {
            color: var(--text-dark);
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="trainer-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1><i class="bi bi-person-badge"></i> My Trainer</h1>
                <p class="lead">Your dedicated fitness professional</p>
            </div>
            <div>
                <a href="dashboard.php" class="btn btn-outline">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Trainer Profile -->
        <div class="card">
            <div class="trainer-profile">
                <!-- Profile Sidebar -->
                <div class="profile-sidebar">
                    <?php
                    $profileImage = !empty($trainer['profile_photo']) ? 
                        '../uploads/profile_photos/' . $trainer['profile_photo'] : 
                        '../assets/default-trainer.jpg';
                    ?>
                    <img src="<?= $profileImage ?>" alt="Trainer Profile" class="profile-image">
                    
                    <div class="specialization-badge">
                        <i class="bi bi-award"></i> <?= htmlspecialchars($specialization) ?>
                    </div>
                    
                    <h2><?= htmlspecialchars($trainer['full_name']) ?></h2>
                    
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?= $activeClients ?></div>
                            <div class="stat-label">Active Clients</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?= $trainer['experience_years'] ?? '0' ?></div>
                            <div class="stat-label">Years Experience</div>
                        </div>
                    </div>
                </div>

                <!-- Profile Details -->
                <div class="profile-details">
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-patch-check"></i> Certification
                        </div>
                        <div class="info-value">
                            <?= !empty($trainer['certification']) ? htmlspecialchars($trainer['certification']) : 'Not specified' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-clock-history"></i> Experience
                        </div>
                        <div class="info-value">
                            <?= $trainer['experience_years'] ? $trainer['experience_years'] . ' years' : 'Not specified' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-star"></i> Specialization
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($specialization) ?>
                        </div>
                    </div>

                    <?php if(!empty($trainer['bio'])): ?>
                    <div class="info-item">
                        <div class="info-label">
                            <i class="bi bi-person-lines-fill"></i> About
                        </div>
                        <div class="bio-content">
                            <?= htmlspecialchars($trainer['bio']) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <a href="message.php?trainer_id=<?= $trainer['trainer_id'] ?>" class="btn">
                            <i class="bi bi-chat-dots"></i> Send Message
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Training Philosophy -->
        <?php if(!empty($trainer['training_philosophy'])): ?>
        <div class="card">
            <h3><i class="bi bi-lightbulb"></i> Training Philosophy</h3>
            <div class="bio-content" style="margin-top: 1rem;">
                <?= htmlspecialchars($trainer['training_philosophy']) ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Qualifications -->
        <div class="card">
            <h3><i class="bi bi-award"></i> Qualifications & Achievements</h3>
            <div style="margin-top: 1.5rem;">
                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-patch-check"></i> Certifications
                    </div>
                    <div class="info-value">
                        <?= !empty($trainer['certification']) ? htmlspecialchars($trainer['certification']) : 'No certifications listed' ?>
                    </div>
                </div>

                <?php if(!empty($trainer['education'])): ?>
                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-mortarboard"></i> Education
                    </div>
                    <div class="info-value">
                        <?= htmlspecialchars($trainer['education']) ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(!empty($trainer['achievements'])): ?>
                <div class="info-item">
                    <div class="info-label">
                        <i class="bi bi-trophy"></i> Achievements
                    </div>
                    <div class="info-value">
                        <?= htmlspecialchars($trainer['achievements']) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Add hover effects to cards
        document.querySelectorAll('.card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 10px 25px rgba(255, 90, 31, 0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.2)';
            });
        });

        // Add hover effects to stat items
        document.querySelectorAll('.stat-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
                this.style.boxShadow = '0 5px 15px rgba(255, 90, 31, 0.2)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = 'none';
            });
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>