<?php
// Inclure le fichier de configuration
require_once __DIR__ . '/config.php';

// Inclure les fonctions d'authentification en premier
require_once __DIR__ . '/includes/auth_functions.php';

// Fonctions utilitaires supplémentaires
function getUserRole() {
    return isAdmin() ? 'admin' : 'user';
}

function getUserName() {
    return isset($_SESSION['fullname']) ? $_SESSION['fullname'] : 'Utilisateur';
}

// Autres fonctions utiles
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/payment_functions.php';
require_once __DIR__ . '/includes/ticket_functions.php';

?>
