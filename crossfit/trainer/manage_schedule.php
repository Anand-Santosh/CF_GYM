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

// Get current assigned members count
$currentMembersStmt = $conn->prepare("
    SELECT COUNT(*) as member_count 
    FROM bookings 
    WHERE trainer_id = ? AND status = 'active'
");
$currentMembersStmt->execute([$trainer['trainer_id']]);
$currentMembers = $currentMembersStmt->fetch()['member_count'];

// Get assigned members with their time slots - FIXED: Removed email column
$assignedMembersStmt = $conn->prepare("
    SELECT 
        b.booking_id,
        m.member_id,
        m.full_name,
        m.phone,
        b.start_date,
        b.end_date,
        b.day_of_week,
        b.start_time,
        b.end_time,
        b.session_date,
        p.name as package_name
    FROM bookings b
    JOIN members m ON b.member_id = m.member_id
    JOIN packages p ON b.package_id = p.package_id
    WHERE b.trainer_id = ? AND b.status = 'active'
    ORDER BY b.day_of_week, b.start_time
");
$assignedMembersStmt->execute([$trainer['trainer_id']]);
$assignedMembers = $assignedMembersStmt->fetchAll();

// Handle time slot updates
if ($_POST && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_time_slot') {
        $booking_id = $_POST['booking_id'];
        $day_of_week = $_POST['day_of_week'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $session_date = $_POST['session_date'];
        
        // Check if time slot conflicts with existing slots for the same trainer
        $conflictCheck = $conn->prepare("
            SELECT * FROM bookings 
            WHERE trainer_id = ? 
            AND booking_id != ?
            AND day_of_week = ? 
            AND (
                (start_time <= ? AND end_time > ?) OR
                (start_time < ? AND end_time >= ?) OR
                (start_time >= ? AND end_time <= ?)
            )
            AND status = 'active'
        ");
        $conflictCheck->execute([
            $trainer['trainer_id'],
            $booking_id,
            $day_of_week,
            $start_time, $start_time,
            $end_time, $end_time,
            $start_time, $end_time
        ]);
        
        if ($conflictCheck->rowCount() > 0) {
            $_SESSION['error'] = "Time slot conflicts with existing schedule! Please choose a different time.";
        } else {
            // Update time slot in bookings table
            $updateStmt = $conn->prepare("
                UPDATE bookings 
                SET day_of_week = ?, start_time = ?, end_time = ?, session_date = ?
                WHERE booking_id = ? AND trainer_id = ?
            ");
            $updateStmt->execute([$day_of_week, $start_time, $end_time, $session_date, $booking_id, $trainer['trainer_id']]);
            $_SESSION['success'] = "Schedule updated successfully!";
        }
        header("Location: manage_schedule.php");
        exit();
    }
}

$pageTitle = "Manage Schedule";
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
            --warning: #ffc107;
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        h1, h2, h3 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
        }

        .capacity-indicator {
            background: var(--dark);
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary);
        }

        .capacity-bar {
            width: 100%;
            height: 20px;
            background: var(--darker);
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }

        .capacity-fill {
            height: 100%;
            background: var(--primary);
            transition: width 0.3s ease;
            border-radius: 10px;
        }

        .card {
            background: var(--dark);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .member-card {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 1.5rem;
            align-items: start;
        }

        .member-info h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .time-slot-form {
            background: var(--darker);
            padding: 1.5rem;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-light);
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            background: var(--dark);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 5px;
            color: var(--text-light);
            font-size: 14px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(255, 90, 31, 0.2);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .time-slot-display {
            background: var(--success);
            color: white;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            text-align: center;
        }

        .no-schedule {
            background: var(--warning);
            color: #000;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            text-align: center;
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.2);
            border: 1px solid var(--success);
            color: var(--text-light);
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.2);
            border: 1px solid var(--danger);
            color: var(--text-light);
        }

        .schedule-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .day-schedule {
            background: var(--dark);
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .day-schedule h4 {
            color: var(--primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .time-slot-item {
            background: var(--darker);
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            border-left: 3px solid var(--success);
        }

        .text-muted {
            color: var(--text-dark) !important;
        }

        @media (max-width: 768px) {
            .member-card {
                grid-template-columns: 1fr;
            }
            
            .schedule-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="bi bi-calendar-check"></i> Manage Member Schedules</h1>
            <a href="dashboard.php" class="btn btn-outline">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?= $_SESSION['success'] ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-circle"></i> <?= $_SESSION['error'] ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="capacity-indicator">
            <h3><i class="bi bi-people-fill"></i> Member Capacity</h3>
            <div class="capacity-bar">
                <div class="capacity-fill" style="width: <?= ($currentMembers / 5) * 100 ?>%"></div>
            </div>
            <p><strong><?= $currentMembers ?> out of 5 members assigned</strong></p>
            <?php if($currentMembers >= 5): ?>
                <p style="color: var(--danger);">
                    <i class="bi bi-exclamation-triangle"></i> Capacity reached! Cannot accept more members.
                </p>
            <?php else: ?>
                <p style="color: var(--success);">
                    <i class="bi bi-check-circle"></i> You can accept <?= 5 - $currentMembers ?> more members.
                </p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3><i class="bi bi-calendar-week"></i> Weekly Schedule Overview</h3>
            
            <?php if(empty($assignedMembers)): ?>
                <p class="text-muted">No members assigned yet.</p>
            <?php else: ?>
                <?php
                // Group members by day for schedule display
                $scheduleByDay = [];
                foreach($assignedMembers as $member) {
                    if($member['day_of_week']) {
                        $scheduleByDay[$member['day_of_week']][] = $member;
                    }
                }
                ?>

                <div class="schedule-grid">
                    <?php 
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    foreach($days as $day): 
                    ?>
                        <div class="day-schedule">
                            <h4><?= ucfirst($day) ?></h4>
                            <?php if(isset($scheduleByDay[$day])): ?>
                                <?php foreach($scheduleByDay[$day] as $slot): ?>
                                    <div class="time-slot-item">
                                        <strong><?= htmlspecialchars($slot['full_name']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($slot['package_name']) ?></small><br>
                                        <?= date('g:i A', strtotime($slot['start_time'])) ?> - <?= date('g:i A', strtotime($slot['end_time'])) ?>
                                        <?php if($slot['session_date']): ?>
                                            <br><small class="text-muted">Next: <?= date('M j, Y', strtotime($slot['session_date'])) ?></small>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted">No sessions scheduled</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <h4 style="margin-top: 2rem; margin-bottom: 1rem; color: var(--primary);">
                    <i class="bi bi-gear"></i> Set Member Time Slots
                </h4>

                <?php foreach($assignedMembers as $member): ?>
                    <div class="card member-card">
                        <div class="member-info">
                            <h4><?= htmlspecialchars($member['full_name']) ?></h4>
                            <p><strong>Package:</strong> <?= htmlspecialchars($member['package_name']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($member['phone']) ?></p>
                            <p><strong>Membership:</strong> <?= date('M j, Y', strtotime($member['start_date'])) ?> - <?= date('M j, Y', strtotime($member['end_date'])) ?></p>
                            
                            <?php if($member['day_of_week'] && $member['start_time']): ?>
                                <div class="time-slot-display">
                                    <strong><i class="bi bi-clock"></i> Current Schedule</strong><br>
                                    <?= ucfirst($member['day_of_week']) ?>, 
                                    <?= date('g:i A', strtotime($member['start_time'])) ?> - <?= date('g:i A', strtotime($member['end_time'])) ?>
                                    <?php if($member['session_date']): ?>
                                        <br><small>Next session: <?= date('M j, Y', strtotime($member['session_date'])) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-schedule">
                                    <i class="bi bi-exclamation-triangle"></i> No schedule set
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="time-slot-form">
                            <form method="POST">
                                <input type="hidden" name="action" value="update_time_slot">
                                <input type="hidden" name="booking_id" value="<?= $member['booking_id'] ?>">
                                
                                <div class="form-group">
                                    <label for="day_of_week_<?= $member['booking_id'] ?>">
                                        <i class="bi bi-calendar"></i> Day of Week
                                    </label>
                                    <select class="form-control" name="day_of_week" id="day_of_week_<?= $member['booking_id'] ?>" required>
                                        <option value="">Select Day</option>
                                        <option value="monday" <?= $member['day_of_week'] == 'monday' ? 'selected' : '' ?>>Monday</option>
                                        <option value="tuesday" <?= $member['day_of_week'] == 'tuesday' ? 'selected' : '' ?>>Tuesday</option>
                                        <option value="wednesday" <?= $member['day_of_week'] == 'wednesday' ? 'selected' : '' ?>>Wednesday</option>
                                        <option value="thursday" <?= $member['day_of_week'] == 'thursday' ? 'selected' : '' ?>>Thursday</option>
                                        <option value="friday" <?= $member['day_of_week'] == 'friday' ? 'selected' : '' ?>>Friday</option>
                                        <option value="saturday" <?= $member['day_of_week'] == 'saturday' ? 'selected' : '' ?>>Saturday</option>
                                        <option value="sunday" <?= $member['day_of_week'] == 'sunday' ? 'selected' : '' ?>>Sunday</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="start_time_<?= $member['booking_id'] ?>">
                                        <i class="bi bi-clock"></i> Start Time
                                    </label>
                                    <input type="time" class="form-control" name="start_time" id="start_time_<?= $member['booking_id'] ?>" 
                                           value="<?= $member['start_time'] ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="end_time_<?= $member['booking_id'] ?>">
                                        <i class="bi bi-clock"></i> End Time
                                    </label>
                                    <input type="time" class="form-control" name="end_time" id="end_time_<?= $member['booking_id'] ?>" 
                                           value="<?= $member['end_time'] ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="session_date_<?= $member['booking_id'] ?>">
                                        <i class="bi bi-calendar-date"></i> Next Session Date
                                    </label>
                                    <input type="date" class="form-control" name="session_date" id="session_date_<?= $member['booking_id'] ?>" 
                                           value="<?= $member['session_date'] ?>">
                                </div>
                                
                                <button type="submit" class="btn">
                                    <i class="bi bi-check-circle"></i> Update Schedule
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Add form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const startTime = this.querySelector('input[name="start_time"]').value;
                const endTime = this.querySelector('input[name="end_time"]').value;
                
                if (startTime && endTime && startTime >= endTime) {
                    e.preventDefault();
                    alert('End time must be after start time!');
                    return false;
                }
            });
        });
    </script>
</body>
</html>