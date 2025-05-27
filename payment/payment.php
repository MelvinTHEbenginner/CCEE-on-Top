<?php
session_start();

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];



echo '<pre>';
print_r($_SESSION);
echo '</pre>';


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

<!-- NAVIGATION -->
<nav class="bg-blue-900 text-white shadow-lg">
    <div class="max-w-6xl mx-auto px-4">
        <div class="flex justify-between items-center py-4">
            <div class="flex items-center space-x-4">
                <img src="../assets/images/logo.png" alt="Logo CCEE" class="h-10">
                <span class="font-bold text-xl">CCEE </span>
            </div>
            <div class="hidden md:flex items-center space-x-8">
                <a href="../dashboard/" class="hover:text-yellow-300">Tableau de bord</a>
                <a href="../dashboard/tickets.html" class="hover:text-yellow-300">Mes tickets</a>
                <a href="#" class="hover:text-yellow-300">Profil</a>
            </div>
            <div class="flex items-center space-x-4">
                <button id="logoutBtn" class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-4 rounded-full transition duration-300">
                    Déconnexion
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- CONTENU -->
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-blue-900">Acheter un ticket</h1>
        <p class="text-gray-600">Participez à la tombola de l'Apothéose pour 1000 FCFA par ticket</p>
    </div>

    <div class="grid md:grid-cols-3 gap-8">
        <!-- FORMULAIRE -->
        <div class="md:col-span-2">
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h2 class="text-xl font-semibold mb-4">Informations de paiement</h2>

                <form id="paymentForm">
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Méthode de paiement</label>
                        <div class="grid grid-cols-3 gap-4">
                            <button type="button" class="payment-method border-2 border-gray-200 rounded-lg p-4 flex flex-col items-center hover:border-blue-500" data-method="OM">
                                <img src="../assets/images/orange-money.png" class="h-8 mb-2">
                                <span>Orange Money</span>
                            </button>
                            <button type="button" class="payment-method border-2 border-gray-200 rounded-lg p-4 flex flex-col items-center hover:border-blue-500" data-method="MOMO">
                                <img src="../assets/images/mtn-momo.png" class="h-8 mb-2">
                                <span>MTN MoMo</span>
                            </button>
                            <button type="button" class="payment-method border-2 border-gray-200 rounded-lg p-4 flex flex-col items-center hover:border-blue-500" data-method="WAVE">
                                <img src="../assets/images/wave.png" class="h-8 mb-2">
                                <span>Wave</span>
                            </button>
                        </div>
                        <input type="hidden" id="paymentMethod" name="paymentMethod" required>
                    </div>

                    

                    <div class="mb-6">
                        <label for="ticketQuantity" class="block text-gray-700 font-medium mb-2">Nombre de tickets</label>
                        <div class="flex items-center">
                            <button type="button" id="decreaseQuantity" class="bg-gray-200 px-4 py-2 rounded-l"><i class="fas fa-minus"></i></button>
                            <input type="number" id="ticketQuantity" name="ticketQuantity" value="1" min="1" max="10" class="w-16 text-center px-4 py-2 border-t border-b">
                            <button type="button" id="increaseQuantity" class="bg-gray-200 px-4 py-2 rounded-r"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg mb-6">
                        <div class="flex justify-between mb-2">
                            <span>Prix unitaire</span>
                            <span>1 000 FCFA</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg">
                            <span>Total à payer</span>
                            <span id="totalAmount">1 000 FCFA</span>
                        </div>
                    </div>

                    
                   <div id="selectedImageContainer" class="mt-6 hidden text-center" >
                    <img id="selectedImage" src="" alt="Méthode sélectionnée" class="h-24 mx-auto" style="height: 300px;">
                   </div>
                  <button type="submit" id="submitButton" class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-3 px-4 rounded-lg transition duration-300 mt-5">
  <span id="buttonText">Réclamer mon ticket (Scannez pour payer)</span>
