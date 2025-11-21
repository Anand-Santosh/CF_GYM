<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/crossfit/includes/auth.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Function to validate email domain
function validateEmailDomain($email) {
    // Get the domain part after @
    $domain = substr(strrchr($email, "@"), 1);
    
    // List of valid domain patterns (you can extend this list)
    $validDomains = [
        'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com',
        'protonmail.com', 'aol.com', 'zoho.com', 'yandex.com', 'gmx.com',
        'mail.com', 'live.com', 'msn.com'
    ];
    
    // Check if domain exists in valid list
    if (in_array($domain, $validDomains)) {
        return true;
    }
    
    // For custom domains, check DNS MX records
    if (checkdnsrr($domain, "MX")) {
        return true;
    }
    
    return false;
}

// Handle delete trainer request
if (isset($_POST['trainer_id']) && isset($_POST['delete_trainer'])) {
    $trainer_id = $_POST['trainer_id'];
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // First get the user_id from the trainer
        $stmt = $conn->prepare("SELECT user_id FROM trainers WHERE trainer_id = ?");
        $stmt->execute([$trainer_id]);
        $trainer = $stmt->fetch();
        
        if ($trainer) {
            $user_id = $trainer['user_id'];
            
            // Delete from trainers table
            $stmt = $conn->prepare("DELETE FROM trainers WHERE trainer_id = ?");
            $stmt->execute([$trainer_id]);
            
            // Delete from users table
            $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $conn->commit();
            $_SESSION['success'] = "Trainer deleted successfully!";
        } else {
            $_SESSION['error'] = "Trainer not found!";
        }
        
        header("Location: trainers.php");
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error deleting trainer: " . $e->getMessage();
        header("Location: trainers.php");
        exit();
    }
}

// Handle create trainer request
if (isset($_POST['create_trainer'])) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $specialization = trim($_POST['specialization']);
    $bio = trim($_POST['bio']);
    $certification = trim($_POST['certification']);
    $experience_years = !empty($_POST['experience_years']) ? (int)$_POST['experience_years'] : null;
    
    // Enhanced Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Please enter a valid email address!";
        header("Location: trainers.php?create=1");
        exit();
    }
    
    // Additional domain validation
    if (!validateEmailDomain($email)) {
        $_SESSION['error'] = "Please enter a valid email address with proper domain (e.g., @gmail.com, @yahoo.com)!";
        header("Location: trainers.php?create=1");
        exit();
    }
    
    try {
        // Begin transaction
        $conn->beginTransaction();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Email already exists!";
            $conn->rollBack();
            header("Location: trainers.php?create=1");
            exit();
        }
        
        // Create user account first
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("
            INSERT INTO users (email, password, role, created_at) 
            VALUES (?, ?, 'trainer', NOW())
        ");
        $stmt->execute([$email, $hashed_password]);
        $user_id = $conn->lastInsertId();
        
        // Create trainer profile
        $stmt = $conn->prepare("
            INSERT INTO trainers 
            (user_id, full_name, specialization, bio, certification, experience_years, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $user_id, 
            $full_name, 
            $specialization, 
            $bio, 
            $certification, 
            $experience_years
        ]);
        
        $conn->commit();
        $_SESSION['success'] = "Trainer created successfully!";
        header("Location: trainers.php");
        exit();
        
    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error creating trainer: " . $e->getMessage();
        header("Location: trainers.php?create=1");
        exit();
    }
}

