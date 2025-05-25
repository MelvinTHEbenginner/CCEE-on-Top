// Tableau de bord
document.addEventListener('DOMContentLoaded', function() {
    
    const userNameElement = document.getElementById('userName');
    if (userNameElement) {
        userNameElement.textContent = localStorage.getItem('userName') || 'Utilisateur';
    }
    
    const ticketsCount = Math.floor(Math.random() * 5) + 1;
    document.getElementById('ticketsCount').textContent = ticketsCount;
    
    // Calculer les chances de gagner (cest juste pour l'effet tu vois non )
    const winChance = (ticketsCount / 1000 * 100).toFixed(2);
    document.getElementById('winChance').textContent = winChance + '%';
    
    // Compte à rebours pour l'événement comme dans fortnite ( ici cest generé)
    function updateCountdown() {
        const now = new Date();
        const eventDate = new Date('2025-06-30T19:00:00');
        const diff = eventDate - now;
        
        if (diff <= 0) {
            document.getElementById('countdown-days').textContent = '00';
            document.getElementById('countdown-hours').textContent = '00';
            document.getElementById('countdown-minutes').textContent = '00';
            document.getElementById('countdown-seconds').textContent = '00';
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        document.getElementById('countdown-days').textContent = days.toString().padStart(2, '0');
        document.getElementById('countdown-hours').textContent = hours.toString().padStart(2, '0');
        document.getElementById('countdown-minutes').textContent = minutes.toString().padStart(2, '0');
        document.getElementById('countdown-seconds').textContent = seconds.toString().padStart(2, '0');
    }
    
    updateCountdown();
    setInterval(updateCountdown, 1000);
});