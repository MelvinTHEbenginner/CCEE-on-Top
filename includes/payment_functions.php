<?php
/**
 * Fonctions liées au paiement pour l'application Tombola CCEE
 */

/**
 * Calcule le montant total pour une commande
 * 
 * @param int $ticketCount Nombre de tickets
 * @return float Montant total
 */
function calculate_total_amount($ticketCount) {
    return $ticketCount * TICKET_PRICE;
}

/**
 * Vérifie si un numéro de téléphone est un numéro Orange Money valide
 * 
 * @param string $phone Numéro de téléphone
 * @return bool True si le numéro est valide, sinon false
 */
function is_valid_orange_money($phone) {
    // Format: +225 07 XX XXX XXX ou 00225 07 XX XXX XXX ou 07 XX XXX XXX
    return preg_match('/^(\+225|00225)?[0]?[57][0-9][0-9]{2}[0-9]{2}[0-9]{2}[0-9]{2}$/', $phone);
}

/**
 * Vérifie si un numéro de téléphone est un numéro MTN Mobile Money valide
 * 
 * @param string $phone Numéro de téléphone
 * @return bool True si le numéro est valide, sinon false
 */
function is_valid_mtn_money($phone) {
    // Format: +225 05 XX XXX XXX ou 00225 05 XX XXX XXX ou 05 XX XXX XXX
    return preg_match('/^(\+225|00225)?[0]?[05][0-9][0-9]{2}[0-9]{2}[0-9]{2}[0-9]{2}$/', $phone);
}

/**
 * Vérifie si un numéro de téléphone est un numéro Wave valide
 * 
 * @param string $phone Numéro de téléphone
 * @return bool True si le numéro est valide, sinon false
 */
function is_valid_wave($phone) {
    // Wave utilise les mêmes numéros que Orange Money et MTN
    return is_valid_orange_money($phone) || is_valid_mtn_money($phone);
}

/**
 * Valide les informations de paiement
 * 
 * @param string $paymentMethod Méthode de paiement
 * @param string $phone Numéro de téléphone (pour les paiements mobiles)
 * @return array Tableau contenant 'valid' (bool) et 'message' (string)
 */
function validate_payment($paymentMethod, $phone = null) {
    $errors = [];
    
    switch ($paymentMethod) {
        case 'orange_money':
            if (empty($phone) || !is_valid_orange_money($phone)) {
                $errors[] = "Numéro Orange Money invalide";
            }
            break;
            
        case 'mtn_money':
            if (empty($phone) || !is_valid_mtn_money($phone)) {
                $errors[] = "Numéro MTN Mobile Money invalide";
            }
            break;
            
        case 'wave':
            if (empty($phone) || !is_valid_wave($phone)) {
                $errors[] = "Numéro Wave invalide";
            }
            break;
            
        case 'cash':
            // Aucune validation nécessaire pour le paiement en espèces
            break;
            
        default:
            $errors[] = "Méthode de paiement non prise en charge";
    }
    
    if (!empty($errors)) {
        return [
            'valid' => false,
            'message' => implode(", ", $errors)
        ];
    }
    
    return [
        'valid' => true,
        'message' => 'Informations de paiement valides'
    ];
}

/**
 * Traite un paiement
 * 
 * @param string $paymentMethod Méthode de paiement
 * @param float $amount Montant à payer
 * @param array $paymentData Données de paiement
 * @return array Résultat du paiement
 */
function process_payment($paymentMethod, $amount, $paymentData = []) {
    // Ici, on simule un traitement de paiement
    // Dans une application réelle, on appellerait l'API du prestataire de paiement
    
    $result = [
        'success' => true,
        'transaction_id' => 'TXN' . uniqid(),
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'status' => 'completed',
        'message' => 'Paiement effectué avec succès',
        'timestamp' => date('Y-m-d H:i:s'),
        'raw_response' => json_encode([
            'payment_method' => $paymentMethod,
            'amount' => $amount,
            'payment_data' => $paymentData,
            'simulated' => true
        ])
    ];
    
    return $result;
}

/**
 * Enregistre une transaction dans la base de données
 * 
 * @param int $userId ID de l'utilisateur
 * @param string $transactionId ID de la transaction
 * @param float $amount Montant de la transaction
 * @param string $paymentMethod Méthode de paiement
 * @param string $status Statut de la transaction
 * @param array $metadata Métadonnées supplémentaires
 * @return int|bool ID de la transaction insérée ou false en cas d'échec
 */
function save_transaction($userId, $transactionId, $amount, $paymentMethod, $status = 'pending', $metadata = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO transactions (
                user_id, 
                transaction_id, 
                amount, 
                payment_method, 
                status, 
                metadata, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $metadataJson = json_encode($metadata);
        
        $stmt->execute([
            $userId,
            $transactionId,
            $amount,
            $paymentMethod,
            $status,
            $metadataJson
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement de la transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le statut d'une transaction
 * 
 * @param string $transactionId ID de la transaction
 * @param string $status Nouveau statut
 * @param array $metadata Métadonnées supplémentaires
 * @return bool True en cas de succès, false sinon
 */
function update_transaction_status($transactionId, $status, $metadata = []) {
    global $pdo;
    
    try {
        $metadataJson = json_encode($metadata);
        
        $stmt = $pdo->prepare("
            UPDATE transactions 
            SET 
                status = ?,
                metadata = ?,
                updated_at = NOW()
            WHERE transaction_id = ?
        ");
        
        return $stmt->execute([$status, $metadataJson, $transactionId]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour de la transaction: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère une transaction par son ID
 * 
 * @param string $transactionId ID de la transaction
 * @return array|bool Tableau des données de la transaction ou false si non trouvée
 */
function get_transaction($transactionId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM transactions 
            WHERE transaction_id = ?
        ");
        
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($transaction && !empty($transaction['metadata'])) {
            $transaction['metadata'] = json_decode($transaction['metadata'], true);
        }
        
        return $transaction;
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération de la transaction: " . $e->getMessage());
        return false;
    }
}
