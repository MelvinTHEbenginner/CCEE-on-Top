<?php
require_once __DIR__ . '/../init.php';
requireAdmin();

// Vérification de la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupération et validation des données
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['ticket_code']) || empty($data['ticket_code'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Code ticket manquant']);
    exit;
}

$ticket_code = trim($data['ticket_code']);

try {
    // Vérification du ticket
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE ticket_code = ?");
    $stmt->execute([$ticket_code]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['error' => 'Ticket non trouvé']);
        exit;
    }

    if ($ticket['is_validated']) {
        http_response_code(400);
        echo json_encode(['error' => 'Ticket déjà validé']);
        exit;
    }

    // Validation du ticket
    $stmt = $pdo->prepare("UPDATE tickets SET is_validated = 1, validation_date = NOW() WHERE id = ?");
    $stmt->execute([$ticket['id']]);

    // Récupération des informations utilisateur
    $stmt = $pdo->prepare("SELECT fullname, email FROM users WHERE id = ?");
    $stmt->execute([$ticket['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Ticket validé avec succès',
        'ticket' => [
            'code' => $ticket['ticket_code'],
            'user' => $user['fullname'],
            'email' => $user['email'],
            'purchase_date' => $ticket['purchase_date']
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la validation : ' . $e->getMessage()]);
    exit;
} 