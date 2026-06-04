<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');

        if (empty($email)) {
            $error = 'L\'email est obligatoire.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format d\'email invalide.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE id = ?");
                $stmt->execute([$first_name, $last_name, $email, $userId]);
                
                // Update session
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                
                $success = 'Profil mis à jour avec succès!';
            } catch (PDOException $e) {
                $error = 'Erreur lors de la mise à jour du profil: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'Tous les champs sont obligatoires.';
        } elseif (strlen($new_password) < 6) {
            $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Les nouveaux mots de passe ne correspondent pas.';
        } else {
            try {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if ($user && password_verify($current_password, $user['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $userId]);
                    $success = 'Mot de passe changé avec succès!';
                } else {
                    $error = 'Le mot de passe actuel est incorrect.';
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors du changement de mot de passe: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'request_deletion') {
        $reason = sanitizeInput($_POST['reason'] ?? '');
        
        try {
            // Check if there's already a pending request
            $stmt = $pdo->prepare("SELECT id FROM deletion_requests WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$userId]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                $error = 'Vous avez déjà une demande de suppression en cours.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO deletion_requests (user_id, reason) VALUES (?, ?)");
                $stmt->execute([$userId, $reason]);
                $success = 'Demande de suppression envoyée. L\'administrateur examinera votre demande.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la demande de suppression: ' . $e->getMessage();
        }
    }
}

// Get user info
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Check if user has a pending deletion request
    $stmt = $pdo->prepare("SELECT * FROM deletion_requests WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$userId]);
    $deletionRequest = $stmt->fetch();
} catch (PDOException $e) {
    $error = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Budgini</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <nav class="nav-container">
            <a href="index.php" class="logo">Budgini</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="budgets.php">Budgets</a></li>
                <li><a href="categories.php">Catégories</a></li>
                <li><a href="profile.php" class="active">Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Mon profil</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="two-column">
            <!-- Profile Information -->
            <div class="form-container">
                <h2 class="form-title">Informations personnelles</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_profile">
                    
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur</label>
                        <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background: var(--light-bg);">
                    </div>
                    
                    <div class="form-group">
                        <label for="first_name">Prénom</label>
                        <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Nom</label>
                        <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="role">Rôle</label>
                        <input type="text" id="role" value="<?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Utilisateur'; ?>" disabled style="background: var(--light-bg);">
                    </div>

                    <button type="submit" class="form-button">Mettre à jour le profil</button>
                </form>
            </div>

            <!-- Change Password -->
            <div class="form-container">
                <h2 class="form-title">Changer le mot de passe</h2>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="form-button">Changer le mot de passe</button>
                </form>
            </div>
        </div>

        <!-- Account Statistics -->
        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Statistiques du compte</h2>
            </div>
            <div style="padding: 1.5rem;">
                <div class="stats-grid" style="margin-bottom: 0;">
                    <div class="stat-card">
                        <h3>Date de création</h3>
                        <div class="value" style="font-size: 1.25rem;"><?php echo formatDate($user['created_at']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Dernière mise à jour</h3>
                        <div class="value" style="font-size: 1.25rem;"><?php echo formatDate($user['updated_at']); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Statut du compte</h3>
                        <div class="value" style="font-size: 1.25rem;">
                            <span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Deletion -->
        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Suppression du compte</h2>
            </div>
            <div style="padding: 1.5rem;">
                <?php if ($deletionRequest): ?>
                    <div class="alert alert-warning">
                        <strong>Demande en cours:</strong> Votre demande de suppression du compte est en attente d'approbation par l'administrateur.
                        <br><small>Demandée le: <?php echo formatDate($deletionRequest['requested_at']); ?></small>
                    </div>
                <?php else: ?>
                    <p style="margin-bottom: 1rem; color: var(--text-secondary);">
                        Vous pouvez demander la suppression de votre compte. Cette action est irréversible et nécessitera l'approbation d'un administrateur.
                    </p>
                    <form method="POST" action="" id="deletionForm">
                        <input type="hidden" name="action" value="request_deletion">
                        <div class="form-group">
                            <label for="reason">Raison de la suppression (optionnel)</label>
                            <textarea id="reason" name="reason" rows="3" placeholder="Expliquez pourquoi vous souhaitez supprimer votre compte..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger" onclick="return confirm('Êtes-vous sûr de vouloir demander la suppression de votre compte? Cette action est irréversible.');">
                            Demander la suppression du compte
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Budgini – Tous droits réservés.</p>
    </footer>
</body>
</html>
