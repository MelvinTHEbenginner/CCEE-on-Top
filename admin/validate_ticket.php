<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Vérifier les droits d'administration
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit;
}

// Définir le type de contenu
header('Content-Type: application/json');

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données de la requête (support à la fois JSON et formulaire)
$data = [];
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
} else {
    $data = $_POST;
}
    
$ticketCode = $data['ticket_code'] ?? null;
$ticketId = $data['ticket_id'] ?? null;  // Pour la compatibilité avec l'ancien code

// Si on a un ID de ticket mais pas de code, on récupère le code
if ($ticketId && !$ticketCode) {
    $stmt = $pdo->prepare("SELECT ticket_code FROM ticket WHERE id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    if ($ticket) {
        $ticketCode = $ticket['ticket_code'];
    }
}
    
if (!$ticketCode) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Code du ticket manquant']);
    exit;
}

try {
    // Démarrer une transaction
    $pdo->beginTransaction();

    // Vérifier si le ticket existe et n'est pas déjà validé
    $stmt = $pdo->prepare("
        SELECT 
            t.*, 
            tr.status as transaction_status, 
            tr.amount,
            tr.payment_method,
            u.fullname as user_name, 
            u.email as user_email,
            u.phone as user_phone
        FROM ticket t
        JOIN transactions tr ON t.id_transaction = tr.id
        JOIN users u ON t.user_id = u.id
        WHERE t.ticket_code = ?
        FOR UPDATE
    ");
    $stmt->execute([$ticketCode]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new Exception('Ticket non trouvé');
    }

    // Vérifier si la transaction est complète
    if ($ticket['transaction_status'] !== 'complete') {
        throw new Exception('La transaction associée à ce ticket n\'est pas complétée');
    }

    // Vérifier si le ticket est déjà validé
    if ($ticket['is_validated']) {
        throw new Exception('Ce ticket a déjà été validé');
    }

    // Mettre à jour le ticket comme validé
    $updateStmt = $pdo->prepare("
        UPDATE ticket 
        SET is_validated = 1, 
            validated_at = NOW()
        WHERE ticket_code = ? AND is_validated = 0
    ");
    
    $updateStmt->execute([$ticketCode]);

    if ($updateStmt->rowCount() === 0) {
        throw new Exception('Échec de la validation du ticket');
    }

    // Vérifier si le ticket est gagnant
    $isWinner = false;
    $prizeInfo = null;
    
    if ($ticket['is_winner']) {
        // Récupérer les informations sur le lot gagné
        $prizeStmt = $pdo->prepare("
            SELECT p.name, p.description, p.image_url 
            FROM prizes p 
            WHERE p.id = ?
        ");
        $prizeStmt->execute([$ticket['prize_id']]);
        $prizeInfo = $prizeStmt->fetch();
        $isWinner = true;
    }

    // Valider la transaction
    $pdo->commit();

    // Préparer la réponse
    $response = [
        'success' => true,
        'message' => 'Ticket validé avec succès',
        'data' => [
            'ticket_code' => $ticket['ticket_code'],
            'validated_at' => date('Y-m-d H:i:s'),
            'is_winner' => $isWinner,
            'user' => [
                'id' => $ticket['user_id'],
                'name' => $ticket['user_name'],
                'email' => $ticket['user_email'],
                'phone' => $ticket['user_phone']
            ],
            'transaction' => [
                'id' => $ticket['id_transaction'],
                'amount' => $ticket['amount'],
                'status' => $ticket['transaction_status'],
                'payment_method' => $ticket['payment_method']
            ]
        ]
    ];

    // Ajouter les informations sur le lot si gagnant
    if ($isWinner && $prizeInfo) {
        $response['data']['prize'] = $prizeInfo;
        
        // Envoyer une notification à l'utilisateur si c'est un gagnant
        try {
            $message = "Félicitations ! Votre ticket #{$ticket['ticket_code']} a été validé et vous avez gagné : {$prizeInfo['name']}.";
            if (!empty($prizeInfo['description'])) {
                $message .= " Détails : " . $prizeInfo['description'];
            }
            
            // Ici, vous pouvez ajouter la logique pour envoyer un email ou une notification push
            // Par exemple : sendEmail($ticket['user_email'], 'Félicitations, vous avez gagné !', $message);
            
        } catch (Exception $e) {
            // On ne fait rien en cas d'échec d'envoi de notification
            error_log("Erreur lors de l'envoi de la notification de gain : " . $e->getMessage());
        }
    }

    // Envoyer la réponse
    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
