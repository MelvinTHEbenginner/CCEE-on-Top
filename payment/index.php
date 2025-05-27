<?php
session_start();
require_once '../config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Tombola CCEE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <img src="../assets/images/logo.png" alt="Logo CCEE" class="h-10">
                    <span class="font-bold text-xl">CCEE </span>
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

    <!-- Contenu du paiement -->
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
                    
                    <div id="paymentSelection">
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
                        </div>
                    </div>

                    <!-- Zone d'affichage du QR Code -->
                    <div id="qrCodeSection" class="hidden">
                        <div class="text-center mb-6">
                            <h3 class="text-xl font-semibold mb-4">Scanner le code QR pour payer</h3>
                            <div class="flex justify-center mb-4">
                                <img id="qrCodeImage" src="../assets/images/qr-codes/" alt="Code QR de paiement" class="w-64 h-64 border-2 border-gray-200 p-2 rounded-lg">
                            </div>
                            <p class="text-gray-600 mb-2">Montant: <span id="paymentAmount">1 000</span> FCFA</p>
                            <p class="text-sm text-gray-500">Une fois le paiement effectué, votre ticket sera automatiquement généré.</p>
                        </div>
                        <button type="button" id="backToMethods" class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-4 rounded-lg transition duration-300">
                            <i class="fas fa-arrow-left mr-2"></i> Retour aux méthodes de paiement
                        </button>
                    </div>
                        
                        <div class="mb-6">
                            <label for="ticketQuantity" class="block text-gray-700 font-medium mb-2">Nombre de tickets (1 000 FCFA par ticket)</label>
                            <div class="flex items-center mb-4">
                                <button type="button" id="decreaseQuantity" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-l-lg">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" id="ticketQuantity" name="ticketQuantity" value="1" min="1" max="10"
                                       class="w-16 text-center px-4 py-2 bg-white border-t border-b border-gray-300 focus:outline-none">
                                <button type="button" id="increaseQuantity" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded-r-lg">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <p class="text-sm text-gray-500">Sélectionnez 1 à 10 tickets maximum par transaction</p>
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
                        
                        <button type="button" id="proceedToPayment" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-4 px-6 rounded-lg transition duration-300">
                            Procéder au paiement <span id="totalAmount">1 000</span> FCFA
                        </button>
                    </div>
                
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

    <!-- Modal de paiement -->
    <div id="paymentModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-6 max-w-md w-full">
            <div class="text-center">
                <div class="mb-4">
                    <i class="fas fa-spinner fa-spin text-4xl text-blue-500"></i>
                </div>
                <h3 class="text-xl font-bold mb-2">Traitement du paiement</h3>
                <p class="text-gray-600 mb-4">Veuillez confirmer le paiement sur votre mobile. Ne quittez pas cette page.</p>
                <div class="bg-blue-50 p-3 rounded-lg mb-4">
                    <p class="font-medium" id="paymentInstruction">Confirmez le paiement de <span id="paymentAmount">1 000 FCFA</span> via <span id="paymentMethodDisplay">Orange Money</span></p>
                </div>
                <div class="h-1 w-full bg-gray-200 rounded-full overflow-hidden mb-4">
                    <div id="paymentProgress" class="h-full bg-blue-500 w-0"></div>
                </div>
                <button id="cancelPayment" class="text-sm text-red-500 hover:text-red-700">Annuler le paiement</button>
            </div>
        </div>
    </div>

    <!-- Modal de succès -->
    <div id="successModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-6 max-w-md w-full text-center">
            <div class="mb-4">
                <i class="fas fa-check-circle text-5xl text-green-500"></i>
            </div>
            <h3 class="text-xl font-bold mb-2">Paiement réussi !</h3>
            <p class="text-gray-600 mb-4">Votre achat de <span id="successQuantity">1</span> ticket(s) a été enregistré avec succès.</p>
            <div class="mb-6">
                <img id="successQrCode" src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=SUCCESS" alt="QR Code" class="mx-auto border border-gray-200 p-2">
                <p class="text-sm text-gray-500 mt-2">Ticket #<span id="successTicketId">0000</span></p>
            </div>
            <div class="flex flex-col space-y-3">
                <a href="../dashboard/tickets.php" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    Voir tous mes tickets
                </a>
                <a href="../dashboard/index.php" class="bg-white hover:bg-gray-100 text-gray-800 font-bold py-2 px-4 rounded-lg border border-gray-300 transition duration-300">
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>

    <script src="../assets/js/payment.js"></script>
