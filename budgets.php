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
    
    if ($action === 'add') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $type = $_POST['type'] ?? 'individual';
        $period = $_POST['period'] ?? 'monthly';
        $total_limit = floatval($_POST['total_limit'] ?? 0);
        $start_date = $_POST['start_date'] ?? date('Y-m-01');
        $end_date = $_POST['end_date'] ?? date('Y-m-t');

        if (empty($name) || $total_limit <= 0) {
            $error = 'Le nom et la limite du budget sont obligatoires.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO budgets (name, description, type, period, start_date, end_date, total_limit, created_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $description, $type, $period, $start_date, $end_date, $total_limit, $userId]);
                $budgetId = $pdo->lastInsertId();

                // Add creator as budget member
                $stmt = $pdo->prepare("INSERT INTO budget_members (budget_id, user_id, role) VALUES (?, ?, 'owner')");
                $stmt->execute([$budgetId, $userId]);

                $success = 'Budget créé avec succès!';
            } catch (PDOException $e) {
                $error = 'Erreur lors de la création du budget: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'add_member') {
        $budget_id = intval($_POST['budget_id'] ?? 0);
        $member_email = sanitizeInput($_POST['member_email'] ?? '');

        if (empty($member_email)) {
            $error = 'L\'email est obligatoire.';
        } elseif (!filter_var($member_email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format d\'email invalide.';
        } else {
            try {
                // Find user by email
                $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
                $stmt->execute([$member_email]);
                $user = $stmt->fetch();

                if (!$user) {
                    $error = 'Aucun utilisateur trouvé avec cet email.';
                } else {
                    // Check if already a member
                    $stmt = $pdo->prepare("SELECT id FROM budget_members WHERE budget_id = ? AND user_id = ?");
                    $stmt->execute([$budget_id, $user['id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        $error = 'Cet utilisateur est déjà membre de ce budget.';
                    } else {
                        // Get budget name for alert
                        $stmt = $pdo->prepare("SELECT name FROM budgets WHERE id = ?");
                        $stmt->execute([$budget_id]);
                        $budget = $stmt->fetch();

                        $stmt = $pdo->prepare("INSERT INTO budget_members (budget_id, user_id, role) VALUES (?, ?, 'member')");
                        $stmt->execute([$budget_id, $user['id']]);
                        
                        // Create alert for the added user
                        createAlert($pdo, $user['id'], $budget_id, 'info', "Vous avez été ajouté au budget \"{$budget['name']}\" par un autre membre.");
                        
                        $success = 'Membre ajouté avec succès!';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'ajout du membre: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $budget_id = intval($_POST['budget_id'] ?? 0);
        try {
            // Check if user is owner
            $stmt = $pdo->prepare("SELECT created_by FROM budgets WHERE id = ?");
            $stmt->execute([$budget_id]);
            $budget = $stmt->fetch();

            if ($budget && $budget['created_by'] == $userId) {
                $stmt = $pdo->prepare("DELETE FROM budgets WHERE id = ?");
                $stmt->execute([$budget_id]);
                $success = 'Budget supprimé avec succès!';
            } else {
                $error = 'Vous n\'avez pas la permission de supprimer ce budget.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la suppression: ' . $e->getMessage();
        }
    }
}

// Get budgets
try {
    $stmt = $pdo->prepare("
        SELECT b.*, 
               (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE budget_id = b.id AND type = 'expense') as spent,
               (SELECT COUNT(*) FROM budget_members WHERE budget_id = b.id) as member_count
        FROM budgets b
        LEFT JOIN budget_members bm ON b.id = bm.budget_id
        WHERE b.created_by = ? OR bm.user_id = ?
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $budgets = $stmt->fetchAll();

    // Get budget members for each budget
    foreach ($budgets as &$budget) {
        $stmt = $pdo->prepare("
            SELECT u.username, u.first_name, u.last_name, bm.role 
            FROM budget_members bm
            JOIN users u ON bm.user_id = u.id
            WHERE bm.budget_id = ?
        ");
        $stmt->execute([$budget['id']]);
        $budget['members'] = $stmt->fetchAll();
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
    <title>Budgets - Budgini</title>
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
                <li><a href="budgets.php" class="active">Budgets</a></li>
                <li><a href="categories.php">Catégories</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Gestion des budgets</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add Budget Form -->
        <div class="form-container" style="max-width: 800px;">
            <h2 class="form-title">Créer un nouveau budget</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="two-column" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="name">Nom du budget *</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="total_limit">Limite totale (€) *</label>
                        <input type="number" id="total_limit" name="total_limit" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="two-column" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="type">Type de budget</label>
                        <select id="type" name="type">
                            <option value="individual">Individuel</option>
                            <option value="shared">Partagé</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="period">Période</label>
                        <select id="period" name="period">
                            <option value="monthly">Mensuel</option>
                            <option value="weekly">Hebdomadaire</option>
                            <option value="custom">Personnalisé</option>
                        </select>
                    </div>
                </div>

                <div class="two-column" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="start_date">Date de début</label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo date('Y-m-01'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">Date de fin</label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo date('Y-m-t'); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="form-button">Créer le budget</button>
            </form>
        </div>

        <!-- Budgets List -->
        <?php foreach ($budgets as $budget): ?>
            <?php $status = getBudgetStatus($budget['spent'] ?? 0, $budget['total_limit']); ?>
            <div class="table-container">
                <div class="table-header">
                    <div>
                        <h2><?php echo htmlspecialchars($budget['name']); ?></h2>
                        <small>
                            <span class="badge badge-info"><?php echo $budget['type'] === 'individual' ? 'Individuel' : 'Partagé'; ?></span>
                            <span class="badge badge-<?php echo $status['status']; ?>">
                                <?php echo $status['text']; ?> (<?php echo round($status['percentage'], 1); ?>%)
                            </span>
                        </small>
                    </div>
                    <div>
                        <?php if ($budget['created_by'] == $userId): ?>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="budget_id" value="<?php echo $budget['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce budget?');">Supprimer</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem;">
                    <div class="stats-grid" style="margin-bottom: 1rem; grid-template-columns: repeat(3, 1fr);">
                        <div class="stat-card">
                            <h3>Limite</h3>
                            <div class="value"><?php echo formatMoney($budget['total_limit']); ?></div>
                        </div>
                        <div class="stat-card expense">
                            <h3>Dépensé</h3>
                            <div class="value"><?php echo formatMoney($budget['spent'] ?? 0); ?></div>
                        </div>
                        <div class="stat-card">
                            <h3>Restant</h3>
                            <div class="value" style="color: <?php echo ($budget['total_limit'] - ($budget['spent'] ?? 0)) >= 0 ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>">
                                <?php echo formatMoney($budget['total_limit'] - ($budget['spent'] ?? 0)); ?>
                            </div>
                        </div>
                    </div>

                    <?php if ($budget['type'] === 'shared'): ?>
                        <div style="margin-top: 1rem;">
                            <h4>Membres (<?php echo count($budget['members']); ?>)</h4>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.5rem;">
                                <?php foreach ($budget['members'] as $member): ?>
                                    <span class="badge badge-<?php echo $member['role'] === 'owner' ? 'info' : 'success'; ?>">
                                        <?php echo htmlspecialchars($member['username']); ?> (<?php echo $member['role'] === 'owner' ? 'Propriétaire' : 'Membre'; ?>)
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <?php if ($budget['created_by'] == $userId): ?>
                                <form method="POST" action="" style="margin-top: 1rem; display: flex; gap: 0.5rem; align-items: flex-end;">
                                    <input type="hidden" name="action" value="add_member">
                                    <input type="hidden" name="budget_id" value="<?php echo $budget['id']; ?>">
                                    <div style="flex: 1;">
                                        <input type="email" name="member_email" placeholder="Email du nouveau membre" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border-color); border-radius: 0.5rem;">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Ajouter</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 1rem; color: var(--text-secondary);">
                        <small>Période: <?php echo formatDate($budget['start_date']); ?> - <?php echo formatDate($budget['end_date']); ?></small>
                        <?php if ($budget['description']): ?>
                            <br><small><?php echo htmlspecialchars($budget['description']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($budgets)): ?>
            <div class="table-container">
                <div style="padding: 2rem; text-align: center;">
                    <p>Aucun budget créé. Créez votre premier budget ci-dessus!</p>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Budgini – Tous droits réservés.</p>
    </footer>
</body>
</html>
