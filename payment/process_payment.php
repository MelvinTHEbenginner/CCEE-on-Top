<?php
// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(0);

// Démarrer le buffer de sortie
ob_start();

session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/ticket_functions.php';

// Définir le type de contenu
header('Content-Type: application/json');

// Fonction pour envoyer une réponse JSON et terminer
function sendJsonResponse($data) {
    // Nettoyer tout buffer de sortie précédent
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode($data);
    exit;
}

// Fonction pour logger les erreurs
function logError($message, $data = null) {
    error_log("=== Erreur Process Payment ===");
    error_log($message);
    if ($data) {
        error_log("Données: " . print_r($data, true));
    }
    error_log("============================");
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    logError("Utilisateur non connecté");
    sendJsonResponse(['success' => false, 'error' => 'Non authentifié']);
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logError("Méthode non autorisée: " . $_SERVER['REQUEST_METHOD']);
    sendJsonResponse(['success' => false, 'error' => 'Méthode non autorisée']);
}

// Log des données reçues
error_log("Données reçues: " . print_r($_POST, true));

// Récupérer et valider les données
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$phoneNumber = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';

// Validation des données
if ($quantity <= 0 || $quantity > 10) {
    logError("Quantité invalide", ['quantity' => $quantity]);
    sendJsonResponse(['success' => false, 'error' => 'Quantité invalide']);
}

if (!in_array($paymentMethod, ['orange', 'mtn', 'wave'])) {
    logError("Méthode de paiement invalide", ['payment_method' => $paymentMethod]);
    sendJsonResponse(['success' => false, 'error' => 'Méthode de paiement invalide']);
}

if (empty($phoneNumber)) {
    logError("Numéro de téléphone manquant");
    sendJsonResponse(['success' => false, 'error' => 'Numéro de téléphone requis']);
}

try {
    // Log début de la transaction
    error_log("Début de la transaction");

    // Démarrer une transaction
    $pdo->beginTransaction();

    // Vérifier s'il y a assez de tickets disponibles
        $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM tickets 
        WHERE user_id = 0 
        AND id_transaction IS NULL
    ");
    $stmt->execute();
    $availableTickets = $stmt->fetchColumn();

    error_log("Tickets disponibles: " . $availableTickets);

    if ($availableTickets < $quantity) {
        throw new Exception("Pas assez de tickets disponibles. Disponibles: {$availableTickets}, Demandés: {$quantity}");
    }

    // Calculer le montant
    $amount = $quantity * 1000; // 1000 FCFA par ticket

    // Créer la transaction
    $insertQuery = "
        INSERT INTO transactions (
            user_id, 
            amount, 
            payment_method, 
            phone_number,
            quantity,
            status,
            is_activated,
            created_at
        ) VALUES (
            :user_id,
            :amount,
            :payment_method,
            :phone_number,
            :quantity,
            'complete',
            0,
            NOW()
        )
    ";

    error_log("Requête d'insertion: " . $insertQuery);

    $stmt = $pdo->prepare($insertQuery);
    $params = [
        ':user_id' => $_SESSION['user_id'],
        ':amount' => $amount,
        ':payment_method' => $paymentMethod,
        ':phone_number' => $phoneNumber,
        ':quantity' => $quantity
    ];

    error_log("Paramètres: " . print_r($params, true));

    $stmt->execute($params);
    $transactionId = $pdo->lastInsertId();

    error_log("Transaction créée avec ID: " . $transactionId);

    // Sélectionner et assigner les tickets
    $updateQuery = "
                UPDATE tickets 
        SET user_id = :user_id,
            id_transaction = :transaction_id,
            purchase_date = NOW()
        WHERE user_id = 0 
        AND id_transaction IS NULL
        LIMIT :quantity
    ";

    error_log("Requête de mise à jour des tickets: " . $updateQuery);

    $stmt = $pdo->prepare($updateQuery);
    $stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(':transaction_id', $transactionId, PDO::PARAM_INT);
    $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->execute();

    error_log("Nombre de tickets mis à jour: " . $stmt->rowCount());

    // Récupérer les tickets assignés
                $stmt = $pdo->prepare("
        SELECT ticket_code 
        FROM tickets 
        WHERE id_transaction = ?
    ");
    $stmt->execute([$transactionId]);
    $tickets = $stmt->fetchAll(PDO::FETCH_COLUMN);

    error_log("Tickets assignés: " . print_r($tickets, true));

    // Valider la transaction
                $pdo->commit();
    error_log("Transaction validée avec succès");

    // Retourner la réponse
    sendJsonResponse([
        'success' => true,
        'message' => 'Tickets attribués avec succès',
        'transaction_id' => $transactionId,
        'tickets' => $tickets
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        error_log("Transaction annulée");
    }
    
    logError("Erreur lors du traitement: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());

    sendJsonResponse([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
