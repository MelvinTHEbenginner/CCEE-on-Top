<?php
// Ce fichier peut aussi être nommé index.php
$api_key = '4223232976834922ac0cca7.40542965'; // Remplace par ta clé API CinetPay
$site_id = '105896366'; // Remplace par ton site ID CinetPay
$notify_url = 'https://ccee.infinityfreeapp.com/notification-cinetpay.php'; // Optionnel
$return_url = 'https://ccee.infinityfreeapp.com/succes.php'; // Facultatif
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - Tombola CCEE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.cinetpay.com/seamless/main.js"></script>
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
                        <label for="phoneNumber" class="block text-gray-700 font-medium mb-2">Numéro de téléphone</label>
                        <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="Ex: 0701234567" required class="w-full px-4 py-3 border rounded-lg focus:ring-blue-500">
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

                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-3 px-4 rounded-lg transition duration-300">
                        Payer maintenant
                    </button>
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

    paymentMethods.forEach(btn => {
        btn.addEventListener('click', () => {
            paymentMethods.forEach(b => b.classList.remove('border-blue-500'));
            btn.classList.add('border-blue-500');
            paymentMethodInput.value = btn.dataset.method;
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

        const phone = document.getElementById('phoneNumber').value;
        const method = document.getElementById('paymentMethod').value;
        const qty = parseInt(ticketQuantity.value);
        const amount = qty * unitPrice;

        if (!method) {
            alert("Veuillez choisir une méthode de paiement.");
            return;
        }

        const transaction_id = "TOMBOLA-" + Date.now();

        CinetPay.setConfig({
            apikey: "<?= $api_key ?>",
            site_id: "<?= $site_id ?>",
            notify_url: "<?= $notify_url ?>"
        });

        CinetPay.getCheckout({
            transaction_id: transaction_id,
            amount: amount,
            currency: "XOF",
            channels: method,
            description: `Paiement de ${qty} ticket(s) pour Tombola CCEE`,
            customer_name: "Client",
            customer_surname: "Anonyme",
            customer_phone_number: phone,
            customer_email: "",
            customer_address: "",
            customer_city: "",
            customer_country: "CI",
            customer_state: "",
            customer_zip_code: ""
        });

        CinetPay.waitResponse(function(data) {
            if (data.status === "REFUSED") {
                alert("Paiement refusé ou annulé");
            }
        });

        CinetPay.onError(function(err) {
            console.error(err);
            alert("Erreur pendant le paiement !");
        });
    });

    updateTotal();
</script>
</body>
</html>
