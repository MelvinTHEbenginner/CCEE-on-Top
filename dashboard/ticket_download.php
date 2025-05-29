<?php
require_once __DIR__ . '/../init.php';
requireLogin();

// Vérification de l'ID du ticket
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message('error', 'ID de ticket invalide');
    redirect('/dashboard/tickets.php');
}

$ticket_id = (int)$_GET['id'];

// Récupération des informations du ticket
try {
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        set_flash_message('error', 'Ticket non trouvé');
        redirect('/dashboard/tickets.php');
    }

    // Vérification des droits d'accès
    if (!isAdmin() && $ticket['user_id'] !== getCurrentUserId()) {
        set_flash_message('error', 'Accès non autorisé à ce ticket');
        redirect('/dashboard/tickets.php');
    }

    // Génération du QR code
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $writer = new \Endroid\QrCode\Writer\PngWriter();
    $qrCode = \Endroid\QrCode\QrCode::create($ticket['ticket_code'])
        ->setSize(300)
        ->setMargin(10);
    
    $result = $writer->write($qrCode);

    // En-têtes pour le téléchargement
    header('Content-Type: ' . $result->getMimeType());
    header('Content-Disposition: attachment; filename="ticket_' . $ticket['ticket_code'] . '.png"');
    
    // Envoi de l'image
    echo $result->getString();
    
} catch (Exception $e) {
    set_flash_message('error', 'Erreur lors de la génération du QR code : ' . $e->getMessage());
    redirect('/dashboard/tickets.php');
} 