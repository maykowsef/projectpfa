<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Gestion Collaborative de Budget - Accueil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <header class="site-header">
        <nav class="nav-container">
            <a href="index.php" class="logo">BudgetCoop</a>
            <ul class="nav-links">
                <li><a href="login.php">Connexion</a></li>
                <li><a href="register.php">Inscription</a></li>
            </ul>
        </nav>
    </header>
    <main class="hero-section">
        <div class="hero-content">
            <h1>Gérez vos finances <span class="highlight">ensemble</span></h1>
            <p>Suivez revenus, dépenses, budgets partagés et visualisez vos statistiques en temps réel.</p>
            <a href="register.php" class="cta-button">Commencer dès maintenant</a>
        </div>
        <div class="hero-image">
            <!-- Placeholder for illustration; could be replaced with generated image -->
            <img src="hero.png" alt="Illustration de gestion de budget" />
        </div>
    </main>
    <footer class="site-footer">
        <p>&copy; 2026 BudgetCoop – Tous droits réservés.</p>
    </footer>
</body>
</html>
