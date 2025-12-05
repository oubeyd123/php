<?php
session_start();
require_once 'database.php';

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Initialize variables
$success_message = '';
$error_message = '';
$password_strength = '';
$password_match = '';

// Get current user data
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['update_profile'])) {
        // Update personal information
        $new_username = trim($_POST['username']);
        $new_email = trim($_POST['email']);
        
        // Basic validation
        if(empty($new_username) || empty($new_email)) {
            $error_message = "Le nom d'utilisateur et l'email sont requis.";
        } elseif(!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Veuillez entrer une adresse email valide.";
        } else {
            // Check if email already exists (for other users)
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check_email->bind_param("si", $new_email, $user_id);
            $check_email->execute();
            
            if($check_email->get_result()->num_rows > 0) {
                $error_message = "Cet email est déjà enregistré par un autre utilisateur.";
            } else {
                // Update user information
                $update_query = "UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("ssi", $new_username, $new_email, $user_id);
                
                if($update_stmt->execute()) {
                    // Update session variables
                    $_SESSION['username'] = $new_username;
                    
                    $success_message = "Profil mis à jour avec succès !";
                    
                    // Refresh user data
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $user = $result->fetch_assoc();
                } else {
                    $error_message = "Échec de la mise à jour du profil. Veuillez réessayer.";
                }
                $update_stmt->close();
            }
            $check_email->close();
        }
    }
    
    // Handle password change
    if(isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate passwords
        if(empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error_message = "Tous les champs de mot de passe sont requis.";
        } elseif($new_password !== $confirm_password) {
            $error_message = "Les nouveaux mots de passe ne correspondent pas.";
        } elseif(strlen($new_password) < 6) {
            $error_message = "Le mot de passe doit contenir au moins 6 caractères.";
        } else {
            // Calculate password strength for display
            $strength = 0;
            if(strlen($new_password) >= 6) $strength += 25;
            if(preg_match('/[A-Z]/', $new_password)) $strength += 25;
            if(preg_match('/[0-9]/', $new_password)) $strength += 25;
            if(preg_match('/[^A-Za-z0-9]/', $new_password)) $strength += 25;
            
            // Set strength message
            if($strength < 50) {
                $password_strength = '<span style="color:#e74c3c">Mot de passe faible</span>';
            } elseif($strength < 75) {
                $password_strength = '<span style="color:#f39c12">Mot de passe moyen</span>';
            } else {
                $password_strength = '<span style="color:#27ae60">Mot de passe fort</span>';
            }
            
            // Verify current password
            if(password_verify($current_password, $user['password'])) {
                // Hash new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_pwd = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                $update_pwd->bind_param("si", $new_password_hash, $user_id);
                
                if($update_pwd->execute()) {
                    $success_message = "Mot de passe modifié avec succès !";
                } else {
                    $error_message = "Échec du changement de mot de passe. Veuillez réessayer.";
                }
                $update_pwd->close();
            } else {
                $error_message = "Le mot de passe actuel est incorrect.";
            }
        }
        
        // Set password match message
        if($new_password === $confirm_password && !empty($confirm_password)) {
            $password_match = '<span style="color:#27ae60">✓ Les mots de passe correspondent</span>';
        } elseif(!empty($confirm_password)) {
            $password_match = '<span style="color:#e74c3c">✗ Les mots de passe ne correspondent pas</span>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres du Profil - DigitalVillage</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Profile Page Styles - Pure CSS */
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: calc(100vh - 200px);
        }
        
        .profile-header {
            margin-bottom: 40px;
            text-align: center;
        }
        
        .profile-header h1 {
            font-size: 2.8rem;
            color: #27ae60;
            margin-bottom: 10px;
        }
        
        .profile-header p {
            color: #666;
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Profile Layout */
        .profile-layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 30px;
        }
        
        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }
        
        /* Sidebar */
        .profile-sidebar {
            background: white;
            border-radius: 16px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            height: fit-content;
        }
        
        .user-avatar {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .avatar-circle {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: white;
        }
        
        .user-info h3 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 1.5rem;
        }
        
        .user-role {
            text-align: center;
            display: inline-block;
            padding: 6px 15px;
            background: #e8f5e9;
            color: #27ae60;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin: 10px auto;
            display: block;
            width: fit-content;
        }
        
        .account-stats {
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #f0f0f0;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px solid #f8f8f8;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.95rem;
        }
        
        .stat-value {
            color: #2c3e50;
            font-weight: 600;
        }
        
        /* Main Content */
        .profile-content {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .profile-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .section-icon {
            font-size: 1.8rem;
            margin-right: 15px;
            color: #27ae60;
        }
        
        .section-title {
            font-size: 1.5rem;
            color: #2c3e50;
            margin: 0;
        }
        
        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.1);
        }
        
        .form-hint {
            display: block;
            margin-top: 6px;
            color: #888;
            font-size: 0.85rem;
        }
        
        /* Password Field Wrapper */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper input {
            padding-right: 50px;
            width: 100%;
        }
        
        .show-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 1.2rem;
            padding: 0;
            margin: 0;
        }
        
        .show-password:focus {
            outline: none;
        }
        
        /* Buttons */
        .btn-submit {
            background: #27ae60;
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-submit:hover {
            background: #219150;
        }
        
        .btn-secondary {
            background: #f0f5f4;
            color: #2c3e50;
            border: 2px solid #e0e0e0;
        }
        
        .btn-secondary:hover {
            background: #e0e5e4;
            border-color: #27ae60;
            color: #27ae60;
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #27ae60;
            border: 1px solid #c8e6c9;
        }
        
        .alert-error {
            background: #ffebee;
            color: #e74c3c;
            border: 1px solid #ffcdd2;
        }
        
        .close-alert {
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: inherit;
        }
        
        .close-alert:focus {
            outline: none;
        }
        
        /* Button Group */
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        /* Role-specific colors */
        .role-teacher { background: #e8f5e9; color: #27ae60; }
        .role-student { background: #e3f2fd; color: #1976d2; }
        .role-admin { background: #fce4ec; color: #c2185b; }
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
            <a href="dashboard.php">Tableau de bord</a>
            <a href="courses/courses.php">Cours</a>
            <span style="color: #333; font-weight: 500;">
                <?php echo htmlspecialchars($username); ?>
            </span>
            <a href="profile.php" class="btn-orange">Profil</a>
            <a href="logout.php" class="btn-outline">Déconnexion</a>
        </div>
    </nav>

    <div class="profile-container">
        <!-- Header -->
        <div class="profile-header">
            <h1>Paramètres du Profil</h1>
            <p>Gérez les informations de votre compte et vos préférences</p>
        </div>

        <!-- Alerts -->
        <?php if($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <button class="close-alert" onclick="this.parentElement.style.display='none'">×</button>
            </div>
        <?php endif; ?>
        
        <?php if($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
                <button class="close-alert" onclick="this.parentElement.style.display='none'">×</button>
            </div>
        <?php endif; ?>

        <div class="profile-layout">
            <!-- Sidebar - User Info -->
            <div class="profile-sidebar">
                <div class="user-avatar">
                    <div class="avatar-circle">
                        <?php 
                            // Show different avatar based on role
                            if($role === 'teacher') {
                                echo '👨‍🏫';
                            } elseif($role === 'admin') {
                                echo '👑';
                            } else {
                                echo '🎓';
                            }
                        ?>
                    </div>
                    <h3><?php echo htmlspecialchars($username); ?></h3>
                    <span class="user-role 
                        <?php 
                            if($role === 'teacher') echo 'role-teacher';
                            elseif($role === 'admin') echo 'role-admin';
                            else echo 'role-student';
                        ?>">
                        <?php 
                            if($role === 'teacher') {
                                echo 'Enseignant';
                            } elseif($role === 'admin') {
                                echo 'Administrateur';
                            } else {
                                echo 'Étudiant';
                            }
                        ?>
                    </span>
                </div>
                
                <div class="account-stats">
                    <div class="stat-item">
                        <span class="stat-label">Membre depuis :</span>
                        <span class="stat-value">
                            <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Dernière mise à jour :</span>
                        <span class="stat-value">
                           <?php echo date('M j, Y', strtotime($user['updated_at'])); ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">ID du compte :</span>
                        <span class="stat-value">#<?php echo $user_id; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Email :</span>
                        <span class="stat-value" style="font-size: 0.85rem; word-break: break-all;"><?php echo htmlspecialchars($user['email']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="profile-content">
                <!-- Personal Information Section -->
                <div class="profile-section">
                    <div class="section-header">
                        <span class="section-icon">👤</span>
                        <h2 class="section-title">Informations Personnelles</h2>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username">Nom d'utilisateur *</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" 
                                       required>
                                <span class="form-hint">C'est ainsi que vous apparaissez sur la plateforme</span>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Adresse Email *</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" 
                                       required>
                                <span class="form-hint">Utilisé pour la connexion et les notifications</span>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-submit">
                            💾 Enregistrer les modifications
                        </button>
                    </form>
                </div>

                <!-- Change Password Section -->
                <div class="profile-section">
                    <div class="section-header">
                        <span class="section-icon">🔒</span>
                        <h2 class="section-title">Changer le Mot de Passe</h2>
                    </div>
                    
                    <form method="POST" action="">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="current_password">Mot de passe actuel *</label>
                                <div class="password-wrapper">
                                    <input type="password" id="current_password" name="current_password" required>
                                    <button type="button" class="show-password" 
                                            onclick="this.previousElementSibling.type = this.previousElementSibling.type === 'password' ? 'text' : 'password';
                                                    this.textContent = this.previousElementSibling.type === 'password' ? '👁️' : '🙈'">
                                        👁️
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe *</label>
                                <div class="password-wrapper">
                                    <input type="password" id="new_password" name="new_password" required>
                                    <button type="button" class="show-password" 
                                            onclick="this.previousElementSibling.type = this.previousElementSibling.type === 'password' ? 'text' : 'password';
                                                    this.textContent = this.previousElementSibling.type === 'password' ? '👁️' : '🙈'">
                                        👁️
                                    </button>
                                </div>
                                <?php if(!empty($password_strength)): ?>
                                    <span class="form-hint"><?php echo $password_strength; ?></span>
                                <?php else: ?>
                                    <span class="form-hint">Minimum 6 caractères</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirmer le nouveau mot de passe *</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="show-password" 
                                            onclick="this.previousElementSibling.type = this.previousElementSibling.type === 'password' ? 'text' : 'password';
                                                    this.textContent = this.previousElementSibling.type === 'password' ? '👁️' : '🙈'">
                                        👁️
                                    </button>
                                </div>
                                <?php if(!empty($password_match)): ?>
                                    <span class="form-hint"><?php echo $password_match; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-submit">
                            🔑 Changer le mot de passe
                        </button>
                    </form>
                </div>

                <!-- Quick Actions -->
                <div class="profile-section">
                    <div class="section-header">
                        <span class="section-icon">⚡</span>
                        <h2 class="section-title">Actions Rapides</h2>
                    </div>
                    
                    <div class="button-group">
                        <a href="dashboard.php" class="btn-submit btn-secondary">
                            ← Retour au tableau de bord
                        </a>
                        <a href="courses/courses.php" class="btn-submit" style="background: #3498db;">
                            📚 Mes Cours
                        </a>
                        <?php if($role === 'teacher'): ?>
                            <a href="courses/create_course.php" class="btn-submit" style="background: #9b59b6;">
                                ➕ Créer un Cours
                            </a>
                        <?php endif; ?>
                        <a href="logout.php" class="btn-submit" style="background: #e74c3c;">
                            🚪 Déconnexion
                        </a>
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
                <a href="dashboard.php">Tableau de bord</a>
                <a href="profile.php">Profil</a>
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
                <a href="#">Paramètres du compte</a>
            </div>
        </div>

        <p class="footer-bottom">© 2025 DigitalVillage — Tous droits réservés</p>
    </footer>
</body>
</html>