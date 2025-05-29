<?php
session_start();
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$paymentMethod = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
$phoneNumber = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';

// Validation des données
if ($quantity <= 0) {
    echo json_encode(['success' => false, 'error' => 'Quantité invalide']);
    exit;
}

if (!in_array($paymentMethod, ['orange', 'mtn', 'wave'])) {
    echo json_encode(['success' => false, 'error' => 'Méthode de paiement invalide']);
    exit;
}

if (empty($phoneNumber)) {
    echo json_encode(['success' => false, 'error' => 'Numéro de téléphone requis']);
    exit;
}

try {
    // Démarrer une transaction
    $pdo->beginTransaction();

    // Calculer le montant
    $amount = $quantity * 1000; // 1000 FCFA par ticket

    // Créer la transaction
    $stmt = $pdo->prepare("
        INSERT INTO transactions (
            user_id, 
            amount, 
            payment_method, 
            phone_number,
            quantity,
            status,
            created_at
        ) VALUES (
            :user_id,
            :amount,
            :payment_method,
            :phone_number,
            :quantity,
            'en_attente',
            NOW()
        )
    ");

    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':amount' => $amount,
        ':payment_method' => $paymentMethod,
        ':phone_number' => $phoneNumber,
        ':quantity' => $quantity
    ]);

    $transactionId = $pdo->lastInsertId();

    // Valider la transaction
    $pdo->commit();

    // Retourner la réponse
    echo json_encode([
        'success' => true,
        'transaction_id' => $transactionId,
        'amount' => $amount,
        'quantity' => $quantity
    ]);

} catch (Exception $e) {
    // En cas d'erreur, annuler la transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Logger l'erreur
    error_log("Erreur lors de la création de la transaction: " . $e->getMessage());

    // Retourner l'erreur
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue lors de la création de la transaction'
    ]);
} 