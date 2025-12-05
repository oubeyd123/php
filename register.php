<?php
session_start();

// Si déjà connecté, rediriger vers le tableau de bord
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

require_once 'database.php';

$error = '';
$success = '';

// Gérer la soumission du formulaire d'inscription
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role']; // 'student' ou 'teacher'
    
    // Valider les entrées
    if(empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Veuillez remplir tous les champs.";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Veuillez entrer une adresse email valide.";
    } elseif(strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères.";
    } elseif($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas.";
    } else {
        // Vérifier si l'email existe déjà
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            $error = "Cet email est déjà enregistré.";
        } else {
            // Hacher le mot de passe
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insérer le nouvel utilisateur
            $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if($stmt->execute()) {
                header("Location: login.php?registered=1");
                exit();
            } else {
                $error = "L'inscription a échoué. Veuillez réessayer.";
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
    <title>Inscription - DigitalVillage</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            padding: 20px;
        }
        
        .auth-box {
            background: white;
            padding: 40px;
            border-radius: 18px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
        }
        
        .auth-box h2 {
            color: #27ae60;
            margin-bottom: 10px;
            font-size: 32px;
            text-align: center;
        }
        
        .auth-box p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2c3e50;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #27ae60;
        }
        
        .btn-submit {
            width: 100%;
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
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .auth-links {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .auth-links a {
            color: #27ae60;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-links a:hover {
            text-decoration: underline;
        }
        
        .back-home {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-home a {
            color: #27ae60;
            text-decoration: none;
        }
        
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 8px;
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .role-option label {
            display: block;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .role-option input[type="radio"]:checked + label {
            background: #27ae60;
            color: white;
            border-color: #27ae60;
        }
        
        .role-option label:hover {
            border-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>👩‍🏫 Rejoindre DigitalVillage</h2>
            <p>Créez votre compte et commencez à apprendre</p>
            
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nom complet</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Adresse email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe (min. 6 caractères)</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label>Je suis :</label>
                    <div class="role-selector">
                        <div class="role-option">
                            <input type="radio" id="student" name="role" value="student" 
                                   <?php echo (!isset($_POST['role']) || $_POST['role'] == 'student') ? 'checked' : ''; ?>>
                            <label for="student">🎓 Étudiant(e)</label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="teacher" name="role" value="teacher" 
                                   <?php echo (isset($_POST['role']) && $_POST['role'] == 'teacher') ? 'checked' : ''; ?>>
                            <label for="teacher">👨‍🏫 Enseignant(e)</label>
                        </div>
                    </div>
                </div>
                
                <button type="submit" name="register" class="btn-submit">Créer un compte</button>
            </form>
            
            <div class="auth-links">
                Vous avez déjà un compte ? <a href="login.php">Se connecter</a>
            </div>
            
            <div class="back-home">
                <a href="index.php">← Retour à l'accueil</a>
            </div>
        </div>
    </div>
</body>
</html>