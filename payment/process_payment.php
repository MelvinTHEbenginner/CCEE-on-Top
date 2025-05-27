<?php
session_start();
require_once '../config.php';
require_once '../includes/functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Vérifier l'ID de transaction
$transactionId = isset($_GET['transaction_id']) ? intval($_GET['transaction_id']) : 0;
if ($transactionId <= 0) {
    die('ID de transaction invalide');
}

try {
    // Récupérer les détails de la transaction
    $stmt = $conn->prepare("
        SELECT t.*, u.email, u.phone 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ? AND t.status = 'pending'
        FOR UPDATE
    ");
    $stmt->bind_param("ii", $transactionId, $_SESSION['user_id']);
    $stmt->execute();
    $transaction = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$transaction) {
        die('Transaction introuvable ou déjà traitée');
    }

    // Ici, vous intégrerez l'API de paiement (Orange Money, MTN Mobile Money, Wave, etc.)
    // Pour l'instant, nous simulons un paiement réussi
    $paymentSuccess = true;

    if ($paymentSuccess) {
        // Démarrer une transaction
        $conn->begin_transaction();

        try {
            // Mettre à jour le statut de la transaction
            $stmt = $conn->prepare("
                UPDATE transactions 
                SET status = 'completed', 
                    payment_date = NOW() 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $transactionId);
            $stmt->execute();
            $stmt->close();

            // Mettre à jour les tickets
            $stmt = $conn->prepare("
                UPDATE tickets 
                SET status = 'vendu',
                    user_id = ?,
                    sold_at = NOW()
                WHERE transaction_id = ?
            ");
            $stmt->bind_param("ii", $_SESSION['user_id'], $transactionId);
            $stmt->execute();
            $stmt->close();

            // Valider la transaction
            $conn->commit();

            // Rediriger vers la page de confirmation
            header('Location: /dashboard/tickets.php?success=1');
            exit;

        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $conn->rollback();
            error_log("Erreur lors de la confirmation du paiement: " . $e->getMessage());
            die("Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer.");
        }
    } else {
        // En cas d'échec du paiement, libérer les tickets réservés
        $stmt = $conn->prepare("
            UPDATE tickets 
            SET status = 'disponible',
                transaction_id = NULL,
                reserved_at = NULL
            WHERE transaction_id = ?
        ");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $stmt->close();

        // Supprimer la transaction échouée
        $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $stmt->close();

        die("Le paiement a échoué. Veuillez réessayer.");
    }

} catch (Exception $e) {
    error_log("Erreur dans process_payment.php: " . $e->getMessage());
    die("Une erreur est survenue. Veuillez réessayer plus tard.");
}
