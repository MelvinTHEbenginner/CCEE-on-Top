document.addEventListener('DOMContentLoaded', function() {
    // Récupérer les tickets de l'utilisateur
    const userTickets = JSON.parse(localStorage.getItem('userTickets')) || [];
    const ticketsList = document.getElementById('ticketsList');
    const noTicketsMessage = document.getElementById('noTicketsMessage');
    const totalTickets = document.getElementById('totalTickets');

    // Afficher mes tickets la 
    if (userTickets.length > 0) {
        noTicketsMessage.style.display = 'none';
        totalTickets.textContent = userTickets.length;
        
        userTickets.forEach(ticket => {
            const ticketElement = document.createElement('div');
            ticketElement.className = 'p-6 flex justify-between items-center hover:bg-gray-50';
            ticketElement.innerHTML = `
                <div>
                    <h3 class="font-bold">Ticket #${ticket.id}</h3>
                    <p class="text-sm text-gray-500">Acheté le ${ticket.date}</p>
                </div>
                <button class="view-qr-btn bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-full text-sm transition duration-300" data-ticket-id="${ticket.id}">
                    Voir QR Code
                </button>
            `;
            ticketsList.appendChild(ticketElement);
        });
    } else {
        totalTickets.textContent = '0';
    }

    // Gestion du QR Code
    const qrModal = document.getElementById('qrModal');
    const closeQrModal = document.getElementById('closeQrModal');
    const qrTicketId = document.getElementById('qrTicketId');
    const qrCodeImage = document.getElementById('qrCodeImage');
    const downloadQrBtn = document.getElementById('downloadQrBtn');

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('view-qr-btn')) {
            const ticketId = e.target.getAttribute('data-ticket-id');
            qrTicketId.textContent = ticketId;
            qrCodeImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=CCEE-TOMBOLA-${ticketId}`;
            qrModal.classList.remove('hidden');
        }
    });

    closeQrModal.addEventListener('click', function() {
        qrModal.classList.add('hidden');
    });

    downloadQrBtn.addEventListener('click', function() {
        const link = document.createElement('a');
        link.href = qrCodeImage.src;
        link.download = `QRCode-Ticket-${qrTicketId.textContent}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
});