// Configuration des méthodes de paiement
const PAYMENT_CONFIG = {
    orange: {
        name: 'Orange Money',
        placeholder: '07XXXXXXXX',
        pattern: '^07[0-9]{8}$',
        qrcode: '../assets/images/qr-codes/qr-orange.png'
    },
    mtn: {
        name: 'MTN MoMo',
        placeholder: '05XXXXXXXX',
        pattern: '^05[0-9]{8}$',
        qrcode: '../assets/images/qr-codes/qr-mtn.png'
    },
    wave: {
        name: 'Wave',
        placeholder: '01XXXXXXXX',
        pattern: '^01[0-9]{8}$',
        qrcode: '../assets/images/qr-codes/qr-wave.png'
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des éléments
    const elements = {
        ticketQuantity: document.getElementById('ticketQuantity'),
        decreaseQuantity: document.getElementById('decreaseQuantity'),
        increaseQuantity: document.getElementById('increaseQuantity'),
        totalAmount: document.getElementById('totalAmount'),
        summaryQuantity: document.getElementById('summaryQuantity'),
        summaryTotal: document.getElementById('summaryTotal'),
        paymentMethods: document.querySelectorAll('.payment-method'),
        paymentMethod: document.getElementById('paymentMethod'),
        paymentForm: document.getElementById('paymentForm'),
        phoneNumber: document.getElementById('phoneNumber'),
        showQRCode: document.getElementById('showQRCode'),
        confirmPayment: document.getElementById('confirmPayment')
    };

    const PRIX_UNITAIRE = 1000;

    // Fonctions utilitaires
    const formatMoney = (amount) => {
        return amount.toLocaleString('fr-FR') + ' FCFA';
    };

    const updateQuantities = () => {
        const quantity = parseInt(elements.ticketQuantity.value);
        const total = quantity * PRIX_UNITAIRE;
        
        elements.totalAmount.textContent = formatMoney(total);
        elements.summaryQuantity.textContent = quantity;
        elements.summaryTotal.textContent = formatMoney(total);
    };

    // Fonction pour afficher le QR Code de prévisualisation
    const showPreviewQRCode = () => {
        const quantity = parseInt(elements.ticketQuantity.value);
        const paymentMethod = elements.paymentMethod.value;
        const phoneNumber = elements.phoneNumber.value;
            
        if (!paymentMethod) {
            alert('Veuillez sélectionner une méthode de paiement');
            return;
        }

        if (!phoneNumber) {
            alert('Veuillez entrer un numéro de téléphone');
            return;
        }

        const qrContainer = document.createElement('div');
        qrContainer.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
        
        const content = document.createElement('div');
        content.className = 'bg-white rounded-lg p-8 max-w-md w-full mx-4 relative';
        
        const closeButton = document.createElement('button');
        closeButton.className = 'absolute top-4 right-4 text-gray-500 hover:text-gray-700';
        closeButton.innerHTML = '<i class="fas fa-times"></i>';
        closeButton.onclick = () => qrContainer.remove();
        
        content.innerHTML = `
            <h3 class="text-xl font-bold mb-4">Détails du paiement</h3>
            <div class="text-center mb-4">
                <div class="bg-blue-50 p-4 rounded-lg mb-4">
                    <p class="mb-2"><strong>Quantité :</strong> ${quantity} ticket(s)</p>
                    <p class="mb-2"><strong>Montant :</strong> ${formatMoney(quantity * PRIX_UNITAIRE)}</p>
                    <p class="mb-2"><strong>Méthode :</strong> ${PAYMENT_CONFIG[paymentMethod].name}</p>
                    <p><strong>Téléphone :</strong> ${phoneNumber}</p>
                </div>
                <div class="mb-4 flex justify-center">
                    <img src="${PAYMENT_CONFIG[paymentMethod].qrcode}" alt="QR Code de paiement" class="w-48 h-48 object-contain">
                </div>
                <p class="text-sm text-gray-600 mb-4">Scannez ce QR Code pour effectuer le paiement</p>
                <div class="space-y-3">
                    <button onclick="document.getElementById('paymentForm').submit()" 
                            class="w-full bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Confirmer le paiement
                    </button>
                    <button onclick="this.closest('.fixed').remove()" 
                            class="w-full bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300">
                        Fermer
                    </button>
                    </div>
                </div>
            `;
        
        content.appendChild(closeButton);
        qrContainer.appendChild(content);
        document.body.appendChild(qrContainer);
    };

    // Gestion des méthodes de paiement
    elements.paymentMethods?.forEach(method => {
        method.addEventListener('click', () => {
            elements.paymentMethods.forEach(m => m.classList.remove('active', 'border-blue-500'));
            method.classList.add('active', 'border-blue-500');
                        
            const selectedMethod = method.getAttribute('data-method');
            elements.paymentMethod.value = selectedMethod;
                    
            if (elements.phoneNumber && PAYMENT_CONFIG[selectedMethod]) {
                elements.phoneNumber.placeholder = PAYMENT_CONFIG[selectedMethod].placeholder;
                elements.phoneNumber.pattern = PAYMENT_CONFIG[selectedMethod].pattern;
            }
        });
    });

    // Gestion de la quantité
        elements.decreaseQuantity?.addEventListener('click', () => {
            const current = parseInt(elements.ticketQuantity.value);
            if (current > 1) {
                elements.ticketQuantity.value = current - 1;
                updateQuantities();
            }
        });

        elements.increaseQuantity?.addEventListener('click', () => {
            const current = parseInt(elements.ticketQuantity.value);
        if (current < 10) {
            elements.ticketQuantity.value = current + 1;
            updateQuantities();
        }
        });

        elements.ticketQuantity?.addEventListener('change', () => {
        let value = parseInt(elements.ticketQuantity.value);
        if (isNaN(value) || value < 1) value = 1;
        if (value > 10) value = 10;
        elements.ticketQuantity.value = value;
            updateQuantities();
        });

    // Gestion du bouton d'affichage du QR Code
    elements.showQRCode?.addEventListener('click', showPreviewQRCode);

    // Gestion du formulaire de paiement
    elements.paymentForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const quantity = parseInt(elements.ticketQuantity.value);
        const paymentMethod = elements.paymentMethod.value;
        const phoneNumber = elements.phoneNumber.value;
        
        if (!paymentMethod) {
            alert('Veuillez sélectionner une méthode de paiement');
            return;
        }
        
        if (!phoneNumber) {
            alert('Veuillez entrer un numéro de téléphone');
            return;
        }
        
        try {
            elements.confirmPayment.disabled = true;
            elements.confirmPayment.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Traitement en cours...';
            
            // Log des données envoyées
            console.log('Données envoyées:', {
                quantity,
                payment_method: paymentMethod,
                phone_number: phoneNumber
            });

            // Créer la transaction avec le chemin complet
            const response = await fetch('/CCEE-on-Top-main/CCEE-on-Top-main/payment/process_payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    quantity: quantity,
                    payment_method: paymentMethod,
                    phone_number: phoneNumber
                })
            });

            // Log de la réponse HTTP
            console.log('Statut de la réponse:', response.status);
            console.log('Headers:', Object.fromEntries(response.headers.entries()));

            // Déboguer la réponse brute
            const rawResponse = await response.text();
            console.log('Réponse brute du serveur:', rawResponse);

            // Tenter de parser la réponse JSON
            let data;
            try {
                data = JSON.parse(rawResponse);
                console.log('Données JSON parsées:', data);
            } catch (jsonError) {
                console.error('Erreur de parsing JSON:', jsonError);
                console.error('Contenu reçu:', rawResponse);
                throw new Error(`Réponse invalide du serveur: ${rawResponse.substring(0, 100)}...`);
            }
            
            if (!data.success) {
                throw new Error(data.error || 'Une erreur est survenue');
            }

            // Afficher la confirmation
            const qrContainer = document.createElement('div');
            qrContainer.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            
            const content = document.createElement('div');
            content.className = 'bg-white rounded-lg p-8 max-w-md w-full mx-4 relative';
            
            content.innerHTML = `
                <h3 class="text-xl font-bold mb-4">Transaction réussie !</h3>
                <div class="text-center mb-4">
                    <div class="bg-green-100 text-green-800 p-4 rounded-lg mb-4">
                        <i class="fas fa-check-circle text-3xl mb-2"></i>
                        <p>Vos tickets ont été attribués avec succès</p>
                    </div>
                    <div class="mb-4">
                        <p class="font-bold mb-2">Référence de transaction: #${data.transaction_id}</p>
                        <p class="text-sm text-gray-600">Conservez cette référence pour vos tickets</p>
                    </div>
                    <button onclick="window.location.href='/CCEE-on-Top-main/CCEE-on-Top-main/dashboard/'" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
                        Voir mes tickets
                    </button>
                </div>
            `;
            
            qrContainer.appendChild(content);
            document.body.appendChild(qrContainer);
            
            } catch (error) {
            console.error('Erreur complète:', error);
            alert(`Erreur: ${error.message}`);
            elements.confirmPayment.disabled = false;
            elements.confirmPayment.textContent = 'Confirmer le paiement';
            }
        });

    // Initialisation
    updateQuantities();
});
