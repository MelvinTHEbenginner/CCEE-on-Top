// Gestion du paiement
document.addEventListener('DOMContentLoaded', function() {
    // tchai les variables la c'est generer flemme
    const ticketQuantity = document.getElementById('ticketQuantity');
    const decreaseQuantity = document.getElementById('decreaseQuantity');
    const increaseQuantity = document.getElementById('increaseQuantity');
    const totalAmount = document.getElementById('totalAmount');
    const summaryQuantity = document.getElementById('summaryQuantity');
    const summaryTotal = document.getElementById('summaryTotal');
    const paymentMethods = document.querySelectorAll('.payment-method');
    const paymentMethod = document.getElementById('paymentMethod');
    const paymentForm = document.getElementById('paymentForm');
    const paymentModal = document.getElementById('paymentModal');
    const paymentProgress = document.getElementById('paymentProgress');
    const cancelPayment = document.getElementById('cancelPayment');
    const successModal = document.getElementById('successModal');
    const successQuantity = document.getElementById('successQuantity');
    const successTicketId = document.getElementById('successTicketId');
    const successQrCode = document.getElementById('successQrCode');
    const paymentAmount = document.getElementById('paymentAmount');
    const paymentMethodDisplay = document.getElementById('paymentMethodDisplay');
    const paymentInstruction = document.getElementById('paymentInstruction');
    
    // Prix qui changra pas 
    const unitPrice = 1000;
    
    // Mise à jour des quantités et montants sinon ah 
    function updateQuantities() {
        const quantity = parseInt(ticketQuantity.value);
        const total = quantity * unitPrice;
        
        totalAmount.textContent = total.toLocaleString('fr-FR') + ' FCFA';
        summaryQuantity.textContent = quantity;
        summaryTotal.textContent = total.toLocaleString('fr-FR') + ' FCFA';
    }
    
    // Diminuer la quantité
    decreaseQuantity.addEventListener('click', function() {
        let value = parseInt(ticketQuantity.value);
        if (value > 1) {
            ticketQuantity.value = value - 1;
            updateQuantities();
        }
    });
    
    // Augmenter la quantité c'est facile
    increaseQuantity.addEventListener('click', function() {
        let value = parseInt(ticketQuantity.value);
        if (value < 10) {
            ticketQuantity.value = value + 1;
            updateQuantities();
        }
    });
    
    // les updating de quantité la
    ticketQuantity.addEventListener('change', function() {
        let value = parseInt(this.value);
        if (isNaN(value) || value < 1) {
            this.value = 1;
        } else if (value > 10) {
            this.value = 10;
        }
        updateQuantities();
    });
    
    // choix 
    paymentMethods.forEach(method => {
        method.addEventListener('click', function() {
            paymentMethods.forEach(m => m.classList.remove('border-blue-500', 'bg-white/10'));
            this.classList.add('border-blue-500', 'bg-white/10');
            paymentMethod.value = this.getAttribute('data-method');
        });
    });
    
    // Soumission du formulaire de paiement
    paymentForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (!paymentMethod.value) {
            alert('Veuillez sélectionner une méthode de paiement');
            return;
        }
        
        const quantity = parseInt(ticketQuantity.value);
        const total = quantity * unitPrice;
        
        // Afficher les wé de paiements tu vas te debrouiller avec backend
        paymentAmount.textContent = total.toLocaleString('fr-FR') + ' FCFA';
        
        let methodDisplay = '';
        switch(paymentMethod.value) {
            case 'orange': methodDisplay = 'Orange Money'; break;
            case 'mtn': methodDisplay = 'MTN MoMo'; break;
            case 'wave': methodDisplay = 'Wave'; break;
        }
        
        paymentMethodDisplay.textContent = methodDisplay;
        paymentInstruction.textContent = `Confirmez le paiement de ${total.toLocaleString('fr-FR')} FCFA via ${methodDisplay}`;
        
        paymentModal.classList.remove('hidden');
        
        // Simulatio
        let progress = 0;
        const interval = setInterval(() => {
            progress += 5;
            paymentProgress.style.width = `${progress}%`;
            
            if (progress >= 100) {
                clearInterval(interval);
                paymentModal.classList.add('hidden');
                
                // Générer un ticket aléatoire pour chaque petit 
                const ticketId = 'T-' + Math.floor(Math.random() * 10000);
                
                // Afficher le succès
                successQuantity.textContent = quantity;
                successTicketId.textContent = ticketId;
                successQrCode.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=CCEE-TOMBOLA-${ticketId}-${localStorage.getItem('userName')}`;
                successModal.classList.remove('hidden');
            }
        }, 300);
        
        // Annulation du paiement
        cancelPayment.addEventListener('click', function() {
            clearInterval(interval);
            paymentModal.classList.add('hidden');
        }, { once: true });
    });
    
    // Initialisation de quantité 
    updateQuantities();
});