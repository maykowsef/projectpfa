<?php
// auth.php - Authentication functions
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}

function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUserRole() {
    return $_SESSION['role'] ?? 'user';
}

function getCurrentUserName() {
    return $_SESSION['username'] ?? '';
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>
