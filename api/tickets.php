<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

try {
    // Récupérer les tickets de l'utilisateur
    $stmt = $pdo->prepare('
        SELECT 
            t.*,
            tr.amount,
            tr.payment_method,
            tr.status as transaction_status,
            tr.created_at as transaction_date,
            p.name as prize_name,
            p.description as prize_description,
            p.image_url as prize_image
        FROM ticket t
        JOIN transactions tr ON t.id_transaction = tr.id
        LEFT JOIN prizes p ON t.prize_id = p.id
        WHERE t.user_id = ?
        ORDER BY t.purchase_date DESC
    ');
    $stmt->execute([$_SESSION['user_id']]);
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formater les données pour la réponse
    $formattedTickets = array_map(function($ticket) {
        $formattedTicket = [
            'id' => $ticket['id'],
            'ticket_code' => $ticket['ticket_code'],
            'purchase_date' => $ticket['purchase_date'],
            'is_winner' => (bool)$ticket['is_winner'],
            'is_validated' => (bool)$ticket['is_validated'],
            'prize' => null,
            'transaction' => [
                'id' => $ticket['id_transaction'],
                'amount' => (float)$ticket['amount'],
                'payment_method' => $ticket['payment_method'],
                'status' => $ticket['transaction_status'],
                'date' => $ticket['transaction_date']
            ]
        ];

        // Ajouter les informations sur le prix si le ticket est gagnant
        if ($ticket['is_winner'] && $ticket['prize_name']) {
            $formattedTicket['prize'] = [
                'name' => $ticket['prize_name'],
                'description' => $ticket['prize_description'],
                'image_url' => $ticket['prize_image']
            ];
        }

        return $formattedTicket;
    }, $tickets);

    // Retourner la réponse
    echo json_encode([
        'success' => true,
        'data' => $formattedTickets,
        'count' => count($formattedTickets)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log("API Tickets Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'error' => $e->getMessage()
    ]);
}
