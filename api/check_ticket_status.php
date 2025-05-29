<?php
session_start();
require_once __DIR__ . '/../init.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

// Vérifier si le code du ticket est fourni
if (!isset($_POST['ticket_code'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Code ticket manquant']);
    exit;
}

$ticketCode = $_POST['ticket_code'];

try {
    // Vérifier si le ticket appartient à l'utilisateur et est activé
    $stmt = $pdo->prepare("
        SELECT t.*, tr.is_activated
        FROM tickets t
        JOIN transactions tr ON t.id_transaction = tr.id
        WHERE t.ticket_code = ?
        AND t.user_id = ?
        AND tr.status = 'complete'
    ");
    
    $stmt->execute([$ticketCode, $_SESSION['user_id']]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket non trouvé']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'is_activated' => (bool)$ticket['is_activated'],
        'ticket_code' => $ticketCode
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Une erreur est survenue lors de la vérification du ticket'
    ]);
} 