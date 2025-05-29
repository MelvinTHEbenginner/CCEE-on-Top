<?php
/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur est admin
 * @return bool
 */
function isAdmin() {
    if (!isLoggedIn()) {
        return false;
    }

    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $user && $user['is_admin'] == 1;
    } catch (Exception $e) {
        error_log("Erreur lors de la vérification admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si l'utilisateur est connecté et redirige si non
 * @throws Exception Si l'utilisateur n'est pas connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = "Vous devez être connecté pour accéder à cette page.";
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Vérifie si l'utilisateur est admin et redirige si non
 */
function requireAdmin() {
    requireLogin();
    
    if (!isAdmin()) {
        $_SESSION['error'] = "Accès non autorisé. Vous devez être administrateur.";
        header('Location: /dashboard/');
        exit;
    }
}

/**
 * Vérifie si l'utilisateur est connecté et non administrateur
 * @throws Exception Si l'utilisateur n'est pas connecté ou est administrateur
 */
function requireUser() {
    requireLogin();
    if (isAdmin()) {
        throw new Exception('Cette page est réservée aux utilisateurs non administrateurs.');
    }
}

/**
 * Nettoie les données entrées par l'utilisateur
 * @param mixed $data
 * @return string
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Génère un token CSRF
 * @return string
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