// Handle view trainer request
$trainerDetails = null;
if (isset($_GET['view_id'])) {
    $trainer_id = $_GET['view_id'];
    $stmt = $conn->prepare("
        SELECT t.*, u.email, u.created_at 
        FROM trainers t
        JOIN users u ON t.user_id = u.user_id
        WHERE t.trainer_id = :trainer_id
    ");
    $stmt->bindParam(':trainer_id', $trainer_id);
    $stmt->execute();
    $trainerDetails = $stmt->fetch();
}

// Get all trainers - SORTED BY ID IN ASCENDING ORDER (1,2,3,4)
$trainers = $conn->query("
    SELECT t.*, u.email 
    FROM trainers t
    JOIN users u ON t.user_id = u.user_id
    ORDER BY t.trainer_id ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Trainers - CrossFit Revolution</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #FF5A1F; /* Vibrant orange */
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
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
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.3);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
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

        .btn-primary {
            background-color: var(--primary);
        }

        .btn-secondary {
            background-color: #6c757d;
        }

        .btn-secondary:hover {
            background-color: #5a6268;
        }

        .btn-info {
            background-color: var(--info);
        }

        .btn-info:hover {
            background-color: #138496;
        }

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #218838;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .mb-3 {
            margin-bottom: 1rem;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
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
            display: <?= (isset($trainerDetails) || isset($_GET['create'])) ? 'block' : 'none' ?>;
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

        .trainer-details {
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

        /* Form Styles */
        form {
            display: inline;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.2);
            background-color: rgba(255,255,255,0.08);
        }

        .form-control.error {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.2);
        }

        .form-control.success {
            border-color: var(--success);
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.2);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .validation-error {
            color: var(--danger) !important;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
        }

        .validation-success {
            color: var(--success) !important;
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
        }

        .form-hint {
            color: var(--text-dark);
            font-size: 0.8rem;
            margin-top: 5px;
            display: block;
        }

        .valid-domains {
            background: rgba(255,255,255,0.05);
            padding: 10px;
            border-radius: 5px;
            margin-top: 5px;
            font-size: 0.75rem;
        }

        .valid-domains strong {
            color: var(--primary);
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
            
            .trainer-details {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header-buttons">
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>

    <h2>Manage Trainers</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i> <?= $_SESSION['success'] ?>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle-fill"></i> <?= $_SESSION['error'] ?>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <a href="?create=1" class="btn btn-success">
            <i class="bi bi-plus-circle"></i> Create New Trainer
        </a>
    </div>

    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>S.NO</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Specialization</th>
                    <th>Experience</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach($trainers as $trainer): ?>
                <tr>
                    <td><?= $counter ?></td>
                    <td><?= htmlspecialchars($trainer['full_name']) ?></td>
                    <td><?= htmlspecialchars($trainer['email']) ?></td>
                    <td><?= htmlspecialchars($trainer['specialization'] ?? 'N/A') ?></td>
                    <td><?= $trainer['experience_years'] ? $trainer['experience_years'] . ' years' : 'N/A' ?></td>
                
                    <td>
                        <a href="?view_id=<?= $trainer['trainer_id'] ?>" class="btn btn-info btn-sm">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <form action="trainers.php" method="POST" style="display:inline;">
                            <input type="hidden" name="trainer_id" value="<?= $trainer['trainer_id'] ?>">
                            <input type="hidden" name="delete_trainer" value="1">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this trainer? This action cannot be undone.')">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php $counter++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Trainer View Modal -->
    <div id="trainerModal" class="modal">
        <div class="modal-content">
            <a href="?" class="close-modal">&times;</a>
            <h2>Trainer Details</h2>
            <?php if ($trainerDetails): ?>
            <div class="trainer-details">
                <div class="detail-group">
                    <span class="detail-label">Trainer ID:</span>
                    <span><?= $trainerDetails['trainer_id'] ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Full Name:</span>
                    <span><?= htmlspecialchars($trainerDetails['full_name']) ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Email:</span>
                    <span><?= htmlspecialchars($trainerDetails['email']) ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Specialization:</span>
                    <span><?= htmlspecialchars($trainerDetails['specialization'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Bio:</span>
                    <span><?= htmlspecialchars($trainerDetails['bio'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Certification:</span>
                    <span><?= htmlspecialchars($trainerDetails['certification'] ?? 'N/A') ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Experience:</span>
                    <span><?= $trainerDetails['experience_years'] ? $trainerDetails['experience_years'] . ' years' : 'N/A' ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Account Created:</span>
                    <span>
                        <?php 
                        if (!empty($trainerDetails['created_at']) && $trainerDetails['created_at'] != '0000-00-00 00:00:00') {
                            echo date('M j, Y', strtotime($trainerDetails['created_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Last Updated:</span>
                    <span>
                        <?php 
                        if (!empty($trainerDetails['updated_at']) && $trainerDetails['updated_at'] != '0000-00-00 00:00:00') {
                            echo date('M j, Y', strtotime($trainerDetails['updated_at']));
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </span>
                </div>
            </div>
            <?php else: ?>
            <p>Trainer details not found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Create Trainer Modal -->
    <div id="createTrainerModal" class="modal" style="display: <?= isset($_GET['create']) ? 'block' : 'none' ?>;">
        <div class="modal-content">
            <a href="?" class="close-modal">&times;</a>
            <h2>Create New Trainer</h2>
            
            <form action="trainers.php" method="POST" id="createTrainerForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address *</label>
                        <input type="email" class="form-control" id="email" name="email" required 
                               title="Please enter a valid email address with proper domain">
                        <div id="emailValidation" class="validation-error" style="display: none;"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                        
                        <div id="passwordValidation" class="validation-error" style="display: none;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="specialization">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" 
                               placeholder="e.g., Weight Loss, Strength Training, Yoga">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="experience_years">Experience (Years)</label>
                        <input type="number" class="form-control" id="experience_years" name="experience_years" 
                               min="0" max="50" placeholder="0">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="certification">Certifications</label>
                        <input type="text" class="form-control" id="certification" name="certification" 
                               placeholder="e.g., ACE, NASM, CrossFit L1">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="bio">Bio</label>
                    <textarea class="form-control" id="bio" name="bio" 
                              placeholder="Brief description about the trainer's background and expertise..."></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 2rem;">
                    <button type="submit" name="create_trainer" value="1" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Trainer
                    </button>
                    <a href="?" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Cancel
                    </a>
                </div>
            </form>
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
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (event.target == modal) {
                        window.location.href = '?';
                    }
                });
            }
            
            // Enhanced form validation
            const createTrainerForm = document.getElementById('createTrainerForm');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            const emailValidation = document.getElementById('emailValidation');
            const passwordValidation = document.getElementById('passwordValidation');
            
            // List of valid domains for client-side validation
            const validDomains = [
                'gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com', 'icloud.com',
                'protonmail.com', 'aol.com', 'zoho.com', 'yandex.com', 'gmx.com',
                'mail.com', 'live.com', 'msn.com'
            ];
            
            if (createTrainerForm) {
                createTrainerForm.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Email validation
                    if (!validateEmail(emailInput.value)) {
                        e.preventDefault();
                        showValidationError(emailInput, emailValidation, 'Please enter a valid email address');
                        isValid = false;
                    } else if (!validateEmailDomain(emailInput.value)) {
                        e.preventDefault();
                        showValidationError(emailInput, emailValidation, 'Please use a valid email domain (e.g., @gmail.com, @yahoo.com)');
                        isValid = false;
                    } else {
                        showValidationSuccess(emailInput, emailValidation, 'Valid email address');
                    }
                    
                    // Password validation
                    if (passwordInput.value.length < 6) {
                        e.preventDefault();
                        showValidationError(passwordInput, passwordValidation, 'Password must be at least 6 characters long');
                        isValid = false;
                    } else {
                        showValidationSuccess(passwordInput, passwordValidation, 'Password is strong');
                    }
                    
                    return isValid;
                });
            }
            
            // Real-time email validation
            if (emailInput) {
                emailInput.addEventListener('blur', function() {
                    validateEmailField(this, emailValidation);
                });
                
                emailInput.addEventListener('input', function() {
                    clearValidation(this, emailValidation);
                });
            }
            
            // Real-time password validation
            if (passwordInput) {
                passwordInput.addEventListener('blur', function() {
                    validatePasswordField(this, passwordValidation);
                });
                
                passwordInput.addEventListener('input', function() {
                    clearValidation(this, passwordValidation);
                });
            }
            
            // Email validation function
            function validateEmail(email) {
                const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                return emailRegex.test(email);
            }
            
            // Email domain validation function
            function validateEmailDomain(email) {
                const domain = email.split('@')[1];
                if (!domain) return false;
                
                // Check against valid domains
                if (validDomains.includes(domain.toLowerCase())) {
                    return true;
                }
                
                // For custom domains, we'll accept them but warn they need to be real
                // In production, you might want to do DNS checking here
                return true; // Accept custom domains for now
            }
            
            // Validate email field
            function validateEmailField(input, validationElement) {
                if (!input.value) {
                    clearValidation(input, validationElement);
                    return;
                }
                
                if (!validateEmail(input.value)) {
                    showValidationError(input, validationElement, 'Please enter a valid email address');
                } else if (!validateEmailDomain(input.value)) {
                    showValidationError(input, validationElement, 'Please use a valid email domain');
                } else {
                    showValidationSuccess(input, validationElement, 'Valid email address');
                }
            }
            
            // Validate password field
            function validatePasswordField(input, validationElement) {
                if (!input.value) {
                    clearValidation(input, validationElement);
                    return;
                }
                
                if (input.value.length < 6) {
                    showValidationError(input, validationElement, 'Password must be at least 6 characters');
                } else {
                    showValidationSuccess(input, validationElement, 'Password is strong');
                }
            }
            
            // Show validation error
            function showValidationError(input, validationElement, message) {
                input.classList.remove('success');
                input.classList.add('error');
                validationElement.style.display = 'block';
                validationElement.innerHTML = `<i class="bi bi-exclamation-circle"></i> ${message}`;
                validationElement.className = 'validation-error';
            }
            
            // Show validation success
            function showValidationSuccess(input, validationElement, message) {
                input.classList.remove('error');
                input.classList.add('success');
                validationElement.style.display = 'block';
                validationElement.innerHTML = `<i class="bi bi-check-circle"></i> ${message}`;
                validationElement.className = 'validation-success';
            }
            
            // Clear validation
            function clearValidation(input, validationElement) {
                input.classList.remove('error', 'success');
                validationElement.style.display = 'none';
                validationElement.innerHTML = '';
            }
        });
    </script>
</body>
</html>