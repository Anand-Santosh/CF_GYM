<?php
require_once '../includes/auth.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get stats for dashboard
$members = $conn->query("SELECT COUNT(*) FROM members")->fetchColumn();
$trainers = $conn->query("SELECT COUNT(*) FROM trainers")->fetchColumn();
$packages = $conn->query("SELECT COUNT(*) FROM packages")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CrossFit Revolution</title>
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

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
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
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.4);
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

        .stat-card.members {
            border-left-color: var(--primary);
        }

        .stat-card.trainers {
            border-left-color: var(--success);
        }

        .stat-card.packages {
            border-left-color: var(--info);
        }

        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.members .stat-icon {
            color: var(--primary);
        }

        .stat-card.trainers .stat-icon {
            color: var(--success);
        }

        .stat-card.packages .stat-icon {
            color: var(--info);
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
        }

        @media (max-width: 576px) {
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <a href="dashboard.php" class="sidebar-logo">CROSSFIT REVOLUTION</a>
            <p style="color: var(--text-dark); font-size: 0.9rem;">Admin Panel</p>
        </div>
        
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            
            <li class="nav-category">Management</li>
            
            <li class="nav-item">
                <a href="members.php" class="nav-link">
                    <i class="bi bi-people"></i> Members
                </a>
            </li>
            
            <li class="nav-item">
                <a href="trainers.php" class="nav-link">
                    <i class="bi bi-person-badge"></i> Trainers
                </a>
            </li>
            
            <li class="nav-item">
                <a href="packages.php" class="nav-link">
                    <i class="bi bi-box-seam"></i> Packages
                </a>
            </li>
            
            <li class="nav-category">Operations</li>
            
            <li class="nav-item">
                <a href="supplements.php" class="nav-link">
                    <i class="bi bi-capsule"></i> Supplements
                </a>
            </li>
            
            <li class="nav-category">System</li>
            
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="bi bi-gear"></i> Settings
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
            <div>
                <h1><i class="bi bi-speedometer2"></i> Admin Dashboard</h1>
                <p style="color: var(--text-dark);">Manage your CrossFit gym operations</p>
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
            <div class="stat-card members">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <h3><?= $members ?></h3>
                <span class="stat-label">Total Members</span>
                
            </div>
            
            <div class="stat-card trainers">
                <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                <h3><?= $trainers ?></h3>
                <span class="stat-label">Total Trainers</span>
                
            </div>
            
            <div class="stat-card packages">
                <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
                <h3><?= $packages ?></h3>
                <span class="stat-label">Active Packages</span>
                
            </div>
        </div>

        <!-- Quick Actions -->
        <h2 style="margin-bottom: 1.5rem;">Quick Actions</h2>
        <div class="actions-grid">
            <div class="action-card">
                <i class="bi bi-people action-icon" style="color: var(--primary);"></i>
                <h5>Manage Members</h5>
                <a href="members.php" class="btn btn-outline">Go</a>
            </div>
            
            <div class="action-card">
                <i class="bi bi-person-badge action-icon" style="color: var(--success);"></i>
                <h5>Manage Trainers</h5>
                <a href="trainers.php" class="btn btn-outline">Go</a>
            </div>
            
            <div class="action-card">
                <i class="bi bi-capsule action-icon" style="color: var(--info);"></i>
                <h5>Supplement Inventory</h5>
                <a href="supplements.php" class="btn btn-outline">Go</a>
            </div>
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
    </script>
</body>
</html>