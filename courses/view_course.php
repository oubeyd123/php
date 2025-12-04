<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($course_id == 0) {
    header("Location: courses.php");
    exit();
}

// Get course details
$stmt = $conn->prepare("SELECT c.*, u.username as teacher_name, u.id as teacher_id 
                        FROM courses c 
                        JOIN users u ON c.teacher_id = u.id 
                        WHERE c.id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: courses.php");
    exit();
}

$course = $result->fetch_assoc();
$is_teacher = ($course['teacher_id'] == $user_id);

// Check if student is enrolled
$is_enrolled = false;
if($role === 'student') {
    $stmt = $conn->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $course_id, $user_id);
    $stmt->execute();
    $is_enrolled = $stmt->get_result()->num_rows > 0;
}

// Handle enrollment
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll'])) {
    if($role === 'student' && !$is_enrolled) {
        $stmt = $conn->prepare("INSERT INTO enrollments (course_id, student_id, enrolled_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("ii", $course_id, $user_id);
        if($stmt->execute()) {
            $is_enrolled = true;
            header("Location: view_course.php?id=" . $course_id);
            exit();
        }
    }
}

// Handle new post/announcement
$post_success = false;
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_post']) && $is_teacher) {
    $title = trim($_POST['post_title']);
    $content = trim($_POST['post_content']);
    
    if(!empty($title) && !empty($content)) {
        $stmt = $conn->prepare("INSERT INTO announcements (course_id, title, content, posted_by, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("issi", $course_id, $title, $content, $user_id);
        if($stmt->execute()) {
            $post_success = true;
            header("Location: view_course.php?id=" . $course_id);
            exit();
        }
    }
}

// Get all posts (announcements) and assignments for the stream
$stream_query = "
    (SELECT 'announcement' as type, id, title, content as description, posted_by as author_id, created_at, NULL as due_date
     FROM announcements WHERE course_id = ?)
    UNION
    (SELECT 'assignment' as type, id, title, description, course_id as author_id, created_at, due_date
     FROM assignments WHERE course_id = ?)
    ORDER BY created_at DESC
";

$stmt = $conn->prepare($stream_query);
$stmt->bind_param("ii", $course_id, $course_id);
$stmt->execute();
$stream_items = $stmt->get_result();

