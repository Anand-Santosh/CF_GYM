<?php
ob_start();
$pageTitle = "Membership Packages";
require_once 'config/database.php';
require_once 'includes/header.php';

// Get all packages from database
$packages = $conn->query("SELECT * FROM packages ORDER BY price")->fetchAll(PDO::FETCH_ASSOC);

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

        .packages-section {
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

        .packages-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .package-card {
            background-color: var(--dark);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .package-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .package-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 30px 20px;
            text-align: center;
            position: relative;
        }

        .popular-badge {
            position: absolute;
            top: -10px;
            right: 20px;
            background-color: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .package-title {
            font-size: 1.8rem;
            margin-bottom: 10px;
            color: white;
        }

        .package-price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .package-duration {
            font-size: 1rem;
            opacity: 0.9;
        }

        .package-body {
            padding: 30px;
        }

        .package-description {
            margin-bottom: 25px;
            color: var(--text-light);
            text-align: center;
        }

        .features-list {
            list-style: none;
            padding: 0;
            margin-bottom: 30px;
        }

        .features-list li {
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
        }

        .features-list li:before {
            content: '✓';
            color: var(--primary);
            font-weight: bold;
            margin-right: 12px;
            font-size: 1.2rem;
        }

        .features-list li:last-child {
            border-bottom: none;
        }

        .package-footer {
            padding: 25px;
            background-color: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 35px;
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

        /* Admin Add Form */
        .add-package-form {
            background-color: var(--dark);
            padding: 30px;
            border-radius: 10px;
            margin-top: 50px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .form-label {
            color: var(--text-light);
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

        @media (max-width: 768px) {
            .packages-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header h2 {
                font-size: 2.2rem;
            }
            
            .package-header {
                padding: 25px 15px;
            }
            
            .package-price {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <section class="packages-section">
        <div class="container">
            <div class="section-header">
                <h2>Membership Packages</h2>
                <p>Choose the perfect plan for your fitness journey</p>
            </div>

            <?php if($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if(empty($packages)): ?>
                <div class="alert alert-info">Currently no packages available. Please check back later!</div>
            <?php else: ?>
                <div class="packages-grid">
                    <?php foreach($packages as $package): ?>
                    <div class="package-card">
                        <div class="package-header">
                            <?php if($package['is_popular'] ?? false): ?>
                                <div class="popular-badge">Most Popular</div>
                            <?php endif; ?>
                            <h3 class="package-title"><?= htmlspecialchars($package['name']) ?></h3>
                            <div class="package-price">₹<?= number_format($package['price'], 0) ?></div>
                            <div class="package-duration">per <?= $package['duration_months'] ?> month<?= $package['duration_months'] > 1 ? 's' : '' ?></div>
                        </div>
                        <div class="package-body">
                            <p class="package-description"><?= htmlspecialchars($package['description']) ?></p>
                            <ul class="features-list">
                                <?php 
                                $features = explode(',', $package['features']);
                                foreach($features as $feature): 
                                ?>
                                    <li><?= trim(htmlspecialchars($feature)) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="package-footer">
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'member'): ?>
                                <a href="/crossfit/member/book_package.php?package_id=<?= $package['package_id'] ?>" class="btn">
                                    Book Now
                                </a>
                            <?php elseif(isset($_SESSION['user_id'])): ?>
                                <button class="btn btn-disabled" disabled>
                                    Members Only
                                </button>
                            <?php else: ?>
                                <a href="/crossfit/login.php" class="btn btn-outline">
                                    Login to Book
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Admin Add Form (only visible to admins) -->
            <?php if(isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
            <div class="add-package-form mt-5">
                <h3 class="text-center mb-4">Add New Package</h3>
                <form action="/crossfit/admin/add_package.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Package Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="duration_months" class="form-label">Duration (months)</label>
                            <input type="number" class="form-control" id="duration_months" name="duration_months" min="1" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Popular Package</label>
                            <div class="form-check mt-2">
                                <input type="checkbox" class="form-check-input" id="is_popular" name="is_popular">
                                <label class="form-check-label" for="is_popular">Mark as popular</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="features" class="form-label">Features (separate with commas)</label>
                        <textarea class="form-control" id="features" name="features" rows="3" placeholder="Feature 1, Feature 2, Feature 3" required></textarea>
                    </div>
                    <div class="text-center">
                        <button type="submit" class="btn">Add Package</button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <?php 
    require_once 'includes/footer.php';
    ob_end_flush();
    ?>
</body>
</html>