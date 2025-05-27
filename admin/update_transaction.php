<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../init.php';

// Vérifier les droits d'administration
requireAdmin();

// Définir le type de contenu
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Méthode non autorisée']));
}

$transaction_id = intval($_POST['transaction_id'] ?? 0);
$action = $_POST['action'] === 'complete' ? 'complete' : 'rejetée';

try {
    $pdo->beginTransaction();

    // Vérifier que la transaction est en attente
    $stmt = $pdo->prepare("
        SELECT t.*, u.email, u.phone, t.quantity 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.status = 'en_attente'
        FOR UPDATE
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction introuvable ou déjà traitée');
    }

    if ($action === 'complete') {
        // 1. Attribuer des tickets existants à cette transaction
        $assignedTickets = assignTicketsToTransaction($transaction_id, $transaction['quantity']);
        
        if ($assignedTickets === false) {
            throw new Exception('Erreur lors de l\'attribution des tickets. Pas assez de tickets disponibles.');
        }
        
        // 2. Mettre à jour le statut de la transaction
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'confirme', 
                updated_at = NOW(),
                ticket_count = ?
            WHERE id = ?
        ");
        $stmt->execute([count($assignedTickets), $transaction_id]);
        
        // 3. Récupérer les informations des tickets créés
        $stmt = $pdo->prepare("
            SELECT ticket_code 
            FROM tickets 
            WHERE id_transaction = ?
            ORDER BY id
        ");
        $stmt->execute([$transaction_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $response = [
            'success' => true,
            'message' => 'Transaction confirmée avec succès. Les tickets ont été créés.',
            'tickets' => $tickets,
            'tickets_count' => count($tickets)
        ];

        // TODO: Envoyer un email de confirmation avec les tickets
        // send_confirmation_email($transaction['email'], $tickets);

    } else {
        // 1. Mettre à jour le statut de la transaction
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'rejetee',
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$transaction_id]);

        $response = [
            'success' => true,
            'message' => 'Transaction rejetée et tickets libérés avec succès'
        ];
    }

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
