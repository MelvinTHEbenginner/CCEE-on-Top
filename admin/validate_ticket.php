<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../init.php';

// Vérifier les droits d'administration
requireAdmin();

// Définir le type de contenu
header('Content-Type: application/json');

header('Content-Type: application/json');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer l'ID du ticket depuis la requête
$ticketId = $_POST['ticket_id'] ?? null;

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID du ticket manquant']);
    exit;
}

try {
    // Vérifier si le ticket existe et n'est pas déjà utilisé
    $stmt = $pdo->prepare("
        SELECT t.*, tr.status as transaction_status 
        FROM tickets t
        JOIN transactions tr ON t.transaction_id = tr.id
        WHERE t.id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new Exception('Ticket non trouvé');
    }

    // Vérifier si la transaction est complète
    if ($ticket['transaction_status'] !== 'complete') {
        throw new Exception('La transaction associée à ce ticket n\'est pas complétée');
    }

    // Vérifier si le ticket est déjà utilisé
    if ($ticket['is_used']) {
        throw new Exception('Ce ticket a déjà été utilisé');
    }

    // Mettre à jour le ticket comme utilisé
    $updateStmt = $pdo->prepare("
        UPDATE tickets 
        SET is_used = 1, 
            used_at = NOW(),
            used_by = ?
        WHERE id = ? AND is_used = 0
    ");
    
    $updateStmt->execute([$_SESSION['user_id'], $ticketId]);

    if ($updateStmt->rowCount() === 0) {
        throw new Exception('Échec de la validation du ticket');
    }

    // Tout s'est bien passé
    echo json_encode([
        'success' => true,
        'message' => 'Ticket validé avec succès',
        'ticket' => [
            'id' => $ticket['id'],
            'code' => $ticket['ticket_code'],
            'used_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
