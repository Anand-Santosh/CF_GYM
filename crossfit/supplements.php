<?php
ob_start();
$pageTitle = "Premium Supplements";
require_once 'config/database.php';
require_once 'includes/header.php';

// First try to get supplements with multiple images
try {
    $supplements = $conn->query("
        SELECT s.*, 
               GROUP_CONCAT(si.image_path SEPARATOR '|||') as images 
        FROM supplements s
        LEFT JOIN supplement_images si ON s.supplement_id = si.supplement_id
        WHERE s.stock > 0 
        GROUP BY s.supplement_id
        ORDER BY s.name
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // If the supplement_images table doesn't exist, fall back to single image
    $supplements = $conn->query("
        SELECT * FROM supplements 
        WHERE stock > 0 
        ORDER BY name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Add the single image to the images array for each supplement
    foreach ($supplements as &$supplement) {
        $supplement['images'] = isset($supplement['image']) ? $supplement['image'] : 'default.jpg';
    }
    unset($supplement); // Break the reference
}

// Process messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
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

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: var(--dark);
            color: var(--text-light);
            line-height: 1.6;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
        }

        .supplements-section {
            padding: 80px 0;
            background-color: var(--darker);
        }

        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }

        .section-header h2 {
            font-size: 2.8rem;
            text-transform: uppercase;
            position: relative;
            display: inline-block;
        }

        .section-header h2:after {
            content: '';
            position: absolute;
            left: 50%;
            bottom: -15px;
            width: 80px;
            height: 4px;
            background: var(--primary);
            transform: translateX(-50%);
        }

        .supplement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
        }

        .supplement-card {
            background-color: var(--dark);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .supplement-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .supplement-img-container {
            height: 250px;
            overflow: hidden;
            position: relative;
        }

        .image-slider {
            display: flex;
            width: 100%;
            height: 100%;
            transition: transform 0.5s ease;
        }

        .supplement-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .supplement-card:hover .supplement-img {
            transform: scale(1.05);
        }

        .slider-controls {
            position: absolute;
            bottom: 10px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .slider-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .slider-dot.active {
            background-color: var(--primary);
            transform: scale(1.2);
        }

        .supplement-body {
            padding: 25px;
        }

        .supplement-title {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .supplement-desc {
            margin-bottom: 20px;
            color: var(--text-light);
        }

        .supplement-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .supplement-price {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--primary);
        }

        .supplement-stock {
            color: var(--text-light);
        }

        .supplement-footer {
            padding: 20px;
            background-color: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
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

        .btn-disabled {
            background-color: #333;
            color: #666;
            cursor: not-allowed;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            margin-bottom: 30px;
            border-radius: 8px;
        }

        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid rgba(40, 167, 69, 0.3);
            color: #28a745;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid rgba(220, 53, 69, 0.3);
            color: #dc3545;
        }

        .alert-info {
            background-color: rgba(23, 162, 184, 0.2);
            border: 1px solid rgba(23, 162, 184, 0.3);
            color: #17a2b8;
        }

        /* Modal Styles */
        .modal-content {
            background-color: var(--dark);
            color: var(--text-light);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
        }

        .form-control:focus {
            background-color: var(--darker);
            color: var(--text-light);
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(255, 90, 31, 0.25);
        }

        .btn-close {
            filter: invert(1);
        }

        /* Admin Add Form */
        .add-supplement-form {
            background-color: var(--dark);
            padding: 30px;
            border-radius: 10px;
            margin-top: 50px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .form-label {
            color: var(--text-light);
        }

        .image-upload-group {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px dashed rgba(255,255,255,0.2);
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .supplement-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header h2 {
                font-size: 2.2rem;
            }
        }
    </style>
</head>
<body>
    <section class="supplements-section">
        <div class="container">
            <div class="section-header">
                <h2>Performance Supplements</h2>
                <p>Premium quality supplements to fuel your training and recovery</p>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if(empty($supplements)): ?>
                <div class="alert alert-info">Currently no supplements available. Check back soon!</div>
            <?php else: ?>
                <div class="supplement-grid">
                    <?php foreach($supplements as $supplement): 
                        // Handle both single image (string) and multiple images (array)
                        $images = is_string($supplement['images']) ? explode('|||', $supplement['images']) : [$supplement['image'] ?? 'default.jpg'];
                        $defaultImage = '/crossfit/assets/images/supplements/default.jpg';
                    ?>
                    <div class="supplement-card">
                        <div class="supplement-img-container">
                            <div class="image-slider" id="slider-<?= $supplement['supplement_id'] ?>">
                                <?php foreach($images as $image): ?>
                                    <?php if(!empty($image)): ?>
                                        <img src="/crossfit/assets/images/supplements/<?= htmlspecialchars($image) ?>" 
                                             class="supplement-img" 
                                             alt="<?= htmlspecialchars($supplement['name']) ?>"
                                             loading="lazy">
                                    <?php else: ?>
                                        <img src="<?= $defaultImage ?>" 
                                             class="supplement-img" 
                                             alt="Default supplement image"
                                             loading="lazy">
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <?php if(count($images) > 1): ?>
                                <div class="slider-controls">
                                    <?php for($i = 0; $i < count($images); $i++): ?>
                                        <div class="slider-dot <?= $i === 0 ? 'active' : '' ?>" 
                                             data-slide="<?= $i ?>"
                                             data-target="slider-<?= $supplement['supplement_id'] ?>"></div>
                                    <?php endfor; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="supplement-body">
                            <h3 class="supplement-title"><?= htmlspecialchars($supplement['name']) ?></h3>
                            <p class="supplement-desc"><?= htmlspecialchars($supplement['description']) ?></p>
                            <div class="supplement-meta">
                                <span class="supplement-price">₹<?= number_format($supplement['price'], 2) ?></span>
                                <span class="supplement-stock">In Stock: <?= $supplement['stock'] ?></span>
                            </div>
                        </div>
                        <div class="supplement-footer">
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'member'): ?>
                                <button class="btn order-btn" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#orderModal"
                                        data-id="<?= $supplement['supplement_id'] ?>"
                                        data-name="<?= htmlspecialchars($supplement['name']) ?>"
                                        data-price="<?= $supplement['price'] ?>"
                                        data-stock="<?= $supplement['stock'] ?>">
                                    Order Now
                                </button>
                            <?php elseif(isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-disabled" disabled>
                                    Members Only
                                </button>
                            <?php else: ?>
                                <a href="/crossfit/login.php" class="btn btn-outline">
                                    Login to Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Admin Add Form (only visible to admins) -->
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
            <div class="add-supplement-form mt-5">
                <h3 class="text-center mb-4">Add New Supplement</h3>
                <form action="/crossfit/admin/add_supplement.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Supplement Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" id="stock" name="stock" min="0" required>
                        </div>
                    </div>
                    
                    <!-- Multiple image uploads -->
                    <div class="mb-3">
                        <label class="form-label">Supplement Images</label>
                        
                        <div class="image-upload-group">
                            <label for="image1" class="form-label">Main Image (Required)</label>
                            <input type="file" class="form-control" id="image1" name="images[]" accept="image/*" required>
                        </div>
                        
                        <div class="image-upload-group">
                            <label for="image2" class="form-label">Secondary Image (Optional)</label>
                            <input type="file" class="form-control" id="image2" name="images[]" accept="image/*">
                        </div>
                        
                        <div class="image-upload-group">
                            <label for="image3" class="form-label">Additional Image (Optional)</label>
                            <input type="file" class="form-control" id="image3" name="images[]" accept="image/*">
                        </div>
                    </div>
                    
                    <div class="text-center">
                        <button type="submit" class="btn">Add Supplement</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Order Modal -->
    <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderModalLabel">Order Supplement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="/crossfit/member/process_order.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="supplement_id" id="modalSupplementId">
                        <div class="mb-3">
                            <label class="form-label">Supplement</label>
                            <input type="text" class="form-control" id="modalSupplementName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price</label>
                            <input type="text" class="form-control" id="modalSupplementPrice" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" min="1" value="1" required>
                            <small class="text-muted" id="maxStock">Max: </small>
                        </div>
                        <div class="mb-3">
                            <label for="pickup_date" class="form-label">Pickup Date</label>
                            <input type="date" class="form-control" id="pickup_date" name="pickup_date" min="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn">Place Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Initialize order modal
        document.querySelectorAll('.order-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('modalSupplementId').value = this.dataset.id;
                document.getElementById('modalSupplementName').value = this.dataset.name;
                document.getElementById('modalSupplementPrice').value = '$' + parseFloat(this.dataset.price).toFixed(2);
                document.getElementById('quantity').max = this.dataset.stock;
                document.getElementById('maxStock').textContent = 'Max: ' + this.dataset.stock;
            });
        });

        // Set minimum date for pickup (today)
        document.getElementById('pickup_date').min = new Date().toISOString().split('T')[0];

        // Initialize image sliders
        document.querySelectorAll('.slider-dot').forEach(dot => {
            dot.addEventListener('click', function() {
                const slideIndex = parseInt(this.dataset.slide);
                const sliderId = this.dataset.target;
                const slider = document.getElementById(sliderId);
                
                // Update slider position
                slider.style.transform = `translateX(-${slideIndex * 100}%)`;
                
                // Update active dot
                this.parentElement.querySelectorAll('.slider-dot').forEach(d => {
                    d.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
    </script>

    <?php 
    require_once 'includes/footer.php';
    ob_end_flush();
    ?>
</body>
</html>