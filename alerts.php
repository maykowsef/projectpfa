<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$success = '';
$error = '';

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    try {
        markAlertsAsRead($pdo, $userId);
        $success = 'Alertes marquées comme lues!';
    } catch (PDOException $e) {
        $error = 'Erreur: ' . $e->getMessage();
    }
}

// Get alerts
try {
    $stmt = $pdo->prepare("
        SELECT a.*, b.name as budget_name 
        FROM alerts a
        LEFT JOIN budgets b ON a.budget_id = b.id
        WHERE a.user_id = ?
        ORDER BY a.is_read ASC, a.created_at DESC
    ");
    $stmt->execute([$userId]);
    $alerts = $stmt->fetchAll();

    $unreadCount = 0;
    foreach ($alerts as $alert) {
        if (!$alert['is_read']) {
            $unreadCount++;
        }
    }

} catch (PDOException $e) {
    $error = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alertes - Budgini</title>
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
                <li><a href="profile.php">Profil</a></li>
                <li><a href="alerts.php" class="active">Alertes <?php if ($unreadCount > 0): ?>(<?php echo $unreadCount; ?>)<?php endif; ?></a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Mes alertes</h1>
            <?php if ($unreadCount > 0): ?>
                <form method="POST" action="" style="display: inline;">
                    <input type="hidden" name="action" value="mark_read">
                    <button type="submit" class="btn btn-primary">Tout marquer comme lu</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (empty($alerts)): ?>
            <div class="table-container">
                <div style="padding: 2rem; text-align: center;">
                    <p>Aucune alerte pour le moment.</p>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($alerts as $alert): ?>
                <div class="table-container" style="margin-bottom: 1rem; <?php echo $alert['is_read'] ? 'opacity: 0.7;' : ''; ?>">
                    <div style="padding: 1.5rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; gap: 1rem;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <span class="badge badge-<?php echo $alert['type']; ?>">
                                        <?php 
                                        echo match($alert['type']) {
                                            'danger' => '⚠️ Danger',
                                            'warning' => '⚡ Avertissement',
                                            'info' => 'ℹ️ Information',
                                            default => 'Info'
                                        };
                                        ?>
                                    </span>
                                    <?php if (!$alert['is_read']): ?>
                                        <span class="badge badge-success">Nouveau</span>
                                    <?php endif; ?>
                                </div>
                                <p style="margin-bottom: 0.5rem; font-weight: 500; font-size: 1.05rem;"><?php echo htmlspecialchars($alert['message']); ?></p>
                                <?php if ($alert['budget_name']): ?>
                                    <div style="margin-top: 0.75rem; padding: 0.75rem; background: var(--bg-secondary); border-radius: var(--radius-md); border-left: 3px solid var(--primary);">
                                        <small style="color: var(--text-secondary); font-weight: 600;">Budget associé:</small>
                                        <div style="margin-top: 0.25rem;">
                                            <a href="budgets.php" style="color: var(--primary); text-decoration: none; font-weight: 500;">
                                                <?php echo htmlspecialchars($alert['budget_name']); ?> →
                                            </a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div style="text-align: right;">
                                <small style="color: var(--text-muted);"><?php echo date('d/m/Y', strtotime($alert['created_at'])); ?></small>
                                <br>
                                <small style="color: var(--text-muted);"><?php echo date('H:i', strtotime($alert['created_at'])); ?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Budgini – Tous droits réservés.</p>
    </footer>
</body>
</html>
