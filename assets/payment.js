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
  
      // Fonctions
      const updateQuantities = () => {
          const quantity = parseInt(elements.ticketQuantity.value);
          const total = quantity * PRIX_UNITAIRE;
          
          elements.totalAmount.textContent = formatMoney(total);
          elements.summaryQuantity.textContent = quantity;
          elements.summaryTotal.textContent = formatMoney(total);
      };
  
      const formatMoney = (amount) => {
          return amount.toLocaleString('fr-FR') + ' FCFA';
      };
  
      const processPayment = async (method, amount, phone, quantity) => {
          try {
              const response = await fetch('../payment/process.php', {
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

              if (!response.ok) {
                  throw new Error('Erreur lors du paiement');
              }

              const data = await response.json();
              return data;
          } catch (error) {
              console.error('Erreur:', error);
              throw error;
          }
      };
  

  
      // Événements
      elements.decreaseQuantity.addEventListener('click', () => {
          let value = parseInt(elements.ticketQuantity.value);
          if (value > 1) {
              elements.ticketQuantity.value = value - 1;
              updateQuantities();
          }
      });
  
      elements.increaseQuantity.addEventListener('click', () => {
          let value = parseInt(elements.ticketQuantity.value);
          if (value < 10) {
              elements.ticketQuantity.value = value + 1;
              updateQuantities();
          }
      });
  
      elements.ticketQuantity.addEventListener('change', function() {
          let value = parseInt(this.value);
          if (isNaN(value) || value < 1) {
              this.value = 1;
          } else if (value > 10) {
              this.value = 10;
          }
          updateQuantities();
      });
  
      elements.paymentMethods.forEach(method => {
          method.addEventListener('click', function() {
              elements.paymentMethods.forEach(m => 
                  m.classList.remove('border-blue-500', 'bg-white/10'));
              this.classList.add('border-blue-500', 'bg-white/10');
              elements.paymentMethod.value = this.getAttribute('data-method');
          });
      });
  
      elements.paymentForm.addEventListener('submit', async function(e) {
          e.preventDefault();
          
          if (!elements.paymentMethod.value) {
              alert('Veuillez sélectionner une méthode de paiement');
              return;
          }
  
          if (!elements.phoneNumber.value || elements.phoneNumber.value.length < 8) {
              alert('Veuillez entrer un numéro de téléphone valide');
              return;
          }
  
          const quantity = parseInt(elements.ticketQuantity.value);
          const total = quantity * PRIX_UNITAIRE;
          const method = elements.paymentMethod.value;
          const phone = elements.phoneNumber.value;
  
          // Afficher modal de paiement
          elements.paymentAmount.textContent = formatMoney(total);
          elements.paymentMethodDisplay.textContent = 
              method === 'orange' ? 'Orange Money' : 
              method === 'mtn' ? 'MTN MoMo' : 'Wave';
          
          elements.paymentInstruction.textContent = 
              `Confirmez le paiement de ${formatMoney(total)} via ${elements.paymentMethodDisplay.textContent}`;
          
          elements.paymentModal.classList.remove('hidden');
  
          // Afficher le modal de paiement
          elements.paymentModal.classList.remove('hidden');
          elements.paymentProgress.style.width = '0%';

          // Simuler la progression
          let progress = 0;
          paymentInterval = setInterval(() => {
              progress = Math.min(progress + 5, 90);
              elements.paymentProgress.style.width = `${progress}%`;
          }, 300);

          try {
              // Traiter le paiement
              const result = await processPayment(method, total, phone, quantity);
              
              // Compléter la barre de progression
              clearInterval(paymentInterval);
              elements.paymentProgress.style.width = '100%';
              
              // Attendre un peu pour montrer la progression complète
              await new Promise(resolve => setTimeout(resolve, 500));
              
              // Cacher le modal de paiement
              elements.paymentModal.classList.add('hidden');
              
              // Afficher le modal de succès
              elements.successQuantity.textContent = quantity;
              elements.successTicketId.textContent = result.tickets[0].code;
              elements.successQrCode.src = `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=CCEE-TOMBOLA-${result.tickets[0].code}`;
              elements.successModal.classList.remove('hidden');
              
              // Rediriger vers la page des tickets après 3 secondes
              setTimeout(() => {
                  window.location.href = '../dashboard/tickets.php';
              }, 3000);
              
          } catch (error) {
              clearInterval(paymentInterval);
              elements.paymentModal.classList.add('hidden');
              alert('Erreur lors du paiement : ' + error.message);
          }
      });
  
      elements.cancelPayment.addEventListener('click', () => {
          clearInterval(paymentInterval);
          elements.paymentModal.classList.add('hidden');
      });
  
      // Initialisation
      updateQuantities();
  });