<?php
require_once '../includes/auth.php';

if ($_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member information
$stmt = $conn->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Retrieve member's active package bookings without trainers
$memberPackages = $conn->query("
    SELECT p.*, b.start_date, b.end_date 
    FROM packages p 
    JOIN bookings b ON p.package_id = b.package_id 
    WHERE b.member_id = {$member['member_id']} 
    AND b.status = 'active'
    AND b.trainer_id IS NULL
    ORDER BY b.start_date DESC
")->fetchAll();

// Retrieve active trainer bookings for display
$successfulBookings = $conn->query("
    SELECT b.*, p.name as package_name, p.duration_months, p.price,
           t.full_name as trainer_name, t.specialization, t.trainer_id
    FROM bookings b
    JOIN packages p ON b.package_id = p.package_id
    JOIN trainers t ON b.trainer_id = t.trainer_id
    WHERE b.member_id = {$member['member_id']} 
    AND b.status = 'active'
    AND b.trainer_id IS NOT NULL
    ORDER BY b.start_date DESC
")->fetchAll();

// Verify if member has active trainer sessions
$hasActiveTrainer = !empty($successfulBookings);

// Process trainer booking request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $trainer_id = $_POST['trainer_id'];
    $package_id = $_POST['package_id'];
    $start_date = $_POST['start_date'];
    $notes = $_POST['notes'] ?? '';
    
    try {
        // Validate package ownership
        $validPackage = false;
        foreach($memberPackages as $package) {
            if ($package['package_id'] == $package_id) {
                $validPackage = true;
                break;
            }
        }
        
        if (!$validPackage) {
            throw new Exception("Invalid package selection");
        }
        
        // Verify trainer availability
        $capacityCheck = $conn->prepare("
            SELECT COUNT(*) as current_members 
            FROM bookings 
            WHERE trainer_id = ? AND status = 'active'
        ");
        $capacityCheck->execute([$trainer_id]);
        $currentMembers = $capacityCheck->fetch()['current_members'];

        if ($currentMembers >= 5) {
            throw new Exception("This trainer has reached maximum capacity (5 members). Please choose another trainer.");
        }
        
        // Calculate training duration
        $stmt = $conn->prepare("SELECT duration_months FROM packages WHERE package_id = ?");
        $stmt->execute([$package_id]);
        $package = $stmt->fetch();
        
        $end_date = date('Y-m-d', strtotime($start_date . " + " . $package['duration_months'] . " months"));
        
        // Retrieve trainer information
        $stmt = $conn->prepare("SELECT full_name FROM trainers WHERE trainer_id = ?");
        $stmt->execute([$trainer_id]);
        $trainer = $stmt->fetch();
        
        // Create training session record
        $stmt = $conn->prepare("INSERT INTO bookings (member_id, trainer_id, package_id, start_date, end_date, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
        $stmt->execute([$member['member_id'], $trainer_id, $package_id, $start_date, $end_date, $notes]);
        
        $_SESSION['success'] = "Trainer booking confirmed! You are now working with " . $trainer['full_name'] . ".";
        header("Location: book_trainer.php");
        exit();
    } catch(PDOException $e) {
        $error = "Booking processing failed: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Retrieve all trainers with current capacity information
$trainers = $conn->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM bookings WHERE trainer_id = t.trainer_id AND status = 'active') as current_members
    FROM trainers t
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Management - CrossFit Revolution</title>
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
}

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
    min-height: 100vh;
    padding: 20px;
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
    display: inline-block;
    padding: 12px 30px;
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
    color: white;
}

.btn-success:hover {
    background-color: #218838;
    transform: translateY(-3px);
}

.booking-form {
    background-color: var(--darker);
    padding: 2.5rem;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    margin-bottom: 2rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-light);
}

.form-control, .form-select {
    width: 100%;
    padding: 12px 15px;
    background-color: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 5px;
    color: var(--text-dark);
    font-family: 'Montserrat', sans-serif;
    transition: all 0.3s ease;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.form-select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23E0E0E0'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    background-size: 16px;
    padding-right: 45px;
}

.form-select option {
    background-color: var(--darker);
    color: var(--text-dark);
    padding: 12px;
}

.form-control:focus, .form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.3);
    background-color: rgba(255,255,255,0.08);
}

.form-control::placeholder {
    color: rgba(224, 224, 224, 0.6);
}

.form-control[type="date"] {
    color-scheme: dark;
}

textarea.form-control {
    resize: vertical;
    min-height: 100px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
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

.alert-warning {
    background-color: rgba(255, 193, 7, 0.15);
    border-color: rgba(255, 193, 7, 0.3);
    color: #856404;
}

.alert-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.btn-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-top: 2rem;
}

@media (max-width: 768px) {
    .btn-group {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .booking-form {
        padding: 1.5rem;
    }
    
    .alert {
        padding: 1.2rem;
        flex-direction: column;
        text-align: center;
    }
}

/* Trainer cards preview */
.trainer-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-top: 2rem;
}

.trainer-card {
    background-color: var(--darker);
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
    border-left: 4px solid var(--primary);
    position: relative;
}

.trainer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

