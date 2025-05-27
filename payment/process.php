<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si c'est une requête GET avec les paramètres nécessaires
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['method'], $_POST['quantity'])) {
    $paymentMethod = $_POST['method'];
    $quantity = intval($_POST['quantity']);
    $amount = $quantity * 1000; // 1000 FCFA par ticket
    $userId = $_SESSION['user_id'];
    
    // Valider la méthode de paiement
    $validMethods = ['orange', 'mtn', 'wave'];
    if (!in_array($paymentMethod, $validMethods)) {
        http_response_code(400);
        echo json_encode(['error' => 'Méthode de paiement invalide']);
        exit;
    }
    
    // Valider la quantité
    if ($quantity < 1 || $quantity > 10) {
        http_response_code(400);
        echo json_encode(['error' => 'Quantité de tickets invalide (1-10)']);
        exit;
    }
    
    try {
        // Vérifier s'il y a assez de tickets disponibles
        $stmt = $conn->prepare("SELECT COUNT(*) as available FROM tickets WHERE status = 'disponible'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['available'] < $quantity) {
            throw new Exception("Désolé, il n'y a pas assez de tickets disponibles");
        }
        
        // Démarrer une transaction
        $conn->begin_transaction();
        
        // Créer une nouvelle transaction
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, amount, payment_method, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("ids", $userId, $amount, $paymentMethod);
        
        if (!$stmt->execute()) {
            throw new Exception("Erreur lors de la création de la transaction");
        }
        
        $transactionId = $conn->insert_id;
        $stmt->close();
        
        // Sélectionner et réserver les tickets disponibles
        $stmt = $conn->prepare("
            UPDATE tickets 
            SET status = 'réservé', 
                reserved_at = NOW(),
                transaction_id = ?
            WHERE id IN (
                SELECT id FROM (
                    SELECT id FROM tickets 
                    WHERE status = 'disponible' 
                    LIMIT ?
                ) as t
            )
        ");
        $stmt->bind_param("ii", $transactionId, $quantity);
        
        if (!$stmt->execute() || $stmt->affected_rows != $quantity) {
            throw new Exception("Erreur lors de la réservation des tickets");
        }
        
        // Valider la transaction
        $conn->commit();
        
        // Rediriger vers le processus de paiement
        echo json_encode([
            'success' => true, 
            'transaction_id' => $transactionId,
            'redirect' => "/payment/process_payment.php?transaction_id=$transactionId"
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if (isset($conn)) {
            $conn->rollback();
        }
        
        // Journaliser l'erreur
        error_log("Erreur de paiement: " . $e->getMessage());
        
        // Rediriger vers la page d'erreur
        header('Location: /payment/?error=1');
        exit;
    }
} else {
    // Si la requête n'est pas une requête GET valide
    http_response_code(400);
    echo json_encode(['error' => 'Requête invalide']);
    exit;
}
