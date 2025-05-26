<?php
session_start();
require_once '../config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $conn->prepare('SELECT fullname FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Récupérer le nombre de tickets de l'utilisateur
$stmt = $conn->prepare('SELECT COUNT(*) as ticket_count FROM tickets WHERE user_id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$tickets = $result->fetch_assoc();

// Calculer les chances de gagner
$stmt = $conn->prepare('SELECT COUNT(*) as total_tickets FROM tickets');
$stmt->execute();
$result = $stmt->get_result();
$total_tickets = $result->fetch_assoc()['total_tickets'];
$win_chance = ($total_tickets > 0) ? ($tickets['ticket_count'] / $total_tickets * 100) : 0;
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
        
        <!--  -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <!-- Tickets  -->
            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col">
                <h3 class="text-xl font-semibold mb-4">Tickets achetés</h3>
                <div class="flex items-center justify-between flex-grow">
                    <div>
                        <p class="text-3xl font-bold"><?php echo $tickets['ticket_count']; ?></p>
                        <p class="text-gray-500">Chance de gagner : <?php echo number_format($win_chance, 3); ?>%</p>
                        <a href="tickets.php" class="text-blue-500 hover:underline inline-flex items-center mt-2">
                            Voir mes tickets
                            <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <i class="fas fa-ticket-alt text-yellow-500 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md flex flex-col">
                <h3 class="text-xl font-semibold mb-4">Chances de gagner</h3>
                <div class="flex items-center justify-between flex-grow">
                    <div>
                        <p class="text-3xl font-bold"><?php echo number_format($win_chance, 1); ?>%</p>
                        <p class="text-gray-500">Basé sur le nombre total de tickets vendus</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-percentage text-blue-500 text-xl"></i>
                    </div>
                </div>
            </div>

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
                
                <!-- <a href="#" class="bg-white hover:bg-gray-50 rounded-lg shadow p-6 text-center transition duration-300 border border-gray-200">
                    <div class="text-purple-500 mb-3">
                        <i class="fas fa-gift text-3xl"></i>
                    </div>
                    <h3 class="font-bold mb-1">Lots à gagner</h3>
                    <p class="text-sm text-gray-500">Découvrez les prix</p>
                </a>
                
                <a href="#" class="bg-white hover:bg-gray-50 rounded-lg shadow p-6 text-center transition duration-300 border border-gray-200">
                    <div class="text-green-500 mb-3">
                        <i class="fas fa-info-circle text-3xl"></i>
                    </div>
                    <h3 class="font-bold mb-1">Informations</h3>
                    <p class="text-sm text-gray-500">Règles et conditions</p>
                </a> -->
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

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>