</button>
<div id="response-message" class="text-sm text-green-600 mt-3"></div>

                   <p id="confirmationMessage" class="mt-4 text-green-600 font-medium hidden text-center">
                        ✅ Vous recevrez votre ticket dans les plus brefs délais.
                    </p>
                </form>
            </div>
        </div>

        <!-- RÉSUMÉ -->
        <div>
            <div class="bg-white rounded-xl shadow-md p-6 sticky top-8">
                <h2 class="text-xl font-semibold mb-4">Résumé de la commande</h2>
                <div class="space-y-4 mb-6">
                    <div class="flex justify-between"><span>Prix unitaire</span><span>1 000 FCFA</span></div>
                    <div class="flex justify-between"><span>Quantité</span><span id="summaryQuantity">1</span></div>
                    <div class="flex justify-between font-bold border-t pt-2"><span>Total</span><span id="summaryTotal">1 000 FCFA</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SCRIPT -->
<script>
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentMethodInput = document.getElementById('paymentMethod');
    const ticketQuantity = document.getElementById('ticketQuantity');
    const totalAmount = document.getElementById('totalAmount');
    const summaryQuantity = document.getElementById('summaryQuantity');
    const summaryTotal = document.getElementById('summaryTotal');
    const unitPrice = 1000;
    const submitButton = document.getElementById('submitButton');
    const buttonText = document.getElementById('buttonText');
    const selectedImageContainer = document.getElementById('selectedImageContainer');
    const selectedImage = document.getElementById('selectedImage');
    const confirmationMessage = document.getElementById('confirmationMessage');



    document.getElementById("submitButton").addEventListener("click", function (e) {
  e.preventDefault(); // Empêche la soumission de formulaire classique

  // On désactive le bouton pour éviter les doublons
  document.getElementById("submitButton").disabled = true;
  document.getElementById("buttonText").textContent = "Traitement...";

  fetch("reclamer_ticket.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "reclamer_ticket=1"
  })
  .then(response => response.text())
  .then(data => {
    document.getElementById("response-message").innerText = data;
    document.getElementById("submitButton").disabled = false;
    document.getElementById("buttonText").textContent = "Réclamer mon ticket (Scannez pour payer)";
  })
  .catch(error => {
    document.getElementById("response-message").innerText = "Erreur serveur.";
    document.getElementById("submitButton").disabled = false;
    document.getElementById("buttonText").textContent = "Réclamer mon ticket (Scannez pour payer)";
  });
});

    paymentMethods.forEach(btn => {
    btn.addEventListener('click', () => {
        // Style
        paymentMethods.forEach(b => b.classList.remove('border-blue-500'));
        btn.classList.add('border-blue-500');

        // Méthode de paiement
        const method = btn.dataset.method;
        paymentMethodInput.value = method;

        // Affichage de l’image selon la méthode sélectionnée
        let imagePath = '';
        switch(method) {
            case 'OM':
                imagePath = '../assets/images/omcode_qr.jpg';
                break;
            case 'MOMO':
                imagePath = '../assets/images/momo-selected.p';
                break;
            case 'WAVE':
                imagePath = '../assets/images/codeqr_wave.jpg';
                break;
        }

        // Afficher l'image
        if (imagePath) {
            selectedImage.src = imagePath;
            selectedImageContainer.classList.remove('hidden');
        }
    });
});

    function updateTotal() {
        const qty = parseInt(ticketQuantity.value);
        const total = qty * unitPrice;
        totalAmount.textContent = `${total.toLocaleString()} FCFA`;
        summaryTotal.textContent = `${total.toLocaleString()} FCFA`;
        summaryQuantity.textContent = qty;
    }

    document.getElementById('increaseQuantity').onclick = () => {
        ticketQuantity.value = parseInt(ticketQuantity.value) + 1;
        updateTotal();
    };

    document.getElementById('decreaseQuantity').onclick = () => {
        if (parseInt(ticketQuantity.value) > 1) {
            ticketQuantity.value = parseInt(ticketQuantity.value) - 1;
            updateTotal();
        }
    };

    ticketQuantity.onchange = updateTotal;

    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const phone = document.getElementById('phoneNumber').value.trim();
        const method = document.getElementById('paymentMethod').value;
        const qty = parseInt(ticketQuantity.value);
        const amount = qty * unitPrice;

        if (!method) {
            alert("Veuillez choisir une méthode de paiement.");
            return;
        }

        if (!phone || phone.length < 8) {
            alert("Numéro de téléphone invalide.");
            return;
        }

        if (isNaN(amount) || amount <= 0) {
            alert("Montant invalide.");
            return;
        }

        const transaction_id = "TOMBOLA-" + Date.now();

        // Debug des valeurs
        

        
    });

    updateTotal();
</script>

</body>
</html>
