// Configuration des numéros de paiement
const PAYMENT_CONFIG = {
    orange: {
      number: "0788045849", 
      apiUrl: "https://api.orange.com/payment",
      merchantCode: "YOUR_ORANGE_MERCHANT_CODE"
    },
    mtn: {
      number: "0500000000", 
      apiUrl: "https://api.mtn.com/v1/payments",
      apiKey: "YOUR_MTN_API_KEY"
    },
    wave: {
      number: "0100000000", 
      apiUrl: "https://api.wave.com/payments",
      clientId: "YOUR_WAVE_CLIENT_ID"
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
  
      const simulatePayment = (method, amount, phone, callback) => {
          // En production, remplacer par un vrai appel API
          console.log(`Envoi de ${amount}FCFA au ${phone} via ${method}`);
          
          // Simulation de délai de paiement
          let progress = 0;
          paymentInterval = setInterval(() => {
              progress += 5;
              elements.paymentProgress.style.width = `${progress}%`;
              
              if (progress >= 100) {
                  clearInterval(paymentInterval);
                  callback(true);
              }
          }, 300);
      };
  
      const generateTicket = (quantity) => {
          const tickets = [];
          for (let i = 0; i < quantity; i++) {
              tickets.push({
                  id: 'T-' + Math.floor(Math.random() * 10000),
                  date: new Date().toISOString().split('T')[0]
              });
          }
          return tickets;
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
  
      elements.paymentForm.addEventListener('submit', function(e) {
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
  
          // Simuler le paiement
          simulatePayment(method, total, phone, (success) => {
              if (success) {
                  elements.paymentModal.classList.add('hidden');
                  const tickets = generateTicket(quantity);
                  
                  // Sauvegarder les tickets (simulation)
                  localStorage.setItem('userTickets', JSON.stringify(tickets));
                  
                  // Afficher le premier ticket comme confirmation
                  elements.successQuantity.textContent = quantity;
                  elements.successTicketId.textContent = tickets[0].id;
                  elements.successQrCode.src = 
                      `https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=CCEE-TOMBOLA-${tickets[0].id}`;
                  elements.successModal.classList.remove('hidden');
              }
          });
      });
  
      elements.cancelPayment.addEventListener('click', () => {
          clearInterval(paymentInterval);
          elements.paymentModal.classList.add('hidden');
      });
  
      // Initialisation
      updateQuantities();
  });