<?php
ob_start();
session_start();
require_once '../config/database.php';

// Verify member access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Get member information
$stmt = $conn->prepare("SELECT m.*, u.email, u.created_at as user_join_date FROM members m JOIN users u ON m.user_id = u.user_id WHERE m.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

// Initialize variables
$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $date_of_birth = $_POST['date_of_birth'];
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);

    try {
        // Validate inputs
        if (empty($full_name)) {
            throw new Exception("Full name is required");
        }

        // Date of birth validation
        if (!empty($date_of_birth)) {
            // Check if date is valid
            $dob_timestamp = strtotime($date_of_birth);
            if (!$dob_timestamp) {
                throw new Exception("Invalid date of birth format");
            }
            
            // Check if date is not in the future
            if ($dob_timestamp > time()) {
                throw new Exception("Date of birth cannot be in the future");
            }
            
            // Check if age is at least 13 years
            $min_age_date = strtotime('-13 years');
            if ($dob_timestamp > $min_age_date) {
                throw new Exception("You must be at least 13 years old");
            }
            
            // Check if age is reasonable (not more than 120 years)
            $max_age_date = strtotime('-120 years');
            if ($dob_timestamp < $max_age_date) {
                throw new Exception("Please enter a valid date of birth");
            }
            
            // Format date for database
            $date_of_birth = date('Y-m-d', $dob_timestamp);
        } else {
            $date_of_birth = null;
        }

        // Phone validation - must be exactly 10 digits
        if (!empty($phone)) {
            // Remove any non-digit characters
            $phone_clean = preg_replace('/[^0-9]/', '', $phone);
            
            // Check if it's exactly 10 digits
            if (strlen($phone_clean) !== 10) {
                throw new Exception("Phone number must be exactly 10 digits");
            }
            
            // Use the cleaned phone number
            $phone = $phone_clean;
        }

        // Check if image column exists in members table
        $stmt = $conn->query("SHOW COLUMNS FROM members LIKE 'image'");
        $imageColumnExists = $stmt->rowCount() > 0;

        // Handle profile photo upload only if column exists
        $imageUpdate = "";
        if ($imageColumnExists && isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../assets/images/members/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                if (!mkdir($uploadDir, 0755, true)) {
                    throw new Exception("Failed to create upload directory.");
                }
            }
            
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            $file = $_FILES['profile_photo'];
            
            // Get file extension
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            // Validate file extension
            if (!in_array($extension, $allowedExtensions)) {
                throw new Exception("Only JPG, PNG, and GIF images are allowed.");
            }
            
            // Validate file size (max 5MB)
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception("Image size must be less than 5MB.");
            }
            
            // Generate unique filename
            $filename = 'member_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            
            // Check if directory is writable
            if (!is_writable($uploadDir)) {
                throw new Exception("Upload directory is not writable. Please check permissions.");
            }
            
            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                // Delete old profile photo if it exists and is not default
                $currentImage = $member['image'] ?? 'default.jpg';
                if ($currentImage !== 'default.jpg' && file_exists($uploadDir . $currentImage)) {
                    unlink($uploadDir . $currentImage);
                }
                $imageUpdate = ", image = ?";
                $imageValue = $filename;
            } else {
                throw new Exception("Failed to upload image. Please try again.");
            }
        }

        // Update members table - use 'dob' column name instead of 'date_of_birth'
        $updateFields = ["full_name = ?", "phone = ?", "gender = ?", "address = ?", "dob = ?"];
        $params = [$full_name, $phone, $gender, $address, $date_of_birth];
        
        if ($imageColumnExists && !empty($imageUpdate)) {
            $updateFields[] = "image = ?";
            $params[] = $imageValue;
        }
        
        $params[] = $_SESSION['user_id'];
        
        $sql = "UPDATE members SET " . implode(", ", $updateFields) . " WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        $_SESSION['success'] = "Profile updated successfully!";
        header("Location: profile.php");
        exit();

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Determine join date to display
$join_date_display = "Not available";
if (!empty($member['join_date']) && $member['join_date'] != '0000-00-00') {
    $join_date_display = date('F j, Y', strtotime($member['join_date']));
} elseif (!empty($member['user_join_date']) && $member['user_join_date'] != '0000-00-00') {
    $join_date_display = date('F j, Y', strtotime($member['user_join_date']));
}

// Format date of birth for display - use 'dob' column instead of 'date_of_birth'
$date_of_birth_display = "";
if (!empty($member['dob']) && $member['dob'] != '0000-00-00') {
    $date_of_birth_display = date('Y-m-d', strtotime($member['dob']));
}

// Check if image column exists to determine if we should show profile image
$stmt = $conn->query("SHOW COLUMNS FROM members LIKE 'image'");
$imageColumnExists = $stmt->rowCount() > 0;
$profileImage = $imageColumnExists ? ($member['image'] ?? 'default.jpg') : 'default.jpg';

