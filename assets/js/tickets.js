document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('qrModal');
    const closeBtn = document.getElementById('closeQrModal');
    const qrTicketId = document.getElementById('qrTicketId');
    const qrCodeImage = document.getElementById('qrCodeImage');
    const downloadQrBtn = document.getElementById('downloadQrBtn');

    // Gestionnaire pour les boutons d'affichage QR
    document.querySelectorAll('.show-qr').forEach(button => {
        button.addEventListener('click', async function() {
            const ticketCode = this.dataset.ticket;
            
            try {
                // Vérifier si le ticket est activé avant d'afficher le QR code
                const response = await fetch('../api/check_ticket_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ticket_code=${ticketCode}`
                });

                const data = await response.json();

                if (data.success && data.is_activated) {
                    // Afficher le QR code
                    qrTicketId.textContent = ticketCode;
                    qrCodeImage.src = `../assets/images/qr-codes/ticket-${ticketCode}.png`;
                    modal.classList.remove('hidden');

                    // Configurer le bouton de téléchargement
                    downloadQrBtn.onclick = () => {
                        const link = document.createElement('a');
                        link.href = qrCodeImage.src;
                        link.download = `ticket-${ticketCode}.png`;
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    };
                } else {
                    alert('Ce ticket n\'est pas encore activé ou n\'existe pas.');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la vérification du ticket.');
            }
        });
    });

    // Fermer le modal
    closeBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
    });

    // Fermer le modal en cliquant en dehors
    window.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});