<script>
// Configuration des codes QR pour chaque méthode de paiement
const qrCodes = {
    'orange': '../assets/images/qr-codes/qr-orange.png',
    'mtn': '../assets/images/qr-codes/qr-mtn.png',
    'wave': '../assets/images/qr-codes/qr-wave.png'
};

// Gestion de la sélection de la méthode de paiement
document.querySelectorAll('.payment-method').forEach(method => {
    method.addEventListener('click', function() {
        const methodType = this.getAttribute('data-method');
        
        // Mettre à jour le bouton sélectionné
        document.querySelectorAll('.payment-method').forEach(btn => {
            btn.classList.remove('border-blue-500', 'ring-2', 'ring-blue-300');
        });
        this.classList.add('border-blue-500', 'ring-2', 'ring-blue-300');
        
        // Activer le bouton de paiement
        document.getElementById('proceedToPayment').disabled = false;
        
        // Stocker la méthode sélectionnée
        document.getElementById('proceedToPayment').dataset.method = methodType;
        const config = PAYMENT_CONFIG[methodType];
        const phoneInput = document.getElementById('phoneNumber');
        
        phoneInput.placeholder = config.placeholder;
        phoneInput.pattern = config.pattern;
        
        // Mettre à jour le texte d'aide
        const helpText = document.createElement('div');
        helpText.className = 'text-sm text-gray-500 mt-1';
        helpText.textContent = `Format: ${config.placeholder}`;
        
        const existingHelp = phoneInput.parentElement.querySelector('.text-sm');
        if (existingHelp) {
            existingHelp.remove();
        }
        phoneInput.parentElement.appendChild(helpText);
    });
});

// Gestion de la quantité de tickets
const ticketQuantity = document.getElementById('ticketQuantity');
const totalAmount = document.getElementById('totalAmount');
const paymentAmount = document.getElementById('paymentAmount');

function updateTotal() {
    const quantity = parseInt(ticketQuantity.value);
    const total = quantity * 1000;
    totalAmount.textContent = total.toLocaleString('fr-FR');
    paymentAmount.textContent = total.toLocaleString('fr-FR');
}

// Gestion des boutons de quantité
document.getElementById('increaseQuantity').addEventListener('click', () => {
    if (parseInt(ticketQuantity.value) < 10) {
        ticketQuantity.value = parseInt(ticketQuantity.value) + 1;
        updateTotal();
    }
});

document.getElementById('decreaseQuantity').addEventListener('click', () => {
    if (parseInt(ticketQuantity.value) > 1) {
        ticketQuantity.value = parseInt(ticketQuantity.value) - 1;
        updateTotal();
    }
});

ticketQuantity.addEventListener('change', () => {
    if (parseInt(ticketQuantity.value) < 1) ticketQuantity.value = 1;
    if (parseInt(ticketQuantity.value) > 10) ticketQuantity.value = 10;
    updateTotal();
});

// Gestion du bouton "Procéder au paiement"
document.getElementById('proceedToPayment').addEventListener('click', function() {
    const selectedMethod = this.dataset.method;
    if (!selectedMethod) {
        alert('Veuillez sélectionner une méthode de paiement');
        return;
    }
    
    // Afficher le code QR correspondant
    document.getElementById('qrCodeImage').src = qrCodes[selectedMethod];
    document.getElementById('paymentSelection').classList.add('hidden');
    document.getElementById('qrCodeSection').classList.remove('hidden');
    
    // Faire défiler vers la section QR Code
    document.getElementById('qrCodeSection').scrollIntoView({ behavior: 'smooth' });
});

// Gestion du bouton "Retour aux méthodes de paiement"
document.getElementById('backToMethods').addEventListener('click', function() {
    document.getElementById('qrCodeSection').classList.add('hidden');
    document.getElementById('paymentSelection').classList.remove('hidden');
    document.getElementById('paymentSelection').scrollIntoView({ behavior: 'smooth' });
});

// Désactiver le bouton de paiement par défaut
document.getElementById('proceedToPayment').disabled = true;
</script>
</body>
</html>