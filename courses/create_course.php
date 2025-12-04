<?php
session_start();

// Check if user is logged in and is a teacher
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

require_once '../database.php';

$error = '';
$success = '';

// Handle course creation
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_course'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $course_code = strtoupper(trim($_POST['course_code']));
    $teacher_id = $_SESSION['user_id'];
    
    // Validate inputs
    if(empty($title) || empty($course_code)) {
        $error = "Course title and code are required.";
    } else {
        // Check if course code already exists
        $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
        $stmt->bind_param("s", $course_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error = "This course code already exists. Please use a different code.";
        } else {
            // Insert new course
            $stmt = $conn->prepare("INSERT INTO courses (title, description, course_code, teacher_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssi", $title, $description, $course_code, $teacher_id);
            
            if($stmt->execute()) {
                $success = "Course created successfully!";
                $course_id = $stmt->insert_id;
                // Redirect to course view after 2 seconds
                header("refresh:2;url=view_course.php?id=" . $course_id);
            } else {
                $error = "Failed to create course. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Course - DigitalVillage</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .page-header {
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            color: #27ae60;
            font-size: 36px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 18px;
        }
        
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 16px;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 14px;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn-submit {
            flex: 1;
            padding: 14px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #219150;
        }
        
        .btn-cancel {
            padding: 14px 30px;
            background: white;
            color: #666;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-cancel:hover {
            border-color: #27ae60;
            color: #27ae60;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
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
            <a href="../index.php">Home</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="../dashboard.php" class="btn-orange">Dashboard</a>
            <a href="../logout.php" class="btn-outline">Logout</a>
        </div>
    </nav>

    <div class="page-container">
        <div class="page-header">
            <h1>Create New Course</h1>
            <p>Set up a new course for your students</p>
        </div>

        <div class="form-card">
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> Redirecting...</div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Course Title *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="e.g., Introduction to Computer Science"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="course_code">Course Code *</label>
                    <input type="text" id="course_code" name="course_code" required 
                           placeholder="e.g., CS101"
                           value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ''; ?>">
                    <small>A unique identifier for your course (letters and numbers only)</small>
                </div>

                <div class="form-group">
                    <label for="description">Course Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Describe what students will learn in this course..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <small>Optional: Add details about topics, prerequisites, and learning objectives</small>
                </div>

                <div class="btn-group">
                    <button type="submit" name="create_course" class="btn-submit">
                        ✓ Create Course
                    </button>
                    <a href="../dashboard.php" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>