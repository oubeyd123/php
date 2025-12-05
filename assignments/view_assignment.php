<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../database.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($assignment_id == 0) {
    header("Location: assignments.php");
    exit();
}

// Get assignment details with course info
$stmt = $conn->prepare("
    SELECT a.*, c.title as course_title, c.course_code, c.teacher_id,
           u.username as teacher_name
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
$is_teacher = ($assignment['teacher_id'] == $user_id);

// Check if student is enrolled
$is_enrolled = false;
if($role === 'student') {
    $stmt = $conn->prepare("SELECT id FROM enrollments WHERE course_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $assignment['course_id'], $user_id);
    $stmt->execute();
    $is_enrolled = $stmt->get_result()->num_rows > 0;
    
    if(!$is_enrolled && !$is_teacher) {
        header("Location: assignments.php");
        exit();
    }
}

// Get submission if exists (for students)
$submission = null;
if($role === 'student') {
    $stmt = $conn->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
    $stmt->bind_param("ii", $assignment_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $submission = $result->fetch_assoc();
    }
}

// Get all submissions (for teachers)
$submissions = [];
if($is_teacher) {
    $stmt = $conn->prepare("
        SELECT s.*, u.username as student_name
        FROM submissions s
        JOIN users u ON s.student_id = u.id
        WHERE s.assignment_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// French month names
$french_months = [
    'Jan' => 'Jan', 'Feb' => 'Fév', 'Mar' => 'Mar', 'Apr' => 'Avr',
    'May' => 'Mai', 'Jun' => 'Juin', 'Jul' => 'Juil', 'Aug' => 'Août',
    'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Déc'
];

$due_date = strtotime($assignment['due_date']);
$now = time();
$is_past_due = $due_date < $now;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($assignment['title']); ?> - DigitalVillage</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .assignment-header {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            color: white;
            padding: 40px;
            border-radius: 18px;
            margin-bottom: 30px;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .breadcrumb a {
            color: white;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .course-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .assignment-header h1 {
            font-size: 36px;
            margin-bottom: 20px;
        }
        
        .assignment-meta {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(255,255,255,0.15);
            padding: 10px 20px;
            border-radius: 8px;
        }
        
        .content-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            align-items: start;
        }
        
        .main-content {
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .section-title {
            color: #27ae60;
            font-size: 24px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .assignment-description {
            color: #666;
            line-height: 1.8;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .sidebar {
            position: sticky;
            top: 20px;
        }
        
        .sidebar-card {
            background: white;
            padding: 25px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }
        
        .sidebar-card h3 {
            color: #27ae60;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #2c3e50;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-submitted {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .status-pending {
            background: #fff3e0;
            color: #e67e22;
        }
        
        .status-graded {
            background: #e3f2fd;
            color: #2196f3;
        }
        
        .status-late {
            background: #ffebee;
            color: #e74c3c;
        }
        
        .action-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #27ae60;
            color: white;
            text-align: center;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 10px;
            transition: background 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .action-btn:hover {
            background: #219150;
        }
        
        .action-btn.secondary {
            background: #3498db;
        }
        
        .action-btn.secondary:hover {
            background: #2980b9;
        }
        
        .action-btn.danger {
            background: #e74c3c;
        }
        
        .action-btn.danger:hover {
            background: #c0392b;
        }
        
        .submission-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid #27ae60;
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .student-name {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .submission-date {
            color: #999;
            font-size: 14px;
        }
        
        .submissions-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-info {
            background: #e3f2fd;
            color: #1976d2;
            border: 1px solid #bbdefb;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #27ae60;
            border: 1px solid #c8e6c9;
        }
        
        .alert-warning {
            background: #fff3e0;
            color: #e67e22;
            border: 1px solid #ffe0b2;
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
            <a href="../index.php">Accueil</a>
            <a href="../dashboard.php">Tableau de bord</a>
            <a href="assignments.php">Devoirs</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($username); ?>
            </span>
            <a href="../profile.php" class="btn-orange">Profil</a>
            <a href="../logout.php" class="btn-outline">Déconnexion</a>
        </div>
    </nav>

    <div class="page-container">
        <div class="assignment-header">
            <div class="breadcrumb">
                <a href="../courses/view_course.php?id=<?php echo $assignment['course_id']; ?>">
                    <?php echo htmlspecialchars($assignment['course_title']); ?>
                </a>
                <span>›</span>
                <span>Devoir</span>
            </div>
            
            <span class="course-badge"><?php echo htmlspecialchars($assignment['course_code']); ?></span>
            <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            
            <div class="assignment-meta">
                <div class="meta-item">
                    <span>👨‍🏫</span>
                    <span><?php echo htmlspecialchars($assignment['teacher_name']); ?></span>
                </div>
                <div class="meta-item">
                    <span>📅</span>
                    <span>À rendre : <?php 
                        $date = date('j M Y, H:i', strtotime($assignment['due_date']));
                        foreach($french_months as $en => $fr) {
                            $date = str_replace($en, $fr, $date);
                        }
                        echo $date;
                    ?></span>
                </div>
                <div class="meta-item">
                    <span>🏆</span>
                    <span><?php echo $assignment['max_points']; ?> points</span>
                </div>
            </div>
        </div>

        <div class="content-wrapper">
            <div class="main-content">
                <?php if($is_past_due && $role === 'student' && !$submission): ?>
                    <div class="alert alert-warning">
                        ⚠️ <strong>Attention :</strong> La date limite de ce devoir est dépassée.
                    </div>
                <?php endif; ?>
                
                <?php if($submission): ?>
                    <div class="alert alert-success">
                        ✓ <strong>Soumis :</strong> Vous avez soumis ce devoir le <?php 
                            $sub_date = date('j M Y à H:i', strtotime($submission['submitted_at']));
                            foreach($french_months as $en => $fr) {
                                $sub_date = str_replace($en, $fr, $sub_date);
                            }
                            echo $sub_date;
                        ?>
                    </div>
                <?php endif; ?>

                <h2 class="section-title">📋 Description du Devoir</h2>
                <div class="assignment-description">
                    <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                </div>

                <?php if($is_teacher): ?>
                    <h2 class="section-title">📊 Soumissions des Étudiants (<?php echo count($submissions); ?>)</h2>
                    
                    <?php if(count($submissions) > 0): ?>
                        <div class="submissions-list">
                            <?php foreach($submissions as $sub): ?>
                                <div class="submission-card">
                                    <div class="submission-header">
                                        <span class="student-name">👤 <?php echo htmlspecialchars($sub['student_name']); ?></span>
                                        <span class="submission-date">
                                            <?php 
                                                $sub_date = date('j M Y, H:i', strtotime($sub['submitted_at']));
                                                foreach($french_months as $en => $fr) {
                                                    $sub_date = str_replace($en, $fr, $sub_date);
                                                }
                                                echo $sub_date;
                                            ?>
                                        </span>
                                    </div>
                                    
                                    <?php if($sub['submission_text']): ?>
                                        <p style="color: #666; margin: 10px 0;">
                                            <?php echo nl2br(htmlspecialchars(substr($sub['submission_text'], 0, 150))); ?>
                                            <?php if(strlen($sub['submission_text']) > 150) echo '...'; ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <?php if($sub['grade']): ?>
                                        <span class="status-badge status-graded">
                                            ✓ Noté : <?php echo $sub['grade']; ?>/<?php echo $assignment['max_points']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">En attente de notation</span>
                                    <?php endif; ?>
                                    
                                    <a href="grade_submission.php?id=<?php echo $sub['id']; ?>" 
                                       style="display: inline-block; margin-top: 10px; color: #27ae60; text-decoration: none; font-weight: 600;">
                                        Voir et noter →
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            📭 Aucune soumission pour le moment. Les étudiants n'ont pas encore soumis de travaux.
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if($submission && $role === 'student'): ?>
                    <h2 class="section-title">📝 Votre Soumission</h2>
                    <div class="submission-card">
                        <p style="color: #666; line-height: 1.6;">
                            <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                        </p>
                        
                        <?php if($submission['file_path']): ?>
                            <p style="margin-top: 15px;">
                                <strong>📎 Fichier joint :</strong> 
                                <a href="../download.php?file=<?php echo basename($submission['file_path']); ?>" 
                                   style="color: #27ae60; text-decoration: none;">
                                    Télécharger le fichier
                                </a>
                            </p>
                        <?php endif; ?>
                        
                        <?php if($submission['grade']): ?>
                            <div style="margin-top: 20px; padding: 15px; background: #e3f2fd; border-radius: 8px;">
                                <strong style="color: #2196f3;">Note : <?php echo $submission['grade']; ?>/<?php echo $assignment['max_points']; ?></strong>
                                
                                <?php if($submission['feedback']): ?>
                                    <p style="margin-top: 10px; color: #666;">
                                        <strong>Commentaire de l'enseignant :</strong><br>
                                        <?php echo nl2br(htmlspecialchars($submission['feedback'])); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p style="margin-top: 15px; color: #999; font-style: italic;">
                                ⏳ En attente de notation par l'enseignant
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="sidebar">
                <?php if($role === 'student'): ?>
                    <div class="sidebar-card">
                        <h3>Statut</h3>
                        <div class="info-row">
                            <span class="info-label">Statut :</span>
                            <span class="info-value">
                                <?php 
                                    if($submission) {
                                        if($submission['grade']) {
                                            echo '<span class="status-badge status-graded">Noté</span>';
                                        } else {
                                            echo '<span class="status-badge status-submitted">Soumis</span>';
                                        }
                                    } else if($is_past_due) {
                                        echo '<span class="status-badge status-late">En retard</span>';
                                    } else {
                                        echo '<span class="status-badge status-pending">Non soumis</span>';
                                    }
                                ?>
                            </span>
                        </div>
                        
                        <?php if($submission && $submission['grade']): ?>
                            <div class="info-row">
                                <span class="info-label">Note :</span>
                                <span class="info-value"><?php echo $submission['grade']; ?>/<?php echo $assignment['max_points']; ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="sidebar-card">
                        <h3>Actions</h3>
                        <?php if(!$submission && !$is_past_due): ?>
                            <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="action-btn">
                                ✍️ Soumettre le Devoir
                            </a>
                        <?php elseif($submission && !$submission['grade']): ?>
                            <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="action-btn secondary">
                                ✏️ Modifier la Soumission
                            </a>
                        <?php endif; ?>
                        
                        <a href="../courses/view_course.php?id=<?php echo $assignment['course_id']; ?>" class="action-btn secondary">
                            ← Retour au Cours
                        </a>
                    </div>
                <?php endif; ?>

                <?php if($is_teacher): ?>
                    <div class="sidebar-card">
                        <h3>Statistiques</h3>
                        <div class="info-row">
                            <span class="info-label">Soumissions :</span>
                            <span class="info-value"><?php echo count($submissions); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Notées :</span>
                            <span class="info-value">
                                <?php 
                                    $graded = array_filter($submissions, function($s) { return $s['grade'] !== null; });
                                    echo count($graded);
                                ?>
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">En attente :</span>
                            <span class="info-value">
                                <?php echo count($submissions) - count($graded); ?>
                            </span>
                        </div>
                    </div>

                    <div class="sidebar-card">
                        <h3>Actions</h3>
                        
                        <a href="../courses/view_course.php?id=<?php echo $assignment['course_id']; ?>" class="action-btn secondary">
                            ← Retour au Cours
                        </a>
                    </div>
                <?php endif; ?>

                <div class="sidebar-card">
                    <h3>Informations</h3>
                    <div class="info-row">
                        <span class="info-label">Créé le :</span>
                        <span class="info-value" style="font-size: 14px;">
                            <?php 
                                $created = date('j M Y', strtotime($assignment['created_at']));
                                foreach($french_months as $en => $fr) {
                                    $created = str_replace($en, $fr, $created);
                                }
                                echo $created;
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date limite :</span>
                        <span class="info-value" style="font-size: 14px;">
                            <?php 
                                $due = date('j M Y, H:i', strtotime($assignment['due_date']));
                                foreach($french_months as $en => $fr) {
                                    $due = str_replace($en, $fr, $due);
                                }
                                echo $due;
                            ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Points max :</span>
                        <span class="info-value"><?php echo $assignment['max_points']; ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="footer-grid">
            <div>
                <h2 class="footer-logo">DigitalVillage</h2>
                <p>Construire des salles de classe numériques indépendantes et résilientes pour les écoles.</p>
            </div>
            <div>
                <h4>Plateforme</h4>
                <a href="../index.php">Accueil</a>
                <a href="assignments.php">Devoirs</a>
                <a href="../courses/courses.php">Cours</a>
                <a href="../dashboard.php">Tableau de bord</a>
            </div>
            <div>
                <h4>Communauté</h4>
                <a href="#">À Propos</a>
                <a href="#">Support</a>
                <a href="#">Écoles</a>
                <a href="#">Événements</a>
            </div>
            <div>
                <h4>Légal</h4>
                <a href="#">Politique de Confidentialité</a>
                <a href="#">Conditions d'Utilisation</a>
            </div>
        </div>
        <p class="footer-bottom">© 2025 DigitalVillage — Tous Droits Réservés</p>
    </footer>
</body>
</html>