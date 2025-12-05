<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'database.php';

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get real statistics from database
$my_courses_count = 0;
$total_students = 0;
$assignments_count = 0;
$enrolled_courses_count = 0;
$assignments_due = 0;
$completed_assignments = 0;

try {
    if($role === 'teacher') {
        // Get teacher's course count
        $stmt = $conn->prepare("SELECT COUNT(*) as course_count FROM courses WHERE teacher_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $my_courses_count = $row['course_count'];
        
        // Get total students across all teacher's courses
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT e.student_id) as student_count 
            FROM courses c 
            LEFT JOIN enrollments e ON c.id = e.course_id 
            WHERE c.teacher_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_students = $row['student_count'] ?: 0;
        
        // Get assignments count for teacher's courses
        $stmt = $conn->prepare("
            SELECT COUNT(*) as assignment_count 
            FROM assignments a 
            JOIN courses c ON a.course_id = c.id 
            WHERE c.teacher_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $assignments_count = $row['assignment_count'] ?: 0;
        
    } elseif($role === 'student') {
        // Get student's enrolled course count
        $stmt = $conn->prepare("SELECT COUNT(*) as course_count FROM enrollments WHERE student_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $enrolled_courses_count = $row['course_count'];
        
        // Get assignments due count (assignments with due date in future)
        $stmt = $conn->prepare("
            SELECT COUNT(*) as assignments_due 
            FROM assignments a 
            JOIN enrollments e ON a.course_id = e.course_id 
            WHERE e.student_id = ? AND a.due_date > NOW()
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $assignments_due = $row['assignments_due'] ?: 0;
        
        // Get completed assignments count
        $completed_assignments = 0;
    }
    
} catch(Exception $e) {
    // Handle error silently or log it
    error_log("Dashboard statistics error: " . $e->getMessage());
}

// Set French month names
$french_months = [
    'Jan' => 'Jan', 'Feb' => 'Fév', 'Mar' => 'Mar', 'Apr' => 'Avr',
    'May' => 'Mai', 'Jun' => 'Juin', 'Jul' => 'Juil', 'Aug' => 'Août',
    'Sep' => 'Sep', 'Oct' => 'Oct', 'Nov' => 'Nov', 'Dec' => 'Déc'
];
$current_date = date('M j');
foreach($french_months as $en => $fr) {
    $current_date = str_replace($en, $fr, $current_date);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - DigitalVillage</title>
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
            flex-wrap: wrap;
        }
        
        .stat-box {
            flex: 1;
            min-width: 150px;
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
        
        .counter {
            font-weight: bold;
        }
        
        .badge-teacher {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .badge-student {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .badge-admin {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .status-up {
            color: #2ecc71;
        }
        
        .status-down {
            color: #e74c3c;
        }
        
        .status-neutral {
            color: #f39c12;
        }
        
        .profile-section {
            background: white;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .section-header {
            margin-bottom: 20px;
        }
        
        .section-title {
            color: #27ae60;
            font-size: 24px;
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
            <a href="index.php">Accueil</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($username); ?>
            </span>
            <a href="dashboard.php" class="btn-orange">Tableau de bord</a>
            <a href="logout.php" class="btn-outline">Déconnexion</a>
        </div>
    </nav>

    <div class="dashboard-container">
        <!-- Header Section -->
        <div class="dashboard-header">
            <h1>Bienvenue, <?php echo htmlspecialchars($username); ?> ! 👋</h1>
            <p>
                <?php 
                    if($role === 'teacher') {
                        echo 'Gérez vos cours et suivez les progrès de vos étudiants.';
                    } else {
                        echo 'Continuez votre parcours d\'apprentissage et suivez vos progrès.';
                    }
                ?>
            </p>
            <span class="user-badge 
                <?php 
                    if($role === 'teacher') echo 'badge-teacher';
                    elseif($role === 'admin') echo 'badge-admin';
                    else echo 'badge-student';
                ?>">
                <?php echo $role === 'teacher' ? '👨‍🏫 Enseignant' : ($role === 'admin' ? '👑 Administrateur' : '🎓 Étudiant'); ?>
            </span>
            
            <!-- Quick Stats -->
            <div class="quick-stats">
                <?php if($role === 'teacher'): ?>
                    <div class="stat-box">
                        <div class="stat-number counter"><?php echo $my_courses_count; ?></div>
                        <div class="stat-label">Mes Cours</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number counter"><?php echo $total_students; ?></div>
                        <div class="stat-label">Total Étudiants</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number counter"><?php echo $assignments_count; ?></div>
                        <div class="stat-label">Devoirs</div>
                    </div>
                <?php else: ?>
                    <div class="stat-box">
                        <div class="stat-number counter"><?php echo $enrolled_courses_count; ?></div>
                        <div class="stat-label">Cours Inscrits</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number counter"><?php echo $assignments_due; ?></div>
                        <div class="stat-label">Devoirs à Rendre</div>
                    </div>
                    <div class="stat-box">
                        <div class="stat-number counter"><?php echo $completed_assignments; ?></div>
                        <div class="stat-label">Terminés</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-grid">
            <?php if($role === 'teacher'): ?>
                <!-- Teacher Actions -->
                <div class="dashboard-card">
                    <div class="card-icon">➕</div>
                    <h3>Créer un Cours</h3>
                    <p>Démarrez un nouveau cours et invitez des étudiants à rejoindre votre classe.</p>
                    <a href="courses/create_course.php" class="card-button">Créer un Cours</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📚</div>
                    <h3>Mes Cours <span class="status-up">(<?php echo $my_courses_count; ?>)</span></h3>
                    <p>Consultez et gérez tous vos cours actifs et supports pédagogiques.</p>
                    <a href="courses/courses.php" class="card-button">Voir les Cours</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📝</div>
                    <h3>Devoirs <span class="status-neutral">(<?php echo $assignments_count; ?>)</span></h3>
                    <p>Créez des devoirs et évaluez les soumissions des étudiants.</p>
                    <?php if($assignments_count > 0): ?>
                        <a href="assignments/assignments.php" class="card-button">Gérer les Devoirs</a>
                    <?php else: ?>
                        <a href="assignments/create_assignment.php" class="card-button">Créer le Premier Devoir</a>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <!-- Student Actions -->
                <div class="dashboard-card">
                    <div class="card-icon">🔍</div>
                    <h3>Parcourir les Cours</h3>
                    <p>Découvrez et inscrivez-vous aux cours disponibles dans votre établissement.</p>
                    <a href="courses/courses.php" class="card-button">Parcourir les Cours</a>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📚</div>
                    <h3>Mes Cours <span class="status-up">(<?php echo $enrolled_courses_count; ?>)</span></h3>
                    <p>Accédez à vos cours inscrits et supports d'apprentissage.</p>
                    <?php if($enrolled_courses_count > 0): ?>
                        <a href="courses/courses.php" class="card-button">Mes Cours</a>
                    <?php else: ?>
                        <a href="courses/courses.php" class="card-button">Trouver des Cours</a>
                    <?php endif; ?>
                </div>
                
                <div class="dashboard-card">
                    <div class="card-icon">📝</div>
                    <h3>Devoirs 
                        <?php if($assignments_due > 0): ?>
                            <span class="status-down">(<?php echo $assignments_due; ?> à rendre)</span>
                        <?php else: ?>
                            <span class="status-up">(0 à rendre)</span>
                        <?php endif; ?>
                    </h3>
                    <p>Consultez les devoirs en attente et soumettez votre travail.</p>
                    <?php if($assignments_due > 0): ?>
                        <a href="assignments/assignments.php" class="card-button">Voir les Devoirs</a>
                    <?php else: ?>
                        <a href="assignments/assignments.php" class="card-button">Voir les Devoirs</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="dashboard-card">
                <div class="card-icon">👤</div>
                <h3>Paramètres du Profil</h3>
                <p>Mettez à jour vos informations personnelles et préférences.</p>
                <a href="profile.php" class="card-button">Modifier le Profil</a>
            </div>
            
            <?php if($role === 'admin'): ?>
                <div class="dashboard-card">
                    <div class="card-icon">⚙️</div>
                    <h3>Panneau d'Administration</h3>
                    <p>Gérez les paramètres système, les utilisateurs et la configuration de la plateforme.</p>
                    <a href="admin.php" class="card-button">Panneau Admin</a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Recent Activity Section -->
        <div class="profile-section" style="margin-top: 40px;">
            <div class="section-header">
                <h2 class="section-title">📈 Aperçu Rapide</h2>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #27ae60;">
                        <?php echo $role === 'teacher' ? $my_courses_count : $enrolled_courses_count; ?>
                    </div>
                    <div style="color: #666; margin-top: 5px;">
                        <?php echo $role === 'teacher' ? 'Cours Enseignés' : 'Cours Inscrits'; ?>
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #3498db;">
                        <?php echo $role === 'teacher' ? $total_students : $assignments_due; ?>
                    </div>
                    <div style="color: #666; margin-top: 5px;">
                        <?php echo $role === 'teacher' ? 'Total Étudiants' : 'Devoirs à Rendre'; ?>
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #9b59b6;">
                        <?php echo $assignments_count; ?>
                    </div>
                    <div style="color: #666; margin-top: 5px;">
                        Total Devoirs
                    </div>
                </div>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center;">
                    <div style="font-size: 2rem; font-weight: bold; color: #e67e22;">
                        <?php echo $current_date; ?>
                    </div>
                    <div style="color: #666; margin-top: 5px;">
                        Date d'Aujourd'hui
                    </div>
                </div>
            </div>
        </div>
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
                <a href="index.php">Accueil</a>
                <a href="courses/courses.php">Cours</a>
                <a href="assignments/assignments.php">Devoirs</a>
                <a href="dashboard.php">Tableau de bord</a>
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