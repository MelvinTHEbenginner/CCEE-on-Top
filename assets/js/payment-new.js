// Configuration
const CONFIG = {
    ticketPrice: 1000, // Prix unitaire d'un ticket en FCFA
    maxTickets: 10,   // Nombre maximum de tickets pouvant être achetés en une fois
    minTickets: 1     // Nombre minimum de tickets pouvant être achetés
};

// Éléments du DOM
const elements = {
    ticketQuantity: document.getElementById('ticketQuantity'),
    increaseQuantity: document.getElementById('increaseQuantity'),
    decreaseQuantity: document.getElementById('decreaseQuantity'),
    totalAmount: document.getElementById('totalAmount'),
    summaryQuantity: document.getElementById('summaryQuantity'),
    summaryTotal: document.getElementById('summaryTotal'),
    payButton: document.querySelector('.btn-pay'),
    cancelButton: document.getElementById('cancelPayment'),
    paymentForm: document.getElementById('paymentForm')
};

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    if (!elements.ticketQuantity) return;

    // Initialiser les écouteurs d'événements
    setupEventListeners();
    
    // Mettre à jour les quantités au chargement
    updateQuantities();
});

// Configuration des écouteurs d'événements
function setupEventListeners() {
    // Augmenter la quantité
    if (elements.increaseQuantity) {
        elements.increaseQuantity.addEventListener('click', () => {
            const current = parseInt(elements.ticketQuantity.value) || 0;
            if (current < CONFIG.maxTickets) {
                elements.ticketQuantity.value = current + 1;
                updateQuantities();
            }
        });
    }

    // Diminuer la quantité
    if (elements.decreaseQuantity) {
        elements.decreaseQuantity.addEventListener('click', () => {
            const current = parseInt(elements.ticketQuantity.value) || CONFIG.minTickets;
            if (current > CONFIG.minTickets) {
                elements.ticketQuantity.value = current - 1;
                updateQuantities();
            }
        });
    }

    // Validation manuelle de la quantité
    if (elements.ticketQuantity) {
        elements.ticketQuantity.addEventListener('change', () => {
            let value = parseInt(elements.ticketQuantity.value) || CONFIG.minTickets;
            value = Math.max(CONFIG.minTickets, Math.min(CONFIG.maxTickets, value));
            elements.ticketQuantity.value = value;
            updateQuantities();
        });
    }

    // Soumission du formulaire de paiement
    if (elements.payButton && elements.paymentForm) {
        elements.paymentForm.addEventListener('submit', handlePayment);
    }

    // Annulation du paiement
    if (elements.cancelButton) {
        elements.cancelButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (confirm('Êtes-vous sûr de vouloir annuler ce paiement ?')) {
                window.location.href = '/CCEE-on-Top-main/';
            }
        });
    }
}

// Mise à jour des quantités et des totaux
function updateQuantities() {
    const quantity = parseInt(elements.ticketQuantity.value) || CONFIG.minTickets;
    const total = quantity * CONFIG.ticketPrice;
    
    if (elements.totalAmount) elements.totalAmount.textContent = formatMoney(total);
    if (elements.summaryQuantity) elements.summaryQuantity.textContent = quantity;
    if (elements.summaryTotal) elements.summaryTotal.textContent = formatMoney(total);
}

// Gestion de la soumission du formulaire de paiement
async function handlePayment(e) {
    e.preventDefault();
    
    const quantity = parseInt(elements.ticketQuantity.value) || CONFIG.minTickets;
    
    // Validation de la quantité
    if (quantity < CONFIG.minTickets || quantity > CONFIG.maxTickets) {
        showError(`Veuillez sélectionner une quantité valide entre ${CONFIG.minTickets} et ${CONFIG.maxTickets} tickets`);
        return;
    }
    
    try {
        // Afficher le chargement
        showLoading('Traitement de votre paiement...');
        
        // Démarrer le processus de paiement
        await processPayment(quantity);
        
    } catch (error) {
        console.error('Erreur lors du traitement du paiement:', error);
        showError(error.message || 'Une erreur est survenue lors du traitement de votre paiement. Veuillez réessayer.');
    }
}

// Traitement du paiement
async function processPayment(quantity) {
    try {
        const response = await fetch('/CCEE-on-Top-main/payment/process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `quantity=${encodeURIComponent(quantity)}`
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Erreur lors du traitement de la transaction');
        }
        
        // Rediriger vers la page de confirmation
        if (data.redirect) {
            window.location.href = data.redirect;
        } else {
            showSuccess('Votre paiement a été traité avec succès !');
        }
        
        return data;
        
    } catch (error) {
        console.error('Erreur lors du paiement:', error);
        throw error;
    }
}

// Fonction utilitaire pour formater l'argent
function formatMoney(amount) {
    return new Intl.NumberFormat('fr-FR', { 
        style: 'currency', 
        currency: 'XOF',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// Fonction pour afficher le chargement
function showLoading(message = 'Chargement...') {
    // Implémentez l'affichage du chargement selon votre interface
    console.log('Chargement:', message);
}

// Fonction pour afficher une erreur
function showError(message) {
    // Implémentez l'affichage des erreurs selon votre interface
    console.error('Erreur:', message);
    alert(message);
}

// Fonction pour afficher un succès
function showSuccess(message) {
    // Implémentez l'affichage du succès selon votre interface
    console.log('Succès:', message);
    alert(message);
}
