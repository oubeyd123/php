<?php
session_start();
require_once '../database.php';

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Récupérer les cours de l'utilisateur
$courses = [];
$course_count = 0;

if($role === 'teacher') {
    // Récupérer les cours enseignés par ce professeur
    $query = "SELECT c.*, 
                     (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) as student_count,
                     (SELECT COUNT(*) FROM announcements a WHERE a.course_id = c.id) as post_count
              FROM courses c 
              WHERE c.teacher_id = ? 
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = $result->fetch_all(MYSQLI_ASSOC);
    $course_count = count($courses);
    
} elseif($role === 'student') {
    // Récupérer les cours auxquels cet étudiant est inscrit
    $query = "SELECT c.*, 
                     u.username as teacher_name,
                     (SELECT COUNT(*) FROM enrollments e2 WHERE e2.course_id = c.id) as student_count,
                     (SELECT COUNT(*) FROM assignments a WHERE a.course_id = c.id AND a.due_date > NOW()) as pending_assignments
              FROM courses c
              JOIN enrollments e ON c.id = e.course_id
              JOIN users u ON c.teacher_id = u.id
              WHERE e.student_id = ?
              ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = $result->fetch_all(MYSQLI_ASSOC);
    $course_count = count($courses);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Cours - DigitalVillage</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        /* Styles spécifiques à Mes Cours */
        .my-courses-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .page-header {
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .page-header h1 {
            font-size: 2.8rem;
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .page-header p {
            color: #666;
            font-size: 1.2rem;
        }
        
        .course-count {
            background: #e8f5e9;
            color: #27ae60;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-left: 10px;
        }
        
        /* Grille des cours */
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .course-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid #f0f0f0;
        }
        
        .course-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .course-card-header {
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .course-code {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .course-title {
            font-size: 1.4rem;
            margin: 10px 0;
            font-weight: 600;
            line-height: 1.3;
            min-height: 3.5em;
        }
        
        .course-card-body {
            padding: 25px;
        }
        
        .course-teacher {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: #666;
        }
        
        .teacher-icon {
            margin-right: 8px;
            font-size: 1.2rem;
        }
        
        .course-stats {
            display: flex;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 15px;
        }
        
        .stat {
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #27ae60;
            display: block;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .course-actions {
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
        
        .btn-materials {
            background: #f0f5f4;
            color: #2c3e50;
            padding: 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-materials:hover {
            background: #e0e5e4;
        }
        
        /* État vide */
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
        
        /* Statistiques rapides */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #27ae60;
            display: block;
            line-height: 1;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 0.9rem;
            margin-top: 8px;
        }
        
        @media (max-width: 768px) {
            .courses-grid {
                grid-template-columns: 1fr;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
            
            .course-title {
                min-height: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar">
        <div class="logo">
            <p class="logo-spc">👩‍🏫</p>
            <span>DigitalVillage</span>
        </div>

        <div class="nav-links">
            <a href="../index.php">Accueil</a>
            <a href="courses.php">Parcourir les cours</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($username); ?>
            </span>
            <a href="../dashboard.php" class="btn-orange">Tableau de bord</a>
            <a href="../logout.php" class="btn-outline">Déconnexion</a>
        </div>
    </nav>

    <div class="my-courses-container">
        <!-- En-tête de page -->
        <div class="page-header">
            <h1>
                <?php 
                    echo $role === 'teacher' ? 'Mes Cours' : 'Mes Cours Inscrits';
                ?>
                <span class="course-count"><?php echo $course_count; ?> cours<?php echo $course_count > 1 ? '' : ''; ?></span>
            </h1>
            <p>
                <?php 
                    if($role === 'teacher') {
                        echo 'Tous les cours que vous enseignez. Gérez le contenu, les étudiants et les devoirs.';
                    } else {
                        echo 'Tous les cours auxquels vous êtes inscrit. Accédez aux documents, devoirs et annonces.';
                    }
                ?>
            </p>
        </div>

        <!-- Statistiques rapides -->
        <?php if($role === 'teacher' && $course_count > 0): ?>
            <?php 
                // Calculer les statistiques pour le professeur
                $total_students = 0;
                $total_posts = 0;
                foreach($courses as $course) {
                    $total_students += $course['student_count'];
                    $total_posts += $course['post_count'];
                }
            ?>
            <div class="quick-stats">
                <div class="stat-card">
                    <span class="number"><?php echo $course_count; ?></span>
                    <span class="label">Cours Actifs</span>
                </div>
                <div class="stat-card">
                    <span class="number"><?php echo $total_students; ?></span>
                    <span class="label">Étudiants Totaux</span>
                </div>
                <div class="stat-card">
                    <span class="number"><?php echo $total_posts; ?></span>
                    <span class="label">Publications</span>
                </div>
            </div>
        <?php endif; ?>

        <!-- Liste des cours -->
        <?php if($course_count > 0): ?>
            <div class="courses-grid">
                <?php foreach($courses as $course): ?>
                    <div class="course-card">
                        <div class="course-card-header">
                            <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                            <h3 class="course-title"><?php echo htmlspecialchars($course['title']); ?></h3>
                        </div>
                        
                        <div class="course-card-body">
                            <!-- Info professeur (pour les étudiants) -->
                            <?php if($role === 'student'): ?>
                                <div class="course-teacher">
                                    <span class="teacher-icon">👨‍🏫</span>
                                    <span>Professeur : <?php echo htmlspecialchars($course['teacher_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Description du cours -->
                            <?php if(!empty($course['description'])): ?>
                                <p style="color: #666; line-height: 1.5; margin-bottom: 15px; font-size: 0.95rem;">
                                    <?php echo substr(htmlspecialchars($course['description']), 0, 150); ?>
                                    <?php if(strlen($course['description']) > 150): ?>...<?php endif; ?>
                                </p>
                            <?php endif; ?>
                            
                            <!-- Statistiques du cours -->
                            <div class="course-stats">
                                <?php if($role === 'teacher'): ?>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $course['student_count']; ?></span>
                                        <span class="stat-label">Étudiants</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $course['post_count']; ?></span>
                                        <span class="stat-label">Publications</span>
                                    </div>
                                <?php else: ?>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $course['student_count']; ?></span>
                                        <span class="stat-label">Camarades</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $course['pending_assignments']; ?></span>
                                        <span class="stat-label">À rendre</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Actions du cours -->
                            <div class="course-actions">
                                <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn-view">
                                    <?php echo $role === 'teacher' ? 'Gérer le cours' : 'Accéder au cours'; ?>
                                </a>
                                <?php if($role === 'teacher'): ?>
                                    <a href="upload_material.php?course_id=<?php echo $course['id']; ?>" 
                                       class="btn-materials" title="Ajouter des documents">
                                        📎
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
        <?php else: ?>
            <!-- État vide -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <?php echo $role === 'teacher' ? '📚' : '🎓'; ?>
                </div>
                <h2>Aucun cours pour le moment</h2>
                <p>
                    <?php 
                        if($role === 'teacher') {
                            echo 'Vous n\'avez pas encore créé de cours. Commencez par créer votre premier cours pour enseigner !';
                        } else {
                            echo 'Vous n\'êtes inscrit à aucun cours. Parcourez les cours disponibles et rejoignez-en un pour commencer à apprendre !';
                        }
                    ?>
                </p>
                <div style="margin-top: 25px;">
                    <?php if($role === 'teacher'): ?>
                        <a href="create_course.php" class="btn-orange" style="display: inline-block; text-decoration: none;">
                            + Créer votre premier cours
                        </a>
                    <?php else: ?>
                        <a href="courses.php" class="btn-orange" style="display: inline-block; text-decoration: none;">
                            Parcourir les cours disponibles
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pied de page -->
    <footer>
        <div class="footer-grid">
            <div>
                <h2 class="footer-logo">DigitalVillage</h2>
                <p>Construire des salles de classe numériques indépendantes et résilientes pour les écoles.</p>
            </div>

            <div>
                <h4>Plateforme</h4>
                <a href="../index.php">Accueil</a>
                <a href="courses.php">Cours</a>
                <a href="../dashboard.php">Tableau de bord</a>
                <a href="my_courses.php">Mes cours</a>
            </div>

            <div>
                <h4>Communauté</h4>
                <a href="#">À propos</a>
                <a href="#">Support</a>
                <a href="#">Écoles</a>
                <a href="#">Événements</a>
            </div>

            <div>
                <h4>Légal</h4>
                <a href="#">Politique de confidentialité</a>
                <a href="#">Conditions d'utilisation</a>
            </div>
        </div>

        <p class="footer-bottom">© 2025 DigitalVillage — Tous droits réservés</p>
    </footer>
</body>
</html>