.trainer-info {
    padding: 1.5rem;
}

.trainer-specialization {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 1rem;
    display: block;
}

.capacity-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
    font-weight: 600;
}

.capacity-available {
    background-color: var(--success);
    color: white;
}

.capacity-full {
    background-color: var(--danger);
    color: white;
}

.capacity-warning {
    background-color: var(--warning);
    color: #000;
}

.select-trainer-btn {
    background: transparent;
    border: 2px solid var(--primary);
    color: var(--primary);
    padding: 8px 15px;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
    width: 100%;
}

.select-trainer-btn:hover:not(:disabled) {
    background: var(--primary);
    color: white;
}

.select-trainer-btn:disabled {
    border-color: var(--danger);
    color: var(--danger);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Success bookings section */
.success-bookings {
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.booking-card {
    background-color: var(--darker);
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border-left: 4px solid #28a745;
    transition: all 0.3s ease;
}

.booking-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.booking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.booking-trainer {
    color: var(--primary);
    font-weight: 600;
    font-size: 1.1rem;
}

.booking-package {
    color: var(--text-light);
    font-weight: 600;
}

.booking-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.booking-detail {
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

.status-badge {
    background-color: #28a745;
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

/* Additional dropdown improvements */
.form-select:hover {
    background-color: rgba(255,255,255,0.08);
    border-color: rgba(255,255,255,0.2);
}

.form-select.focused {
    background-color: rgba(255,255,255,0.08);
    transform: translateY(-1px);
}

.form-select.has-value {
    border-color: rgba(255, 90, 31, 0.3);
}

.capacity-info {
    margin-top: 0.5rem;
    font-size: 0.9rem;
    padding: 8px 12px;
    border-radius: 5px;
    background: rgba(255,255,255,0.05);
}

.trainer-option {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.capacity-text {
    font-size: 0.8rem;
    opacity: 0.8;
}

/* View Trainer Button Styles */
.view-trainer-btn {
    background: var(--success);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 1rem;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.view-trainer-btn:hover {
    background: #218838;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
    color: white;
}

.booking-actions {
    display: flex;
    gap: 1rem;
    margin-top: 1.5rem;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .booking-actions {
        flex-direction: column;
    }
    
    .booking-actions .btn,
    .booking-actions .view-trainer-btn {
        width: 100%;
        text-align: center;
    }
}

.already-booked {
    text-align: center;
    padding: 3rem 2rem;
    background-color: var(--darker);
    border-radius: 10px;
    margin-bottom: 2rem;
}

.already-booked-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.already-booked h2 {
    color: var(--success);
    margin-bottom: 1rem;
}

.already-booked p {
    margin-bottom: 2rem;
    font-size: 1.1rem;
}
</style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1><?php echo $hasActiveTrainer ? 'My Trainer' : 'Book a Trainer'; ?></h1>
            <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
        </div>

        <?php if(isset($error)): ?>
            <div class="alert alert-danger">
                <div class="alert-icon">⚠️</div>
                <div>
                    <strong>Booking Processing Failed</strong><br>
                    <?php echo $error; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <div class="alert-icon">✅</div>
                <div>
                    <strong>Success!</strong><br>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
                <div class="booking-actions" style="margin-top: 1rem;">
                    <a href="view_trainer.php" class="view-trainer-btn">
                        View My Trainer
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if($hasActiveTrainer): ?>
            <!-- Display when member has active trainer sessions -->
            <div class="already-booked">
                <div class="already-booked-icon">✅</div>
                <h2>Active Training Session Confirmed</h2>
                <p>Your trainer assignment is currently active. View your session details below or contact administration for modifications.</p>
                <div class="booking-actions">
                    <a href="message.php" class="btn btn-outline">
                        Message My Trainer
                    </a>
                </div>
            </div>
        <?php elseif(empty($memberPackages)): ?>
            <div class="alert alert-info">
                <div class="alert-icon">ℹ️</div>
                <div>
                    <h3>No Active Packages Available</h3>
                    <p>An active training package is required to book a trainer session.</p>
                    <a href="book_package.php" class="btn" style="margin-top: 1rem;">Purchase Training Package</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Display booking interface for available packages -->
            <div class="booking-form">
                <form method="POST" id="bookingForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="trainer_id" class="form-label">Select Training Professional</label>
                            <select class="form-select" id="trainer_id" name="trainer_id" required>
                                <option value="">Choose Your Trainer</option>
                                <?php foreach($trainers as $trainer): 
                                    $availableSlots = 5 - $trainer['current_members'];
                                    $isAvailable = $availableSlots > 0;
                                ?>
                                <option value="<?= $trainer['trainer_id'] ?>" <?= !$isAvailable ? 'disabled' : '' ?>>
                                    <span class="trainer-option">
                                        <span>
                                            <?= htmlspecialchars($trainer['full_name']) ?> - <?= htmlspecialchars($trainer['specialization']) ?>
                                        </span>
                                        <span class="capacity-text">
                                            <?= $isAvailable ? "({$availableSlots} slots available)" : "(FULL)" ?>
                                        </span>
                                    </span>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="capacity-info">
                                <small>Maximum capacity: 5 members per trainer. Available slots displayed.</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="package_id" class="form-label">Training Package Selection</label>
                            <select class="form-select" id="package_id" name="package_id" required>
                                <option value="">Select Your Package</option>
                                <?php foreach($memberPackages as $package): ?>
                                <option value="<?= $package['package_id'] ?>">
                                    <?= htmlspecialchars($package['name']) ?> 
                                    (₹<?= number_format($package['price'], 2) ?> - 
                                    <?= $package['duration_months'] ?> months)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="start_date" class="form-label">Training Commencement Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes" class="form-label">Training Preferences (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Specific fitness goals or focus areas?"></textarea>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn">Confirm Trainer Booking</button>
                        <a href="dashboard.php" class="btn btn-outline">Cancel Request</a>
                    </div>
                </form>
            </div>

            <h2>Available Training Professionals</h2>
            <div class="trainer-preview">
                <?php foreach($trainers as $trainer): 
                    $availableSlots = 5 - $trainer['current_members'];
                    $isAvailable = $availableSlots > 0;
                    $capacityClass = '';
                    if (!$isAvailable) {
                        $capacityClass = 'capacity-full';
                    } elseif ($availableSlots <= 2) {
                        $capacityClass = 'capacity-warning';
                    } else {
                        $capacityClass = 'capacity-available';
                    }
                ?>
                <div class="trainer-card">
                    <div class="capacity-badge <?= $capacityClass ?>">
                        <?= $isAvailable ? "{$availableSlots}/5 slots" : "FULL" ?>
                    </div>
                    <div class="trainer-info">
                        <h3><?= htmlspecialchars($trainer['full_name']) ?></h3>
                        <span class="trainer-specialization"><?= htmlspecialchars($trainer['specialization']) ?></span>
                        <p><?= htmlspecialchars($trainer['bio']) ?></p>
                        <button type="button" class="select-trainer-btn" data-trainer-id="<?= $trainer['trainer_id'] ?>" <?= !$isAvailable ? 'disabled' : '' ?>>
                            <?= $isAvailable ? 'Select This Trainer' : 'Fully Booked' ?>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Active Training Sessions Display -->
        <?php if(!empty($successfulBookings)): ?>
        <div class="success-bookings">
            <h2>Your Active Training Sessions</h2>
            <?php foreach($successfulBookings as $booking): ?>
            <div class="booking-card">
                <div class="booking-header">
                    <div>
                        <div class="booking-trainer"><?= htmlspecialchars($booking['trainer_name']) ?></div>
                        <div class="booking-package"><?= htmlspecialchars($booking['package_name']) ?></div>
                    </div>
                    <span class="status-badge">Active</span>
                </div>
                <div class="booking-details">
                    <div class="booking-detail">
                        <span class="detail-label">Specialization</span>
                        <span class="detail-value"><?= htmlspecialchars($booking['specialization']) ?></span>
                    </div>
                    <div class="booking-detail">
                        <span class="detail-label">Start Date</span>
                        <span class="detail-value"><?= date('M j, Y', strtotime($booking['start_date'])) ?></span>
                    </div>
                    <div class="booking-detail">
                        <span class="detail-label">End Date</span>
                        <span class="detail-value"><?= date('M j, Y', strtotime($booking['end_date'])) ?></span>
                    </div>
                    <div class="booking-detail">
                        <span class="detail-label">Package Duration</span>
                        <span class="detail-value"><?= $booking['duration_months'] ?> months</span>
                    </div>
                </div>
                <?php if(!empty($booking['notes'])): ?>
                <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <span class="detail-label">Training Notes</span>
                    <p style="color: var(--text-light); margin-top: 0.5rem;"><?= htmlspecialchars($booking['notes']) ?></p>
                </div>
                <?php endif; ?>
                
                <div class="booking-actions">
                    <a href="message.php?trainer_id=<?= $booking['trainer_id'] ?>" class="btn btn-outline">
                        Contact Trainer
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Trainer selection functionality
        document.querySelectorAll('.select-trainer-btn').forEach(button => {
            button.addEventListener('click', function() {
                if (!this.disabled) {
                    const trainerId = this.getAttribute('data-trainer-id');
                    document.getElementById('trainer_id').value = trainerId;
                    
                    // Navigate to booking form
                    document.getElementById('bookingForm').scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Form validation implementation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const startDate = new Date(document.getElementById('start_date').value);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if (startDate < today) {
                e.preventDefault();
                alert('Please select a future date for your training commencement.');
            }

            const trainerId = document.getElementById('trainer_id').value;
            const selectedOption = document.querySelector(`#trainer_id option[value="${trainerId}"]`);
            if (selectedOption && selectedOption.disabled) {
                e.preventDefault();
                alert('This trainer is currently at full capacity. Please select an available trainer.');
            }
        });

        // Visual feedback for trainer selection
        document.getElementById('trainer_id').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.disabled) {
                this.style.borderColor = 'var(--danger)';
                this.style.backgroundColor = 'rgba(220, 53, 69, 0.1)';
            } else {
                this.style.borderColor = '';
                this.style.backgroundColor = '';
            }
        });
    </script>
</body>
</html>