$pageTitle = "My Profile";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
        :root {
            --primary: #FF5A1F;
            --primary-dark: #E04A14;
            --dark: #121212;
            --darker: #0A0A0A;
            --light: #F8F9FA;
            --text-dark: #E0E0E0;
            --text-light: #FFFFFF;
            --danger: #dc3545;
            --success: #28a745;
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

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .dashboard-button {
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 900px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
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

        .card-header {
            background-color: var(--primary);
            color: white;
            padding: 1.2rem 1.5rem;
            border-bottom: none;
            border-radius: 15px 15px 0 0;
            margin: -2rem -2rem 2rem -2rem;
        }

        .card-header.bg-danger {
            background-color: var(--danger) !important;
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

        .btn-outline-danger {
            background-color: transparent;
            border: 2px solid var(--danger);
            color: var(--danger);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger);
            color: white;
        }

        .form-control, .form-select {
            width: 100%;
            padding: 12px 15px;
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

        /* Fix for select dropdown options */
        .form-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23FF5A1F' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        .form-select option {
            background-color: var(--dark);
            color: var(--text-light);
            padding: 10px;
        }

        .form-select option:hover {
            background-color: var(--primary);
            color: white;
        }

        /* For Firefox */
        @-moz-document url-prefix() {
            .form-select {
                color: var(--text-light);
            }
            .form-select option {
                background-color: var(--dark);
                color: var(--text-light);
            }
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

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            border-color: rgba(220, 53, 69, 0.2);
            color: #f8d7da;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            border-color: rgba(40, 167, 69, 0.2);
            color: #d4edda;
        }

        .profile-img-container {
            position: relative;
            display: inline-block;
            margin-bottom: 1.5rem;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--primary);
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.3);
            transition: all 0.3s ease;
        }

        .profile-img-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .profile-img-container:hover .profile-img-overlay {
            opacity: 1;
        }

        .profile-img-container:hover .profile-img {
            transform: scale(1.05);
        }

        .profile-upload-icon {
            color: white;
            font-size: 24px;
        }

        #profile-photo-input {
            display: none;
        }

        .file-input-label {
            display: block;
            padding: 10px 15px;
            background-color: var(--primary);
            color: white;
            border-radius: 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .file-input-label:hover {
            background-color: var(--primary-dark);
        }

        .file-info {
            margin-top: 0.5rem;
            font-size: 12px;
            color: var(--text-dark);
        }

        .error-message {
            color: var(--danger);
            font-size: 14px;
            margin-top: 5px;
            display: none;
        }

        .text-muted {
            color: var(--text-dark) !important;
        }

        .form-label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 600;
            color: var(--text-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .dashboard-button {
                position: relative;
                top: 0;
                right: 0;
                margin-bottom: 1rem;
            }
            
            .profile-container {
                padding: 15px;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .profile-img {
                width: 120px;
                height: 120px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 15px;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Dashboard Button -->
    <div class="dashboard-button">
        <a href="dashboard.php" class="btn btn-outline">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </div>

    <div class="profile-container">
        <div class="profile-grid">
            <!-- Left Column - Profile Image -->
            <div>
                <div class="card">
                    <div class="card-body" style="text-align: center;">
                        <div class="profile-img-container">
                            <img src="../assets/images/members/<?= htmlspecialchars($profileImage) ?>" 
                                 alt="Profile" class="profile-img" id="profile-image-preview">
                            <?php if ($imageColumnExists): ?>
                            <div class="profile-img-overlay" onclick="document.getElementById('profile-photo-input').click()">
                                <span class="profile-upload-icon">📷</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <h3><?= htmlspecialchars($member['full_name']) ?></h3>
                        <p class="text-muted">Member since <?= $join_date_display ?></p>
                        
                        <?php if ($imageColumnExists): ?>
                        <label for="profile-photo-input" class="file-input-label">
                            <i class="bi bi-camera"></i> Change Photo
                        </label>
                        
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Column - Profile Form -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle-fill"></i> <?= htmlspecialchars($error) ?>
                            </div>
                        <?php elseif(isset($_SESSION['success'])): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_SESSION['success']) ?>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" id="profile-form">
                            <?php if ($imageColumnExists): ?>
                            <input type="file" id="profile-photo-input" name="profile_photo" accept=".jpg,.jpeg,.png,.gif">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Email</label>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($member['email']) ?>" readonly>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Join Date</label>
                                    <input type="text" class="form-control" value="<?= $join_date_display ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($member['full_name']) ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="date_of_birth" class="form-label">Date of Birth</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?= htmlspecialchars($date_of_birth_display) ?>"
                                       max="<?= date('Y-m-d') ?>">
                                <div class="error-message" id="dob-error">Please enter a valid date of birth (must be at least 13 years old)</div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($member['phone']) ?>" 
                                           pattern="[0-9]{10}" 
                                           title="Please enter exactly 10 digits"
                                           maxlength="10">
                                    <div class="error-message" id="phone-error">Phone number must be exactly 10 digits</div>
                                </div>
                                <div class="form-group">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select</option>
                                        <option value="male" <?= $member['gender'] == 'male' ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= $member['gender'] == 'female' ? 'selected' : '' ?>>Female</option>
                                        <option value="other" <?= $member['gender'] == 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($member['address']) ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn" style="width: 100%;">
                                <i class="bi bi-check-circle"></i> Update Profile
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-danger">
                        <h5 class="mb-0">Account Settings</h5>
                    </div>
                    <div class="card-body">
                        <a href="change_password.php" class="btn btn-outline-danger" style="width: 100%;">
                            <i class="bi bi-key"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($imageColumnExists): ?>
    <script>
        // Preview profile image before upload
        document.getElementById('profile-photo-input').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-image-preview').src = e.target.result;
                }
                reader.readAsDataURL(file);
                
                // Show file name
                const fileInfo = document.querySelector('.file-info');
                fileInfo.textContent = 'Selected: ' + file.name + ' • Max 5MB • JPG, PNG, GIF';
            }
        });
    </script>
    <?php endif; ?>
    
    <script>
        // Phone number validation - prevent typing more than 10 digits
        document.getElementById('phone').addEventListener('input', function(e) {
            const phoneInput = e.target;
            const phoneError = document.getElementById('phone-error');
            
            // Remove any non-digit characters
            let phoneValue = phoneInput.value.replace(/\D/g, '');
            
            // Limit to 10 digits
            if (phoneValue.length > 10) {
                phoneValue = phoneValue.substring(0, 10);
            }
            
            // Update the input value
            phoneInput.value = phoneValue;
            
            // Show/hide error message
            if (phoneValue && phoneValue.length !== 10) {
                phoneError.style.display = 'block';
                phoneInput.setCustomValidity('Phone number must be exactly 10 digits');
            } else {
                phoneError.style.display = 'none';
                phoneInput.setCustomValidity('');
            }
        });
        
        // Prevent non-digit input
        document.getElementById('phone').addEventListener('keypress', function(e) {
            // Allow only digits (0-9) and control keys (backspace, delete, tab, etc.)
            if (e.key < '0' || e.key > '9') {
                if (!['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                    e.preventDefault();
                }
            }
        });

        // Date of birth validation
        document.getElementById('date_of_birth').addEventListener('change', function(e) {
            const dobInput = e.target;
            const dobError = document.getElementById('dob-error');
            const selectedDate = new Date(dobInput.value);
            const today = new Date();
            
            // Check if date is valid
            if (isNaN(selectedDate.getTime())) {
                dobError.style.display = 'block';
                dobError.textContent = 'Please enter a valid date';
                dobInput.setCustomValidity('Invalid date');
                return;
            }
            
            // Check if date is in the future
            if (selectedDate > today) {
                dobError.style.display = 'block';
                dobError.textContent = 'Date of birth cannot be in the future';
                dobInput.setCustomValidity('Date cannot be in the future');
                return;
            }
            
            // Check if age is at least 13 years
            const minAgeDate = new Date();
            minAgeDate.setFullYear(today.getFullYear() - 13);
            
            if (selectedDate > minAgeDate) {
                dobError.style.display = 'block';
                dobError.textContent = 'You must be at least 13 years old';
                dobInput.setCustomValidity('Must be at least 13 years old');
                return;
            }
            
            // Check if age is reasonable (not more than 120 years)
            const maxAgeDate = new Date();
            maxAgeDate.setFullYear(today.getFullYear() - 120);
            
            if (selectedDate < maxAgeDate) {
                dobError.style.display = 'block';
                dobError.textContent = 'Please enter a valid date of birth';
                dobInput.setCustomValidity('Invalid date of birth');
                return;
            }
            
            // All validations passed
            dobError.style.display = 'none';
            dobInput.setCustomValidity('');
        });
        
        // Form validation
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const phoneInput = document.getElementById('phone');
            const phoneValue = phoneInput.value.replace(/\D/g, '');
            
            if (phoneValue && phoneValue.length !== 10) {
                e.preventDefault();
                document.getElementById('phone-error').style.display = 'block';
                phoneInput.focus();
            }
            
            const dobInput = document.getElementById('date_of_birth');
            if (dobInput.value) {
                const selectedDate = new Date(dobInput.value);
                const today = new Date();
                const minAgeDate = new Date();
                minAgeDate.setFullYear(today.getFullYear() - 13);
                
                if (selectedDate > minAgeDate) {
                    e.preventDefault();
                    document.getElementById('dob-error').style.display = 'block';
                    document.getElementById('dob-error').textContent = 'You must be at least 13 years old';
                    dobInput.focus();
                }
            }
        });
    </script>
</body>
</html>
<?php ob_end_flush(); ?>