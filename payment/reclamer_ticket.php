<?php
session_start();
require_once '../config.php'; // Fichier de connexion à la base

if (!isset($_SESSION['user_id']) || !isset($_SESSION['phone']) || !isset($_SESSION['fullname'])) {
    echo "Erreur : utilisateur non connecté.";
    exit;
}

// Récupération des infos utilisateur
$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['user_fullname'];
$phone = $_SESSION['user_phone'];

// Vérifie si un ticket a déjà été réclamé aujourd'hui (optionnel)
$stmt = $conn->prepare("SELECT * FROM tickets WHERE user_id = ? AND DATE(purchase_date) = CURDATE()");
$stmt->execute([$user_id]);
if ($stmt->rowCount() > 0) {
    echo "Vous avez déjà réclamé un ticket aujourd'hui.";
    exit;
}

// Insérer le ticket
try {
    $insert = $conn->prepare("INSERT INTO tickets (user_id, purchase_date) VALUES (?,NOW())");
    $insert->execute([$user_id]);

    echo "Votre demande de ticket a bien été enregistrée.";
} catch (PDOException $e) {
    echo "Erreur lors de la création du ticket : " . $e->getMessage();
}
?>
