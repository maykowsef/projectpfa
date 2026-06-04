<?php
// functions.php - Common utility functions

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatMoney($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function getBudgetStatus($spent, $limit) {
    if ($limit == 0) return 'unknown';
    
    $percentage = ($spent / $limit) * 100;
    
    if ($percentage >= 100) {
        return ['status' => 'danger', 'text' => 'Dépassé', 'percentage' => $percentage];
    } elseif ($percentage >= 80) {
        return ['status' => 'warning', 'text' => 'Proche de la limite', 'percentage' => $percentage];
    } else {
        return ['status' => 'success', 'text' => 'Maîtrisé', 'percentage' => $percentage];
    }
}

function createAlert($pdo, $userId, $budgetId, $type, $message) {
    $stmt = $pdo->prepare("INSERT INTO alerts (user_id, budget_id, type, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $budgetId, $type, $message]);
}

function getUnreadAlerts($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT * FROM alerts WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function markAlertsAsRead($pdo, $userId) {
    $stmt = $pdo->prepare("UPDATE alerts SET is_read = TRUE WHERE user_id = ?");
    $stmt->execute([$userId]);
}
?>
