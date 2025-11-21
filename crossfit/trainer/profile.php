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

// Get user info for email
$stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Initialize counts with default values
$client_count = 0;
$active_bookings_count = 0;

// Get count of clients assigned to this trainer
try {
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT m.member_id) AS client_count 
        FROM members m 
        JOIN bookings b ON m.member_id = b.member_id 
        WHERE b.trainer_id = ?
    ");
    $stmt->execute([$trainer['trainer_id']]);
    $client_count = $stmt->fetch()['client_count'];
} catch(PDOException $e) {
    error_log("Error getting client count: " . $e->getMessage());
    $client_count = 0;
}

// Get count of active bookings for this trainer
try {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS active_bookings_count 
        FROM bookings 
        WHERE trainer_id = ? AND status = 'active'
    ");
    $stmt->execute([$trainer['trainer_id']]);
    $active_bookings_count = $stmt->fetch()['active_bookings_count'];
} catch(PDOException $e) {
    error_log("Error getting active bookings count: " . $e->getMessage());
    $active_bookings_count = 0;
}

// Handle profile update
$updateSuccess = false;
$updateError = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $specialization = $_POST['specialization'];
    $bio = $_POST['bio'];
    $certification = $_POST['certification'];
    $experience_years = $_POST['experience_years'];
    
    try {
        // Update trainers table
        $stmt = $conn->prepare("UPDATE trainers SET full_name = ?, specialization = ?, bio = ?, certification = ?, experience_years = ? WHERE user_id = ?");
        $stmt->execute([$full_name, $specialization, $bio, $certification, $experience_years, $_SESSION['user_id']]);
            
        
        // Handle profile photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/profile_photos/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $filename = 'trainer_' . $trainer['trainer_id'] . '_' . time() . '.' . $fileExtension;
            $uploadFile = $uploadDir . $filename;
            
            // Check if image file is actual image
            $check = getimagesize($_FILES['profile_photo']['tmp_name']);
            if ($check !== false) {
                // Validate file size (max 2MB)
                if ($_FILES['profile_photo']['size'] > 2000000) {
                    $updateError = "Sorry, your file is too large. Maximum size is 2MB.";
                } else {
                    // Remove old profile photo if exists
                    if (!empty($trainer['profile_photo']) && file_exists($uploadDir . $trainer['profile_photo'])) {
                        unlink($uploadDir . $trainer['profile_photo']);
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $uploadFile)) {
                        // Update database with photo path
                        $stmt = $conn->prepare("UPDATE trainers SET profile_photo = ? WHERE user_id = ?");
                        $stmt->execute([$filename, $_SESSION['user_id']]);
                    } else {
                        $updateError = "Sorry, there was an error uploading your file.";
                    }
                }
            } else {
                $updateError = "File is not an image.";
            }
        }
        
        // Refresh trainer data
        $stmt = $conn->prepare("SELECT * FROM trainers WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $trainer = $stmt->fetch();
        
        // Refresh user data
        $stmt = $conn->prepare("SELECT email FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        $updateSuccess = "Profile updated successfully!";
    } catch(PDOException $e) {
        $updateError = "Failed to update profile: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trainer Profile - CrossFit Revolution</title>
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

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .profile-header {
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

        .btn-dashboard {
            background-color: transparent;
            border: 2px solid var(--info);
            color: var(--info);
        }

        .btn-dashboard:hover {
            background-color: var(--info);
            color: white;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        }

        @media (max-width: 900px) {
            .profile-content {
                grid-template-columns: 1fr;
            }
        }

        .profile-card {
            background-color: var(--dark);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .profile-info {
            text-align: center;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1.5rem;
            border: 4px solid var(--primary);
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.3);
        }

        .profile-name {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .profile-email {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .profile-specialization {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .profile-details {
            text-align: left;
            margin-top: 1.5rem;
        }

        .profile-stats {
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

        .form-group {
            margin-bottom: 1.5rem;
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
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.3);
            background-color: rgba(255,255,255,0.08);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .file-upload-input {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px 15px;
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            color: var(--text-dark);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload-label:hover {
            border-color: var(--primary);
            color: var(--primary);
            background-color: rgba(255, 90, 31, 0.1);
        }

        .alert {
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #d4edda;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #f8d7da;
        }

        .info-item {
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .info-label {
            font-size: 0.9rem;
            color: var(--text-dark);
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        .info-value {
            font-size: 1.1rem;
            color: var(--text-light);
        }

        .bio-section {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .bio-section h3 {
            margin-bottom: 1rem;
            color: var(--primary);
        }

        .bio-content {
            color: var(--text-dark);
            line-height: 1.6;
            background-color: rgba(255,255,255,0.05);
            padding: 1rem;
            border-radius: 5px;
            border-left: 3px solid var(--primary);
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .profile-stats {
                grid-template-columns: 1fr;
            }
            
            .profile-name {
                flex-direction: column;
                text-align: center;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        /* Additional alignment improvements */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div>
                <h1><i class="bi bi-person-badge"></i> Trainer Profile</h1>
                <p style="color: var(--text-dark);">Manage your professional information</p>
            </div>
            <div class="header-actions">
            <a href="dashboard.php" class="btn">
            <i class="bi bi-speedometer2"></i> Dashboard
    </a>
</div>
        </div>

        <?php if($updateSuccess): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i> <?= $updateSuccess ?>
            </div>
        <?php endif; ?>
        
        <?php if($updateError): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle-fill"></i> <?= $updateError ?>
            </div>
        <?php endif; ?>

        <div class="profile-content">
            <!-- Profile Info Card -->
            <div class="profile-card profile-info">
                <?php
                $profilePhoto = !empty($trainer['profile_photo']) ? 
                    '../uploads/profile_photos/' . $trainer['profile_photo'] : 
                    '../assets/default-avatar.jpg';
                ?>
                <img src="<?= $profilePhoto ?>" alt="Profile Avatar" class="profile-avatar">
                <h2 class="profile-name">
                    <?= htmlspecialchars($trainer['full_name']) ?>
                </h2>
                
                <?php if(!empty($trainer['specialization'])): ?>
                    <div class="profile-specialization">
                        <i class="bi bi-award"></i> <?= htmlspecialchars($trainer['specialization']) ?>
                    </div>
                <?php endif; ?>
                
                <div class="profile-email">
                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $client_count ?></div>
                        <div class="stat-label">Total Clients</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= $active_bookings_count ?></div>
                        <div class="stat-label">Active Bookings</div>
                    </div>
                </div>
                
                <div class="profile-details">
                    <?php if(!empty($trainer['certification'])): ?>
                        <div class="info-item">
                            <div class="info-label">Certification</div>
                            <div class="info-value">
                                <i class="bi bi-patch-check"></i> <?= htmlspecialchars($trainer['certification']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if(!empty($trainer['experience_years'])): ?>
                        <div class="info-item">
                            <div class="info-label">Experience</div>
                            <div class="info-value">
                                <i class="bi bi-clock-history"></i> <?= htmlspecialchars($trainer['experience_years']) ?> years
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if(!empty($trainer['bio'])): ?>
                    <div class="bio-section">
                        <h3><i class="bi bi-person-lines-fill"></i> About Me</h3>
                        <div class="bio-content">
                            <?= htmlspecialchars($trainer['bio']) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Profile Edit Form -->
            <div class="profile-card">
                <h2 style="margin-bottom: 1.5rem; color: var(--primary);">
                    <i class="bi bi-pencil-square"></i> Edit Profile Information
                </h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="full_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?= htmlspecialchars($trainer['full_name']) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="specialization" class="form-label">Specialization</label>
                            <input type="text" class="form-control" id="specialization" name="specialization" 
                                   value="<?= htmlspecialchars($trainer['specialization']) ?>" 
                                   placeholder="E.g., Strength Training, Yoga, Nutrition">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="certification" class="form-label">Certification</label>
                            <input type="text" class="form-control" id="certification" name="certification" 
                                   value="<?= htmlspecialchars($trainer['certification']) ?>" 
                                   placeholder="E.g., NASM Certified Personal Trainer">
                        </div>
                        
                        <div class="form-group">
                            <label for="experience_years" class="form-label">Years of Experience</label>
                            <input type="number" class="form-control" id="experience_years" name="experience_years" 
                                   value="<?= htmlspecialchars($trainer['experience_years']) ?>" 
                                   min="0" max="50">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="bio" class="form-label">Bio/Description</label>
                        <textarea class="form-control" id="bio" name="bio" 
                                  placeholder="Tell clients about yourself, your training philosophy, etc."><?= htmlspecialchars($trainer['bio']) ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Profile Photo</label>
                        <div class="file-upload">
                            <input type="file" id="profile_photo" name="profile_photo" class="file-upload-input" accept="image/*">
                            <label for="profile_photo" class="file-upload-label">
                                <i class="bi bi-cloud-upload"></i> Choose profile photo
                            </label>
                        </div>
                        <small style="color: var(--text-dark); display: block; margin-top: 0.5rem;">
                            Max 2MB • JPG, PNG, GIF
                        </small>
                    </div>
                    
                    <button type="submit" name="update_profile" class="btn" style="margin-top: 1rem;">
                        <i class="bi bi-check-circle"></i> Update Profile
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Focus on first form field if there's an error
            <?php if($updateError): ?>
                document.querySelector('form').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });
            <?php endif; ?>
            
            // Show file name when selected
            const fileInputs = [
                document.getElementById('profile_photo')
            ];
            
            fileInputs.forEach(input => {
                if (input) {
                    input.addEventListener('change', function(e) {
                        const fileName = e.target.files[0]?.name || 'Choose a file';
                        const label = this.parentElement.querySelector('.file-upload-label');
                        if (label) {
                            label.innerHTML = `<i class="bi bi-file-earmark"></i> ${fileName}`;
                        }
                    });
                }
            });
        });
    </script>
</body>
</html>