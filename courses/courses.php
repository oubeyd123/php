<?php
session_start();

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

require_once '../database.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Récupérer tous les cours avec les informations des enseignants
$query = "SELECT c.*, u.username as teacher_name,
          (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as student_count
          FROM courses c 
          JOIN users u ON c.teacher_id = u.id 
          ORDER BY c.created_at DESC";

$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les cours - DigitalVillage</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        
        .page-header h1 {
            color: #27ae60;
            font-size: 36px;
        }
        
        .courses-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
        }
        
        .course-card {
            background: white;
            border-radius: 18px;
            padding: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .course-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
            border-color: #27ae60;
        }
        
        .course-code {
            display: inline-block;
            background: #e8f5e9;
            color: #27ae60;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .course-card h3 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 10px;
        }
        
        .course-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .course-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }
        
        .teacher-name {
            color: #666;
            font-size: 14px;
        }
        
        .student-count {
            color: #27ae60;
            font-weight: bold;
            font-size: 14px;
        }
        
        .btn-view {
            display: block;
            text-align: center;
            padding: 12px;
            background: #27ae60;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background 0.3s;
        }
        
        .btn-view:hover {
            background: #219150;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .empty-state-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        
        .empty-state h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 30px;
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
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($_SESSION['username']); ?>
            </span>
            <a href="../dashboard.php" class="btn-orange">Tableau de bord</a>
            <a href="../logout.php" class="btn-outline">Déconnexion</a>
        </div>
    </nav>

    <div class="page-container">
        <div class="page-header">
            <div>
                <h1>Tous les cours</h1>
                <p style="color: #666; margin-top: 5px;">Parcourez les cours disponibles</p>
            </div>
            <?php if($role === 'teacher'): ?>
                <a href="create_course.php" class="btn-orange">+ Créer un cours</a>
            <?php endif; ?>
        </div>

        <?php if($result->num_rows > 0): ?>
            <div class="courses-grid">
                <?php while($course = $result->fetch_assoc()): ?>
                    <div class="course-card">
                        <span class="course-code"><?php echo htmlspecialchars($course['course_code']); ?></span>
                        <h3><?php echo htmlspecialchars($course['title']); ?></h3>
                        <p><?php echo htmlspecialchars($course['description'] ?: 'Aucune description disponible'); ?></p>
                        
                        <div class="course-meta">
                            <span class="teacher-name">👨‍🏫 <?php echo htmlspecialchars($course['teacher_name']); ?></span>
                            <span class="student-count">👥 <?php echo $course['student_count']; ?> étudiants</span>
                        </div>
                        
                        <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn-view">
                            Voir le cours →
                        </a>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-state-icon">📚</div>
                <h2>Aucun cours pour le moment</h2>
                <p>Il n'y a aucun cours disponible actuellement.</p>
                <?php if($role === 'teacher'): ?>
                    <a href="create_course.php" class="btn-orange">Créer votre premier cours</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>