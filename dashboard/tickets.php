<?php
session_start();
require_once '../config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les tickets de l'utilisateur
$stmt = $conn->prepare('
SELECT t.*, p.name as prize_name, p.description as prize_description, p.image_url as prize_image
FROM tickets t
LEFT JOIN prizes p ON t.prize_id = p.id
WHERE t.user_id = ?
ORDER BY t.purchase_date DESC
');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$tickets = $result->fetch_all(MYSQLI_ASSOC);

// Récupérer le nombre total de tickets
$total_tickets = count($tickets);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Tickets - Tombola CCEE</title>
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
                    <span class="font-bold text-xl">Communauté Catholique des Etudiants de l'ESATIC</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="hover:text-yellow-300">Tableau de bord</a>
                    <a href="tickets.php" class="hover:text-yellow-300 font-medium">Mes tickets</a>
                    <a href="profile.php" class="hover:text-yellow-300">Profil</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../auth/logout.php" class="text-white hover:text-yellow-300">
                        Déconnexion
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Tickets  -->
    <div class="max-w-6xl mx-auto px-4 py-8">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-blue-900">Mes Tickets</h1>
                <p class="text-gray-600">Vos participations à la tombola de l'Apothéose</p>
            </div>
            <a href="../payment/" class="bg-blue-900 hover:bg-blue-800 text-white font-bold py-2 px-6 rounded-full transition duration-300">
                <i class="fas fa-plus mr-2"></i> Acheter un ticket
            </a>
        </div>
        
        <!-- Tickets  -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold">Vos tickets (<span id="totalTickets"><?php echo $total_tickets; ?></span>)</h2>
            </div>
            
            <div class="divide-y divide-gray-200" id="ticketsList">
                <?php if ($total_tickets === 0): ?>
                <div class="p-6 text-center text-gray-500" id="noTicketsMessage">
                    Vous n'avez pas encore de tickets. <a href="../payment/" class="text-yellow-500 hover:text-yellow-600">Achetez-en un maintenant</a>.
                </div>
                <?php else: 
                    foreach ($tickets as $ticket): ?>
                <div class="p-6 flex items-center justify-between hover:bg-gray-50 transition-colors">
                    <div class="flex-1">
                        <div class="flex items-center space-x-4">
                            <div class="bg-blue-100 rounded-full p-3">
                                <i class="fas fa-ticket-alt text-blue-500"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Ticket #<?php echo htmlspecialchars($ticket['ticket_code']); ?></h3>
                                <p class="text-sm text-gray-500">Acheté le <?php echo date('d/m/Y à H:i', strtotime($ticket['purchase_date'])); ?></p>
                            </div>
                        </div>
                        <?php if ($ticket['is_winner'] && $ticket['prize_id']): ?>
                        <div class="mt-4 bg-green-50 text-green-700 p-4 rounded-lg">
                            <h4 class="font-semibold">🎉 Félicitations ! Vous avez gagné :</h4>
                            <div class="flex items-center mt-2">
                                <?php if ($ticket['prize_image']): ?>
                                <img src="<?php echo htmlspecialchars($ticket['prize_image']); ?>" alt="<?php echo htmlspecialchars($ticket['prize_name']); ?>" class="w-16 h-16 object-cover rounded-lg mr-4">
                                <?php endif; ?>
                                <div>
                                    <p class="font-medium"><?php echo htmlspecialchars($ticket['prize_name']); ?></p>
                                    <?php if ($ticket['prize_description']): ?>
                                    <p class="text-sm text-green-600"><?php echo htmlspecialchars($ticket['prize_description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php elseif ($ticket['is_winner'] === 0): ?>
                        <p class="mt-2 text-sm text-gray-500">En attente du tirage</p>
                        <?php endif; ?>
                    </div>
                    <div class="ml-4 flex items-center space-x-4">
                        <button class="show-qr text-gray-500 hover:text-gray-700" data-ticket="<?php echo htmlspecialchars($ticket['ticket_code']); ?>">
                            <i class="fas fa-qrcode text-xl"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach;
                endif; ?>
            </div>
        </div>
    </div>

    <!-- QR Code -->
    <div id="qrModal" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-6 max-w-sm w-full">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Votre QR Code</h3>
                <button id="closeQrModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-center mb-4">
                <p class="text-sm text-gray-600 mb-2">Ticket #<span id="qrTicketId">0000</span></p>
                <div class="flex justify-center">
                    <img id="qrCodeImage" src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=EXAMPLE" alt="QR Code" class="border border-gray-200 p-2">
                </div>
                <p class="text-sm text-gray-600 mt-4">Présentez ce code lors du tirage</p>
            </div>
            <div class="text-center">
                <button id="downloadQrBtn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-full text-sm transition duration-300">
                    <i class="fas fa-download mr-2"></i> Télécharger
                </button>
            </div>
        </div>
    </div>

    <script src="../assets/js/tickets.js"></script>
</body>
</html>