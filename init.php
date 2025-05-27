<?php
/**
 * Fichier d'initialisation de l'application
 * 
 * Ce fichier est inclus au début de chaque page pour initialiser
 * les configurations, les dépendances et les fonctions globales.
 */

// Démarrer la session si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Définir le fuseau horaire
date_default_timezone_set('Africa/Abidjan');

// Définir les constantes de base
define('ROOT_PATH', __DIR__);
define('APP_PATH', dirname(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Charger la configuration de l'application
$config = require ROOT_PATH . '/config/app.php';

// Définir les constantes de configuration
define('APP_NAME', $config['app']['name']);
define('APP_URL', rtrim($config['app']['url'], '/'));
define('ADMIN_EMAIL', $config['app']['admin_email']);

define('TICKET_PRICE', $config['tickets']['price']);
define('MIN_TICKETS', $config['tickets']['min_purchase']);
define('MAX_TICKETS', $config['tickets']['max_purchase']);
define('TOTAL_TICKETS', $config['tickets']['total_tickets']);

// Charger les dépendances
require_once ROOT_PATH . '/config/database.php';

// Charger les fonctions utilitaires
require_once ROOT_PATH . '/includes/functions.php';
require_once ROOT_PATH . '/includes/ticket_functions.php';
require_once ROOT_PATH . '/includes/payment_functions.php';

// Fonctions utilitaires
function isAdmin($email) {
    global $pdo;
    
    if (!isset($pdo)) {
        error_log("Erreur: La connexion à la base de données n'est pas disponible");
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        return $user && $user['is_admin'] == 1;
    } catch (PDOException $e) {
        error_log("Erreur de vérification des droits admin: " . $e->getMessage());
        return false;
    }
}

function requireAdmin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /CCEE-on-Top-main/auth/login.php');
        exit();
    }
    
    if (!isset($_SESSION['email']) || !isAdmin($_SESSION['email'])) {
        header('HTTP/1.0 403 Forbidden');
        echo 'Accès refusé. Vous n\'avez pas les droits d\'administration.';
        exit();
    }
}

// Fonction pour afficher les messages flash
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}
