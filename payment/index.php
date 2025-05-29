<?php
session_start();
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Tombola CCEE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.rawgit.com/davidshimjs/qrcodejs/gh-pages/qrcode.min.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <img src="../assets/images/logo.png" alt="Logo CCEE" class="h-10">
                    <span class="font-bold text-xl">CCEE</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="../dashboard/index.php" class="hover:text-yellow-300">Tableau de bord</a>
                    <a href="../dashboard/tickets.php" class="hover:text-yellow-300">Mes tickets</a>
                    <a href="../dashboard/profile.php" class="hover:text-yellow-300">Profil</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../auth/logout.php" class="text-white hover:text-yellow-300">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-blue-900">Acheter un ticket</h1>
            <p class="text-gray-600">Participez à la tombola de l'Apothéose pour 1000 FCFA par ticket</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8">
            <!-- Formulaire de paiement -->
            <div class="md:col-span-2">
                <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                    <h2 class="text-xl font-semibold mb-4">Informations de paiement</h2>
                    <form id="paymentForm" class="space-y-6">
                        <div class="mb-6">
                            <label class="block text-gray-700 font-medium mb-2">Choisissez votre méthode de paiement</label>
                            <div class="grid grid-cols-3 gap-4">
                                <button type="button" class="payment-method border-2 border-gray-200 rounded-lg p-4 flex flex-col items-center hover:border-blue-500 transition duration-300" data-method="orange">
                                    <img src="../assets/images/orange-money.png" alt="Orange Money" class="h-12 mb-2">
                                    <span>Orange Money</span>
                                </button>
                                <button type="button" class="payment-method border-2 border-gray-200 rounded-lg p-4 flex flex-col items-center hover:border-blue-500 transition duration-300" data-method="mtn">
                                    <img src="../assets/images/mtn-momo.png" alt="MTN MoMo" class="h-12 mb-2">
                                    <span>MTN MoMo</span>
                                </button>
                                <button type="button" class="payment-method border-2 border-gray-200 rounded-lg p-4 flex flex-col items-center hover:border-blue-500 transition duration-300" data-method="wave">
                                    <img src="../assets/images/wave.png" alt="Wave" class="h-12 mb-2">
                                    <span>Wave</span>
                                </button>
                            </div>
                            <input type="hidden" id="paymentMethod" name="payment_method" value="">
                        </div>

                        <div class="mb-6">
                            <label for="ticketQuantity" class="block text-gray-700 font-medium mb-2">Nombre de tickets (1 000 FCFA par ticket)</label>
                            <div class="flex items-center mb-4">
                                <button type="button" id="decreaseQuantity" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-l-lg">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="ticketQuantity" name="quantity" value="1" min="1" max="10"
                                       class="w-16 text-center px-4 py-2 bg-white border-t border-b border-gray-300 focus:outline-none">
                                <button type="button" id="increaseQuantity" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-r-lg">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <p class="text-sm text-gray-500">Sélectionnez 1 à 10 tickets maximum par transaction</p>
                        </div>

                        <div class="mb-6">
                            <label for="phoneNumber" class="block text-gray-700 font-medium mb-2">Numéro de téléphone</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-phone text-gray-400"></i>
                                </div>
                                <input type="tel" id="phoneNumber" name="phone_number" 
                                       class="w-full pl-10 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Entrez votre numéro" required>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Format: 07XXXXXXXX (Orange) / 05XXXXXXXX (MTN) / 01XXXXXXXX (Wave)</p>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg mb-6">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-gray-700">Prix unitaire</span>
                                <span class="font-medium">1 000 FCFA</span>
                            </div>
                            <div class="flex justify-between items-center font-bold text-lg">
                                <span>Total à payer</span>
                                <span id="totalAmount">1 000 FCFA</span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <button type="button" id="showQRCode" class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-4 px-6 rounded-lg transition duration-300">
                                <i class="fas fa-qrcode mr-2"></i>Afficher le QR Code
                            </button>

                            <button type="submit" id="confirmPayment" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition duration-300">
                                Confirmer le paiement
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Informations importantes -->
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h2 class="text-xl font-semibold mb-4">Informations importantes</h2>
                    <ul class="space-y-3 text-gray-700">
                        <li class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                            <span>Chaque ticket coûte 1000 FCFA et vous donne une chance de gagner les lots.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                            <span>Vous recevrez un QR Code unique pour chaque ticket acheté.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                            <span>Le tirage aura lieu lors de l'Apothéose le 30 juin 2025.</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-2"></i>
                            <span>Vous devez présenter votre QR Code pour réclamer votre lot si vous gagnez.</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Résumé de la commande -->
            <div>
                <div class="bg-white rounded-xl shadow-md p-6 sticky top-8">
                    <h2 class="text-xl font-semibold mb-4">Résumé de la commande</h2>
                    <div class="space-y-4 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Prix unitaire</span>
                            <span>1 000 FCFA</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Quantité</span>
                            <span id="summaryQuantity">1</span>
                        </div>
                        <div class="border-t border-gray-200 pt-2 flex justify-between font-bold">
                            <span>Total</span>
                            <span id="summaryTotal">1 000 FCFA</span>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg">
                        <h3 class="font-medium mb-2">Lots à gagner</h3>
                        <ul class="text-sm space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-trophy text-yellow-500 mr-2"></i>
                                <span>1er Prix: Smartphone haut de gamme</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-medal text-gray-500 mr-2"></i>
                                <span>2ème Prix: Tablette tactile</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-award text-amber-600 mr-2"></i>
                                <span>3ème Prix: Bon d'achat 50 000 FCFA</span>
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-gift text-purple-500 mr-2"></i>
                                <span>Et bien d'autres lots...</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
    <div id="confirmationModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4">Confirmation de paiement</h3>
            <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir procéder au paiement de <span id="modalAmount" class="font-bold">1 000 FCFA</span> ?</p>
            <div class="flex justify-end space-x-4">
                <button id="cancelPayment" class="px-4 py-2 text-gray-600 hover:text-gray-800">
                    Annuler
                </button>
                <button id="confirmPaymentBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                    Confirmer
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/payment.js"></script>
</body>
</html>