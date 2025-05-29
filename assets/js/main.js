/**
 * Script principal pour la Tombola CCEE ESATIC
 * Gère la navigation, les animations et les interactions utilisateur
 */

document.addEventListener('DOMContentLoaded', function() {
    // Gestion du header au défilement
    const header = document.querySelector('.header');
    if (header) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    }

    // Menu mobile
    const mobileMenuButton = document.querySelector('[aria-controls="mobile-menu"]');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            const expanded = this.getAttribute('aria-expanded') === 'true' || false;
            this.setAttribute('aria-expanded', !expanded);
            mobileMenu.classList.toggle('hidden');
            
            // Animation de l'icône du menu
            const menuIcon = this.querySelector('i');
            if (menuIcon) {
                if (expanded) {
                    menuIcon.classList.remove('fa-times');
                    menuIcon.classList.add('fa-bars');
                } else {
                    menuIcon.classList.remove('fa-bars');
                    menuIcon.classList.add('fa-times');
                }
            }
        });
    }

    // Fermer le menu mobile au clic sur un lien
    document.querySelectorAll('#mobile-menu a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.add('hidden');
            mobileMenuButton.setAttribute('aria-expanded', 'false');
            const menuIcon = mobileMenuButton.querySelector('i');
            if (menuIcon) {
                menuIcon.classList.remove('fa-times');
                menuIcon.classList.add('fa-bars');
            }
        });
    });

    // Animation au défilement
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.animate-on-scroll');
        
        elements.forEach(element => {
            const elementTop = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementTop < windowHeight - 100) {
                element.classList.add('animate-fade-in-up');
            }
        });
    };
    
    // Détection de la visibilité des éléments
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1
        });
        
        document.querySelectorAll('.feature-card, .prize-card, .section-title, .section-subtitle').forEach(el => {
            observer.observe(el);
        });
    } else {
        // Fallback pour les navigateurs qui ne supportent pas IntersectionObserver
        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll(); // Exécuter une fois au chargement
    }

    // Compteur de tickets vendus
    const ticketCounter = document.getElementById('ticketCounter');
    if (ticketCounter) {
        const target = parseInt(ticketCounter.getAttribute('data-target') || '500');
        const duration = 3000; // 3 secondes
        const step = Math.ceil(target / (duration / 16)); // 60fps
        let current = 0;
        
        const updateCounter = () => {
            current += step;
            if (current >= target) {
                current = target;
                ticketCounter.textContent = current.toLocaleString();
                return;
            }
            
            ticketCounter.textContent = current.toLocaleString();
            requestAnimationFrame(updateCounter);
        };
        
        // Démarrer le compteur quand il est visible
        const observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) {
                updateCounter();
                observer.disconnect();
            }
        });
        
        observer.observe(ticketCounter);
    }

    // Mise en forme de la date de l'événement
    const eventDateElements = document.querySelectorAll('[data-event-date]');
    if (eventDateElements.length > 0) {
        const eventDate = new Date('2025-06-30');
        const options = { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        
        const formattedDate = new Intl.DateTimeFormat('fr-FR', options).format(eventDate);
        
        eventDateElements.forEach(element => {
            element.textContent = formattedDate;
        });
    }

    // Animation du bouton d'appel à l'action
    const ctaButtons = document.querySelectorAll('.cta-button');
    ctaButtons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.classList.add('animate-pulse');
        });
        
        button.addEventListener('animationend', () => {
            button.classList.remove('animate-pulse');
        });
    });

    // Initialisation des tooltips
    const tooltipTriggers = document.querySelectorAll('[data-tooltip]');
    tooltipTriggers.forEach(trigger => {
        const tooltip = document.createElement('div');
        tooltip.className = 'tooltip';
        tooltip.textContent = trigger.getAttribute('data-tooltip');
        
        trigger.appendChild(tooltip);
        
        trigger.addEventListener('mouseenter', () => {
            tooltip.classList.add('show');
        });
        
        trigger.addEventListener('mouseleave', () => {
            tooltip.classList.remove('show');
        });
    });
});

// Fonction pour afficher des notifications
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `notification ${type} animate-fade-in-up`;
    
    const icon = document.createElement('i');
    icon.className = type === 'success' ? 'fas fa-check-circle mr-2' : 'fas fa-exclamation-circle mr-2';
    
    const text = document.createTextNode(message);
    
    notification.appendChild(icon);
    notification.appendChild(text);
    
    document.body.appendChild(notification);
    
    // Supprimer la notification après 5 secondes
    setTimeout(() => {
        notification.classList.add('opacity-0', 'transition-opacity', 'duration-500');
        setTimeout(() => notification.remove(), 500);
    }, 5000);
}

// Fonction pour copier du texte dans le presse-papier
function copyToClipboard(text, successMessage = 'Copié !') {
    navigator.clipboard.writeText(text).then(() => {
        showNotification(successMessage, 'success');
    }).catch(err => {
        console.error('Erreur lors de la copie :', err);
        showNotification('Erreur lors de la copie', 'error');
    });
}

// Export des fonctions pour une utilisation globale
window.CCEE = {
    showNotification,
    copyToClipboard
};