<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/crossfit/includes/auth.php');

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../supplements.php");
    exit();
}

// Handle package deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_package'])) {
    $package_id = (int)$_POST['package_id'];
    
    if ($package_id > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM packages WHERE package_id = ?");
            $stmt->execute([$package_id]);
            
            $success = "Package deleted successfully!";
            // Refresh to show updated list
            header("Location: packages.php");
            exit();
            
        } catch (PDOException $e) {
            $error = "Error deleting package: " . $e->getMessage();
        }
    }
}

// Handle form submission for adding new package
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_package'])) {
    $name = trim($_POST['name']);
    $duration_months = (int)$_POST['duration_months'];
    $price = (float)$_POST['price'];
    $description = trim($_POST['description']);

    // Validation
    if (empty($name) || $duration_months <= 0 || $price <= 0) {
        $error = "Please fill in all required fields with valid values.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO packages (name, duration_months, price, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $duration_months, $price, $description]);
            
            $success = "Package added successfully!";
            
            // Clear form fields
            $_POST = array();
            
        } catch (PDOException $e) {
            $error = "Error adding package: " . $e->getMessage();
        }
    }
}

// Handle view package request
$packageDetails = null;
if (isset($_GET['view_id'])) {
    $package_id = $_GET['view_id'];
    $stmt = $conn->prepare("SELECT * FROM packages WHERE package_id = :package_id");
    $stmt->bindParam(':package_id', $package_id);
    $stmt->execute();
    $packageDetails = $stmt->fetch();
}

// Get all packages
$packages = $conn->query("SELECT * FROM packages ORDER BY price")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Packages - CrossFit Revolution</title>
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

        .btn-primary {
            background-color: var(--primary);
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.4);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
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

        .btn-success {
            background-color: var(--success);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        .btn-success:hover {
            background-color: #218838;
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.6);
        }

        .btn-danger {
            background-color: var(--danger);
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

        .mb-3 {
            margin-bottom: 1rem;
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

        /* Form Styles */
        .form-container {
            background-color: var(--darker);
            border-radius: 10px;
            padding: 30px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-light);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
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

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-select {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 5px;
            color: var(--text-light);
            font-family: 'Montserrat', sans-serif;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(255, 90, 31, 0.2);
            background-color: rgba(255,255,255,0.08);
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.15);
            border: 1px solid rgba(40, 167, 69, 0.2);
            color: #d4edda;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.15);
            border: 1px solid rgba(220, 53, 69, 0.2);
            color: #f8d7da;
        }

        /* Modal Styles */
        .modal {
            display: <?= $packageDetails ? 'block' : 'none' ?>;
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

        .package-details {
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

        /* Page Header */
        .page-header {
            margin-top: 0;
            padding-top: 0;
            position: relative;
        }

        .page-header::after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--primary-dark));
            margin-top: 10px;
            border-radius: 2px;
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
            
            .package-details {
                grid-template-columns: 1fr;
            }
            
            .form-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="header-buttons">
        <a href="dashboard.php" class="btn">Back to Dashboard</a>
    </div>

    <h2 class="page-header">Manage Packages</h2>

    <!-- Add Package Form -->
    <div class="form-container">
        <h3><i class="bi bi-plus-circle"></i> Add New Package</h3>
        
        <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="addPackageForm">
            <div class="form-group">
                <label for="name" class="form-label">Package Name *</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="duration_months" class="form-label">Duration (Months) *</label>
                    <input type="number" class="form-control" id="duration_months" name="duration_months" 
                           min="1" max="36" value="<?= htmlspecialchars($_POST['duration_months'] ?? '1') ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="price" class="form-label">Price (₹) *</label>
                    <input type="number" class="form-control" id="price" name="price" 
                           min="0" step="0.01" value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" 
                          placeholder="Describe the package benefits..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" name="add_package" class="btn btn-success">
                <i class="bi bi-plus-circle"></i> Add Package
            </button>
        </form>
    </div>

    <!-- Packages Table -->
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>S.No</th>
                    <th>Name</th>
                    <th>Duration</th>
                    <th>Price</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach($packages as $package): ?>
                <tr>
                    <td><?= $counter ?></td>
                    <td><?= htmlspecialchars($package['name']) ?></td>
                    <td><?= $package['duration_months'] ?> month<?= $package['duration_months'] > 1 ? 's' : '' ?></td>
                    <td>₹<?= number_format($package['price'], 2) ?></td>
                    <td>
                        <a href="?view_id=<?= $package['package_id'] ?>" class="btn btn-info btn-sm">View</a>
                        <form action="" method="POST" style="display:inline;">
                            <input type="hidden" name="package_id" value="<?= $package['package_id'] ?>">
                            <input type="hidden" name="delete_package" value="1">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this package?')">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php $counter++; ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Package View Modal -->
    <div id="packageModal" class="modal">
        <div class="modal-content">
            <a href="?" class="close-modal">&times;</a>
            <h2>Package Details</h2>
            <?php if ($packageDetails): ?>
            <div class="package-details">
                <div class="detail-group">
                    <span class="detail-label">Package ID:</span>
                    <span><?= $packageDetails['package_id'] ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Name:</span>
                    <span><?= htmlspecialchars($packageDetails['name']) ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Duration:</span>
                    <span><?= $packageDetails['duration_months'] ?> month<?= $packageDetails['duration_months'] > 1 ? 's' : '' ?></span>
                </div>
                <div class="detail-group">
                    <span class="detail-label">Price:</span>
                    <span>Rs <?= number_format($packageDetails['price'], 2) ?></span>
                </div>
                <div class="detail-group" style="grid-column: 1 / -1;">
                    <span class="detail-label">Description:</span>
                    <span><?= htmlspecialchars($packageDetails['description'] ?? 'No description available') ?></span>
                </div>
                
            </div>
            <?php else: ?>
            <p>Package details not found.</p>
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
                const modal = document.getElementById('packageModal');
                if (event.target == modal) {
                    window.location.href = '?';
                }
            }

            // Form validation
            const form = document.getElementById('addPackageForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const price = document.getElementById('price').value;
                    const duration = document.getElementById('duration_months').value;
                    
                    if (price <= 0) {
                        e.preventDefault();
                        alert('Price must be greater than 0');
                        return false;
                    }
                    
                    if (duration <= 0) {
                        e.preventDefault();
                        alert('Duration must be at least 1 month');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>