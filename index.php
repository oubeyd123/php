<?php 
session_start();

// Check if user is logged in and set variables safely
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DigitalVillage - Your Independent Learning Platform</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <p class="logo-spc">👩‍🏫</p>
            <span>DigitalVillage</span>
        </div>

        <div class="nav-links">
            <?php if($is_logged_in): ?>
                <span style="color: #333; font-weight: 500;">
                    Welcome, <?php echo htmlspecialchars($username); ?>
                </span>
                <a href="dashboard.php" class="btn-orange">Dashboard</a>
                <a href="logout.php" class="btn-outline">Logout</a>
            <?php else: ?>
                <a href="login.php">Sign In</a>
                <a href="login.php" class="btn-orange">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="body">
        <div class="body-content">
            <h1>
                Empowering Schools to<br>
                <span class="gradient-text">Resist Big Tech</span>
            </h1>
            <p>
                Join our initiative to give schools the tools to create, manage, and control 
                their own digital classrooms. Keep students' data safe and independent from big corporations.
            </p>

            <div class="body-btns">
                <?php if($is_logged_in): ?>
                    <a href="dashboard.php" class="btn-orange">Go to Dashboard →</a>
                    <a href="courses.php" class="btn-outline">Browse Courses</a>
                <?php else: ?>
                    <a href="login.php" class="btn-orange">Start Your Classroom →</a>
                    <a href="#how-we-help" class="btn-outline">Learn More</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How We Help Section -->
    <section class="Help" id="how-we-help">
        <div class="Help-content">
            <h1>How We Help</h1>
            <p>
                Our platform empowers schools to build their own digital ecosystem, 
                control their data, and provide students with a safe, independent learning environment.
            </p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="feature-card">
            <div style="font-size:60px;">📚</div>
            <h3>Create Courses</h3>
            <p>
                Teachers can create and share courses directly in the local system, 
                without relying on external services.
            </p>
        </div>

        <div class="feature-card">
            <div style="font-size:60px;">📝</div>
            <h3>Manage Assignments</h3>
            <p>
                Assign homework, collect submissions, and provide grades — all within your school's own server.
            </p>
        </div>

        <div class="feature-card">
            <div style="font-size:60px;">🔒</div>
            <h3>Protect Data</h3>
            <p>
                Keep all student and teacher data local, secure, and independent from Big Tech platforms.
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div>
                <h2 class="footer-logo">DigitalVillage</h2>
                <p>Building independent, resilient digital classrooms for schools.</p>
            </div>

            <div>
                <h4>Platform</h4>
                <a href="index.php">Home</a>
                <a href="courses.php">Courses</a>
                <a href="assignments.php">Assignments</a>
                <a href="dashboard.php">Dashboard</a>
            </div>

            <div>
                <h4>Community</h4>
                <a href="#">About Us</a>
                <a href="#">Support</a>
                <a href="#">Schools</a>
                <a href="#">Events</a>
            </div>

            <div>
                <h4>Legal</h4>
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>

        <p class="footer-bottom">© 2025 DigitalVillage — All Rights Reserved</p>
    </footer>

</body>
</html>