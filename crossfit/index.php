<?php
ob_start();
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Home - CrossFit Revolution";
require_once 'config/database.php';

// Get featured packages
$featuredPackages = $conn->query("SELECT * FROM packages ORDER BY RAND() LIMIT 3")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Oswald', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
            color: var(--text-light);
        }

        /* Full Width Container */
        .container-fluid {
            width: 100%;
            padding: 0;
            margin: 0;
        }

        /* Header */
        header {
            width: 100%;
            background-color: rgba(18, 18, 18, 0.95);
            position: fixed;
            z-index: 1000;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        /* Hero Section with Parallax */
        .hero {
            width: 100%;
            height: 100vh;
            min-height: 800px;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding-top: 80px;
        }

        .hero-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/images/gym-hero.jpg') center/cover no-repeat;
            z-index: -1;
            transform: translateZ(0);
            will-change: transform;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: -1;
        }

        .hero-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            font-size: 4.5rem;
            margin-bottom: 20px;
            line-height: 1.1;
            color: var(--primary);
            text-shadow: 2px 2px 5px rgba(0,0,0,0.5);
        }

        .hero p {
            font-size: 1.3rem;
            max-width: 600px;
            margin-bottom: 30px;
            color: var(--text-dark);
        }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 15px 35px;
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
            font-size: 1rem;
            box-shadow: 0 5px 15px rgba(255, 90, 31, 0.4);
        }

        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 90, 31, 0.6);
        }

        .btn-outline {
            background-color: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            margin-left: 15px;
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Sections */
        section {
            width: 100%;
            padding: 100px 0;
        }

        .section-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Transformation Section */
        .transformation {
            background-color: var(--darker);
            position: relative;
        }

        .transformation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('assets/images/transformation-bg.jpg') center/cover no-repeat;
            opacity: 0.2;
            z-index: 0;
        }

        .transformation-content {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .transformation-image {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }

        .transformation-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
        }

        .transformation-image:hover img {
            transform: scale(1.05);
        }

        /* Why Choose Us */
        .why-choose {
            background-color: var(--dark);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .feature-card {
            background-color: var(--darker);
            padding: 40px 30px;
            border-radius: 10px;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }

        .feature-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 20px;
        }

        /* BMI Calculator Section */
        .bmi-calculator {
            background-color: var(--darker);
        }

        .bmi-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: var(--dark);
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
        }

        .bmi-title {
            text-align: center;
            margin-bottom: 30px;
            color: var(--primary);
        }

        .bmi-form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 5px;
            color: var(--text-dark);
            font-size: 16px;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }

        .gender-selection {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .gender-option {
            flex: 1;
            text-align: center;
        }

        .gender-btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border: 2px solid transparent;
            border-radius: 5px;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .gender-btn:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .gender-btn.selected {
            border-color: var(--primary);
            background-color: rgba(255, 90, 31, 0.2);
            color: var(--primary);
        }

        .bmi-result {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            display: none;
        }

        .bmi-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary);
            margin: 10px 0;
        }

        .bmi-category {
            font-size: 1.2rem;
            margin-bottom: 15px;
        }

        .bmi-interpretation {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Supplements */
        .supplements {
            background-color: var(--darker);
        }

        .supplements-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .supplement-card {
            background-color: var(--dark);
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .supplement-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .supplement-img {
            height: 250px;
            overflow: hidden;
        }

        .supplement-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .supplement-card:hover .supplement-img img {
            transform: scale(1.1);
        }

        .supplement-body {
            padding: 20px;
        }

        .supplement-price {
            color: var(--primary);
            font-weight: 700;
            font-size: 1.3rem;
            margin: 10px 0;
        }

        /* Membership Plans */
        .membership {
            background-color: var(--dark);
        }

        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 50px;
        }

        .plan-card {
            background-color: var(--darker);
            padding: 40px 30px;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
        }

        .plan-card.popular {
            border: 2px solid var(--primary);
        }

        .popular-badge {
            position: absolute;
            top: -15px;
            right: 20px;
            background-color: var(--primary);
            color: white;
            padding: 5px 15px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
        }

        .plan-card h3 {
            font-size: 1.8rem;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .plan-features {
            list-style: none;
            margin-bottom: 30px;
        }

        .plan-features li {
            padding: 10px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* Footer */
        footer {
            background-color: var(--darker);
            padding: 60px 0 30px;
            color: var(--text-dark);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
        }

        .footer-logo {
            font-family: 'Oswald', sans-serif;
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 20px;
            display: block;
            text-decoration: none;
        }

        .footer-links h3 {
            margin-bottom: 20px;
            font-size: 1.3rem;
            color: var(--text-light);
        }

        .footer-links ul {
            list-style: none;
        }

        .footer-links li {
            margin-bottom: 10px;
        }

        .footer-links a {
            color: var(--text-dark);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary);
        }

        .copyright {
            text-align: center;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .hero h1 {
                font-size: 3.5rem;
            }
            
            .transformation-content {
                grid-template-columns: 1fr;
            }
            
            .transformation-image {
                order: -1;
            }
            
            .bmi-form {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .hero {
                min-height: 700px;
            }
            
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .btn-outline {
                margin-left: 0;
                margin-top: 15px;
            }
            
            .gender-selection {
                flex-direction: column;
            }
        }

        @media (max-width: 576px) {
            .hero h1 {
                font-size: 2rem;
            }
            
            .bmi-container {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
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

    <!-- Hero Section with Parallax -->
    <section class="hero">
        <div class="hero-bg" id="parallax-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>TRANSFORM YOUR FITNESS JOURNEY</h1>
            <p>Join our CrossFit community and push your limits with world-class coaching and facilities designed for maximum results.</p>
            <div>
                <a href="join.php" class="btn">JOIN NOW</a>
                <a href="#membership" class="btn btn-outline">VIEW PLANS</a>
            </div>
        </div>
    </section>

    <!-- Transformation Section -->
    <section class="transformation">
        <div class="section-container">
            <div class="transformation-content">
                <div class="transformation-text">
                    <h2>YOUR TRANSFORMATION STARTS HERE</h2>
                    <p>At CrossFit Revolution, we don't just change workouts - we change lives. Our proven system combines expert coaching, community support, and science-backed programming to deliver real results.</p>
                    <p>Whether you're looking to lose weight, build strength, or compete at the highest level, our team will create a personalized plan to help you reach your goals.</p>
                    <a href="#" class="btn">LEARN MORE</a>
                </div>
                <div class="transformation-image">
                    <img src="https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80" alt="Fitness transformation">
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="why-choose">
        <div class="section-container">
            <h2 class="text-center">WHY CHOOSE CROSSFIT REVOLUTION</h2>
            <p class="text-center">We're different from ordinary gyms. Here's what makes us special:</p>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-trophy"></i>
                    </div>
                    <h3>EXPERT COACHES</h3>
                    <p>Our certified CrossFit trainers have 10+ years experience helping athletes of all levels reach their potential.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h3>SUPPORTIVE COMMUNITY</h3>
                    <p>Train with like-minded people who push you to be better every day. We celebrate every victory together.</p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="bi bi-heart-pulse"></i>
                    </div>
                    <h3>PROVEN RESULTS</h3>
                    <p>98% of our members see measurable improvements in strength and body composition within 8 weeks.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- BMI Calculator Section -->
    <section class="bmi-calculator" id="bmi-calculator">
        <div class="section-container">
            <h2 class="text-center">CALCULATE YOUR BMI</h2>
            <p class="text-center">Body Mass Index is a simple index of weight-for-height that is commonly used to classify underweight, overweight and obesity in adults.</p>
            
            <div class="bmi-container">
                <form id="bmiForm" class="bmi-form">
                    <div class="form-group">
                        <label for="height">Height (in cm)</label>
                        <input type="number" id="height" class="form-control" placeholder="Enter your Height" min="50" max="250" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="weight">Weight (in kg)</label>
                        <input type="number" id="weight" class="form-control" placeholder="Enter your Weight" min="10" max="300" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Your Age</label>
                        <input type="number" id="age" class="form-control" placeholder="Age" min="15" max="100" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <div class="gender-selection">
                            <div class="gender-option">
                                <button type="button" class="gender-btn" data-gender="male">Male</button>
                            </div>
                            <div class="gender-option">
                                <button type="button" class="gender-btn" data-gender="female">Female</button>
                            </div>
                        </div>
                        <input type="hidden" id="gender" name="gender" required>
                    </div>
                    
                    <div class="form-group" style="grid-column: span 2; text-align: center;">
                        <button type="submit" class="btn" style="width: 100%;">Calculate Now!</button>
                    </div>
                </form>
                
                <div id="bmiResult" class="bmi-result">
                    <h3>Your BMI Result</h3>
                    <div id="bmiValue" class="bmi-value">--</div>
                    <div id="bmiCategory" class="bmi-category">--</div>
                    <div class="bmi-interpretation">
                        BMI categories: Underweight (&lt;18.5), Normal weight (18.5-24.9), 
                        Overweight (25-29.9), Obesity (≥30)
                    </div>
                </div>
            </div>
        </div>
    </section>

     <!-- Supplements Section -->
    <section class="supplements">
        <div class="section-container">
            <h2 class="text-center">PERFORMANCE SUPPLEMENTS</h2>
            <p class="text-center">Premium supplements to fuel your training and recovery</p>
            
            <div class="supplements-grid">
                <div class="supplement-card">
                    <div class="supplement-img">
                        <img src="assets/images/whey-protein.jpg" alt="Whey Protein">
                    </div>
                    <div class="supplement-body">
                        <h3>WHEY PROTEIN</h3>
                        <p>Premium quality protein for muscle recovery</p>
                        <div class="supplement-price">₹399</div>
                        <a href="member/supplements.php" class="btn">Join Now</a>
                    </div>
                </div>
                
                <div class="supplement-card">
                    <div class="supplement-img">
                        <img src="assets/images/pre-workout.jpg" alt="Pre-Workout">
                    </div>
                    <div class="supplement-body">
                        <h3>PRE-WORKOUT</h3>
                        <p>Energy boost for maximum performance</p>
                        <div class="supplement-price">₹599</div>
                        <a href="member/supplements.php" class="btn">Join to Book</a>
                    </div>
                </div>
                
                <div class="supplement-card">
                    <div class="supplement-img">
                        <img src="assets/images/bcaa.jpg" alt="BCAAs">
                    </div>
                    <div class="supplement-body">
                        <h3>BCAAs</h3>
                        <p>Essential amino acids for recovery</p>
                        <div class="supplement-price">₹999</div>
                        <a href="member/supplements.php" class="btn">Join to Book</a>
                    </div>
                </div>
                
                <div class="supplement-card">
                    <div class="supplement-img">
                        <img src="assets/images/creatine.jpg" alt="Creatine">
                    </div>
                    <div class="supplement-body">
                        <h3>CREATINE</h3>
                        <p>For strength and power output</p>
                        <div class="supplement-price">₹1299</div>
                        <a href="member/supplements.php" class="btn">Join to Book</a>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Membership Plans -->
    <section class="membership" id="membership">
        <div class="section-container">
            <h2 class="text-center">MEMBERSHIP PLANS</h2>
            <p class="text-center">Flexible options to match your goals and schedule</p>
            
            <div class="plans-grid">
                <?php foreach($featuredPackages as $package): ?>
                <div class="plan-card <?= $package['package_id'] == 2 ? 'popular' : '' ?>">
                    <?php if($package['package_id'] == 2): ?>
                        <div class="popular-badge">MOST POPULAR</div>
                    <?php endif; ?>
                    <h3><?= htmlspecialchars($package['name']) ?></h3>
                    <div class="plan-price">₹<?= number_format($package['price'], 0) ?>/mo</div>
                    <ul class="plan-features">
                        <?php 
                        $features = explode(',', $package['features']);
                        foreach($features as $feature): 
                        ?>
                            <li><?= trim($feature) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="member/book_package.php" class="btn">Join to Book</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section class="about-us" style="background-color: var(--darker); padding: 80px 0;">
        <div class="section-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <h2 class="text-center" style="text-align: center; margin-bottom: 30px; color: var(--primary);">ABOUT CROSSFIT REVOLUTION</h2>
            <p class="text-center" style="text-align: center; margin-bottom: 50px; font-size: 1.1rem; max-width: 800px; margin-left: auto; margin-right: auto;">More than a gym - we're a community dedicated to transforming lives through functional fitness, nutrition, and unwavering support.</p>
            
            <div class="about-content" style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">
                <div class="about-text">
                    <h3 style="color: var(--primary); margin: 25px 0 15px; font-size: 1.5rem;">OUR STORY</h3>
                    <p style="margin-bottom: 20px;">Founded in 2023, CrossFit Revolution began with a simple mission: to make elite fitness accessible to everyone. What started as a small garage gym with just a handful of members has grown into a premier fitness facility serving hundreds of dedicated athletes.</p>
                    
                    <h3 style="color: var(--primary); margin: 25px 0 15px; font-size: 1.5rem;">OUR PHILOSOPHY</h3>
                    <p style="margin-bottom: 20px;">We believe that fitness should be functional, varied, and intense. Our programs are designed to improve your life outside the gym by building strength, endurance, and resilience that translates to real-world activities.</p>
                    
                    <h3 style="color: var(--primary); margin: 25px 0 15px; font-size: 1.5rem;">OUR COMMUNITY</h3>
                    <p style="margin-bottom: 20px;">What truly sets us apart is our community. We're a diverse group of individuals united by a common goal: to become better versions of ourselves. Every member supports and challenges each other to push beyond perceived limits.</p>
                    
                    <div class="stats-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 30px;">
                        <div class="stat-item" style="text-align: center; padding: 15px; background-color: rgba(255, 90, 31, 0.1); border-radius: 8px;">
                            <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--primary);">2+</div>
                            <div class="stat-label" style="font-size: 0.9rem;">Years of Excellence</div>
                        </div>
                        <div class="stat-item" style="text-align: center; padding: 15px; background-color: rgba(255, 90, 31, 0.1); border-radius: 8px;">
                            <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--primary);">500+</div>
                            <div class="stat-label" style="font-size: 0.9rem;">Members Transformed</div>
                        </div>
                        <div class="stat-item" style="text-align: center; padding: 15px; background-color: rgba(255, 90, 31, 0.1); border-radius: 8px;">
                            <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--primary);">5+</div>
                            <div class="stat-label" style="font-size: 0.9rem;">Certified Coaches</div>
                        </div>
                        <div class="stat-item" style="text-align: center; padding: 15px; background-color: rgba(255, 90, 31, 0.1); border-radius: 8px;">
                            <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--primary);">89%</div>
                            <div class="stat-label" style="font-size: 0.9rem;">Success Rate</div>
                        </div>
                    </div>
                </div>
                
                <div class="about-image" style="border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                    <img src="https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=600&q=80" alt="CrossFit Revolution Team" style="width: 100%; height: auto; display: block;">
                </div>
            </div>
            
            <div class="mission-section" style="margin-top: 80px; text-align: center;">
                <h3 style="color: var(--primary); margin-bottom: 20px; font-size: 1.8rem;">OUR MISSION</h3>
                <p style="font-size: 1.2rem; max-width: 900px; margin: 0 auto; font-style: italic;">"To empower individuals to surpass their fitness goals through world-class coaching, community support, and a commitment to excellence in every workout."</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-container">
            <div class="footer-about">
                <a href="#" class="footer-logo">CROSSFIT REVOLUTION</a>
                <p>Transforming lives through functional fitness since 2015.</p>
            </div>
            
            <div class="footer-links">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="packages.php">Packages</a></li>
                    <li><a href="supplements.php">Supplements</a></li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h3>Contact</h3>
                <ul>
                    <li>Kochi</li>
                    <li>Lulu Mall, 4th Floor</li>
                    <li>Phone:+91 8281772456</li>
                    <li>Email:info@crossfitrevolution.com</li>
                </ul>
            </div>
            
            <div class="footer-links">
                <h3>Hours</h3>
                <ul>
                    <li>Monday-Friday: 5AM - 10PM</li>
                    <li>Saturday: 7AM - 8PM</li>
                    <li>Sunday: 8AM - 6PM</li>
                </ul>
            </div>
        </div>
        
        <div class="copyright">
            <p>&copy; <?= date('Y') ?> CrossFit Revolution. All rights reserved.</p>
        </div>
    </footer>
    

    <script>
        // Parallax Effect for Hero
        window.addEventListener('scroll', function() {
            const scrollPosition = window.pageYOffset;
            const parallaxBg = document.getElementById('parallax-bg');
            parallaxBg.style.transform = 'translateY(' + scrollPosition * 0.5 + 'px)';
            
            // Header scroll effect
            const header = document.querySelector('header');
            if(scrollPosition > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
        
        // Animate elements on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const animateOnScroll = function() {
                const elements = document.querySelectorAll('.feature-card, .supplement-card, .plan-card');
                
                elements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenPosition = window.innerHeight / 1.3;
                    
                    if(elementPosition < screenPosition) {
                        element.style.opacity = '1';
                        element.style.transform = 'translateY(0)';
                    }
                });
            };
            
            // Set initial state
            const cards = document.querySelectorAll('.feature-card, .supplement-card, .plan-card');
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.5s ease';
            });
            
            window.addEventListener('scroll', animateOnScroll);
            animateOnScroll(); // Run once on load
            
            // BMI Calculator Functionality
            const genderButtons = document.querySelectorAll('.gender-btn');
            const genderInput = document.getElementById('gender');
            const bmiForm = document.getElementById('bmiForm');
            const bmiResult = document.getElementById('bmiResult');
            const bmiValue = document.getElementById('bmiValue');
            const bmiCategory = document.getElementById('bmiCategory');
            
            // Gender selection
            genderButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Remove selected class from all buttons
                    genderButtons.forEach(btn => btn.classList.remove('selected'));
                    
                    // Add selected class to clicked button
                    this.classList.add('selected');
                    
                    // Set the hidden input value
                    genderInput.value = this.getAttribute('data-gender');
                });
            });
            
            // BMI form submission
            bmiForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form values
                const height = parseFloat(document.getElementById('height').value);
                const weight = parseFloat(document.getElementById('weight').value);
                const age = parseInt(document.getElementById('age').value);
                const gender = genderInput.value;
                
                // Validate inputs
                if (!height || !weight || !age || !gender) {
                    alert('Please fill all fields');
                    return;
                }
                
                // Calculate BMI (weight (kg) / (height (m) * height (m)))
                const heightInMeters = height / 100;
                const bmi = weight / (heightInMeters * heightInMeters);
                
                // Round to one decimal place
                const roundedBmi = bmi.toFixed(1);
                
                // Determine BMI category
                let category = '';
                if (bmi < 18.5) {
                    category = 'Underweight';
                } else if (bmi >= 18.5 && bmi < 25) {
                    category = 'Normal weight';
                } else if (bmi >= 25 && bmi < 30) {
                    category = 'Overweight';
                } else {
                    category = 'Obesity';
                }
                
                // Display result
                bmiValue.textContent = roundedBmi;
                bmiCategory.textContent = category;
                bmiResult.style.display = 'block';
                
                // Scroll to result
                bmiResult.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });
    </script>
</body>
</html>

<?php 
ob_end_flush();
?>