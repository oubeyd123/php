<?php 
session_start();

// Check if user is logged in and set variables safely
$is_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['username']);
$username = $is_logged_in ? $_SESSION['username'] : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>DigitalVillage - Votre plateforme d’apprentissage indépendante</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">
            <p class="logo-spc">👩‍🏫</p>
            <span>DigitalVillage</span>
        </div>

        <div class="nav-links">
            <?php if($is_logged_in): ?>
                <span style="color: #333; font-weight: 500;">
                    Bienvenue, <?php echo htmlspecialchars($username); ?>
                </span>
                <a href="dashboard.php" class="btn-orange">Tableau de bord</a>
                <a href="logout.php" class="btn-outline">Se déconnecter</a>
            <?php else: ?>
                <a href="login.php">Se connecter</a>
                <a href="login.php" class="btn-orange">Commencer</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="body">
        <div class="body-content">
            <h1>
                Donner aux écoles le pouvoir de<br>
                <span class="gradient-text">Résister aux GAFAM</span>
            </h1>
            <p>
                Rejoignez notre initiative pour offrir aux écoles les outils nécessaires pour créer,
                gérer et contrôler leurs propres classes numériques. Gardez les données des élèves
                en sécurité et indépendantes des grandes entreprises technologiques.
            </p>

            <div class="body-btns">
                <?php if($is_logged_in): ?>
                    <a href="dashboard.php" class="btn-orange">Aller au tableau de bord →</a>
                    <a href="courses.php" class="btn-outline">Voir les cours</a>
                <?php else: ?>
                    <a href="login.php" class="btn-orange">Créer votre classe →</a>
                    <a href="#how-we-help" class="btn-outline">En savoir plus</a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How We Help Section -->
    <section class="Help" id="how-we-help">
        <div class="Help-content">
            <h1>Comment nous aidons</h1>
            <p>
                Notre plateforme aide les écoles à construire leur propre écosystème numérique,
                à contrôler leurs données et à offrir aux élèves un environnement
                d’apprentissage sûr et indépendant.
            </p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features">
        <div class="feature-card">
            <div style="font-size:60px;">📚</div>
            <h3>Créer des cours</h3>
            <p>
                Les enseignants peuvent créer et partager des cours directement dans le système local,
                sans dépendre de services externes.
            </p>
        </div>

        <div class="feature-card">
            <div style="font-size:60px;">📝</div>
            <h3>Gérer les devoirs</h3>
            <p>
                Donnez des devoirs, collectez les rendus et attribuez des notes — 
                le tout depuis le serveur de votre école.
            </p>
        </div>

        <div class="feature-card">
            <div style="font-size:60px;">🔒</div>
            <h3>Protéger les données</h3>
            <p>
                Gardez toutes les données des élèves et enseignants locales, sécurisées
                et indépendantes des plateformes des GAFAM.
            </p>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div>
                <h2 class="footer-logo">DigitalVillage</h2>
                <p>Construire des classes numériques indépendantes et résilientes pour les écoles.</p>
            </div>

            <div>
                <h4>Plateforme</h4>
                <a href="index.php">Accueil</a>
                <a href="courses.php">Cours</a>
                <a href="assignments.php">Devoirs</a>
                <a href="dashboard.php">Tableau de bord</a>
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
                <a href="#">Conditions d’utilisation</a>
            </div>
        </div>

        <p class="footer-bottom">© 2025 DigitalVillage — Tous droits réservés</p>
    </footer>

</body>
</html>
