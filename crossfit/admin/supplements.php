<?php
session_start();
require_once '../config/database.php';

// Debug: Check session status
error_log("Session status: " . session_status());
error_log("User ID in session: " . ($_SESSION['user_id'] ?? 'Not set'));
error_log("User role in session: " . ($_SESSION['role'] ?? 'Not set'));

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log("Redirecting to index.php - User not logged in or not an admin");
    header("Location: ../index.php");
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Add new supplement
    if (isset($_POST['add_supplement'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $stock = $_POST['stock'];
        
        // Handle image upload
        $image = 'default.jpg'; // default image
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../assets/images/supplements/";
            $imageFileType = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
            $image = uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $image;
            
            // Check if image file is an actual image
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check !== false) {
                move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            } else {
                $_SESSION['error'] = "File is not an image.";
                header("Location: supplements.php");
                exit();
            }
        }
        
        try {
            $stmt = $conn->prepare("INSERT INTO supplements (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $description, $price, $stock, $image]);
            
            $_SESSION['success'] = "Supplement added successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error adding supplement: " . $e->getMessage();
        }
    }
    // Delete supplement
    elseif (isset($_POST['delete_supplement'])) {
        $supplement_id = $_POST['supplement_id'];
        
        try {
            // Get image name to delete file
            $stmt = $conn->prepare("SELECT image FROM supplements WHERE supplement_id = ?");
            $stmt->execute([$supplement_id]);
            $supplement = $stmt->fetch();
            
            $stmt = $conn->prepare("DELETE FROM supplements WHERE supplement_id = ?");
            $stmt->execute([$supplement_id]);
            
            // Delete image file if it's not the default
            if ($supplement['image'] != 'default.jpg') {
                $file_path = "../assets/images/supplements/" . $supplement['image'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            $_SESSION['success'] = "Supplement deleted successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error deleting supplement: " . $e->getMessage();
        }
    }
    // Update supplement stock
    elseif (isset($_POST['update_stock'])) {
        $supplement_id = $_POST['supplement_id'];
        $stock = $_POST['stock'];
        
        try {
            $stmt = $conn->prepare("UPDATE supplements SET stock = ? WHERE supplement_id = ?");
            $stmt->execute([$stock, $supplement_id]);
            
            $_SESSION['success'] = "Stock updated successfully!";
        } catch(PDOException $e) {
            $_SESSION['error'] = "Error updating stock: " . $e->getMessage();
        }
    }
    
    // Redirect to the same page
    header("Location: supplements.php");
    exit();
}

// Get all supplements
$supplements = $conn->query("SELECT * FROM supplements ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Supplements - CrossFit Revolution</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--darker);
            color: var(--text-light);
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
            margin-top: 0;
        }

        .container {
            padding: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--dark);
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .card-img-container {
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .card-img-top {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .card:hover .card-img-top {
            transform: scale(1.05);
        }

        .card-body {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .card-title {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .card-text {
            margin-bottom: 15px;
            flex-grow: 1;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
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

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #bd2130;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary);
        }

        .form-control {
            background-color: var(--dark);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: var(--dark);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 90, 31, 0.25);
            outline: none;
        }

        .form-label {
            color: var(--text-light);
            margin-bottom: 8px;
            display: block;
            font-weight: 600;
        }

        .admin-actions {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-top: 15px;
        }

        .stock-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .stock-input {
            width: 80px;
            margin: 0;
            text-align: center;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .add-form {
            background-color: var(--dark);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid var(--primary);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-full-width {
            grid-column: 1 / -1;
        }

        .file-input-container {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
            margin-bottom: 15px;
        }

        .file-input-button {
            background-color: var(--primary);
            color: white;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-align: center;
            width: 100%;
            transition: all 0.3s ease;
        }

        .file-input-button:hover {
            background-color: var(--primary-dark);
        }

        .file-input {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-name {
            margin-top: 8px;
            font-size: 0.9rem;
            color: var(--text-dark);
            text-align: center;
        }

        .price-tag {
            background-color: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin: 5px 0;
        }

        .stock-status {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin: 5px 0;
        }

        .stock-high {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }

        .stock-medium {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }

        .stock-low {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background-color: var(--dark);
            border-radius: 12px;
            padding: 25px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transform: translateY(-50px);
            transition: transform 0.3s ease;
        }

        .modal-overlay.active .modal {
            transform: translateY(0);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .modal-title {
            color: var(--primary);
            font-size: 1.5rem;
        }

        .modal-close {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.5rem;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: var(--primary);
        }

        .modal-body {
            margin-bottom: 20px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        .empty-state p {
            color: var(--text-dark);
            margin-bottom: 25px;
        }

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .admin-actions {
                flex-direction: column;
            }
            
            .stock-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .stock-input {
                width: 100%;
            }
            
            .header-actions {
                width: 100%;
                justify-content: space-between;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .add-form {
                padding: 20px;
            }
        }

        @media (max-width: 576px) {
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .header-actions {
                flex-direction: column;
            }
            
            .modal {
                width: 95%;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>Manage Supplements</h2>
            <div class="header-actions">
                <a href="dashboard.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Dashboard</a>
            </div>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_SESSION['success']) ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- Add New Supplement Form -->
        <div class="add-form">
            <h3 style="color: var(--primary); margin-bottom: 20px;"><i class="fas fa-plus-circle"></i> Add New Supplement</h3>
            <form method="POST" enctype="multipart/form-data" id="addSupplementForm">
                <div class="form-grid">
                    <div>
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    
                    <div>
                        <label for="price" class="form-label">Price (₹)</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div>
                        <label for="stock" class="form-label">Initial Stock</label>
                        <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                    </div>
                    
                    <div>
                        <label class="form-label">Image</label>
                        <div class="file-input-container">
                            <div class="file-input-button">
                                <i class="fas fa-upload"></i> Choose Image
                            </div>
                            <input type="file" class="file-input" id="image" name="image" accept="image/*">
                        </div>
                        <div class="file-name" id="file-name">No file chosen</div>
                    </div>
                    
                    <div class="form-full-width">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                </div>
                
                <button type="submit" name="add_supplement" class="btn">
                    <i class="fas fa-save"></i> Add Supplement
                </button>
            </form>
        </div>

        <h3>Current Supplements</h3>
        
        <?php if(empty($supplements)): ?>
            <div class="empty-state">
                <i class="fas fa-box-open"></i>
                <h3>No Supplements Found</h3>
                <p>Get started by adding your first supplement using the form above.</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach($supplements as $supplement): 
                    // Determine stock status
                    $stockClass = 'stock-high';
                    $stockIcon = 'fas fa-check-circle';
                    if ($supplement['stock'] < 10) {
                        $stockClass = 'stock-low';
                        $stockIcon = 'fas fa-exclamation-circle';
                    } elseif ($supplement['stock'] < 25) {
                        $stockClass = 'stock-medium';
                        $stockIcon = 'fas fa-info-circle';
                    }
                ?>
                <div class="card">
                    <div class="card-img-container">
                        <img src="../assets/images/supplements/<?= htmlspecialchars($supplement['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($supplement['name']) ?>">
                    </div>
                    <div class="card-body">
                        <h3 class="card-title"><?= htmlspecialchars($supplement['name']) ?></h3>
                        <p class="card-text"><?= htmlspecialchars($supplement['description']) ?></p>
                        
                        <div class="price-tag">₹<?= number_format($supplement['price'], 2) ?></div>
                        
                        <div class="stock-status <?= $stockClass ?>">
                            <i class="<?= $stockIcon ?>"></i>
                            <span>Stock: <?= $supplement['stock'] ?></span>
                        </div>
                        
                        <div class="admin-actions">
                            <!-- Update Stock Form -->
                            <form method="POST" class="stock-form">
                                <input type="hidden" name="supplement_id" value="<?= $supplement['supplement_id'] ?>">
                                <div style="display: flex; align-items: center; gap: 10px; width: 100%;">
                                    <input type="number" class="form-control stock-input" name="stock" value="<?= $supplement['stock'] ?>" min="0" required>
                                    <button type="submit" name="update_stock" class="btn btn-outline btn-sm">
                                        <i class="fas fa-sync-alt"></i> Update
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Delete Supplement Button -->
                            <button type="button" class="btn btn-danger btn-sm delete-btn" data-id="<?= $supplement['supplement_id'] ?>" data-name="<?= htmlspecialchars($supplement['name']) ?>">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Confirm Deletion</h3>
                <button class="modal-close" id="closeModal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteItemName"></strong>? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline" id="cancelDelete">Cancel</button>
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="supplement_id" id="deleteId">
                    <button type="submit" name="delete_supplement" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show selected file name
        document.getElementById('image').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file chosen';
            document.getElementById('file-name').textContent = fileName;
        });

        // Delete confirmation modal
        const deleteModal = document.getElementById('deleteModal');
        const deleteButtons = document.querySelectorAll('.delete-btn');
        const deleteItemName = document.getElementById('deleteItemName');
        const deleteId = document.getElementById('deleteId');
        const deleteForm = document.getElementById('deleteForm');
        const closeModal = document.getElementById('closeModal');
        const cancelDelete = document.getElementById('cancelDelete');

        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                const name = this.getAttribute('data-name');
                
                deleteId.value = id;
                deleteItemName.textContent = name;
                deleteModal.classList.add('active');
            });
        });

        function closeDeleteModal() {
            deleteModal.classList.remove('active');
        }

        closeModal.addEventListener('click', closeDeleteModal);
        cancelDelete.addEventListener('click', closeDeleteModal);

        // Close modal when clicking outside
        deleteModal.addEventListener('click', function(e) {
            if (e.target === deleteModal) {
                closeDeleteModal();
            }
        });

        // Form validation
        document.getElementById('addSupplementForm').addEventListener('submit', function(e) {
            const price = document.getElementById('price').value;
            const stock = document.getElementById('stock').value;
            
            if (price <= 0) {
                e.preventDefault();
                alert('Price must be greater than 0');
                return false;
            }
            
            if (stock < 0) {
                e.preventDefault();
                alert('Stock cannot be negative');
                return false;
            }
        });
    </script>
</body>
</html>