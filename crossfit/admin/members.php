<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/crossfit/includes/auth.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Handle delete member request
if (isset($_POST['member_id']) && isset($_POST['delete_member'])) {
    $member_id = $_POST['member_id'];
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // First get the user_id from the member
        $stmt = $conn->prepare("SELECT user_id FROM members WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        
        if ($member) {
            $user_id = $member['user_id'];
            
            // Delete from members table
            $stmt = $conn->prepare("DELETE FROM members WHERE member_id = ?");
            $stmt->execute([$member_id]);
            
            // Delete from users table
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $conn->commit();
            $_SESSION['success'] = "Member deleted successfully!";
        } else {
            $_SESSION['error'] = "Member not found!";
        }
        
        header("Location: members.php");
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error deleting member: " . $e->getMessage();
        header("Location: members.php");
        exit();
    }
}

// Handle view member request
$memberDetails = null;
if (isset($_GET['view_id'])) {
    $member_id = $_GET['view_id'];
    $stmt = $conn->prepare("
        SELECT m.*, u.email, u.created_at 
        FROM members m
        JOIN users u ON m.user_id = u.user_id
        WHERE m.member_id = :member_id
    ");
    $stmt->bindParam(':member_id', $member_id);
    $stmt->execute();
    $memberDetails = $stmt->fetch();
}

// Get all members - JOIN DATE FETCHED FROM USERS TABLE
$members = $conn->query("
    SELECT m.*, u.email, u.created_at as join_date 
    FROM members m
    JOIN users u ON m.user_id = u.user_id
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members - CrossFit Revolution</title>
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

        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--text-dark);
            line-height: 1.6;
            overflow-x: hidden;
            padding: 80px 20px 20px 20px;
            position: relative;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-bottom: 20px;
        }

        .text-center {
            text-align: center;
        }

        /* Header buttons */
        .header-buttons {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
            z-index: 100;
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 10px 20px;
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

        .btn-secondary {
            background-color: #6c757d;
            box-shadow: 0 5px 15px rgba(108, 117, 125, 0.4);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.6);
        }

        .btn-info {
            background-color: #17a2b8;
            box-shadow: 0 5px 15px rgba(23, 162, 184, 0.4);
        }

        .btn-info:hover {
            background-color: #138496;
            box-shadow: 0 8px 20px rgba(23, 162, 184, 0.6);
        }

        .btn-danger {
            background-color: #dc3545;
            box-shadow: 0 5px 15px rgba(220, 53, 69, 0.4);
        }

        .btn-danger:hover {
            background-color: #c82333;
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.6);
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.8rem;
        }

        /* Table Styles */
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 30px 0;
            background-color: var(--darker);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-dark);
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        th {
            background-color: rgba(255, 90, 31, 0.2);
            color: var(--primary);
            font-family: 'Oswald', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tr:hover {
            background-color: rgba(255,255,255,0.05);
        }

        /* Modal Styles */
        .modal {
            display: <?= $memberDetails ? 'block' : 'none' ?>;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal-content {
            background-color: var(--darker);
            margin: 50px auto;
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            position: relative;
        }

        .close-modal {
            position: absolute;
            top: 15px;
            right: 15px;
            color: var(--text-dark);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .close-modal:hover {
            color: var(--primary);
        }

        .member-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }

        .detail-group {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 5px;
            display: block;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border-color: rgba(40, 167, 69, 0.3);
            color: #28a745;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border-color: rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        /* Form Styles */
        form {
            display: inline;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 70px 10px 10px 10px;
            }
            
            .header-buttons {
                position: fixed;
                top: 10px;
                right: 10px;
            }
            
            .btn {
                padding: 8px 15px;
                font-size: 0.8rem;
            }
            
            th, td {
                padding: 10px;
            }
            
            .modal-content {
                width: 95%;
                padding: 20px;
            }
            
            .member-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header-buttons">
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>

    <h2>Manage Members</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Join Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach($members as $member): ?>
                <tr>
                    <td><?= $counter ?></td>
                    <td><?= htmlspecialchars($member['full_name']) ?></td>
                    <td><?= htmlspecialchars($member['email']) ?></td>
                    <td><?= htmlspecialchars($member['phone']) ?></td>
                    <td>
                        <?php 
                        // Join date is now fetched from users table (created_at)
                        if (!empty($member['join_date']) && $member['join_date'] != '0000-00-00 00:00:00') {
                            echo date('M j, Y', strtotime($member['join_date']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </td>
                    <td>
                        <a href="?view_id=<?= $member['member_id'] ?>" class="btn btn-info btn-sm">View</a>
                        <form action="members.php" method="POST" style="display:inline;">
                            <input type="hidden" name="member_id" value="<?= $member['member_id'] ?>">
                            <input type="hidden" name="delete_member" value="1">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this member? This action cannot be undone.')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php $counter++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Member View Modal -->
    <div id="memberModal" class="modal">
        <div class="modal-content">
            <a href="?" class="close-modal">&times;</a>
            <h2>Member Details</h2>
            <?php if ($memberDetails): ?>
            <div class="member-details">
                <div class="detail-group">
                    <span class="detail-label">Member ID:</span>
                    <span><?= $memberDetails['member_id'] ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Full Name:</span>
                    <span><?= htmlspecialchars($memberDetails['full_name']) ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Email:</span>
                    <span><?= htmlspecialchars($memberDetails['email']) ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Phone:</span>
                    <span><?= htmlspecialchars($memberDetails['phone']) ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Address:</span>
                    <span><?= htmlspecialchars($memberDetails['address'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Date of Birth:</span>
                    <span>
                        <?php 
                        if (!empty($memberDetails['dob']) && $memberDetails['dob'] != '0000-00-00') {
                            echo date('M j, Y', strtotime($memberDetails['dob']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                
                <div class="detail-group">
                    <span class="detail-label">Join Date:</span>
                    <span>
                        <?php 
                        // Join date is now fetched from users table (created_at)
                        if (!empty($memberDetails['created_at']) && $memberDetails['created_at'] != '0000-00-00 00:00:00') {
                            echo date('M j, Y', strtotime($memberDetails['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Membership Status:</span>
                    <span><?= htmlspecialchars($memberDetails['membership_status'] ?? 'Active') ?></span>
                </div>
            </div>
            <?php else: ?>
            <p>Member details not found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add some animation to table rows
        document.addEventListener('DOMContentLoaded', function() {
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach((row, index) => {
                row.style.opacity = '0';
                row.style.transform = 'translateX(-20px)';
                row.style.transition = 'all 0.5s ease';
                
                setTimeout(() => {
                    row.style.opacity = '1';
                    row.style.transform = 'translateX(0)';
                }, 100 * index);
            });
            
            // Close modal when clicking outside of it
            window.onclick = function(event) {
                const modal = document.getElementById('memberModal');
                if (event.target == modal) {
                    window.location.href = '?';
                }
            }
        });
    </script>
</body>
</html>