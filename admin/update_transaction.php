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
    // Vérifier que la transaction est en attente
    $stmt = $pdo->prepare("
        SELECT t.*, u.email, u.phone, t.quantity 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.status = 'en_attente'
    ");
    $stmt->execute([$transaction_id]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        throw new Exception('Transaction introuvable ou déjà traitée');
    }

    if ($action === 'complete') {
        // Utiliser la fonction confirmPayment pour gérer tout le processus
        if (!confirmPayment($transaction_id)) {
            throw new Exception('Erreur lors de la confirmation du paiement');
        }
        
        // Récupérer les tickets validés
        $tickets = getValidatedTickets($transaction_id);
        
        if (empty($tickets)) {
            throw new Exception('Erreur : Aucun ticket n\'a été assigné');
        }

        $ticket_codes = array_column($tickets, 'ticket_code');
        
        $response = [
            'success' => true,
            'message' => 'Transaction confirmée avec succès. Les tickets ont été assignés.',
            'tickets' => $ticket_codes,
            'tickets_count' => count($ticket_codes)
        ];

        // TODO: Envoyer un email de confirmation avec les tickets
        // send_confirmation_email($transaction['email'], $tickets);

    } else {
        // Mettre à jour le statut de la transaction comme rejetée
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET status = 'rejetée',
                updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$transaction_id]);

        $response = [
            'success' => true,
            'message' => 'Transaction rejetée avec succès'
        ];
    }

} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
