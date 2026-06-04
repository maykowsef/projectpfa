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
        $type = $_POST['type'] ?? '';
        $amount = floatval($_POST['amount'] ?? 0);
        $description = sanitizeInput($_POST['description'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $budget_id = intval($_POST['budget_id'] ?? 0);
        $transaction_date = $_POST['transaction_date'] ?? date('Y-m-d');

        if (empty($type) || $amount <= 0 || empty($description)) {
            $error = 'Veuillez remplir tous les champs obligatoires.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (user_id, type, amount, description, category_id, budget_id, transaction_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$userId, $type, $amount, $description, $category_id ?: null, $budget_id ?: null, $transaction_date]);
                $success = 'Transaction ajoutée avec succès!';

                // Check budget limits and create alerts if needed
                if ($budget_id > 0 && $type === 'expense') {
                    $stmt = $pdo->prepare("
                        SELECT b.total_limit, 
                               (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE budget_id = b.id AND type = 'expense') as spent
                        FROM budgets b
                        WHERE b.id = ?
                    ");
                    $stmt->execute([$budget_id]);
                    $budget = $stmt->fetch();

                    if ($budget) {
                        $percentage = ($budget['spent'] / $budget['total_limit']) * 100;
                        
                        if ($percentage >= 100) {
                            createAlert($pdo, $userId, $budget_id, 'danger', 'Budget dépassé! Vous avez dépassé votre limite de ' . formatMoney($budget['total_limit']));
                        } elseif ($percentage >= 80) {
                            createAlert($pdo, $userId, $budget_id, 'warning', 'Attention: Vous avez atteint ' . round($percentage, 1) . '% de votre budget.');
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'ajout de la transaction: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $transaction_id = intval($_POST['transaction_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->execute([$transaction_id, $userId]);
            $success = 'Transaction supprimée avec succès!';
        } catch (PDOException $e) {
            $error = 'Erreur lors de la suppression: ' . $e->getMessage();
        }
    }
}

// Get transactions
try {
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, c.icon, b.name as budget_name
        FROM transactions t
        LEFT JOIN categories c ON t.category_id = c.id
        LEFT JOIN budgets b ON t.budget_id = b.id
        WHERE t.user_id = ?
        ORDER BY t.transaction_date DESC, t.created_at DESC
    ");
    $stmt->execute([$userId]);
    $transactions = $stmt->fetchAll();

    // Get categories
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();

    // Get user's budgets
    $stmt = $pdo->prepare("
        SELECT b.* 
        FROM budgets b
        LEFT JOIN budget_members bm ON b.id = bm.budget_id
        WHERE b.created_by = ? OR bm.user_id = ?
        GROUP BY b.id
        ORDER BY b.name
    ");
    $stmt->execute([$userId, $userId]);
    $budgets = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - BudgetCoop</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <nav class="nav-container">
            <a href="index.php" class="logo">BudgetCoop</a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="transactions.php" class="active">Transactions</a></li>
                <li><a href="budgets.php">Budgets</a></li>
                <li><a href="categories.php">Catégories</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Gestion des transactions</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add Transaction Form -->
        <div class="form-container" style="max-width: 800px;">
            <h2 class="form-title">Ajouter une transaction</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="two-column" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="type">Type *</label>
                        <select id="type" name="type" required>
                            <option value="">Sélectionner...</option>
                            <option value="income">Revenu</option>
                            <option value="expense">Dépense</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Montant (€) *</label>
                        <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description *</label>
                    <input type="text" id="description" name="description" required>
                </div>

                <div class="two-column" style="grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="category_id">Catégorie</label>
                        <select id="category_id" name="category_id">
                            <option value="">Aucune catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?> <?php echo $category['icon']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget_id">Budget</label>
                        <select id="budget_id" name="budget_id">
                            <option value="">Aucun budget</option>
                            <?php foreach ($budgets as $budget): ?>
                                <option value="<?php echo $budget['id']; ?>">
                                    <?php echo htmlspecialchars($budget['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="transaction_date">Date</label>
                    <input type="date" id="transaction_date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>">
                </div>

                <button type="submit" class="form-button">Ajouter la transaction</button>
            </form>
        </div>

        <!-- Transactions List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Toutes vos transactions</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Catégorie</th>
                        <th>Budget</th>
                        <th>Type</th>
                        <th>Montant</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">Aucune transaction pour le moment.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Non catégorisé'); ?> <?php echo $transaction['icon'] ?? ''; ?></td>
                                <td><?php echo htmlspecialchars($transaction['budget_name'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $transaction['type'] === 'income' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $transaction['type'] === 'income' ? 'Revenu' : 'Dépense'; ?>
                                    </span>
                                </td>
                                <td style="color: <?php echo $transaction['type'] === 'income' ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>; font-weight: 600;">
                                    <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?><?php echo formatMoney($transaction['amount']); ?>
                                </td>
                                <td>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette transaction?');">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 BudgetCoop – Tous droits réservés.</p>
    </footer>
</body>
</html>
