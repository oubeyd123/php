<?php
session_start();

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

require_once '../database.php';

$user_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';
$selected_file_name = '';

if($assignment_id == 0) {
    header("Location: assignments.php");
    exit();
}

// Get assignment details
$stmt = $conn->prepare("
    SELECT a.*, c.title as course_title, c.course_code,
           c.teacher_id, u.username as teacher_name
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN users u ON c.teacher_id = u.id
    WHERE a.id = ?
");
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("Location: assignments.php");
    exit();
}

$assignment = $result->fetch_assoc();

// Check if student is enrolled
$stmt = $conn->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
$stmt->bind_param("ii", $assignment['course_id'], $user_id);
$stmt->execute();
if($stmt->get_result()->num_rows == 0) {
    header("Location: assignments.php");
    exit();
}

// Check if assignment is past due
$due_date = strtotime($assignment['due_date']);
$now = time();
$is_past_due = $due_date < $now;

// Check for existing submission
$stmt = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->bind_param("ii", $assignment_id, $user_id);
$stmt->execute();
$existing_submission = $stmt->get_result()->fetch_assoc();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $submission_text = trim($_POST['submission_text'] ?? '');
    $file_path = '';
    
    // Check if at least one field is filled
    if(empty($submission_text) && empty($_FILES['assignment_file']['name'])) {
        $error = "Veuillez soit écrire votre réponse soit téléverser un fichier.";
    } else {
        // Handle file upload
        if(!empty($_FILES['assignment_file']['name'])) {
            $upload_dir = "../uploads/assignments/";
            if(!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . $_SESSION['username'] . '_' . basename($_FILES['assignment_file']['name']);
            $target_file = $upload_dir . $file_name;
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Check file size
            if($_FILES['assignment_file']['size'] > 10 * 1024 * 1024) {
                $error = "Le fichier est trop volumineux. Maximum 10MB.";
            }
            // Check file type
            elseif(!in_array($file_type, ['pdf', 'doc', 'docx', 'txt', 'jpg', 'jpeg', 'png', 'zip'])) {
                $error = "Type de fichier non autorisé. Formats: PDF, DOC, DOCX, TXT, JPG, PNG, ZIP.";
            }
            // Upload file
            elseif(move_uploaded_file($_FILES['assignment_file']['tmp_name'], $target_file)) {
                $file_path = $target_file;
                $selected_file_name = htmlspecialchars($_FILES['assignment_file']['name']);
            } else {
                $error = "Erreur lors du téléversement du fichier.";
            }
        }
        
        // Save submission if no errors
        if(empty($error)) {
            if($existing_submission) {
                // Update existing submission (sans is_late)
                $stmt = $conn->prepare("
                    UPDATE submissions 
                    SET submission_text = ?, file_path = ?, submitted_at = NOW()
                    WHERE id = ?
                ");
                $file_path_to_save = !empty($file_path) ? $file_path : $existing_submission['file_path'];
                $stmt->bind_param("ssi", $submission_text, $file_path_to_save, $existing_submission['id']);
            } else {
                // Insert new submission (sans is_late)
                $stmt = $conn->prepare("
                    INSERT INTO submissions (assignment_id, student_id, submission_text, file_path, submitted_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->bind_param("iiss", $assignment_id, $user_id, $submission_text, $file_path);
            }
            
            if($stmt->execute()) {
                $success = $existing_submission ? "Soumission mise à jour avec succès !" : "Devoir soumis avec succès !";
                // Redirect after 2 seconds
                header("refresh:2;url=view_assignment.php?id=" . $assignment_id);
            } else {
                $error = "Erreur lors de la soumission. Veuillez réessayer.";
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre le Devoir - DigitalVillage</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .page-header {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            color: white;
            padding: 30px;
            border-radius: 18px;
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .page-header p {
            opacity: 0.9;
            font-size: 16px;
        }
        
        .course-info {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .form-card {
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .deadline-info {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3498db;
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .deadline-warning {
            background: #fff3e0;
            color: #e67e22;
            border-left-color: #e67e22;
        }
        
        .deadline-danger {
            background: #ffebee;
            color: #e74c3c;
            border-left-color: #e74c3c;
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
        
        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            min-height: 200px;
            resize: vertical;
            transition: border-color 0.3s;
        }
        
        .form-group textarea:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .file-upload-box {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #f8f9fa;
        }
        
        .file-upload-box h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .file-input-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .file-name-display {
            padding: 8px 12px;
            background: #e8f5e9;
            border-radius: 6px;
            color: #27ae60;
            font-weight: 500;
        }
        
        .existing-submission {
            background: #e3f2fd;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #2196f3;
        }
        
        .existing-submission h3 {
            color: #2196f3;
            margin-bottom: 10px;
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
        
        .instructions-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .instructions-box h3 {
            color: #27ae60;
            margin-bottom: 15px;
        }
        
        .instruction-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            color: #666;
        }
        
        .instruction-number {
            background: #27ae60;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: bold;
            flex-shrink: 0;
        }
        
        .file-types {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .file-type-tag {
            background: #e8f5e9;
            color: #27ae60;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .late-warning {
            background: #fff3e0;
            color: #e67e22;
            padding: 10px 15px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 3px solid #e67e22;
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
            <a href="../index.php">Accueil</a>
            <a href="../dashboard.php">Tableau de bord</a>
            <a href="assignments.php">Devoirs</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="../profile.php" class="btn-orange">Profil</a>
            <a href="../logout.php" class="btn-outline">Déconnexion</a>
        </div>
    </nav>

    <div class="page-container">
        <div class="page-header">
            <h1><?php echo $existing_submission ? 'Modifier votre soumission' : 'Soumettre votre travail'; ?></h1>
            <p>Devoir : <?php echo htmlspecialchars($assignment['title']); ?></p>
            <div class="course-info">
                <?php echo htmlspecialchars($assignment['course_title']); ?> (<?php echo htmlspecialchars($assignment['course_code']); ?>)
            </div>
        </div>

        <?php 
        // Display deadline warning
        $time_left = $due_date - $now;
        $hours_left = floor($time_left / 3600);
        $days_left = floor($hours_left / 24);
        
        if($is_past_due) {
            echo '<div class="deadline-info deadline-danger">
                    ⚠️ <strong>Date limite dépassée</strong> • La date de rendu était le ' . 
                    date('d/m/Y à H:i', strtotime($assignment['due_date'])) . 
                    '.<br>Vous pouvez toujours soumettre, mais votre travail sera marqué comme tardif.
                  </div>';
        } elseif($days_left == 0 && $hours_left < 24) {
            echo '<div class="deadline-info deadline-warning">
                    ⏰ <strong>Attention</strong> • Il reste ' . $hours_left . ' heure' . ($hours_left > 1 ? 's' : '') . 
                    ' pour soumettre ce devoir.
                  </div>';
        } elseif($days_left < 3) {
            echo '<div class="deadline-info deadline-warning">
                    📅 <strong>Date limite proche</strong> • Il reste ' . $days_left . ' jour' . ($days_left > 1 ? 's' : '') . 
                    ' pour soumettre ce devoir.
                  </div>';
        } else {
            echo '<div class="deadline-info">
                    📅 <strong>À rendre avant le</strong> ' . 
                    date('d/m/Y à H:i', strtotime($assignment['due_date'])) . 
                    '
                  </div>';
        }
        ?>

        <div class="form-card">
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> Redirection...</div>
            <?php endif; ?>

            <?php if($existing_submission): ?>
                <div class="existing-submission">
                    <h3>📋 Votre soumission actuelle</h3>
                    <p><strong>Soumise le :</strong> <?php echo date('d/m/Y à H:i', strtotime($existing_submission['submitted_at'])); ?></p>
                    <?php if($existing_submission['file_path']): ?>
                        <p><strong>Fichier joint :</strong> <?php echo basename($existing_submission['file_path']); ?></p>
                    <?php endif; ?>
                    <?php if($is_past_due): ?>
                        <div class="late-warning">⚠️ Cette soumission sera marquée comme tardive</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="instructions-box">
                <h3>📝 Comment soumettre votre travail</h3>
                
                <div class="instruction-item">
                    <div class="instruction-number">1</div>
                    <div>Rédigez votre réponse dans la zone de texte ci-dessous</div>
                </div>
                
                <div class="instruction-item">
                    <div class="instruction-number">2</div>
                    <div>OU téléversez un fichier contenant votre travail</div>
                </div>
                
                <div class="instruction-item">
                    <div class="instruction-number">3</div>
                    <div>Vous pouvez aussi combiner les deux options</div>
                </div>
                
                <div class="instruction-item">
                    <div class="instruction-number">4</div>
                    <div>Cliquez sur "Soumettre" pour finaliser</div>
                </div>
                
                <div style="margin-top: 15px;">
                    <strong>Formats de fichiers acceptés :</strong>
                    <div class="file-types">
                        <span class="file-type-tag">PDF</span>
                        <span class="file-type-tag">DOC/DOCX</span>
                        <span class="file-type-tag">TXT</span>
                        <span class="file-type-tag">JPG/PNG</span>
                        <span class="file-type-tag">ZIP</span>
                    </div>
                    <p style="margin-top: 10px; color: #666; font-size: 14px;">
                        <strong>Taille maximum :</strong> 10MB
                    </p>
                </div>
            </div>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="submission_text">Votre réponse écrite</label>
                    <textarea id="submission_text" name="submission_text" 
                              placeholder="Écrivez votre réponse ici. Vous pouvez inclure des explications, des calculs, du code, ou toute autre information pertinente..."><?php 
                        echo isset($_POST['submission_text']) ? htmlspecialchars($_POST['submission_text']) : 
                            ($existing_submission ? htmlspecialchars($existing_submission['submission_text']) : ''); 
                    ?></textarea>
                    <p style="color: #666; font-size: 14px; margin-top: 5px;">
                        Laissez vide si vous soumettez uniquement un fichier.
                    </p>
                </div>

                <div class="form-group">
                    <label for="assignment_file">Téléverser un fichier (optionnel)</label>
                    <div class="file-upload-box">
                        <h4>Sélectionnez votre fichier</h4>
                        <div class="file-input-wrapper">
                            <input type="file" id="assignment_file" name="assignment_file" 
                                   style="flex: 1; padding: 10px; border: 2px solid #ddd; border-radius: 6px;"
                                   accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.zip">
                        </div>
                        <?php if(!empty($selected_file_name)): ?>
                            <div class="file-name-display">
                                📎 Fichier sélectionné : <?php echo $selected_file_name; ?>
                            </div>
                        <?php endif; ?>
                        <p style="color: #666; font-size: 14px; margin-top: 10px;">
                            Si vous avez déjà soumis un fichier et que vous en téléversez un nouveau, 
                            l'ancien sera remplacé.
                        </p>
                    </div>
                </div>

                <?php if($is_past_due): ?>
                    <div class="late-warning">
                        ⚠️ <strong>Attention :</strong> La date limite est dépassée. Votre soumission sera considérée comme tardive.
                    </div>
                <?php endif; ?>

                <div class="btn-group">
                    <button type="submit" name="submit_assignment" class="btn-submit">
                        <?php if($existing_submission): ?>
                            ✏️ Mettre à jour la soumission
                        <?php else: ?>
                            ✓ Soumettre le devoir
                        <?php endif; ?>
                    </button>
                    <a href="view_assignment.php?id=<?php echo $assignment_id; ?>" class="btn-cancel">
                        Annuler et retourner
                    </a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>