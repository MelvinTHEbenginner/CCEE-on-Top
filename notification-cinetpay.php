<?php
// notification-cinetpay.php
$api_key = '4223232976834922ac0cca7.40542965';
$site_id = '105896366';

if (isset($_POST['cpm_trans_id'])) {
    $trans_id = $_POST['cpm_trans_id'];

    // Vérification via l’API CinetPay
    $url = "https://api-checkout.cinetpay.com/v2/payment/check";
    $data = [
        "apikey" => $api_key,
        "site_id" => $site_id,
        "transaction_id" => $trans_id
    ];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($data),
    ]);

    $response = curl_exec($curl);
    curl_close($curl);
    $result = json_decode($response, true);

    if ($result && $result['data']['status'] == 'ACCEPTED') {
        // Paiement réussi : enregistrer le ticket
        // ex: enregistrer en base de données
    } else {
        // Paiement échoué ou refusé
    }
}
