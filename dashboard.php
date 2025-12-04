<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - DigitalVillage</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            color: white;
            padding: 40px;
            border-radius: 18px;
            margin-bottom: 40px;
        }
        
        .dashboard-header h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .dashboard-header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .user-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            margin-top: 15px;
            font-weight: 500;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .dashboard-card {
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.12);
        }
        
        .dashboard-card h3 {
            color: #27ae60;
            margin-bottom: 15px;
            font-size: 22px;
        }
        
        .dashboard-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        
        .card-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .card-button {
            display: inline-block;
            background: #27ae60;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.3s;
        }
        
        .card-button:hover {
            background: #219150;
        }
        
        .quick-stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-box {
            flex: 1;
            background: rgba(255,255,255,0.15);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <p class="logo-spc">👩‍🏫</p>
            <span>DigitalVillage</span>
        </div>

        <div class="nav-links">
            <a href="index.php">Home</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($username); ?>
            </span>
            <a href="dashboard.php" class="btn-orange">Dashboard</a>
            <a href="logout.php" class="btn-outline">Logout</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-header">
            <h1>Welcome back, <?php echo htmlspecialchars($username); ?>! 👋</h1>
            <p>Ready to continue your learning journey?</p>
            <span class="user-badge">
                <?php echo $role === 'teacher' ? '👨‍🏫 Teacher' : '🎓 Student'; ?>
            </span>
            
            <?php if($role === 'student'): ?>
                <div class="quick-stats">
                    <div class="stat-box">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Enrolled Courses</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Assignments Due</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Completed</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="quick-stats">
                    <div class="stat-box">
                        <div class="stat-number">0</div>
                        <div class="stat-label">My Courses</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number">0</div>
                        <div class="stat-label">Assignments</div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-grid">
            <?php if($role === 'teacher'): ?>
                <!-- Teacher Actions -->
                <div class="dashboard-card">
                    <div class="card-icon">➕</div>
                    <h3>Create Course</h3>
                    <p>Start a new course and invite students to join your classroom.</p>
                    <a href="courses/create_course.php" class="card-button">Create Course</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📚</div>
                    <h3>My Courses</h3>
                    <p>View and manage all your active courses and materials.</p>
                    <a href="courses/courses.php" class="card-button">View Courses</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📝</div>
                    <h3>Assignments</h3>
                    <p>Create assignments and grade student submissions.</p>
                    <a href="assignments/assignments.php" class="card-button">Manage Assignments</a>
                </div>
            <?php else: ?>
                <!-- Student Actions -->
                <div class="dashboard-card">
                    <div class="card-icon">🔍</div>
                    <h3>Browse Courses</h3>
                    <p>Discover and enroll in courses available at your institution.</p>
                    <a href="courses/courses.php" class="card-button">Browse Courses</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📚</div>
                    <h3>My Courses</h3>
                    <p>Access your enrolled courses and learning materials.</p>
                    <a href="courses/my_courses.php" class="card-button">My Courses</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📝</div>
                    <h3>Assignments</h3>
                    <p>View pending assignments and submit your work.</p>
                    <a href="assignments/assignments.php" class="card-button">View Assignments</a>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-card">
                <div class="card-icon">👤</div>
                <h3>Profile Settings</h3>
                <p>Update your personal information and preferences.</p>
                <a href="profile.php" class="card-button">Edit Profile</a>
            </div>
        </div>
    </div>

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