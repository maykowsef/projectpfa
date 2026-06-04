<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

requireLogin();

$userId = getCurrentUserId();
$userRole = getCurrentUserRole();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $description = sanitizeInput($_POST['description'] ?? '');
        $icon = sanitizeInput($_POST['icon'] ?? '');

        if (empty($name)) {
            $error = 'Le nom de la catégorie est obligatoire.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (name, description, icon, created_by) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $description, $icon, $userId]);
                $success = 'Catégorie ajoutée avec succès!';
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'ajout de la catégorie: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete') {
        $category_id = intval($_POST['category_id'] ?? 0);
        try {
            // Check if category is default
            $stmt = $pdo->prepare("SELECT is_default FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch();

            if ($category && $category['is_default']) {
                $error = 'Impossible de supprimer une catégorie par défaut.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ? AND (created_by = ? OR is_default = FALSE)");
                $stmt->execute([$category_id, $userId]);
                $success = 'Catégorie supprimée avec succès!';
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la suppression: ' . $e->getMessage();
        }
    }
}

// Get categories
try {
    $stmt = $pdo->query("
        SELECT c.*, u.username as creator_name 
        FROM categories c 
        LEFT JOIN users u ON c.created_by = u.id 
        ORDER BY c.is_default DESC, c.name
    ");
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - BudgetCoop</title>
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
                <li><a href="transactions.php">Transactions</a></li>
                <li><a href="budgets.php">Budgets</a></li>
                <li><a href="categories.php" class="active">Catégories</a></li>
                <li><a href="profile.php">Profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h1>Gestion des catégories</h1>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Add Category Form -->
        <div class="form-container" style="max-width: 600px;">
            <h2 class="form-title">Ajouter une catégorie personnalisée</h2>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="name">Nom de la catégorie *</label>
                    <input type="text" id="name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="icon">Icône (emoji)</label>
                    <input type="text" id="icon" name="icon" placeholder="📦" maxlength="2">
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>

                <button type="submit" class="form-button">Ajouter la catégorie</button>
            </form>
        </div>

        <!-- Categories List -->
        <div class="table-container">
            <div class="table-header">
                <h2>Toutes les catégories</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Icône</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Aucune catégorie trouvée.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td style="font-size: 1.5rem;"><?php echo htmlspecialchars($category['icon'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description'] ?? '-'); ?></td>
                                <td>
                                    <?php if ($category['is_default']): ?>
                                        <span class="badge badge-info">Par défaut</span>
                                    <?php else: ?>
                                        <span class="badge badge-success">Personnalisée</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$category['is_default']): ?>
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie?');">Supprimer</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">Non modifiable</span>
                                    <?php endif; ?>
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
