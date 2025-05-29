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
    // Supprimer tous les caractères non numériques
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Vérifier la longueur (10 chiffres pour la Côte d'Ivoire)
    if (strlen($phone) !== 10) {
        return false;
    }
    
    // Vérifier le préfixe (01, 05, 07 pour Wave, MTN, Orange respectivement)
    $validPrefixes = ['01', '05', '07'];
    $prefix = substr($phone, 0, 2);
    
    return in_array($prefix, $validPrefixes);
}
