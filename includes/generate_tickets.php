<?php
/**
 * Génère des tickets pour une transaction approuvée
 * 
 * @param PDO $pdo Instance PDO
 * @param int $transaction_id ID de la transaction
 * @param int $user_id ID de l'utilisateur
 * @param int $quantity Nombre de tickets à générer
 * @return array Tableau contenant les IDs des tickets générés
 * @throws Exception En cas d'erreur
 */
function generateTickets($pdo, $transaction_id, $user_id, $quantity) {
    $generated_tickets = [];
    
    try {
        // Vérifier que la transaction existe et est en attente
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'en_attente' AND user_id = ?");
        $stmt->execute([$transaction_id, $user_id]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            throw new Exception("Transaction non trouvée ou déjà traitée.");
        }
        
        // Préparer la requête d'insertion des tickets
        $stmt = $pdo->prepare(
            "INSERT INTO ticket (ticket_code, user_id, purchase_date, is_winner, is_validated, id_transaction) " .
            "VALUES (:ticket_code, :user_id, NOW(), 0, 1, :id_transaction)"
        );
        
        // Générer les tickets
        for ($i = 0; $i < $quantity; $i++) {
            $ticket_code = 'TKT-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
            
            $stmt->execute([
                ':ticket_code' => $ticket_code,
                ':user_id' => $user_id,
                ':id_transaction' => $transaction_id
            ]);
            
            $generated_tickets[] = $pdo->lastInsertId();
        }
        
        return $generated_tickets;
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la génération des tickets: " . $e->getMessage());
        throw new Exception("Une erreur est survenue lors de la génération des tickets.");
    }
}

/**
 * Met à jour le statut d'une transaction
 * 
 * @param PDO $pdo Instance PDO
 * @param int $transaction_id ID de la transaction
 * @param string $status Nouveau statut ('complete' ou 'rejetée')
 * @return bool True si la mise à jour a réussi
 * @throws Exception En cas d'erreur
 */
function updateTransactionStatus($pdo, $transaction_id, $status) {
    if (!in_array($status, ['complete', 'rejetée'])) {
        throw new Exception("Statut de transaction invalide.");
    }
    
    try {
        $stmt = $pdo->prepare(
            "UPDATE transactions SET status = :status, updated_at = NOW() WHERE id = :id"
        );
        
        return $stmt->execute([
            ':status' => $status,
            ':id' => $transaction_id
        ]);
        
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du statut de la transaction: " . $e->getMessage());
        throw new Exception("Une erreur est survenue lors de la mise à jour du statut de la transaction.");
    }
}
