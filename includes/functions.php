<?php
/**
 * Fonctions utilitaires générales pour l'application Tombola CCEE
 */

/**
 * Redirige vers une URL spécifiée
 * 
 * @param string $url URL de destination
 * @param int $statusCode Code HTTP de redirection (par défaut: 302)
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Formate un montant avec le séparateur de milliers
 * 
 * @param float $amount Montant à formater
 * @param int $decimals Nombre de décimales
 * @return string Montant formaté
 */
function format_amount($amount, $decimals = 0) {
    return number_format($amount, $decimals, ',', ' ');
}

/**
 * Nettoie une chaîne de caractères pour la sécurité
 * 
 * @param string $data Donnée à nettoyer
 * @return string Donnée nettoyée
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Vérifie si une requête est de type AJAX
 * 
 * @return bool True si la requête est AJAX, sinon false
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Affiche une réponse JSON et arrête l'exécution
 * 
 * @param mixed $data Données à encoder en JSON
 * @param int $statusCode Code HTTP de la réponse
 */
function json_response($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Génère un jeton CSRF
 * 
 * @return string Jeton CSRF
 */
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie la validité d'un jeton CSRF
 * 
 * @param string $token Jeton à vérifier
 * @return bool True si le jeton est valide, sinon false
 */
function verify_csrf_token($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formate une date au format français
 * 
 * @param string $date Date à formater
 * @param bool $withTime Inclure l'heure
 * @return string Date formatée
 */
function format_date($date, $withTime = false) {
    $format = $withTime ? 'd/m/Y H:i' : 'd/m/Y';
    return date($format, strtotime($date));
}

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return bool True si un utilisateur est connecté, sinon false
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur actuel est un administrateur
 * 
 * @return bool True si l'utilisateur est un administrateur, sinon false
 */
function is_admin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Force l'authentification
 * 
 * @param string $redirect URL de redirection si non connecté
 */
function require_auth($redirect = '/auth/login.php') {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect($redirect);
    }
}

/**
 * Force les droits administrateur
 * 
 * @param string $redirect URL de redirection si non administrateur
 */
function require_admin($redirect = '/dashboard/') {
    require_auth($redirect);
    
    if (!is_admin()) {
        set_flash_message('error', 'Accès refusé. Vous devez être administrateur.');
        redirect($redirect);
    }
}

/**
 * Définit un message flash
 * 
 * @param string $type Type de message (success, error, warning, info)
 * @param string $message Contenu du message
 */
function set_flash_message($type, $message) {
    $_SESSION['flash_messages'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Récupère et supprime les messages flash
 * 
 * @return array Tableau des messages flash
 */
function get_flash_messages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Affiche les messages flash formatés
 */
function display_flash_messages() {
    $messages = get_flash_messages();
    
    if (empty($messages)) {
        return;
    }
    
    $output = '<div class="flash-messages">';
    
    foreach ($messages as $message) {
        $output .= sprintf(
            '<div class="alert alert-%s">%s</div>',
            htmlspecialchars($message['type'], ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($message['message'], ENT_QUOTES, 'UTF-8')
        );
    }
    
    $output .= '</div>';
    
    echo $output;
}

/**
 * Valide une adresse email
 * 
 * @param string $email Adresse email à valider
 * @return bool True si l'email est valide, sinon false
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valide un numéro de téléphone
 * 
 * @param string $phone Numéro de téléphone à valider
 * @return bool True si le numéro est valide, sinon false
 */
function is_valid_phone($phone) {
    // Format international: +225 XX XX XX XX ou 00225 XX XX XX XX
    return preg_match('/^(\+225|00225)?[0-9]{2}[\s.-]?[0-9]{2}[\s.-]?[0-9]{2}[\s.-]?[0-9]{2}$/', $phone);
}
