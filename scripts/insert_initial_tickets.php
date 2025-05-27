<?php
require_once __DIR__ . '/../init.php';

/**
 * Script pour insérer 100 tickets initiaux dans la base de données
 * Exécuter ce script une seule fois
 */

function generateTicketCode($length = 8) {
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $code = '';
    
    do {
        $code = 'TKT';
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        // Vérifier l'unicité
        $stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) FROM tickets WHERE ticket_code = ?");
        $stmt->execute([$code]);
        $exists = $stmt->fetchColumn();
    } while ($exists > 0);
    
    return $code;
}

try {
    $pdo->beginTransaction();
    
    // Vérifier s'il y a déjà des tickets
    $stmt = $pdo->query("SELECT COUNT(*) FROM tickets");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "Des tickets existent déjà dans la base de données.\n";
        exit(1);
    }
    
    // Insérer 100 tickets disponibles
    for ($i = 0; $i < 100; $i++) {
        $ticketCode = generateTicketCode(6); // 6 caractères après TKT
        
        $stmt = $pdo->prepare("
            INSERT INTO tickets (ticket_code, is_validated, date_creation, id_transaction, user_id)
            VALUES (?, 0, NOW(), NULL, NULL)
        
        ");
        $stmt->execute([$ticketCode]);
        
        echo "Ticket créé : $ticketCode\n";
    }
    
    $pdo->commit();
    echo "100 tickets ont été créés avec succès.\n";
    
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
