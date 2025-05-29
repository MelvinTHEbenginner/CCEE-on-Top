<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/ticket_functions.php';

// Définir le type de contenu de la réponse
header('Content-Type: application/json');

// Activer le reporting d'erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fonction pour envoyer une réponse d'erreur
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    sendError('Non authentifié', 401);
}

// Vérifier si c'est une requête POST avec les paramètres nécessaires
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['method'], $_POST['quantity'])) {
    sendError('Méthode non autorisée ou paramètres manquants');
}

// Vérifier la connexion à la base de données
if (!isset($pdo)) {
    sendError('Erreur de connexion à la base de données', 500);
}

// Récupérer et valider les paramètres
try {
    // Récupérer les paramètres
    $quantity = intval($_POST['quantity']);
    $amount = $quantity * 1000; // 1000 FCFA par ticket
    $userId = $_SESSION['user_id'];
    
    // Récupérer les informations de paiement de l'utilisateur
    $stmt = $pdo->prepare("SELECT phone, operator FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        sendError('Utilisateur non trouvé', 404);
    }
    
    $paymentMethod = $user['operator'] ?? 'orange'; // Par défaut à orange si non défini
    $phoneNumber = $user['phone'];
    
    if (empty($phoneNumber)) {
        sendError('Veuillez configurer votre numéro de téléphone dans votre profil avant de procéder au paiement');
    }
    
    // Valider la quantité
    if ($quantity < 1 || $quantity > 10) {
        sendError('Quantité de tickets invalide (1-10)');
    }
    
    // Valider le numéro de téléphone
    if (empty($phoneNumber)) {
        sendError('Le numéro de téléphone est requis');
    }
    
    // Valider le format du numéro de téléphone
    $phonePatterns = [
        'orange' => '/^07[0-9]{8}$/',
        'mtn' => '/^05[0-9]{8}$/',
        'wave' => '/^01[0-9]{8}$/'
    ];
    
    // Si le numéro ne correspond pas au format attendu, on essaie de le formater
    if (!preg_match($phonePatterns[$paymentMethod], $phoneNumber)) {
        // Supprimer les espaces et caractères spéciaux
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Si le numéro commence par 225 (indicatif pays), on le supprime
        if (strpos($phoneNumber, '225') === 0) {
            $phoneNumber = substr($phoneNumber, 3);
        }
        
        // Ajouter le préfixe approprié selon l'opérateur
        $prefixes = [
            'orange' => '07',
            'mtn' => '05',
            'wave' => '01'
        ];
        
        // Si le numéro ne commence pas par le bon préfixe, on le corrige
        $prefix = $prefixes[$paymentMethod] ?? '07';
        if (strpos($phoneNumber, $prefix) !== 0) {
            // Supprimer les premiers chiffres jusqu'à avoir 8 chiffres
            $phoneNumber = substr($phoneNumber, -8);
            $phoneNumber = $prefix . $phoneNumber;
        }
    }
    
    // Vérifier s'il y a assez de tickets disponibles
    $stmt = $pdo->prepare("SELECT COUNT(*) as available FROM tickets WHERE id_transaction IS NULL AND is_validated = 0");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['available'] < $quantity) {
        throw new Exception("Désolé, il n'y a pas assez de tickets disponibles");
    }
    
    // Démarrer une transaction
    $pdo->beginTransaction();
    
    try {
        // Créer une nouvelle transaction
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                user_id, 
                amount, 
                payment_method, 
                phone_number,
                status, 
                quantity, 
                created_at
            ) VALUES (:user_id, :amount, :payment_method, :phone_number, 'en_attente', :quantity, NOW())
        ");
        
        // Exécuter la requête avec les paramètres nommés
        $stmt->execute([
            ':user_id' => $userId,
            ':amount' => $amount,
            ':payment_method' => $paymentMethod,
            ':phone_number' => $phoneNumber,
            ':quantity' => $quantity
        ]);
        
        $transactionId = $pdo->lastInsertId();
        
        if (!$transactionId) {
            throw new Exception("Échec de la création de la transaction");
        }
        
        // Attribuer les tickets à la transaction
        $assignedTickets = assignTicketsToTransaction($transactionId, $quantity);
        
        if (!$assignedTickets || !is_array($assignedTickets) || count($assignedTickets) < $quantity) {
            throw new Exception("Désolé, il n'y a pas assez de tickets disponibles");
        }
        
        // Valider la transaction
        $pdo->commit();
        
        // Retourner la réponse
        echo json_encode([
            'success' => true, 
            'transaction_id' => $transactionId,
            'status' => 'en_attente',
            'message' => 'Transaction créée avec succès',
            'redirect' => "/CCEE-on-Top-main/payment/process_payment.php?transaction_id=$transactionId"
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    
} catch (Exception $e) {
    // Journaliser l'erreur
    error_log("Erreur de paiement: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Retourner l'erreur
    sendError($e->getMessage());
}
