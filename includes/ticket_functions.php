<?php
require_once __DIR__ . '/../init.php';

/**
 * Récupère des tickets disponibles pour une transaction
 * @param int $transactionId ID de la transaction
 * @param int $quantity Nombre de tickets nécessaires
 * @return array|bool Tableau des tickets attribués ou false en cas d'échec
 */
function assignTicketsToTransaction($transactionId, $quantity) {
    global $pdo;
    
    try {
        // Démarrer une transaction
        $pdo->beginTransaction();
        
        // Vérifier si la transaction existe et n'est pas déjà traitée
        $stmt = $pdo->prepare("
            SELECT t.*, u.id as user_id 
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE t.id = ? AND t.status = 'en_attente'
            FOR UPDATE
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();
        
        if (!$transaction) {
            throw new Exception("Transaction introuvable ou déjà traitée");
        }
        
        // Sélectionner les tickets disponibles
        $stmt = $pdo->prepare("
            SELECT id 
            FROM tickets 
            WHERE user_id = 0 
            AND id_transaction IS NULL
            LIMIT ?
        ");
        $stmt->execute([$quantity]);
        $tickets = $stmt->fetchAll();
        
        if (count($tickets) < $quantity) {
            throw new Exception("Pas assez de tickets disponibles");
        }
        
        // Assigner les tickets à l'utilisateur et à la transaction
        $ticketIds = array_column($tickets, 'id');
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET user_id = ?,
                id_transaction = ?,
                purchase_date = NOW()
            WHERE id IN (" . implode(',', $ticketIds) . ")
        ");
        $stmt->execute([$transaction['user_id'], $transactionId]);
        
        // Mettre à jour le statut de la transaction
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'complete',
                is_activated = 1,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$transactionId]);
        
        // Valider la transaction
        $pdo->commit();
        
        return true;
        
    } catch (Exception $e) {
        // En cas d'erreur, annuler la transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur lors de l'assignation des tickets: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcule les statistiques des tickets
 * @return array Tableau des statistiques
 */
function getTicketStats() {
    global $pdo;
    
    $stats = [];
    
    // Nombre total de tickets dans le système
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
    $totalTickets = $stats['total_tickets'] = (int)$stmt->fetchColumn();
    
    // Nombre de tickets disponibles (non attribués)
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE id_transaction IS NULL");
    $availableTickets = $stats['available_tickets'] = (int)$stmt->fetchColumn();
    
    // Nombre de tickets attribués (vendus)
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE id_transaction IS NOT NULL");
    $soldTickets = $stats['sold_tickets'] = (int)$stmt->fetchColumn();
    
    // Nombre de tickets validés
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE is_validated = 1");
    $validatedTickets = $stats['validated_tickets'] = (int)$stmt->fetchColumn();
    
    // Calcul de la chance de gagner (pourcentage de tickets possédés par l'utilisateur)
    $userTickets = 0;
    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userTickets = (int)$stmt->fetchColumn();
    }
    
    $stats['ticket_count'] = $userTickets;
    $stats['win_chance'] = ($soldTickets > 0) ? ($userTickets / $soldTickets) * 100 : 0;
    
    // Pourcentage de chance (basé sur les tickets vendus / totaux)
    $stats['win_chance'] = $stats['total_tickets'] > 0 
        ? round(($stats['sold_tickets'] / $stats['total_tickets']) * 100, 2)
        : 0;
    
    return $stats;
}

/**
 * Récupère les tickets d'un utilisateur
 * @param int $userId ID de l'utilisateur
 * @return array Liste des tickets avec les détails des transactions
 */
function getUserTickets($userId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.ticket_code,
            t.is_validated,
            t.date_creation,
            t.date_attribution,
            tr.id as transaction_id,
            tr.transaction_date,
            tr.amount,
            tr.status as transaction_status,
            tr.ticket_count,
            u.fullname as user_name,
            u.email as user_email
        FROM tickets t
        JOIN transactions tr ON t.id_transaction = tr.id
        JOIN users u ON tr.user_id = u.id
        WHERE tr.user_id = ?
        ORDER BY t.date_attribution DESC, t.date_creation DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Récupère les tickets disponibles
 * @return array Liste des tickets disponibles
 */
function getAvailableTickets() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT id, ticket_code, date_creation
        FROM tickets 
        WHERE id_transaction IS NULL
        ORDER BY date_creation ASC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Valide un ticket (marqué comme utilisé)
 * @param string $ticketCode Code du ticket à valider
 * @return bool Succès de l'opération
 */
function validateTicket($ticketCode) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, tr.status as transaction_status, u.fullname, u.email
            FROM tickets t
            JOIN transactions tr ON t.id_transaction = tr.id
            JOIN users u ON t.user_id = u.id
            WHERE t.ticket_code = ?
        ");
        $stmt->execute([$ticketCode]);
        return $stmt->fetch();
    } catch (Exception $e) {
        error_log("Erreur lors de la validation du ticket: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les tickets d'une transaction
 */
function getTransactionTickets($transactionId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT t.*, tr.status as transaction_status
            FROM tickets t
            JOIN transactions tr ON t.id_transaction = tr.id
            WHERE tr.id = ?
        ");
        $stmt->execute([$transactionId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération des tickets: " . $e->getMessage());
        return [];
    }
}

/**
 * Marque un ticket comme utilisé
 */
function markTicketAsUsed($ticketId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET is_used = 1,
                used_at = NOW()
            WHERE id = ?
            AND is_validated = 1
            AND is_used = 0
        ");
        return $stmt->execute([$ticketId]);
    } catch (Exception $e) {
        error_log("Erreur lors du marquage du ticket: " . $e->getMessage());
        return false;
    }
}