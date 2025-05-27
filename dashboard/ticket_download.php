<?php
session_start();
require_once __DIR__ . '/../init.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit(json_encode(['success' => false, 'message' => 'Accès refusé']));
}

// Vérifier si un code de ticket est fourni
$ticketCode = $_GET['code'] ?? '';
if (empty($ticketCode)) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'message' => 'Code de ticket manquant']));
}

try {
    // Vérifier si l'utilisateur est administrateur
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    
    // Récupérer les informations du ticket avec la transaction et l'utilisateur
    $stmt = $pdo->prepare("
        SELECT 
            t.*, 
            tr.transaction_date, 
            tr.amount, 
            tr.ticket_count,
            u.fullname, 
            u.email,
            u.phone
        FROM tickets t
        JOIN transactions tr ON t.id_transaction = tr.id
        JOIN users u ON tr.user_id = u.id
        WHERE t.ticket_code = ? 
        AND (tr.user_id = ? OR ? = 1)
    ");
    
    $stmt->execute([$ticketCode, $_SESSION['user_id'], $isAdmin ? 1 : 0]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket) {
        throw new Exception('Ticket non trouvé ou accès non autorisé');
    }

    // Vérifier si TCPDF est disponible
    if (!class_exists('TCPDF')) {
        // Si TCPDF n'est pas disponible, générer un fichier texte simple
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="ticket_' . $ticketCode . '.txt"');
        
        echo "=== TICKET D'ENTRÉE TOMBOLA CCEE ===\n\n";
        echo "Code: $ticketCode\n";
        echo "Nom: {$ticket['fullname']}\n";
        echo "Email: {$ticket['email']}\n";
        if (!empty($ticket['phone'])) {
            echo "Téléphone: {$ticket['phone']}\n";
        }
        echo "Date d'attribution: " . date('d/m/Y H:i', strtotime($ticket['date_attribution'])) . "\n";
        echo "Montant total: " . number_format($ticket['amount'], 0, ',', ' ') . " FCFA\n";
        echo "Nombre de tickets: " . $ticket['ticket_count'] . "\n";
        echo "Statut: " . ($ticket['is_validated'] ? '✅ Déjà validé' : '🔄 En attente de validation') . "\n\n";
        echo "=== INFORMATIONS IMPORTANTES ===\n";
        echo "- Ce ticket est valable pour une seule entrée\n";
        echo "- Présentez ce code à l'entrée: $ticketCode\n";
        echo "- En cas de problème, contactez le support au +225 XX XX XX XX\n";
        exit;
    }

    // Si TCPDF est disponible, générer un PDF
    $pdf = new TCPDF('P', 'mm', 'A6', true, 'UTF-8', false);

    // Définir les informations du document
    $pdf->SetCreator('CCEE Tombola');
    $pdf->SetAuthor('CCEE');
    $pdf->SetTitle('Ticket #' . $ticketCode);
    $pdf->SetSubject('Ticket pour la tombola CCEE');

    // Supprimer les en-têtes et pieds de page par défaut
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);

    // Ajouter une page
    $pdf->AddPage();

    // Logo (à remplacer par le chemin de votre logo)
    $logoPath = __DIR__ . '/../assets/images/logo.png';
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 10, 10, 30, 0, '', '', '', false, 300, '', false, false, 0);
    }

    // Titre
    $pdf->SetY(15);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'TICKET TOMBOLA CCEE', 0, 1, 'C');
    
    // Ligne de séparation
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(10, 30, $pdf->getPageWidth() - 10, 30);
    $pdf->Ln(10);

    // Code du ticket
    $pdf->SetFont('helvetica', 'B', 24);
    $pdf->Cell(0, 15, $ticketCode, 0, 1, 'C');
    
    // Détails du ticket
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, 'Nom: ' . $ticket['fullname'], 0, 1);
    if (!empty($ticket['phone'])) {
        $pdf->Cell(0, 6, 'Téléphone: ' . $ticket['phone'], 0, 1);
    }
    $pdf->Cell(0, 6, 'Date: ' . date('d/m/Y H:i', strtotime($ticket['date_attribution'])), 0, 1);
    $pdf->Cell(0, 6, 'Montant: ' . number_format($ticket['amount'], 0, ',', ' ') . ' FCFA', 0, 1);
    $pdf->Cell(0, 6, 'Statut: ' . ($ticket['is_validated'] ? '✅ Validé' : '🔄 En attente'), 0, 1);

    // QR Code (si l'extension GD est disponible)
    if (function_exists('imagecreate')) {
        $pdf->Ln(5);
        $qrSize = 40;
        $x = ($pdf->getPageWidth() - $qrSize) / 2;
        
        // Générer un QR code avec la bibliothèque phpqrcode si disponible
        if (file_exists(__DIR__ . '/../vendor/endroid/qr-code/src/QrCode.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            $qrCode = new Endroid\QrCode\QrCode($ticketCode);
            $qrCode->setSize(300);
            $qrCode->setMargin(10);
            
            $tempFile = tempnam(sys_get_temp_dir(), 'qrcode_') . '.png';
            $qrCode->writeFile($tempFile);
            
            $pdf->Image($tempFile, $x, $pdf->GetY(), $qrSize, $qrSize, 'PNG');
            unlink($tempFile);
        } else {
            // Solution de secours si la bibliothèque QR n'est pas disponible
            $pdf->Rect($x, $pdf->GetY(), $qrSize, $qrSize, 'D');
            $pdf->SetXY($x, $pdf->GetY() + $qrSize + 2);
            $pdf->Cell(0, 6, 'Code: ' . $ticketCode, 0, 1, 'C');
        }
    }

    // Ajouter des informations supplémentaires
    $pdf->SetY($pdf->GetY() + 10);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, 'INFORMATIONS IMPORTANTES', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 8);
    
    $infoText = "- Ce ticket est strictement personnel\n";
    $infoText .= "- Présentez ce code à l'entrée: $ticketCode\n";
    $infoText .= "- En cas de perte, contactez le support\n";
    $infoText .= "- L'organisateur se réserve le droit de refuser l'entrée";
    
    $pdf->MultiCell(0, 4, $infoText, 0, 'C');
    
    // Pied de page
    $pdf->SetY(-15);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, '© ' . date('Y') . ' CCEE - Tous droits réservés', 0, 0, 'C');

    // Générer le PDF et le forcer à se télécharger
    $pdf->Output('ticket_' . $ticketCode . '.pdf', 'D');

} catch (Exception $e) {
    // En cas d'erreur, afficher un message d'erreur
    header('Content-Type: text/plain');
    http_response_code(500);
    echo "Une erreur est survenue lors de la génération du ticket :\n";
    echo $e->getMessage();
}
?>
