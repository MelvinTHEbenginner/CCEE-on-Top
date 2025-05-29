<?php
// Désactiver l'affichage des erreurs
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Démarrer la capture de la sortie
ob_start();

session_start();
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($success, $message, $statusCode = 200) {
    http_response_code($statusCode);
    // S'assurer qu'il n'y a pas d'autre sortie
    ob_clean();
    echo json_encode([
        'success' => $success,
        'error' => $success ? null : $message,
        'message' => $success ? $message : null
    ]);
    ob_end_flush();
    exit;
}

// Fonction pour logger les erreurs
function logError($message, $data = null) {
    $logFile = __DIR__ . '/../logs/validation.log';
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data) {
        $logMessage .= "\nData: " . print_r($data, true);
    }
    $logMessage .= "\n------------------------\n";
    
    // Créer le dossier logs s'il n'existe pas
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

try {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['user_id'])) {
        logError("Session non trouvée", $_SESSION);
        sendJsonResponse(false, "Session expirée", 401);
    }

    // Vérifier si l'utilisateur est admin
    if (!function_exists('isAdmin')) {
        logError("Fonction isAdmin non trouvée");
        sendJsonResponse(false, "Erreur de configuration", 500);
    }

    if (!isAdmin()) {
        logError("Accès non autorisé", ['user_id' => $_SESSION['user_id']]);
        sendJsonResponse(false, "Accès non autorisé", 403);
    }

    // Vérifier l'ID de transaction
    if (!isset($_POST['transaction_id']) || !is_numeric($_POST['transaction_id'])) {
        logError("ID de transaction invalide", $_POST);
        sendJsonResponse(false, "ID de transaction invalide", 400);
    }

    $transactionId = intval($_POST['transaction_id']);

    // Vérifier la connexion à la base de données
    if (!isset($pdo)) {
        logError("Connexion à la base de données non disponible");
        sendJsonResponse(false, "Erreur de connexion à la base de données", 500);
    }

    // Démarrer la transaction
    $pdo->beginTransaction();

    // Vérifier si la transaction existe
    $stmt = $pdo->prepare("SELECT status, is_activated FROM transactions WHERE id = ?");
    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        $pdo->rollBack();
        sendJsonResponse(false, "Transaction introuvable", 404);
    }

    if ($transaction['is_activated']) {
        $pdo->rollBack();
        sendJsonResponse(false, "Cette transaction est déjà validée", 400);
    }

    // Mettre à jour la transaction
    $updateStmt = $pdo->prepare("
        UPDATE transactions 
        SET is_activated = 1,
            updated_at = NOW()
        WHERE id = ? 
        AND status = 'complete'
        AND is_activated = 0
    ");

    $updateStmt->execute([$transactionId]);

    if ($updateStmt->rowCount() === 0) {
        $pdo->rollBack();
        sendJsonResponse(false, "Impossible de valider la transaction", 400);
    }

    // Valider la transaction
    $pdo->commit();

    logError("Transaction validée avec succès", [
        'transaction_id' => $transactionId,
        'user_id' => $_SESSION['user_id']
    ]);

    sendJsonResponse(true, "Transaction validée avec succès");

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logError("Erreur PDO", [
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    sendJsonResponse(false, "Erreur de base de données", 500);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logError("Exception", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    sendJsonResponse(false, $e->getMessage(), 500);

} catch (Error $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logError("Erreur PHP", [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
    sendJsonResponse(false, "Erreur système", 500);
}
