<?php
require_once __DIR__ . '/../init.php';

// Définir le type de contenu
header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Non autorisé']));
}

// Vérifier si la méthode est POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Méthode non autorisée']));
}

// Vérifier si le code du ticket est fourni
$ticketCode = $_POST['ticket_code'] ?? '';
if (empty($ticketCode)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Code de ticket manquant']));
}

try {
    // Vérifier si l'utilisateur est administrateur
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    
    // Récupérer les informations du ticket
    $stmt = $pdo->prepare("
        SELECT t.*, tr.user_id 
        FROM tickets t
        LEFT JOIN transactions tr ON t.id_transaction = tr.id
        WHERE t.ticket_code = ?
        FOR UPDATE
    ");
    $stmt->execute([$ticketCode]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier si le ticket existe
    if (!$ticket) {
        throw new Exception('Ticket non trouvé');
    }
    
    // Vérifier si l'utilisateur a le droit de valider ce ticket
    if (!$isAdmin && $ticket['user_id'] !== $_SESSION['user_id']) {
        throw new Exception('Vous n\'êtes pas autorisé à valider ce ticket');
    }
    
    // Vérifier si le ticket est déjà validé
    if ($ticket['is_validated']) {
        throw new Exception('Ce ticket a déjà été validé');
    }
    
    // Valider le ticket
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET is_validated = 1,
            date_validation = NOW(),
            validated_by = ?
        WHERE ticket_code = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $ticketCode]);
    
    // Envoyer une réponse de succès
    echo json_encode([
        'success' => true,
        'message' => 'Ticket validé avec succès',
        'ticket_code' => $ticketCode
    ]);
    
} catch (Exception $e) {
    // En cas d'erreur, renvoyer un message d'erreur
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
