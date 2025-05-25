// Gestion de l'authentification
document.addEventListener('DOMContentLoaded', function() {
    //tchai je susi fatigué d'indiquer hein 
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');
    
    //  connexion
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            // Ici, requete à faire backend
            localStorage.setItem('userLoggedIn', 'true');
            localStorage.setItem('userName', 'Jean Dupont');
            window.location.href = '../dashboard/';
        });
    }
    
    // Simulation 
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (password !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas');
                return;
            }
            
            // Ici la faut faire les requetes la, backend
            localStorage.setItem('userLoggedIn', 'true');
            localStorage.setItem('userName', document.getElementById('fullname').value);
            window.location.href = '../dashboard/';
        });
    }
    
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            localStorage.removeItem('userLoggedIn');
            localStorage.removeItem('userName');
            window.location.href = '../../auth/login.html';
        });
    }
    
    // tchai tu vas comprendre
    const protectedPages = ['/dashboard/', '/dashboard/tickets.html', '/payment/'];
    if (protectedPages.some(page => window.location.pathname.includes(page))) {
        if (!localStorage.getItem('userLoggedIn')) {
            window.location.href = '../../auth/login.html';
        }
    }
});