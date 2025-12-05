<?php
session_start();
require_once '../database.php';

// Check if user is logged in and is a teacher
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Get teacher's courses for selection
$stmt = $conn->prepare("SELECT id, title, course_code FROM courses WHERE teacher_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$teacher_courses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$error = '';
$success = '';

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $max_points = intval($_POST['max_points']);
    $due_date = $_POST['due_date'];
    $selected_course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
    
    // Validation
    if(empty($title) || empty($description) || empty($due_date) || $selected_course_id == 0) {
        $error = "Please fill in all required fields.";
    } elseif($max_points <= 0) {
        $error = "Maximum points must be greater than 0.";
    } else {
        // Verify teacher owns the selected course
        $check_stmt = $conn->prepare("SELECT id FROM courses WHERE id = ? AND teacher_id = ?");
        $check_stmt->bind_param("ii", $selected_course_id, $user_id);
        $check_stmt->execute();
        
        if($check_stmt->get_result()->num_rows > 0) {
            // Insert assignment - matches your exact database structure
            $insert_stmt = $conn->prepare("
                INSERT INTO assignments (course_id, title, description, max_points, due_date) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert_stmt->bind_param("issis", $selected_course_id, $title, $description, $max_points, $due_date);
            
            if($insert_stmt->execute()) {
                $assignment_id = $insert_stmt->insert_id;
                $success = "Assignment created successfully for " . htmlspecialchars($teacher_courses[array_search($selected_course_id, array_column($teacher_courses, 'id'))]['title']) . "!";
                
                // Redirect to assignment view after 2 seconds
                header("refresh:2;url=view_assignment.php?id=" . $assignment_id);
                exit();
            } else {
                $error = "Failed to create assignment. Please try again.";
            }
            $insert_stmt->close();
        } else {
            $error = "You don't have permission to create assignments for this course.";
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment - DigitalVillage</title>
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
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-hint {
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
        
        /* Course Selection Styles - Pure CSS */
        .course-selection {
            margin-top: 15px;
        }
        
        .course-option {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s;
            position: relative;
            cursor: pointer;
        }
        
        .course-option:hover {
            border-color: #27ae60;
            background: #f9f9f9;
        }
        
        .course-code {
            background: #27ae60;
            color: white;
            padding: 6px 12px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 14px;
            min-width: 80px;
            text-align: center;
        }
        
        .course-title {
            flex: 1;
            font-weight: 500;
            color: #2c3e50;
        }
        
        /* Custom Radio Button - Pure CSS */
        .radio-container {
            position: relative;
            margin-bottom: 10px;
        }
        
        .radio-input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        
        .radio-custom {
            position: absolute;
            top: 50%;
            left: 15px;
            transform: translateY(-50%);
            height: 22px;
            width: 22px;
            background-color: white;
            border: 2px solid #ddd;
            border-radius: 50%;
            z-index: 1;
        }
        
        /* Show selected state based on radio */
        .radio-input:checked + .course-option {
            border-color: #27ae60;
            background: #e8f5e9;
        }
        
        .radio-input:checked + .course-option .radio-custom {
            border-color: #27ae60;
        }
        
        .radio-input:checked + .course-option .radio-custom::after {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #27ae60;
        }
        
        .no-courses-message {
            text-align: center;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        
        .no-courses-message p {
            color: #666;
            margin-bottom: 20px;
        }
        
        /* Responsive */
        @media (max-width: 600px) {
            .course-option {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                padding-left: 50px;
            }
            
            .course-code {
                align-self: flex-start;
            }
            
            .form-grid {
                grid-template-columns: 1fr !important;
            }
            
            .radio-custom {
                left: 15px;
                top: 30px;
            }
        }
        
        /* Date time input styling */
        input[type="datetime-local"] {
            font-family: inherit;
        }
        
        /* Form grid */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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
                <?php echo htmlspecialchars($username); ?>
            </span>
            <a href="../dashboard.php" class="btn-orange">Dashboard</a>
            <a href="../logout.php" class="btn-outline">Logout</a>
        </div>
    </nav>

    <div class="page-container">
        <div class="page-header">
            <h1>Create New Assignment</h1>
            <p>Select a course and set up an assignment for your students</p>
        </div>

        <div class="form-card">
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> Redirecting...</div>
            <?php endif; ?>

            <?php if(count($teacher_courses) > 0): ?>
                <form method="POST" action="">
                    <!-- Course Selection -->
                    <div class="form-group">
                        <label>Select Course <span class="required">*</span></label>
                        <div class="course-selection">
                            <?php foreach($teacher_courses as $course): 
                                $is_checked = (isset($_POST['course_id']) && $_POST['course_id'] == $course['id']) ? 'checked' : '';
                            ?>
                                <div class="radio-container">
                                    <input type="radio" 
                                           id="course_<?php echo $course['id']; ?>" 
                                           name="course_id" 
                                           value="<?php echo $course['id']; ?>" 
                                           class="radio-input"
                                           <?php echo $is_checked; ?>
                                           required>
                                    <label for="course_<?php echo $course['id']; ?>" class="course-option">
                                        <div class="radio-custom"></div>
                                        <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                                        <span class="course-title"><?php echo htmlspecialchars($course['title']); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <span class="form-hint">Choose which course this assignment is for</span>
                    </div>

                    <div class="form-group">
                        <label for="title">Assignment Title <span class="required">*</span></label>
                        <input type="text" id="title" name="title" required 
                               placeholder="e.g., Midterm Exam, Research Paper, Final Project"
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="description">Description <span class="required">*</span></label>
                        <textarea id="description" name="description" required 
                                  placeholder="Describe what students need to do..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <span class="form-hint">Explain the assignment requirements and objectives</span>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="max_points">Maximum Points <span class="required">*</span></label>
                            <input type="number" id="max_points" name="max_points" required 
                                   min="1" max="1000" value="<?php echo isset($_POST['max_points']) ? htmlspecialchars($_POST['max_points']) : '100'; ?>">
                            <span class="form-hint">Total points for this assignment</span>
                        </div>

                        <div class="form-group">
                            <label for="due_date">Due Date <span class="required">*</span></label>
                            <input type="datetime-local" id="due_date" name="due_date" required 
                                   value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                            <span class="form-hint">When students must submit by</span>
                        </div>
                    </div>

                    <div class="btn-group">
                        <button type="submit" name="create_assignment" class="btn-submit">
                            📝 Create Assignment
                        </button>
                        <a href="../dashboard.php" class="btn-cancel">Cancel</a>
                    </div>
                </form>
                
            <?php else: ?>
                <!-- No courses message -->
                <div class="no-courses-message">
                    <p style="font-size: 1.2rem; margin-bottom: 20px; color: #2c3e50;">📚 You don't have any courses yet!</p>
                    <p style="color: #666; margin-bottom: 30px;">
                        You need to create a course before you can create assignments.
                    </p>
                    <div style="display: flex; gap: 15px; justify-content: center;">
                        <a href="../courses/create_course.php" class="btn-submit" style="text-decoration: none; padding: 12px 25px;">
                            ➕ Create Your First Course
                        </a>
                        <a href="../dashboard.php" class="btn-cancel" style="text-decoration: none;">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>