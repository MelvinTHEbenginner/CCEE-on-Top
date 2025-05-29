<?php
// Fonction pour créer une transaction
function createTransaction($userId, $paymentMethod, $quantity) {
    global $conn;
    
    try {
        $amount = $quantity * 1000;
        $stmt = $conn->prepare("
            INSERT INTO transactions (user_id, amount, payment_method, status, created_at, quantity) 
            VALUES (?, ?, ?, 'en_attente', NOW(), ?)");
        $stmt->bind_param("iiis", $userId, $amount, $paymentMethod, $quantity);
        $stmt->execute();
        
        return [
            'id' => $conn->insert_id,
            'user_id' => $userId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'quantity' => $quantity,
            'status' => 'en_attente'
        ];
    } catch (Exception $e) {
        error_log("Erreur lors de la création de la transaction: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer l'état d'une transaction
function getTransactionState($transactionId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT status, is_activated FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération de l'état: " . $e->getMessage());
        return false;
    }
}

// Fonction pour confirmer un paiement
function confirmPayment($transactionId) {
    global $pdo;
    
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Récupérer les informations de la transaction
        $stmt = $pdo->prepare("SELECT quantity FROM transactions WHERE id = ? AND status = 'en_attente'");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction introuvable ou déjà traitée");
        }
        
        // Assigner les tickets à la transaction en utilisant la fonction de ticket_functions.php
        $assignedTickets = assignTicketsToTransaction($transactionId, $transaction['quantity']);
        
        if (!$assignedTickets) {
            throw new Exception("Erreur lors de l'assignation des tickets");
        }
        
        // Mettre à jour la transaction
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'complete', 
                is_activated = 1,
                updated_at = NOW() 
            WHERE id = ?");
        $stmt->execute([$transactionId]);
        
        // Valider la transaction
        $pdo->commit();
        return true;
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur lors de la confirmation du paiement: " . $e->getMessage());
        return false;
    }
}

// Fonction pour récupérer les tickets validés
function getValidatedTickets($transactionId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT * FROM tickets 
            WHERE id_transaction = ? 
            AND is_validated = 1");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des tickets validés: " . $e->getMessage());
        return [];
    }
}
