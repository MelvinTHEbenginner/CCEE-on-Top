// Configuration des méthodes de paiement
const PAYMENT_CONFIG = {
    orange: {
        name: 'Orange Money',
        placeholder: '07XXXXXXXX',
        pattern: '^07[0-9]{8}$'
    },
    mtn: {
        name: 'MTN MoMo',
        placeholder: '05XXXXXXXX',
        pattern: '^05[0-9]{8}$'
    },
    wave: {
        name: 'Wave',
        placeholder: '01XXXXXXXX',
        pattern: '^01[0-9]{8}$'
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
        paymentModal: document.getElementById('paymentModal'),
        paymentProgress: document.getElementById('paymentProgress'),
        cancelPayment: document.getElementById('cancelPayment'),
        successModal: document.getElementById('successModal'),
        successQuantity: document.getElementById('successQuantity'),
        successTicketId: document.getElementById('successTicketId'),
        successQrCode: document.getElementById('successQrCode'),
        paymentAmount: document.getElementById('paymentAmount'),
        paymentMethodDisplay: document.getElementById('paymentMethodDisplay'),
        paymentInstruction: document.getElementById('paymentInstruction'),
        phoneNumber: document.getElementById('phoneNumber')
    };

    const PRIX_UNITAIRE = 1000;
    let paymentInterval;

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

    // Fonction pour vérifier le statut d'une transaction
    const checkTransactionStatus = async (transactionId) => {
        try {
            const response = await fetch(`/CCEE-on-Top-main/check_transaction.php?transaction_id=${transactionId}`);
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            return data;
        } catch (error) {
            console.error('Erreur lors de la vérification de la transaction:', error);
            throw error;
        }
    };

    // Fonction pour afficher les tickets
    const showTickets = (tickets) => {
        const ticketsList = tickets.map(ticket => 
            `<div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-center">
                <span class="font-mono text-lg">${ticket}</span>
            </div>`
        ).join('');
        
        const paymentForm = document.getElementById('payment-form');
        if (paymentForm) {
            paymentForm.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-green-500 text-5xl mb-4">✓</div>
                    <h3 class="text-2xl font-bold mb-2">Paiement approuvé !</h3>
                    <p class="mb-6">Voici vos numéros de ticket :</p>
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 max-w-2xl mx-auto mb-8">
                        ${ticketsList}
                    </div>
                    <p class="text-sm text-gray-600">Ces tickets sont également disponibles dans votre espace personnel.</p>
                    <div class="mt-8">
                        <a href="/CCEE-on-Top-main/profile.php" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-full inline-block">
                            Voir mon profil
                        </a>
                    </div>
                </div>
            `;
        }
    };

    // Fonction pour afficher les erreurs
    const showError = (message) => {
        const paymentForm = document.getElementById('payment-form');
        if (paymentForm) {
            paymentForm.innerHTML = `
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-700">
                                ${message}
                            </p>
                            <div class="mt-4">
                                <button onclick="window.location.reload()" class="text-red-700 hover:text-red-600 font-medium">
                                    ← Retour au paiement
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }
    };

    // Fonction principale de traitement du paiement
    const processPayment = async (method, amount, phone, quantity) => {
        const paymentForm = document.getElementById('payment-form');
        
        try {
            // Afficher le chargement
            if (paymentForm) {
                paymentForm.innerHTML = `
                    <div class="text-center py-12">
                        <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-blue-500 mx-auto mb-4"></div>
                        <p class="text-lg font-medium mb-2">Traitement de votre paiement...</p>
                        <p class="text-sm text-gray-500">Veuillez patienter, cette opération peut prendre quelques instants.</p>
                    </div>
                `;
            }

            // Envoyer la requête de paiement
            const response = await fetch('/CCEE-on-Top-main/payment/process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    paymentMethod: method,
                    phoneNumber: phone,
                    quantity: quantity,
                    amount: amount
                })
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.message || 'Erreur lors du traitement du paiement');
            }

            // Vérifier périodiquement le statut de la transaction
            const checkStatus = async () => {
                try {
                    const status = await checkTransactionStatus(data.transaction_id);
                    
                    if (status.status === 'complete') {
                        // Afficher les tickets
                        showTickets(status.tickets);
                    } else if (status.status === 'rejetée') {
                        throw new Error('Votre paiement a été rejeté. Veuillez réessayer.');
                    } else {
                        // Vérifier à nouveau après 5 secondes
                        setTimeout(checkStatus, 5000);
                    }
                } catch (error) {
                    console.error('Erreur lors de la vérification du statut:', error);
                    showError(error.message || 'Erreur lors de la vérification du statut du paiement');
                }
            };
            
            // Démarrer la vérification
            await checkStatus();
            
        } catch (error) {
            console.error('Erreur lors du traitement du paiement:', error);
            showError(error.message || 'Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer.');
        }
    };

    // Initialisation
    if (elements.ticketQuantity) {
        updateQuantities();
        
        // Événements
        elements.decreaseQuantity?.addEventListener('click', () => {
            const current = parseInt(elements.ticketQuantity.value);
            if (current > 1) {
                elements.ticketQuantity.value = current - 1;
                updateQuantities();
            }
        });

        elements.increaseQuantity?.addEventListener('click', () => {
            const current = parseInt(elements.ticketQuantity.value);
            elements.ticketQuantity.value = current + 1;
            updateQuantities();
        });

        elements.ticketQuantity?.addEventListener('change', () => {
            const value = parseInt(elements.ticketQuantity.value);
            if (isNaN(value) || value < 1) {
                elements.ticketQuantity.value = 1;
            }
            updateQuantities();
        });
    }

    // Gestion des méthodes de paiement
    elements.paymentMethods?.forEach(method => {
        method.addEventListener('click', () => {
            elements.paymentMethods.forEach(m => m.classList.remove('border-blue-500', 'bg-blue-50'));
            method.classList.add('border-blue-500', 'bg-blue-50');
            elements.paymentMethod.value = method.dataset.method;
            
            // Mettre à jour les instructions de paiement
            const methodConfig = PAYMENT_CONFIG[method.dataset.method];
            if (methodConfig) {
                elements.phoneNumber.placeholder = methodConfig.placeholder;
                elements.phoneNumber.pattern = methodConfig.pattern;
                elements.paymentMethodDisplay.textContent = methodConfig.name;
            }
        });
    });

    // Soumission du formulaire
    elements.paymentForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const method = elements.paymentMethod.value;
        const phone = elements.phoneNumber.value;
        const quantity = parseInt(elements.ticketQuantity.value);
        const amount = quantity * PRIX_UNITAIRE;
        
        if (!method) {
            alert('Veuillez sélectionner une méthode de paiement');
            return;
        }
        
        if (!phone) {
            alert('Veuillez entrer votre numéro de téléphone');
            return;
        }
        
        // Vérifier le format du numéro de téléphone
        const methodConfig = PAYMENT_CONFIG[method];
        if (methodConfig && !new RegExp(methodConfig.pattern).test(phone)) {
            alert(`Veuillez entrer un numéro ${methodConfig.name} valide`);
            return;
        }
        
        try {
            await processPayment(method, amount, phone, quantity);
        } catch (error) {
            console.error('Erreur lors du traitement du paiement:', error);
            showError('Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer.');
        }
    });

    // Annulation du paiement
    elements.cancelPayment?.addEventListener('click', () => {
        if (confirm('Êtes-vous sûr de vouloir annuler ce paiement ?')) {
            window.location.href = '/CCEE-on-Top-main/';
        }
    });
});
