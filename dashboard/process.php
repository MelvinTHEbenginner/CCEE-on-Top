<?php
session_start();
require_once '../config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer et valider les données
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['paymentMethod'], $data['phoneNumber'], $data['quantity'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

$paymentMethod = $data['paymentMethod'];
$phoneNumber = $data['phoneNumber'];
$quantity = intval($data['quantity']);
$amount = $quantity * 1000; // 1000 FCFA par ticket

// Simuler les clés API (à remplacer par les vraies clés)
$apiKeys = [
    'orange' => 'ORANGE_API_KEY_HERE',
    'mtn' => 'MTN_API_KEY_HERE',
    'wave' => 'WAVE_API_KEY_HERE'
];

// Simuler une transaction
function simulateTransaction($method, $phone, $amount) {
    // Simuler un délai de traitement
    sleep(2);
    
    // Simuler un succès avec 90% de probabilité
    return rand(1, 10) <= 9;
}

// Générer un code de ticket unique
function generateTicketCode() {
    return strtoupper(substr(uniqid(), -8));
}

try {
    // Simuler le paiement
    $transactionSuccess = simulateTransaction($paymentMethod, $phoneNumber, $amount);
    
    if ($transactionSuccess) {
        // Démarrer une transaction SQL
        $conn->begin_transaction();
        
        try {
            // Créer la transaction
            $stmt = $conn->prepare('INSERT INTO transactions (user_id, amount, payment_method, phone_number, status) VALUES (?, ?, ?, ?, "complete")');
            $stmt->bind_param('idss', $_SESSION['user_id'], $amount, $paymentMethod, $phoneNumber);
            $stmt->execute();
            $transactionId = $conn->insert_id;
            
            // Créer les tickets
            $tickets = [];
            for ($i = 0; $i < $quantity; $i++) {
                $ticketCode = generateTicketCode();
                $stmt = $conn->prepare('INSERT INTO tickets (ticket_code, user_id, purchase_date) VALUES (?, ?, NOW())');
                $stmt->bind_param('si', $ticketCode, $_SESSION['user_id']);
                $stmt->execute();
                $tickets[] = [
                    'id' => $conn->insert_id,
                    'code' => $ticketCode
                ];
            }
            
            // Valider la transaction
            $conn->commit();
            
            // Renvoyer la réponse
            echo json_encode([
                'success' => true,
                'message' => 'Paiement réussi',
                'transaction_id' => $transactionId,
                'tickets' => $tickets
            ]);
            
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $conn->rollback();
            throw $e;
        }
    } else {
        // Simuler une erreur de paiement
        throw new Exception('Le paiement a échoué. Veuillez réessayer.');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
