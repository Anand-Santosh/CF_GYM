<?php
ob_start();
session_start();
require_once '../config/database.php';

// Verify member access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'member') {
    header("Location: ../index.php");
    exit();
}

// Handle supplement order
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $supplement_id = $_POST['supplement_id'];
    $quantity = $_POST['quantity'];
    $pickup_date = $_POST['pickup_date'];
    
    try {
        // First, get the member_id from the members table
        $stmt = $conn->prepare("SELECT member_id FROM members WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $member = $stmt->fetch();
        
        if (!$member) {
            $_SESSION['error'] = "Member profile not found";
            header("Location: supplements.php");
            exit();
        }
        
        $member_id = $member['member_id'];
        
        // Check stock
        $stmt = $conn->prepare("SELECT stock FROM supplements WHERE supplement_id = ?");
        $stmt->execute([$supplement_id]);
        $supplement = $stmt->fetch();
        
        if ($supplement && $supplement['stock'] >= $quantity) {
            // Create order - use the actual member_id from members table
            $stmt = $conn->prepare("INSERT INTO supplement_orders (member_id, supplement_id, quantity, pickup_date, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$member_id, $supplement_id, $quantity, $pickup_date]);
            
            // Update stock
            $new_stock = $supplement['stock'] - $quantity;
            $stmt = $conn->prepare("UPDATE supplements SET stock = ? WHERE supplement_id = ?");
            $stmt->execute([$new_stock, $supplement_id]);
            
            $_SESSION['success'] = "Supplement ordered successfully! Please collect on $pickup_date";
        } else {
            $_SESSION['error'] = "Not enough stock available";
        }
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error placing order: " . $e->getMessage();
    }
    header("Location: supplements.php");
    exit();
}

// Get all supplements
$supplements = $conn->query("SELECT * FROM supplements WHERE stock > 0")->fetchAll();

$pageTitle = "Supplements";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - CrossFit Revolution</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background-color: var(--dark);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255, 90, 31, 0.2);
        }

        .card-img-top {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .card-body {
            padding: 20px;
        }

        .card-title {
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
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

        .form-control {
            background-color: var(--darker);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-light);
            padding: 12px 15px;
            width: 100%;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .form-control:focus {
            background-color: var(--darker);
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

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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

        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h2>Performance Supplements</h2>
            <a href="dashboard.php" class="btn btn-outline">← Back to Dashboard</a>
        </div>

        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="grid">
            <?php foreach($supplements as $supplement): ?>
            <div class="card">
                <img src="../assets/images/supplements/<?= htmlspecialchars($supplement['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($supplement['name']) ?>">
                <div class="card-body">
                    <h3 class="card-title"><?= htmlspecialchars($supplement['name']) ?></h3>
                    <p><?= htmlspecialchars($supplement['description']) ?></p>
                    <p><strong>Price:</strong>₹<?= number_format($supplement['price'], 2) ?></p>
                    <p><strong>In Stock:</strong> <?= $supplement['stock'] ?></p>
                    
                    <form method="POST">
                        <input type="hidden" name="supplement_id" value="<?= $supplement['supplement_id'] ?>">
                        
                        <div>
                            <label for="quantity_<?= $supplement['supplement_id'] ?>" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity_<?= $supplement['supplement_id'] ?>" 
                            
                                   name="quantity" min="1" max="<?= $supplement['stock'] ?>" value="1" required>
                        </div>
                        
                        <div>
                            <label for="pickup_date_<?= $supplement['supplement_id'] ?>" class="form-label">Pickup Date</label>
                            <input type="date" class="form-control" id="pickup_date_<?= $supplement['supplement_id'] ?>" 
                                   name="pickup_date" min="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <button type="submit" class="btn" style="width: 100%; margin-top: 10px;">Order Now</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>