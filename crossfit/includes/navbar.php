<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/crossfit/index.php">CrossFit</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="/crossfit/index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="/crossfit/packages.php">Packages</a></li>
                <li class="nav-item"><a class="nav-link" href="/crossfit/supplements.php">Supplements</a></li>
                
            </ul>
            <div class="d-flex">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="/crossfit/logout.php" class="btn btn-outline-light me-2">Logout</a>
                <?php else: ?>
                    <a href="/crossfit/login.php" class="btn btn-outline-light me-2">Login</a>
                    <a href="/crossfit/join.php" class="btn btn-primary">Join Us</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>