// Get enrolled students count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
$stmt->bind_param("i", $course_id);
$stmt->execute();
$student_count = $stmt->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($course['title']); ?> - DigitalVillage</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .course-header {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            color: white;
            padding: 40px;
            border-radius: 18px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .course-info h1 {
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .course-code-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .course-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        
        .stat-item {
            background: rgba(255,255,255,0.15);
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 25px;
            align-items: start;
        }
        
        .stream {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .post-composer {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .post-composer h3 {
            color: #27ae60;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .post-composer input,
        .post-composer textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 12px;
            font-family: inherit;
            font-size: 15px;
        }
        
        .post-composer textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .post-composer input:focus,
        .post-composer textarea:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .btn-post {
            width: 100%;
            padding: 12px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-post:hover {
            background: #219150;
        }
        
        .stream-item {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            border-left: 4px solid #27ae60;
        }
        
        .stream-item.assignment {
            border-left-color: #e67e22;
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .item-type {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .item-type.announcement {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .item-type.assignment {
            background: #fff3e0;
            color: #e67e22;
        }
        
        .stream-item h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .stream-item p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        
        .item-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
            font-size: 14px;
            color: #999;
        }
        
        .due-date {
            color: #e67e22;
            font-weight: bold;
        }
        
        .sidebar {
            position: sticky;
            top: 20px;
        }
        
        .sidebar-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .sidebar-card h3 {
            color: #27ae60;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .action-btn {
            display: block;
            width: 100%;
            padding: 12px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 10px;
            transition: background 0.3s;
        }
        
        .action-btn:hover {
            background: #219150;
        }
        
        .btn-enrolled {
            background: #e8f5e9;
            color: #27ae60;
            border: 2px solid #27ae60;
            cursor: not-allowed;
        }
        
        .empty-stream {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .empty-stream-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        @media (max-width: 968px) {
            .content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                position: static;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <p class="logo-spc">👩‍🏫</p>
            <span>DigitalVillage</span>
        </div>
        <div class="nav-links">
            <a href="../index.php">Home</a>
            <a href="courses.php">Courses</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="../dashboard.php" class="btn-orange">Dashboard</a>
            <a href="../logout.php" class="btn-outline">Logout</a>
        </div>
    </nav>

    <div class="page-container">
        <div class="course-header">
            <div class="course-info">
                <span class="course-code-badge"><?php echo htmlspecialchars($course['course_code']); ?></span>
                <h1><?php echo htmlspecialchars($course['title']); ?></h1>
                <div class="course-stats">
                    <span class="stat-item">👨‍🏫 <?php echo htmlspecialchars($course['teacher_name']); ?></span>
                    <span class="stat-item">👥 <?php echo $student_count; ?> students</span>
                </div>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="stream">
                <?php if($is_teacher): ?>
                    <!-- Post Composer for Teachers -->
                    <div class="post-composer">
                        <h3>📢 Share with your class</h3>
                        <form method="POST">
                            <input type="text" name="post_title" placeholder="Post title..." required>
                            <textarea name="post_content" placeholder="Share an announcement, material, or resource..." required></textarea>
                            <button type="submit" name="create_post" class="btn-post">Post to Class</button>
                        </form>
                    </div>
                <?php endif; ?>

                <!-- Stream of Posts and Assignments -->
                <?php if($stream_items->num_rows > 0): ?>
                    <?php while($item = $stream_items->fetch_assoc()): ?>
                        <div class="stream-item <?php echo $item['type']; ?>">
                            <div class="item-header">
                                <span class="item-type <?php echo $item['type']; ?>">
                                    <?php echo $item['type'] === 'assignment' ? '📝 Assignment' : '📢 Announcement'; ?>
                                </span>
                                <small><?php echo date('M j, Y - g:i A', strtotime($item['created_at'])); ?></small>
                            </div>
                            
                            <h3><?php echo htmlspecialchars($item['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                            
                            <?php if($item['type'] === 'assignment'): ?>
                                <div class="item-footer">
                                    <span class="due-date">📅 Due: <?php echo date('M j, Y', strtotime($item['due_date'])); ?></span>
                                    <?php if($is_enrolled || $is_teacher): ?>
                                        <a href="../assignments/view_assignment.php?id=<?php echo $item['id']; ?>" 
                                           style="color: #27ae60; text-decoration: none; font-weight: bold;">
                                            View Assignment →
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-stream">
                        <div class="empty-stream-icon">📭</div>
                        <h2>No posts yet</h2>
                        <p>The class stream is empty. <?php echo $is_teacher ? 'Be the first to post something!' : 'Check back later for updates.'; ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sidebar">
                <?php if($role === 'student'): ?>
                    <div class="sidebar-card">
                        <h3>Enrollment</h3>
                        <?php if($is_enrolled): ?>
                            <button class="action-btn btn-enrolled">✓ Enrolled</button>
                        <?php else: ?>
                            <form method="POST">
                                <button type="submit" name="enroll" class="action-btn">Join Class</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if($is_teacher): ?>
                    <div class="sidebar-card">
                        <h3>Quick Actions</h3>
                        <a href="../assignments/create_assignment.php?course_id=<?php echo $course_id; ?>" class="action-btn">
                            + New Assignment
                        </a>
                        <a href="upload_material.php?course_id=<?php echo $course_id; ?>" class="action-btn">
                            📎 Upload File
                        </a>
                    </div>
                <?php endif; ?>

                <div class="sidebar-card">
                    <h3>About Course</h3>
                    <p style="color: #666; line-height: 1.6;">
                        <?php echo htmlspecialchars($course['description'] ?: 'No description available'); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>