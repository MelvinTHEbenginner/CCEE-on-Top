document.addEventListener('DOMContentLoaded', function() {
    // Gestion des QR Codes
    const qrButtons = document.querySelectorAll('.show-qr');

    // Gestion du QR Code
    const qrModal = document.getElementById('qrModal');
    const closeQrModal = document.getElementById('closeQrModal');
    const qrTicketId = document.getElementById('qrTicketId');
    const qrCodeImage = document.getElementById('qrCodeImage');
    const downloadQrBtn = document.getElementById('downloadQrBtn');

    qrButtons.forEach(button => {
        button.addEventListener('click', function() {
            const ticketCode = this.getAttribute('data-ticket');
            qrTicketId.textContent = ticketCode;
            qrCodeImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=CCEE-TOMBOLA-${ticketCode}`;
            qrModal.classList.remove('hidden');
        });
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