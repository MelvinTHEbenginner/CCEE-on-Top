<?php
/**
 * Récupère des tickets disponibles pour une transaction
 * @param int $transactionId ID de la transaction
 * @param int $quantity Nombre de tickets nécessaires
 * @return array|bool Tableau des tickets attribués ou false en cas d'échec
 */
function assignTicketsToTransaction($transactionId, $quantity) {
    try {
        $pdo = $GLOBALS['pdo'];
        $pdo->beginTransaction();
        
        // Récupérer les informations de la transaction
        $stmt = $pdo->prepare("
            SELECT user_id 
            FROM transactions 
            WHERE id = ? AND status = 'en_attente'
            FOR UPDATE
        ");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction invalide ou déjà traitée");
        }
        $userId = $transaction['user_id'];
        
        // Sélectionner des tickets disponibles
        $stmt = $pdo->prepare("
            SELECT id 
            FROM tickets 
            WHERE id_transaction IS NULL 
            AND is_validated = 0 
            LIMIT ?
            FOR UPDATE SKIP LOCKED
        ");
        $stmt->execute([$quantity]);
        $availableTickets = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($availableTickets) < $quantity) {
            throw new Exception("Pas assez de tickets disponibles");
        }
        
        // Mettre à jour les tickets sélectionnés
        $placeholders = rtrim(str_repeat('?,', $quantity), ',');
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET id_transaction = ?, 
                user_id = ?,
                date_attribution = NOW()
            WHERE id IN ($placeholders)
        ");
        $params = array_merge([$transactionId, $userId], $availableTickets);
        $stmt->execute($params);
        
        // Récupérer les informations des tickets attribués
        $stmt = $pdo->prepare("
            SELECT ticket_code 
            FROM tickets 
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($availableTickets);
        $ticketCodes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $pdo->commit();
        return $ticketCodes;
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Erreur lors de l'attribution des tickets : " . $e->getMessage());
        return false;
    }
}

/**
 * Calcule les statistiques des tickets
 * @return array Tableau des statistiques
 */
function getTicketStats() {
    $pdo = $GLOBALS['pdo'];
    
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
    $stmt = $GLOBALS['pdo']->prepare("
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
    $stmt = $GLOBALS['pdo']->query("
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
    try {
        $pdo = $GLOBALS['pdo'];
        $pdo->beginTransaction();
        
        // Vérifier si le ticket existe et n'est pas déjà validé
        $stmt = $pdo->prepare("
            SELECT id, is_validated 
            FROM tickets 
            WHERE ticket_code = ?
            FOR UPDATE
        ");
        $stmt->execute([$ticketCode]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket) {
            throw new Exception('Ticket non trouvé');
        }
        
        if ($ticket['is_validated']) {
            throw new Exception('Ce ticket a déjà été validé');
        }
        
        // Mettre à jour le statut du ticket
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET is_validated = 1,
                date_validation = NOW()
            WHERE ticket_code = ?
        ");
        $stmt->execute([$ticketCode]);
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Erreur lors de la validation du ticket : " . $e->getMessage());
        return false;
    }
}
