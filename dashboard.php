<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$userRole = getCurrentUserRole();

// Get statistics
$totalIncome = 0;
$totalExpense = 0;
$balance = 0;

try {
    // Get total income
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'income'");
    $stmt->execute([$userId]);
    $totalIncome = $stmt->fetch()['total'];

    // Get total expense
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND type = 'expense'");
    $stmt->execute([$userId]);
    $totalExpense = $stmt->fetch()['total'];

    $balance = $totalIncome - $totalExpense;

    // Get recent transactions
    $stmt = $pdo->prepare("
        SELECT t.*, c.name as category_name, c.icon 
        FROM transactions t 
        LEFT JOIN categories c ON t.category_id = c.id 
        WHERE t.user_id = ? 
        ORDER BY t.transaction_date DESC, t.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentTransactions = $stmt->fetchAll();

    // Get expenses by category
    $stmt = $pdo->prepare("
        SELECT c.name, c.icon, COALESCE(SUM(t.amount), 0) as total 
        FROM categories c 
        LEFT JOIN transactions t ON c.id = t.category_id AND t.user_id = ? AND t.type = 'expense'
        GROUP BY c.id, c.name, c.icon
        HAVING total > 0
        ORDER BY total DESC
    ");
    $stmt->execute([$userId]);
    $expensesByCategory = $stmt->fetchAll();

    // Get user's budgets
    $stmt = $pdo->prepare("
        SELECT b.*, 
               (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE budget_id = b.id AND type = 'expense') as spent
        FROM budgets b
        LEFT JOIN budget_members bm ON b.id = bm.budget_id
        WHERE b.created_by = ? OR bm.user_id = ?
        GROUP BY b.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $budgets = $stmt->fetchAll();

    // Get unread alerts
    $alerts = getUnreadAlerts($pdo, $userId);

} catch (PDOException $e) {
    $error = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Budgini</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <header class="site-header">
        <nav class="nav-container">
            <a href="index.php" class="logo">Budgini</a>
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Tableau de bord</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="budgets.php">Budgets</a></li>
                <li><a href="categories.php">Catégories</a></li>
                <?php if ($userRole === 'admin'): ?>
                    <li><a href="admin.php">Administration</a></li>
                <?php endif; ?>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['first_name'] ?? getCurrentUserName()); ?>!</h1>
            <?php if (!empty($alerts)): ?>
                <a href="alerts.php" class="btn btn-warning">
                    🔔 <?php echo count($alerts); ?> Alertes
                </a>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card income">
                <h3>Total Revenus</h3>
                <div class="value"><?php echo formatMoney($totalIncome); ?></div>
            </div>
            <div class="stat-card expense">
                <h3>Total Dépenses</h3>
                <div class="value"><?php echo formatMoney($totalExpense); ?></div>
            </div>
            <div class="stat-card">
                <h3>Solde Disponible</h3>
                <div class="value" style="color: <?php echo $balance >= 0 ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>">
                    <?php echo formatMoney($balance); ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>Nombre de Transactions</h3>
                <div class="value"><?php echo count($recentTransactions); ?></div>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-container">
            <div class="chart-card">
                <h3>Répartition des dépenses par catégorie</h3>
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>État des budgets</h3>
                <canvas id="budgetChart"></canvas>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="table-container">
            <div class="table-header">
                <h2>Transactions récentes</h2>
                <a href="transactions.php" class="btn btn-primary">Voir toutes</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Catégorie</th>
                        <th>Type</th>
                        <th>Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucune transaction pour le moment.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentTransactions as $transaction): ?>
                            <tr>
                                <td><?php echo formatDate($transaction['transaction_date']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                                <td><?php echo htmlspecialchars($transaction['category_name'] ?? 'Non catégorisé'); ?> <?php echo $transaction['icon'] ?? ''; ?></td>
                                <td>
                                    <span class="badge <?php echo $transaction['type'] === 'income' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $transaction['type'] === 'income' ? 'Revenu' : 'Dépense'; ?>
                                    </span>
                                </td>
                                <td style="color: <?php echo $transaction['type'] === 'income' ? 'var(--secondary-color)' : 'var(--danger-color)'; ?>; font-weight: 600;">
                                    <?php echo $transaction['type'] === 'income' ? '+' : '-'; ?><?php echo formatMoney($transaction['amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Budgets Overview -->
        <div class="table-container">
            <div class="table-header">
                <h2>Vos budgets</h2>
                <a href="budgets.php" class="btn btn-primary">Gérer les budgets</a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Limite</th>
                        <th>Dépensé</th>
                        <th>État</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($budgets)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucun budget créé. <a href="budgets.php">Créer un budget</a></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($budgets as $budget): ?>
                            <?php $status = getBudgetStatus($budget['spent'] ?? 0, $budget['total_limit']); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($budget['name']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $budget['type'] === 'individual' ? 'Individuel' : 'Partagé'; ?></span>
                                </td>
                                <td><?php echo formatMoney($budget['total_limit']); ?></td>
                                <td><?php echo formatMoney($budget['spent'] ?? 0); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $status['status']; ?>">
                                        <?php echo $status['text']; ?> (<?php echo round($status['percentage'], 1); ?>%)
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Budgini – Tous droits réservés.</p>
    </footer>

    <script>
        // Category Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($expensesByCategory, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($expensesByCategory, 'total')); ?>,
                    backgroundColor: [
                        '#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
                        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Budget Chart
        const budgetCtx = document.getElementById('budgetChart').getContext('2d');
        new Chart(budgetCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($budgets, 'name')); ?>,
                datasets: [{
                    label: 'Dépensé',
                    data: <?php echo json_encode(array_column($budgets, 'spent')); ?>,
                    backgroundColor: '#ef4444'
                }, {
                    label: 'Restant',
                    data: <?php echo json_encode(array_map(function($b) { return $b['total_limit'] - ($b['spent'] ?? 0); }, $budgets)); ?>,
                    backgroundColor: '#10b981'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
