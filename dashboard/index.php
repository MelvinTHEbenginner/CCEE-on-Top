<?php
session_start();
require_once '../init.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare('SELECT id, fullname, email, phone FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur et ses tickets
$stats = [
    'ticket_count' => 0,
    'win_chance' => 0
];

// Récupérer le nombre de tickets de l'utilisateur
$stmt = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$stats['ticket_count'] = (int)$stmt->fetchColumn();

// Récupérer le nombre total de tickets vendus pour le calcul des chances
$stmt = $pdo->query("SELECT COUNT(*) FROM tickets WHERE id_transaction IS NOT NULL");
$soldTickets = (int)$stmt->fetchColumn();

// Calculer le pourcentage de chance de gagner
$stats['win_chance'] = ($soldTickets > 0) ? 
    round(($stats['ticket_count'] / $soldTickets) * 100, 2) : 0;

// Récupérer les tickets de l'utilisateur
$userTickets = [];
$stmt = $pdo->prepare("
    SELECT t.*, tr.transaction_date
    FROM tickets t
    JOIN transactions tr ON t.id_transaction = tr.id
    WHERE tr.user_id = ?
    ORDER BY t.purchase_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$userTickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les tickets de l'utilisateur avec les détails des transactions
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Tombola CCEE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navigation -->
    <nav class="bg-blue-900 text-white shadow-lg">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <img src="../assets/images/logo.png" alt="Logo CCEE" class="h-10">
                    <span class="font-bold text-xl">CCEE </span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="hover:text-yellow-300 font-medium">Tableau de bord</a>
                    <a href="tickets.php" class="hover:text-yellow-300">Mes tickets</a>
                    <a href="profile.php" class="hover:text-yellow-300">Profil</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../auth/logout.php" class="text-white hover:text-yellow-300">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Dashboard -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-blue-900">Bonjour, <span id="userName"><?php echo htmlspecialchars($user['fullname']); ?></span> !</h1>
            <p class="text-gray-600">Bienvenue sur votre espace personnel pour la tombola de l'Apothéose.</p>
        </div>

        <!-- Statistiques utilisateur -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Mes tickets -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                        <i class="fas fa-ticket-alt text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Mes tickets</p>
                        <h3 class="text-2xl font-bold"><?php echo $stats['ticket_count']; ?></h3>
                    </div>
                </div>
            </div>
            
            <!-- Chances de gagner -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                        <i class="fas fa-trophy text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 text-sm">Chances de gagner</p>
                        <h3 class="text-2xl font-bold"><?php echo number_format($stats['win_chance'], 2); ?>%</h3>
                        <p class="text-xs text-gray-400 mt-1">Basé sur les tickets vendus</p>
                    </div>
                </div>
            </div>
        </div>

        
        <!--  -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <!-- Tickets  -->
            
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col">
                <h3 class="text-xl font-semibold mb-4">Acheter un ticket</h3>
                <div class="flex items-center justify-between flex-grow">
                    <div>
                        <p class="text-3xl font-bold">1000 FCFA</p>
                        <p class="text-gray-500">Prix par ticket</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-shopping-cart text-green-500 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <a href="../payment/" class="inline-block bg-blue-900 hover:bg-blue-800 text-white font-bold py-3 px-8 rounded-full text-lg transition duration-300">
                        Acheter maintenant
                    </a>
                </div>
            </div>
        </div>
        
        
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-blue-900 mb-4">Actions rapides</h2>
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="../payment/index.php" class="bg-white hover:bg-gray-50 rounded-lg shadow p-6 text-center transition duration-300 border border-gray-200">
                    <div class="text-blue-500 mb-3">
                        <i class="fas fa-plus-circle text-3xl"></i>
                    </div>
                    <h3 class="font-bold mb-1">Acheter un ticket</h3>
                    <p class="text-sm text-gray-500">1000 FCFA par ticket</p>
                </a>
                
                <a href="tickets.php" class="bg-white hover:bg-gray-50 rounded-lg shadow p-6 text-center transition duration-300 border border-gray-200">
                    <div class="text-yellow-500 mb-3">
                        <i class="fas fa-ticket-alt text-3xl"></i>
                    </div>
                    <h3 class="font-bold mb-1">Mes tickets</h3>
                    <p class="text-sm text-gray-500">Voir vos participations</p>
                </a>
                
            </div>
        </div>
        
        <!--  Countdown pour ce putain event  -->
        <div class="bg-blue-900 text-white rounded-xl shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4">Tirage de la tombola</h2>
            <p class="mb-6">Le tirage aura lieu lors de l'Apothéose le 08 juin 2025. Il vous reste :</p>
            
            <div class="grid grid-cols-4 gap-4 text-center">
                <div class="bg-white/20 p-4 rounded-lg">
                    <div class="text-3xl font-bold" id="countdown-days">00</div>
                    <div class="text-sm uppercase">Jours</div>
                </div>
                <div class="bg-white/20 p-4 rounded-lg">
                    <div class="text-3xl font-bold" id="countdown-hours">00</div>
                    <div class="text-sm uppercase">Heures</div>
                </div>
                <div class="bg-white/20 p-4 rounded-lg">
                    <div class="text-3xl font-bold" id="countdown-minutes">00</div>
                    <div class="text-sm uppercase">Minutes</div>
                </div>
                <div class="bg-white/20 p-4 rounded-lg">
                    <div class="text-3xl font-bold" id="countdown-seconds">00</div>
                    <div class="text-sm uppercase">Secondes</div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Fonction pour télécharger un ticket
    function downloadTicket(ticketCode) {
        // Créer une URL pour le téléchargement du ticket
        const url = `ticket_download.php?code=${encodeURIComponent(ticketCode)}`;
        
        // Créer un lien temporaire et déclencher le téléchargement
        const link = document.createElement('a');
        link.href = url;
        link.download = `ticket-${ticketCode}.pdf`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    // Fonction pour valider un ticket
    async function validateTicket(ticketCode, element) {
        if (!confirm('Êtes-vous sûr de vouloir valider ce ticket ? Cette action est irréversible.')) {
            return false;
        }

        const icon = element.querySelector('i');
        const originalIcon = icon.className;
        
        try {
            // Afficher un indicateur de chargement
            icon.className = 'fas fa-spinner fa-spin';
            
            // Envoyer la requête de validation
            const response = await fetch('validate_ticket.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ticket_code=${encodeURIComponent(ticketCode)}`
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Mettre à jour l'interface utilisateur
                const row = element.closest('tr');
                const statusCell = row.querySelector('td:nth-child(4)');
                const actionsCell = row.querySelector('td:last-child');
                
                // Mettre à jour le statut
                statusCell.innerHTML = `
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i> Validé
                    </span>
                `;
                
                // Supprimer le bouton de validation
                const validateButton = actionsCell.querySelector('a[onclick*="validateTicket"]');
                if (validateButton) {
                    validateButton.remove();
                }
                
                // Afficher un message de succès
                showAlert('success', 'Ticket validé avec succès !');
            } else {
                throw new Error(result.message || 'Erreur lors de la validation du ticket');
            }
        } catch (error) {
            console.error('Erreur:', error);
            showAlert('error', error.message || 'Une erreur est survenue lors de la validation du ticket');
            icon.className = originalIcon;
        }
        
        return false;
    }
    
    // Fonction pour afficher des messages d'alerte
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 p-4 rounded-md shadow-lg ${
            type === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
        }`;
        alertDiv.innerHTML = `
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium">
                        ${message}
                    </p>
                </div>
                <div class="ml-4">
                    <button type="button" class="inline-flex text-gray-500 hover:text-gray-700 focus:outline-none" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
        
        // Supprimer l'alerte après 5 secondes
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    
    // Initialisation des tooltips
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips avec Tippy.js si la bibliothèque est chargée
        if (typeof tippy !== 'undefined') {
            tippy('[title]');
        }

        // Définir la date du tirage (8 juin 2025)
        const drawDate = new Date('2025-06-08T20:00:00');
        
        // Mettre à jour le compte à rebours toutes les secondes
        const countdownInterval = setInterval(updateCountdown, 1000);
        
        // Mettre à jour immédiatement pour éviter le délai d'une seconde
        updateCountdown();
        
        function updateCountdown() {
            const now = new Date();
            const distance = drawDate - now;
            
            // Arrêter le compte à rebours si la date est passée
            if (distance < 0) {
                clearInterval(countdownInterval);
                document.getElementById('countdown-days').textContent = '00';
                document.getElementById('countdown-hours').textContent = '00';
                document.getElementById('countdown-minutes').textContent = '00';
                document.getElementById('countdown-seconds').textContent = '00';
                return;
            }
            
            // Calculer les jours, heures, minutes et secondes
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            // Mettre à jour l'affichage
            document.getElementById('countdown-days').textContent = days.toString().padStart(2, '0');
            document.getElementById('countdown-hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('countdown-minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('countdown-seconds').textContent = seconds.toString().padStart(2, '0');
        }
    });
    </script>
</body>
</html>