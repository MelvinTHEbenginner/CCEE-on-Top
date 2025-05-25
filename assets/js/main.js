// Script principal pour la page d'accueil

document.addEventListener('DOMContentLoaded', function() {
    // Animation du titre
    const title = document.querySelector('h1');
    if (title) {
        setTimeout(() => {
            title.classList.add('animate-pulse-glow');
        }, 1000);
    }

    // Vérifier si l'utilisateur est connecté pour modifier le header
    const isLoggedIn = localStorage.getItem('userLoggedIn');
    const navAuthSection = document.querySelector('nav .hidden.md\\:flex.space-x-6');
    
    if (isLoggedIn && navAuthSection) {
        // Modifier les liens de navigation pour un utilisateur connecté
        navAuthSection.innerHTML = `
            <a href="dashboard/" class="hover:text-yellow-300">Tableau de bord</a>
            <a href="dashboard/tickets.html" class="hover:text-yellow-300">Mes tickets</a>
            <a href="#" class="hover:text-yellow-300">Profil</a>
        `;
        
        // Changer le bouton d'inscription en déconnexion
        const registerBtn = document.querySelector('nav a[href="auth/register.html"]');
        if (registerBtn) {
            registerBtn.outerHTML = `
                <button id="logoutBtn" class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-4 rounded-full transition duration-300">
                    Déconnexion
                </button>
            `;
            
            // Gérer la déconnexion
            document.getElementById('logoutBtn').addEventListener('click', function() {
                localStorage.removeItem('userLoggedIn');
                localStorage.removeItem('userName');
                window.location.href = 'auth/login.html';
            });
        }
    }

    // Animation des cartes de lot
    const prizeCards = document.querySelectorAll('.prize-card');
    prizeCards.forEach((card, index) => {
        card.style.transitionDelay = `${index * 0.1}s`;
    });

    // Effet de scroll doux pour les ancres
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Initialiser un compteur de tickets vendus (simulation)
    const ticketCounter = document.getElementById('ticketCounter');
    if (ticketCounter) {
        // Simuler un compteur qui augmente
        let count = 0;
        const target = Math.floor(Math.random() * 500) + 300;
        const interval = setInterval(() => {
            count += 5;
            if (count >= target) {
                count = target;
                clearInterval(interval);
            }
            ticketCounter.textContent = count;
        }, 50);
    }

    // Ajouter la date de l'événement dans le footer
    const eventDateElement = document.getElementById('eventDate');
    if (eventDateElement) {
        const eventDate = new Date('2025-06-30');
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        eventDateElement.textContent = eventDate.toLocaleDateString('fr-FR', options);
    }
});

// Fonction pour afficher une notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    } text-white`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
        setTimeout(() => {
            notification.remove();
        }, 500);
    }, 3000);
}