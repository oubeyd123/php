<?php
session_start();

// Vérifier si l'utilisateur est connecté et est un professeur
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../login.php");
    exit();
}

require_once '../database.php';

$error = '';
$success = '';

// Gérer la création de cours
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_course'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $course_code = strtoupper(trim($_POST['course_code']));
    $teacher_id = $_SESSION['user_id'];
    
    // Valider les entrées
    if(empty($title) || empty($course_code)) {
        $error = "Le titre et le code du cours sont obligatoires.";
    } else {
        // Vérifier si le code de cours existe déjà
        $stmt = $conn->prepare("SELECT id FROM courses WHERE course_code = ?");
        $stmt->bind_param("s", $course_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error = "Ce code de cours existe déjà. Veuillez utiliser un code différent.";
        } else {
            // Insérer le nouveau cours
            $stmt = $conn->prepare("INSERT INTO courses (title, description, course_code, teacher_id, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssi", $title, $description, $course_code, $teacher_id);
            
            if($stmt->execute()) {
                $success = "Cours créé avec succès !";
                $course_id = $stmt->insert_id;
                // Rediriger vers la vue du cours après 2 secondes
                header("refresh:2;url=view_course.php?id=" . $course_id);
            } else {
                $error = "Échec de la création du cours. Veuillez réessayer.";
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Cours - DigitalVillage</title>
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
            <h1>Créer un Nouveau Cours</h1>
            <p>Configurez un nouveau cours pour vos étudiants</p>
        </div>

        <div class="form-card">
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?> Redirection en cours...</div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Titre du Cours *</label>
                    <input type="text" id="title" name="title" required 
                           placeholder="ex: Introduction à l'Informatique"
                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="course_code">Code du Cours *</label>
                    <input type="text" id="course_code" name="course_code" required 
                           placeholder="ex: INFO101"
                           value="<?php echo isset($_POST['course_code']) ? htmlspecialchars($_POST['course_code']) : ''; ?>">
                    <small>Un identifiant unique pour votre cours (lettres et chiffres uniquement)</small>
                </div>

                <div class="form-group">
                    <label for="description">Description du Cours</label>
                    <textarea id="description" name="description" 
                              placeholder="Décrivez ce que les étudiants apprendront dans ce cours..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    <small>Optionnel : Ajoutez des détails sur les sujets, prérequis et objectifs d'apprentissage</small>
                </div>

                <div class="btn-group">
                    <button type="submit" name="create_course" class="btn-submit">
                        ✓ Créer le Cours
                    </button>
                    <a href="../dashboard.php" class="btn-cancel">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>