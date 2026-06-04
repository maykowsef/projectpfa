<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireAdmin();

$userId = getCurrentUserId();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'activate_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE users SET is_active = TRUE WHERE id = ?");
            $stmt->execute([$user_id]);
            $success = 'Utilisateur activé avec succès!';
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'activation: ' . $e->getMessage();
        }
    } elseif ($action === 'deactivate_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        try {
            if ($user_id == $userId) {
                $error = 'Vous ne pouvez pas désactiver votre propre compte.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = 'Utilisateur désactivé avec succès!';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la désactivation: ' . $e->getMessage();
        }
    } elseif ($action === 'change_role') {
        $user_id = intval($_POST['user_id'] ?? 0);
        $new_role = $_POST['role'] ?? 'user';
        try {
            if ($user_id == $userId) {
                $error = 'Vous ne pouvez pas modifier votre propre rôle.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$new_role, $user_id]);
                $success = 'Rôle modifié avec succès!';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la modification du rôle: ' . $e->getMessage();
        }
    } elseif ($action === 'delete_user') {
        $user_id = intval($_POST['user_id'] ?? 0);
        try {
            if ($user_id == $userId) {
                $error = 'Vous ne pouvez pas supprimer votre propre compte.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $success = 'Utilisateur supprimé avec succès!';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la suppression: ' . $e->getMessage();
        }
    } elseif ($action === 'approve_deletion') {
        $request_id = intval($_POST['request_id'] ?? 0);
        try {
            // Get the user_id from the request
            $stmt = $pdo->prepare("SELECT user_id FROM deletion_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            
            if ($request) {
                if ($request['user_id'] == $userId) {
                    $error = 'Vous ne pouvez pas approuver votre propre suppression.';
                } else {
                    // Update the request status
                    $stmt = $pdo->prepare("UPDATE deletion_requests SET status = 'approved', processed_at = NOW(), processed_by = ? WHERE id = ?");
                    $stmt->execute([$userId, $request_id]);
                    
                    // Delete the user
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$request['user_id']]);
                    
                    $success = 'Demande approuvée et compte supprimé avec succès!';
                }
            } else {
                $error = 'Demande introuvable.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'approbation: ' . $e->getMessage();
        }
    } elseif ($action === 'reject_deletion') {
        $request_id = intval($_POST['request_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE deletion_requests SET status = 'rejected', processed_at = NOW(), processed_by = ? WHERE id = ?");
            $stmt->execute([$userId, $request_id]);
            $success = 'Demande rejetée avec succès!';
        } catch (PDOException $e) {
            $error = 'Erreur lors du rejet: ' . $e->getMessage();
        }
    }
}

// Get statistics
try {
    $totalUsers = 0;
    $activeUsers = 0;
    $totalTransactions = 0;
    $totalBudgets = 0;
    $totalIncome = 0;
    $totalExpenses = 0;
    $totalBalance = 0;
    $sharedBudgets = 0;

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $totalUsers = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_active = TRUE");
    $activeUsers = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM transactions");
    $totalTransactions = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM budgets");
    $totalBudgets = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM budgets WHERE type = 'shared'");
    $sharedBudgets = $stmt->fetch()['count'];

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'income'");
    $totalIncome = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE type = 'expense'");
    $totalExpenses = $stmt->fetch()['total'];

    $totalBalance = $totalIncome - $totalExpenses;

    // Get category breakdown
    $stmt = $pdo->query("
        SELECT c.name, COALESCE(SUM(t.amount), 0) as total
        FROM categories c
        LEFT JOIN transactions t ON c.id = t.category_id AND t.type = 'expense'
        GROUP BY c.id, c.name
        ORDER BY total DESC
        LIMIT 10
    ");
    $categoryStats = $stmt->fetchAll();

    // Get monthly expense evolution
    $stmt = $pdo->query("
        SELECT DATE_FORMAT(transaction_date, '%Y-%m') as month, COALESCE(SUM(amount), 0) as total
        FROM transactions
        WHERE type = 'expense'
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthlyExpenses = $stmt->fetchAll();

    // Get all users
    $stmt = $pdo->query("
        SELECT u.*,
               (SELECT COUNT(*) FROM transactions WHERE user_id = u.id) as transaction_count,
               (SELECT COUNT(*) FROM budgets WHERE created_by = u.id) as budget_count
        FROM users u
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();

    // Get all budgets with their status
    $stmt = $pdo->query("
        SELECT b.*,
               (SELECT COUNT(*) FROM budget_members WHERE budget_id = b.id) as member_count,
               (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE budget_id = b.id AND type = 'expense') as spent
        FROM budgets b
        ORDER BY b.created_at DESC
    ");
    $budgets = $stmt->fetchAll();

    // Get deletion requests
    $stmt = $pdo->query("
        SELECT dr.*, u.username, u.email, u.first_name, u.last_name
        FROM deletion_requests dr
        JOIN users u ON dr.user_id = u.id
        WHERE dr.status = 'pending'
        ORDER BY dr.requested_at DESC
    ");
    $deletionRequests = $stmt->fetchAll();

} catch (PDOException $e) {
    $error = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Budgini</title>
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
                <li><a href="dashboard.php">Tableau de bord</a></li>
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="budgets.php">Budgets</a></li>
                <li><a href="categories.php">Catégories</a></li>
                <li><a href="admin.php" class="active">Administration</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Panel d'administration</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Global Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Utilisateurs</h3>
                <div class="value"><?php echo $totalUsers; ?></div>
            </div>
            <div class="stat-card income">
                <h3>Utilisateurs Actifs</h3>
                <div class="value"><?php echo $activeUsers; ?></div>
            </div>
            <div class="stat-card expense">
                <h3>Total Transactions</h3>
                <div class="value"><?php echo $totalTransactions; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Budgets</h3>
                <div class="value"><?php echo $totalBudgets; ?></div>
            </div>
            <div class="stat-card income">
                <h3>Revenus Totaux</h3>
                <div class="value"><?php echo formatMoney($totalIncome); ?></div>
            </div>
            <div class="stat-card expense">
                <h3>Dépenses Totales</h3>
                <div class="value"><?php echo formatMoney($totalExpenses); ?></div>
            </div>
            <div class="stat-card">
                <h3>Solde Disponible</h3>
                <div class="value"><?php echo formatMoney($totalBalance); ?></div>
            </div>
            <div class="stat-card">
                <h3>Budgets Partagés</h3>
                <div class="value"><?php echo $sharedBudgets; ?></div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="stats-grid" style="margin-top: 2rem;">
            <div class="stat-card" style="grid-column: span 2;">
                <h3>Répartition des dépenses par catégorie</h3>
                <canvas id="categoryChart" style="max-height: 300px;"></canvas>
            </div>
            <div class="stat-card" style="grid-column: span 2;">
                <h3>Évolution des dépenses mensuelles</h3>
                <canvas id="expenseChart" style="max-height: 300px;"></canvas>
            </div>
        </div>

        <!-- Budgets Overview -->
        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Aperçu des budgets</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Période</th>
                        <th>Limite</th>
                        <th>Dépensé</th>
                        <th>Membres</th>
                        <th>Statut</th>
                        <th>Créé par</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($budgets as $budget): ?>
                        <tr>
                            <td><?php echo $budget['id']; ?></td>
                            <td><?php echo htmlspecialchars($budget['name']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $budget['type'] === 'shared' ? 'info' : 'success'; ?>">
                                    <?php echo $budget['type'] === 'shared' ? 'Partagé' : 'Individuel'; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($budget['period']); ?></td>
                            <td><?php echo formatMoney($budget['total_limit']); ?></td>
                            <td><?php echo formatMoney($budget['spent']); ?></td>
                            <td><?php echo $budget['member_count']; ?></td>
                            <td>
                                <?php
                                $status = getBudgetStatus($budget['spent'], $budget['total_limit']);
                                ?>
                                <span class="badge badge-<?php echo $status['status']; ?>">
                                    <?php echo $status['text']; ?>
                                </span>
                            </td>
                            <td><?php echo $budget['created_by']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Users Management -->
        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Gestion des utilisateurs</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Email</th>
                        <th>Nom complet</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Transactions</th>
                        <th>Budgets</th>
                        <th>Date de création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $user['role'] === 'admin' ? 'danger' : 'info'; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'Administrateur' : 'Utilisateur'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['is_active'] ? 'Actif' : 'Inactif'; ?>
                                </span>
                            </td>
                            <td><?php echo $user['transaction_count']; ?></td>
                            <td><?php echo $user['budget_count']; ?></td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td>
                                <?php if ($user['id'] != $userId): ?>
                                    <div style="display: flex; gap: 0.25rem; flex-wrap: wrap;">
                                        <?php if ($user['is_active']): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="deactivate_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Désactiver cet utilisateur?');">Désactiver</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="action" value="activate_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">Activer</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="change_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="role" value="<?php echo $user['role'] === 'admin' ? 'user' : 'admin'; ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Changer le rôle de cet utilisateur?');">
                                                <?php echo $user['role'] === 'admin' ? 'Rétrograder' : 'Promouvoir'; ?>
                                            </button>
                                        </form>

                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet utilisateur et toutes ses données?');">Supprimer</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 0.875rem;">Compte actuel</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Deletion Requests -->
        <?php if (!empty($deletionRequests)): ?>
        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Demandes de suppression de compte</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Raison</th>
                        <th>Date de demande</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deletionRequests as $request): ?>
                        <tr>
                            <td><?php echo $request['id']; ?></td>
                            <td>
                                <?php echo htmlspecialchars($request['username']); ?>
                                <br><small><?php echo htmlspecialchars(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td><?php echo htmlspecialchars($request['reason'] ?? 'Non spécifiée'); ?></td>
                            <td><?php echo formatDate($request['requested_at']); ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="approve_deletion">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Approuver cette demande supprimera définitivement le compte et toutes ses données. Continuer?');">Approuver</button>
                                    </form>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="action" value="reject_deletion">
                                        <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirm('Rejeter cette demande?');">Rejeter</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- System Information -->
        <div class="table-container" style="margin-top: 2rem;">
            <div class="table-header">
                <h2>Informations système</h2>
            </div>
            <div style="padding: 1.5rem;">
                <div class="stats-grid" style="margin-bottom: 0;">
                    <div class="stat-card">
                        <h3>Version PHP</h3>
                        <div class="value" style="font-size: 1.25rem;"><?php echo phpversion(); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Version MySQL</h3>
                        <div class="value" style="font-size: 1.25rem;">
                            <?php 
                            try {
                                $stmt = $pdo->query("SELECT VERSION() as version");
                                echo $stmt->fetch()['version'];
                            } catch (PDOException $e) {
                                echo "N/A";
                            }
                            ?>
                        </div>
                    </div>
                    <div class="stat-card">
                        <h3>Base de données</h3>
                        <div class="value" style="font-size: 1.25rem;">budget_app</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <p>&copy; 2026 Budgini – Tous droits réservés.</p>
    </footer>

    <script>
        // Category Chart (Pie Chart)
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        const categoryLabels = <?php echo json_encode(array_column($categoryStats, 'name')); ?>;
        const categoryData = <?php echo json_encode(array_column($categoryStats, 'total')); ?>;
        const categoryColors = ['#2563eb', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1'];

        new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: categoryLabels,
                datasets: [{
                    data: categoryData,
                    backgroundColor: categoryColors,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });

        // Expense Evolution Chart (Bar Chart)
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        const monthlyLabels = <?php echo json_encode(array_reverse(array_column($monthlyExpenses, 'month'))); ?>;
        const monthlyData = <?php echo json_encode(array_reverse(array_column($monthlyExpenses, 'total'))); ?>;

        new Chart(expenseCtx, {
            type: 'bar',
            data: {
                labels: monthlyLabels,
                datasets: [{
                    label: 'Dépenses (€)',
                    data: monthlyData,
                    backgroundColor: '#ef4444',
                    borderColor: '#dc2626',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
