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

// Get assigned members list with correct user_id
$assignedMembers = $conn->query("
    SELECT DISTINCT m.member_id, m.full_name, m.user_id, u.created_at
    FROM members m 
    JOIN users u ON m.user_id = u.user_id
    JOIN bookings b ON m.member_id = b.member_id 
    WHERE b.trainer_id = {$trainer['trainer_id']}
    ORDER BY m.full_name
")->fetchAll();

// Get current assigned members count for capacity
$currentMembers = $conn->query("
    SELECT COUNT(*) as member_count 
    FROM bookings 
    WHERE trainer_id = {$trainer['trainer_id']} AND status = 'active'
")->fetch()['member_count'];

// Get today's sessions count
$today = strtolower(date('l'));
$todaySessions = $conn->query("
    SELECT COUNT(*) as count FROM bookings 
    WHERE trainer_id = {$trainer['trainer_id']} 
    AND day_of_week = '$today' 
    AND status = 'active'
")->fetch()['count'];

// Get unread messages count (check if read_status column exists)
try {
    // First check if read_status column exists
    $columnCheck = $conn->query("SHOW COLUMNS FROM messages LIKE 'read_status'")->fetch();
    if ($columnCheck) {
        $unreadMessages = $conn->query("
            SELECT COUNT(*) as unread_count 
            FROM messages 
            WHERE receiver_id = {$_SESSION['user_id']} AND read_status = 0
        ")->fetch()['unread_count'];
    } else {
        $unreadMessages = 0;
    }
} catch (PDOException $e) {
    $unreadMessages = 0;
}

// Get recent messages using correct column names (newest first at top)
try {
    $recentMessages = $conn->query("
        SELECT m.*, mem.full_name as sender_name 
        FROM messages m 
        LEFT JOIN members mem ON m.sender_id = mem.user_id 
        WHERE m.receiver_id = {$_SESSION['user_id']} 
        ORDER BY m.sent_at DESC 
        LIMIT 5
    ")->fetchAll();
} catch (PDOException $e) {
    $recentMessages = [];
}

// Handle sending a message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $message_content = $_POST['message_content'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $receiver_id, $message_content]);
        
        $messageSuccess = "Message sent successfully!";
        
        // Refresh page to show new message
        header("Location: dashboard.php");
        exit();
    } catch(PDOException $e) {
        $messageError = "Failed to send message: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Dashboard - CrossFit Revolution</title>
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

        .trainer-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .specialization-badge {
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

        .stat-card.clients {
            border-left-color: var(--primary);
        }

        .stat-card.schedule {
            border-left-color: var(--success);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.clients .stat-icon {
            color: var(--primary);
        }

        .stat-card.schedule .stat-icon {
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

        .capacity-info {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-top: 0.5rem;
        }

        .capacity-full {
            color: var(--danger);
            font-weight: 600;
        }

        .capacity-available {
            color: var(--success);
            font-weight: 600;
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

        /* Messaging Section */
        .messaging-section {
            background-color: var(--dark);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .message-list {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 1.5rem;
            border-radius: 10px;
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .message-item {
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            transition: background-color 0.3s ease;
        }

        .message-item:last-child {
            border-bottom: none;
        }

        .message-item:hover {
            background-color: rgba(255,255,255,0.05);
        }

        .message-item.unread {
            background-color: rgba(23, 162, 184, 0.1);
            border-left: 4px solid var(--info);
        }

        .message-sender {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .message-content {
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .message-time {
            font-size: 0.8rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .message-form {
            background-color: var(--darker);
            padding: 1.5rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--text-light);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 14px 16px;
            background-color: var(--dark);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.2);
            background-color: rgba(255,255,255,0.08);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
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

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            border-color: rgba(40, 167, 69, 0.2);
            color: #d4edda;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            border-color: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }

        .section-header h2 {
            margin-bottom: 0;
            color: var(--primary);
        }

        /* Style for dropdown options */
        .form-select option {
            color: var(--text-light);
            background-color: var(--dark);
        }

        .form-select option:first-child {
            color: var(--text-dark);
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
        }

        @media (max-width: 576px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
            
            .messaging-section {
                padding: 1.5rem;
            }
            
            .message-form {
                padding: 1.2rem;
            }
        }

        /* Custom scrollbar */
        .message-list::-webkit-scrollbar {
            width: 6px;
        }

        .message-list::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
            border-radius: 3px;
        }

        .message-list::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .message-list::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">CROSSFIT REVOLUTION</a>
            <p style="color: var(--text-dark); font-size: 0.9rem;">Trainer Panel</p>
        </div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-category">Management</li>
            
            <li class="nav-item">
                <a href="clients.php" class="nav-link">
                    <i class="bi bi-people"></i> My Clients
                </a>
            </li>
            
            <!-- ADDED: Manage Schedules Link -->
            <li class="nav-item">
                <a href="manage_schedule.php" class="nav-link">
                    <i class="bi bi-calendar-check"></i> Manage Schedules
                </a>
            </li>
            
            <li class="nav-category">Communication</li>
            
            <li class="nav-item">
                <a href="messages.php" class="nav-link">
                    <i class="bi bi-chat-dots"></i> Messages
                </a>
            </li>
            
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
            <div class="trainer-info">
                <div>
                    <h1><i class="bi bi-person-badge"></i> Trainer Dashboard</h1>
                    <p style="color: var(--text-dark);">Welcome back, <?= htmlspecialchars($trainer['full_name']) ?></p>
                    <div class="specialization-badge">
                        <i class="bi bi-award"></i> <?= htmlspecialchars($trainer['specialization']) ?>
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
            <div class="stat-card clients">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <h3><?= $currentMembers ?>/5</h3>
                <span class="stat-label">Assigned Clients</span>
                <div class="capacity-info">
                    <?php if($currentMembers >= 5): ?>
                        <span class="capacity-full"><i class="bi bi-exclamation-triangle"></i> Capacity Full</span>
                    <?php else: ?>
                        <span class="capacity-available"><i class="bi bi-check-circle"></i> <?= 5 - $currentMembers ?> slots available</span>
                    <?php endif; ?>
                </div>
                <a href="clients.php"></a>
            </div>
            
            <div class="stat-card schedule">
                <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
                <h3><?= $todaySessions ?></h3>
                <span class="stat-label">Today's Sessions</span>
                <a href="manage_schedule.php"></a>
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 1.5rem;">Quick Actions</h2>
        <div class="actions-grid">
            <div class="action-card">
                <i class="bi bi-people-fill action-icon" style="color: var(--primary);"></i>
                <h5>Manage Clients</h5>
                <a href="clients.php" class="btn btn-outline">Go</a>
            </div>
            
            <!-- ADDED: Manage Schedules Action Card -->
            <div class="action-card">
                <i class="bi bi-calendar-check action-icon" style="color: var(--success);"></i>
                <h5>Manage Schedules</h5>
                <a href="manage_schedule.php" class="btn btn-outline">Go</a>
            </div>
            
            <div class="action-card">
                <i class="bi bi-chat-dots-fill action-icon" style="color: var(--info);"></i>
                <h5>View Messages</h5>
                <a href="messages.php" class="btn btn-outline">Go</a>
            </div>
            
            <div class="action-card">
                <i class="bi bi-person-gear action-icon" style="color: var(--warning);"></i>
                <h5>Profile Settings</h5>
                <a href="profile.php" class="btn btn-outline">Go</a>
            </div>
        </div>

        <!-- Messaging Section -->
        <div class="messaging-section">
            <div class="section-header">
                <i class="bi bi-chat-dots-fill" style="color: var(--info); font-size: 1.5rem;"></i>
                <h2>Quick Messaging</h2>
            </div>
            
            <?php if(isset($messageSuccess)): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle-fill"></i> <?= $messageSuccess ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($messageError)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle-fill"></i> <?= $messageError ?>
                </div>
            <?php endif; ?>
            
            <!-- Recent Messages -->
            <h3 style="margin-bottom: 1rem; color: var(--text-light);">
                <i class="bi bi-clock-history"></i> Recent Messages
            </h3>
            <div class="message-list">
                <?php if(count($recentMessages) > 0): ?>
                    <?php foreach($recentMessages as $message): ?>
                        <div class="message-item <?= (isset($message['read_status']) && $message['read_status'] == 0) ? 'unread' : '' ?>">
                            <div class="message-sender">
                                <i class="bi bi-person"></i> <?= htmlspecialchars($message['sender_name']) ?>
                            </div>
                            <div class="message-content"><?= htmlspecialchars($message['content']) ?></div>
                            <div class="message-time">
                                <i class="bi bi-clock"></i> <?= date('M j, Y g:i A', strtotime($message['sent_at'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 2rem; text-align: center; color: var(--text-dark);">
                        <i class="bi bi-chat-square-text" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                        No messages yet
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Send Message Form -->
            <h3 style="margin-bottom: 1rem; color: var(--text-light);">
                <i class="bi bi-send"></i> Send New Message
            </h3>
            <form method="POST" class="message-form">
                <div class="form-group">
                    <label for="receiver_id" class="form-label">
                        <i class="bi bi-person"></i> Select Client
                    </label>
                    <select class="form-select" id="receiver_id" name="receiver_id" required>
                        <option value="">Choose a client...</option>
                        <?php foreach($assignedMembers as $member): ?>
                            <option value="<?= $member['user_id'] ?>"><?= htmlspecialchars($member['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="message_content" class="form-label">
                        <i class="bi bi-chat-text"></i> Your Message
                    </label>
                    <textarea class="form-control" id="message_content" name="message_content" rows="4" placeholder="Type your message here..." required></textarea>
                </div>
                
                <button type="submit" name="send_message" class="btn">
                    <i class="bi bi-send-fill"></i> Send Message
                </button>
            </form>
        </div>
    </main>

    <script>
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

        // Auto-focus on message textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('message_content');
            if (textarea) {
                textarea.focus();
            }
        });

        // Scroll to top when page loads
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>