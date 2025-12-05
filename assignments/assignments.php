<?php
session_start();
require_once '../database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get assignments based on role
$assignments = [];
$page_title = '';

if($role === 'teacher') {
    $page_title = 'Mes Devoirs';
    
    $query = "
        SELECT a.*, c.title as course_title, c.course_code
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = ?
        ORDER BY a.due_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} else {
    $page_title = 'Mes Devoirs';
    
    $query = "
        SELECT a.*, c.title as course_title, c.course_code,
               u.username as teacher_name
        FROM assignments a
        JOIN courses c ON a.course_id = c.id
        JOIN users u ON c.teacher_id = u.id
        JOIN enrollments e ON c.id = e.course_id
        WHERE e.student_id = ?
        ORDER BY a.due_date ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $assignments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// French month names
$french_months = [
    'Jan' => 'Jan', 'Feb' => 'Fév', 'Mar' => 'Mar', 'Apr' => 'Avr',
    'May' => 'Mai', 'Jun' => 'Juin', 'Jul' => 'Juil', 'Aug' => 'Août',
    'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Déc'
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - DigitalVillage</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .page-header {
            margin-bottom: 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .page-header h1 {
            color: #27ae60;
            font-size: 36px;
            margin: 0;
        }
        
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .assignment-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            position: relative;
        }
        
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        
        .assignment-card-header {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            color: white;
            padding: 20px;
        }
        
        .assignment-course {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .course-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
        }
        
        .assignment-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin: 10px 0;
            line-height: 1.4;
        }
        
        .assignment-card-body {
            padding: 25px;
        }
        
        .assignment-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }
        
        .info-item i {
            color: #27ae60;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }
        
        .due-date {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .due-date.upcoming {
            background: #fff3e0;
            color: #e67e22;
        }
        
        .due-date.past-due {
            background: #ffebee;
            color: #e74c3c;
        }
        
        .due-date.submitted {
            background: #e8f5e9;
            color: #27ae60;
        }
        
        .assignment-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-view {
            flex: 1;
            background: #27ae60;
            color: white;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn-view:hover {
            background: #219150;
        }
        
        .btn-submit {
            background: #3498db;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn-submit:hover {
            background: #2980b9;
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            margin-top: 30px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #ddd;
        }
        
        .empty-state h2 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .empty-state p {
            color: #888;
            max-width: 500px;
            margin: 0 auto 25px;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .assignments-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }
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
            <a href="../index.php">Accueil</a>
            <a href="../dashboard.php">Tableau de bord</a>
            <a href="../courses/courses.php">Mes Cours</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($username); ?>
            </span>
            <a href="assignments.php" class="btn-orange">Devoirs</a>
            <a href="../logout.php" class="btn-outline">Déconnexion</a>
        </div>
    </nav>

    <div class="page-container">
        <!-- Header -->
        <div class="page-header">
            <h1><?php echo $page_title; ?></h1>
            
            <?php if($role === 'teacher'): ?>
                <a href="create_assignment.php" class="btn-orange" style="text-decoration: none;">
                    ➕ Créer un Devoir
                </a>
            <?php endif; ?>
        </div>

        <!-- Assignments List -->
        <?php if(count($assignments) > 0): ?>
            <div class="assignments-grid">
                <?php foreach($assignments as $assignment): 
                    $due_date = strtotime($assignment['due_date']);
                    $now = time();
                    
                    if($due_date < $now) {
                        $status = 'En retard';
                        $status_class = 'past-due';
                    } elseif(($due_date - $now) < (7 * 24 * 60 * 60)) {
                        $status = 'Bientôt dû';
                        $status_class = 'upcoming';
                    } else {
                        $status = 'À venir';
                        $status_class = 'upcoming';
                    }
                    
                    // Format date in French
                    $formatted_date = date('M j, Y H:i', strtotime($assignment['due_date']));
                    foreach($french_months as $en => $fr) {
                        $formatted_date = str_replace($en, $fr, $formatted_date);
                    }
                ?>
                    <div class="assignment-card">
                        <div class="assignment-card-header">
                            <div class="assignment-course">
                                <span class="course-badge"><?php echo htmlspecialchars($assignment['course_code']); ?></span>
                                <span class="due-date <?php echo $status_class; ?>"><?php echo $status; ?></span>
                            </div>
                            <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                        </div>
                        
                        <div class="assignment-card-body">
                            <div class="assignment-info">
                                <div class="info-item">
                                    <i>📚</i>
                                    <span><?php echo htmlspecialchars($assignment['course_title']); ?></span>
                                </div>
                                
                                <?php if($role === 'student' && isset($assignment['teacher_name'])): ?>
                                    <div class="info-item">
                                        <i>👨‍🏫</i>
                                        <span>Enseignant : <?php echo htmlspecialchars($assignment['teacher_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="info-item">
                                    <i>📅</i>
                                    <span>À rendre : <?php echo $formatted_date; ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <i>🏆</i>
                                    <span>Points : <?php echo $assignment['max_points']; ?></span>
                                </div>
                            </div>
                            
                            <p style="color: #666; line-height: 1.5; margin-bottom: 15px; font-size: 0.95rem;">
                                <?php 
                                    $description = htmlspecialchars($assignment['description']);
                                    echo strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description;
                                ?>
                            </p>
                            
                            <div class="assignment-actions">
                                <a href="view_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-view">
                                    Voir les détails
                                </a>
                                
                                <?php if($role === 'student' && $due_date > $now): ?>
                                    <a href="submit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn-submit">
                                        Soumettre
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <?php echo $role === 'teacher' ? '📝' : '📚'; ?>
                </div>
                <h2>Aucun devoir pour le moment</h2>
                <p>
                    <?php 
                        if($role === 'teacher') {
                            echo 'Vous n\'avez pas encore créé de devoirs. Commencez par créer votre premier devoir !';
                        } else {
                            echo 'Vous n\'avez pas encore de devoirs. Revenez bientôt ou demandez à votre enseignant.';
                        }
                    ?>
                </p>
                <div style="margin-top: 25px;">
                    <?php if($role === 'teacher'): ?>
                        <a href="create_assignment.php" class="btn-orange" style="display: inline-block; text-decoration: none;">
                            ➕ Créer votre premier devoir
                        </a>
                    <?php else: ?>
                        <a href="../courses/courses.php" class="btn-orange" style="display: inline-block; text-decoration: none;">
                            📚 Parcourir les cours
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
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