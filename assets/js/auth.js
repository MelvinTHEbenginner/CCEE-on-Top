document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    // Connexion
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;

            // Simulation d'authentification
            const users = JSON.parse(localStorage.getItem('users')) || [];
            const user = users.find(u => u.email === email && u.password === password);

            if (user) {
                localStorage.setItem('userLoggedIn', 'true');
                localStorage.setItem('userName', user.fullname);
                localStorage.setItem('userEmail', user.email);
                localStorage.setItem('userPhone', user.phone);
                window.location.href = '../dashboard/';
            } else {
                alert('Email ou mot de passe incorrect');
            }
        });
    }

    // Inscription
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas');
                return;
            }

            const user = {
                fullname: document.getElementById('fullname').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                password: password
            };

            // Sauvegarde de l'utilisateur
            const users = JSON.parse(localStorage.getItem('users')) || [];
            users.push(user);
            localStorage.setItem('users', JSON.stringify(users));

            // Connexion automatique
            localStorage.setItem('userLoggedIn', 'true');
            localStorage.setItem('userName', user.fullname);
            localStorage.setItem('userEmail', user.email);
            localStorage.setItem('userPhone', user.phone);
            window.location.href = '../dashboard/';
        });
    }

    // Déconnexion
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
            localStorage.removeItem('userLoggedIn');
            localStorage.removeItem('userName');
            localStorage.removeItem('userEmail');
            localStorage.removeItem('userPhone');
            window.location.href = '../auth/login.html';
        });
    }

    // Protection des pages
    const protectedPages = ['/dashboard/', '/dashboard/tickets.html', '/payment/'];
    if (protectedPages.some(page => window.location.pathname.includes(page))) {
        if (!localStorage.getItem('userLoggedIn')) {
            window.location.href = '../auth/login.html';
        }
    }
});