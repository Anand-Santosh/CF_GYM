<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CrossFit Gym Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    // Start session at the very beginning
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    ?>
    
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">CrossFit</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="packages.php">Packages</a></li>
                    <li class="nav-item"><a class="nav-link" href="supplements.php">Supplements</a></li>
                    

                    
                    <!-- Dynamic Dashboard Link (shown only when logged in) -->
                    <?php if(isset($_SESSION['user_id']) && isset($_SESSION['role'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $_SESSION['role']; ?>/dashboard.php">
                            Dashboard
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <div class="d-flex">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="logout.php" class="btn btn-outline-light me-2">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                        <a href="join.php" class="btn btn-primary">Join Us</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">