<?php
session_start();

// Check if user is teacher
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

require_once '../database.php';

$user_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if($course_id == 0) {
    header("Location: courses.php");
    exit();
}

// Verify teacher owns this course
$stmt = $conn->prepare("SELECT title FROM courses WHERE id = ? AND teacher_id = ?");
$stmt->bind_param("ii", $course_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: courses.php");
    exit();
}

$course = $result->fetch_assoc();
$error = '';
$success = '';

// Handle file upload
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_material'])) {
    $title = trim($_POST['material_title']);
    $description = trim($_POST['material_description']);
    
    if(empty($title)) {
        $error = "Please provide a title for the material.";
    } elseif(!isset($_FILES['material_file']) || $_FILES['material_file']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please select a file to upload.";
    } else {
        $file = $_FILES['material_file'];
        
        // Check for upload errors
        if($file['error'] !== UPLOAD_ERR_OK) {
            $error = "File upload failed. Error code: " . $file['error'];
        } else {
            // Validate file size (max 10MB)
            $max_size = 10 * 1024 * 1024; // 10MB in bytes
            if($file['size'] > $max_size) {
                $error = "File is too large. Maximum size is 10MB.";
            } else {
                // Get file extension
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Allowed extensions
                $allowed_extensions = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'zip', 'rar'];
                
                if(!in_array($file_ext, $allowed_extensions)) {
                    $error = "Invalid file type. Allowed: " . implode(', ', $allowed_extensions);
                } else {
                    // Generate unique filename
                    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
                    
                    // Get the absolute path to project root and create uploads directory
                    $project_root = dirname(dirname(__FILE__)); // Goes up from courses/ to project_php/
                    $upload_dir = $project_root . '/uploads/';
                    
                    // Create uploads folder if it doesn't exist
                    if(!file_exists($upload_dir)) {
                        if(!mkdir($upload_dir, 0777, true)) {
                            $error = "Failed to create uploads directory. Path: " . $upload_dir;
                        }
                    }
                    
                    if(empty($error)) {
                        $upload_path = $upload_dir . $new_filename;
                        
                        // Move uploaded file
                        if(move_uploaded_file($file['tmp_name'], $upload_path)) {
                            // Save to database as announcement with file
                            $original_name = htmlspecialchars($file['name']);
                            $file_size_kb = round($file['size']/1024, 2);
                            $content = $description . "\n\n📎 File: " . $original_name . " (" . $file_size_kb . " KB)\nDownload: uploads/" . $new_filename;
                            
                            $stmt = $conn->prepare("INSERT INTO announcements (course_id, title, content, posted_by, created_at) VALUES (?, ?, ?, ?, NOW())");
                            $stmt->bind_param("issi", $course_id, $title, $content, $user_id);
                            
                            if($stmt->execute()) {
                                $success = "Material uploaded successfully!";
                                header("refresh:2;url=view_course.php?id=" . $course_id);
                            } else {
                                $error = "Failed to save material information.";
                                unlink($upload_path); // Delete uploaded file
                            }
                        } else {
                            $error = "Failed to move uploaded file. Check permissions on: " . $upload_dir;
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Material - DigitalVillage</title>
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
            font-size: 16px;
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
        
        .form-group input[type="text"],
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
            min-height: 100px;
            resize: vertical;
        }
        
        .file-upload-area {
            border: 3px dashed #e0e0e0;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            background: #fafafa;
        }
        
        .file-upload-area:hover {
            border-color: #27ae60;
            background: #f0f9f4;
        }
        
        .file-upload-area.drag-over {
            border-color: #27ae60;
            background: #e8f5e9;
        }
        
        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .file-input {
            display: none;
        }
        
        .file-info {
            margin-top: 15px;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 8px;
            display: none;
        }
        
        .file-info.show {
            display: block;
        }
        
        .allowed-types {
            margin-top: 10px;
            font-size: 14px;
            color: #666;
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
        
        .debug-info {
            margin-top: 10px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            font-size: 12px;
            font-family: monospace;
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
        <div class="page-header">
            <h1>📎 Upload Material</h1>
            <p>Share files and resources with your class: <strong><?php echo htmlspecialchars($course['title']); ?></strong></p>
        </div>

        <div class="form-card">
            <?php if($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                    <div class="debug-info">
                        Upload directory should be at: <?php echo dirname(dirname(__FILE__)) . '/uploads/'; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> Redirecting...</div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="material_title">Material Title *</label>
                    <input type="text" id="material_title" name="material_title" required 
                           placeholder="e.g., Lecture Notes - Chapter 3">
                </div>

                <div class="form-group">
                    <label for="material_description">Description</label>
                    <textarea id="material_description" name="material_description" 
                              placeholder="Optional: Add context or instructions for this material..."></textarea>
                </div>

                <div class="form-group">
                    <label>Upload File *</label>
                    <div class="file-upload-area" id="fileUploadArea">
                        <div class="upload-icon">📁</div>
                        <p><strong>Click to browse</strong> or drag and drop file here</p>
                        <p class="allowed-types">
                            Allowed: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, JPG, PNG, ZIP (Max: 10MB)
                        </p>
                    </div>
                    <input type="file" id="material_file" name="material_file" class="file-input" required>
                    <div class="file-info" id="fileInfo">
                        <strong>Selected:</strong> <span id="fileName"></span> (<span id="fileSize"></span>)
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" name="upload_material" class="btn-submit">
                        ✓ Upload Material
                    </button>
                    <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn-cancel">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('material_file');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        // Click to upload
        fileUploadArea.addEventListener('click', () => fileInput.click());

        // File selected
        fileInput.addEventListener('change', (e) => {
            if(e.target.files.length > 0) {
                const file = e.target.files[0];
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
                fileInfo.classList.add('show');
            }
        });

        // Drag and drop
        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.classList.add('drag-over');
        });

        fileUploadArea.addEventListener('dragleave', () => {
            fileUploadArea.classList.remove('drag-over');
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.classList.remove('drag-over');
            
            if(e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                const file = e.dataTransfer.files[0];
                fileName.textContent = file.name;
                fileSize.textContent = (file.size / 1024).toFixed(2) + ' KB';
                fileInfo.classList.add('show');
            }
        });
    </script>
</body>
</html>