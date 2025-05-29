<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Vérifier les droits d'administration
if (!isset($_SESSION['user_id']) || !isAdmin($_SESSION['user_id'])) {
    header('Location: /auth/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$error = '';
$success = '';
$ticketInfo = null;
$searchPerformed = false;

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['ticket_code'])) {
        // Recherche d'un ticket
        $searchPerformed = true;
        $ticketCode = trim($_POST['ticket_code']);
        
        try {
            // Récupérer les informations du ticket
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    u.fullname as user_name,
                    u.email as user_email,
                    u.phone as user_phone,
                    tr.amount,
                    tr.payment_method,
                    tr.status as transaction_status,
                    p.name as prize_name,
                    p.description as prize_description,
                    p.image_url as prize_image
                FROM ticket t
                JOIN users u ON t.user_id = u.id
                JOIN transactions tr ON t.id_transaction = tr.id
                LEFT JOIN prizes p ON t.prize_id = p.id
                WHERE t.ticket_code = ?
            ");
            
            $stmt->execute([$ticketCode]);
            $ticketInfo = $stmt->fetch();
            
            if (!$ticketInfo) {
                $error = 'Aucun ticket trouvé avec ce code';
            }
        } catch (Exception $e) {
            $error = 'Une erreur est survenue lors de la recherche du ticket';
            error_log('Erreur lors de la recherche du ticket: ' . $e->getMessage());
        }
    } elseif (isset($_POST['validate_ticket'])) {
        // Validation d'un ticket
        $ticketCode = $_POST['ticket_code'];
        
        try {
            // Vérifier si le ticket existe et n'est pas déjà validé
            $stmt = $pdo->prepare("
                SELECT * FROM ticket 
                WHERE ticket_code = ? AND is_validated = 0
                FOR UPDATE
            ");
            $stmt->execute([$ticketCode]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                throw new Exception('Ticket non trouvé ou déjà validé');
            }
            
            // Mettre à jour le ticket comme validé
            $updateStmt = $pdo->prepare("
                UPDATE ticket 
                SET is_validated = 1, 
                    validated_at = NOW()
                WHERE ticket_code = ? AND is_validated = 0
            ");
            
            $updateStmt->execute([$ticketCode]);
            
            if ($updateStmt->rowCount() === 0) {
                throw new Exception('Échec de la validation du ticket');
            }
            
            $success = 'Le ticket a été validé avec succès';
            
            // Recharger les informations du ticket
            $stmt = $pdo->prepare("
                SELECT 
                    t.*,
                    u.fullname as user_name,
                    u.email as user_email,
                    u.phone as user_phone,
                    tr.amount,
                    tr.payment_method,
                    tr.status as transaction_status,
                    p.name as prize_name,
                    p.description as prize_description,
                    p.image_url as prize_image
                FROM ticket t
                JOIN users u ON t.user_id = u.id
                JOIN transactions tr ON t.id_transaction = tr.id
                LEFT JOIN prizes p ON t.prize_id = p.id
                WHERE t.ticket_code = ?
            ");
            
            $stmt->execute([$ticketCode]);
            $ticketInfo = $stmt->fetch();
            $searchPerformed = true;
            
        } catch (Exception $e) {
            $error = 'Erreur lors de la validation du ticket: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérifier un ticket - Admin CCEE Tombola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Mettre le focus sur le champ de recherche au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('ticket_code').focus();
            
            // Gérer la soumission du formulaire avec Entrée
            const form = document.getElementById('ticketForm');
            form.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.submit();
                }
            });
        });
        
        // Fonction pour copier le code du ticket
        function copyTicketCode(code) {
            navigator.clipboard.writeText(code).then(() => {
                // Afficher un message temporaire
                const copyBtn = document.getElementById('copyBtn');
                const originalText = copyBtn.innerHTML;
                copyBtn.innerHTML = '<i class="fas fa-check mr-1"></i> Copié !';
                setTimeout(() => {
                    copyBtn.innerHTML = originalText;
                }, 2000);
            });
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 overflow-x-hidden overflow-y-auto">
            <div class="p-8">
                <!-- En-tête -->
                <div class="flex justify-between items-center mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Vérifier un ticket</h1>
                        <p class="text-gray-600">Recherchez et validez les tickets des participants</p>
                    </div>
                </div>

                <!-- Formulaire de recherche -->
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <form id="ticketForm" method="POST" class="flex items-center">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                id="ticket_code" 
                                name="ticket_code" 
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                                placeholder="Entrez le code du ticket..."
                                value="<?php echo isset($ticketCode) ? htmlspecialchars($ticketCode) : ''; ?>"
                                autocomplete="off"
                                required
                            >
                        </div>
                        <button 
                            type="submit" 
                            class="ml-4 px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                        >
                            <i class="fas fa-search mr-2"></i> Rechercher
                        </button>
                    </form>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-red-700"><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-green-700"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($searchPerformed && $ticketInfo): ?>
                    <!-- Carte d'information du ticket -->
                    <div class="bg-white rounded-lg shadow-md overflow-hidden mb-8">
                        <div class="p-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-800">
                                        Ticket #<?php echo htmlspecialchars($ticketInfo['ticket_code']); ?>
                                    </h2>
                                    <div class="mt-1 flex items-center">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $ticketInfo['is_validated'] ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo $ticketInfo['is_validated'] ? 'Validé' : 'Non validé'; ?>
                                        </span>
                                        <?php if ($ticketInfo['is_winner']): ?>
                                            <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                Gagnant
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <button 
                                    onclick="copyTicketCode('<?php echo $ticketInfo['ticket_code']; ?>')" 
                                    id="copyBtn"
                                    class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm leading-5 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    <i class="far fa-copy mr-1"></i> Copier
                                </button>
                            </div>

                            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Informations du participant -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Informations du participant</h3>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600"></i>
                                            </div>
                                            <div class="ml-4">
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($ticketInfo['user_name']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($ticketInfo['user_email']); ?></p>
                                                <?php if (!empty($ticketInfo['user_phone'])): ?>
                                                    <p class="text-sm text-gray-500 mt-1">
                                                        <i class="fas fa-phone-alt mr-1"></i> 
                                                        <?php echo htmlspecialchars($ticketInfo['user_phone']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Détails de la transaction -->
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Détails de la transaction</h3>
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <dl class="grid grid-cols-1 gap-4">
                                            <div class="sm:col-span-1">
                                                <dt class="text-sm font-medium text-gray-500">Date d'achat</dt>
                                                <dd class="mt-1 text-sm text-gray-900">
                                                    <?php echo date('d/m/Y H:i', strtotime($ticketInfo['purchase_date'])); ?>
                                                </dd>
                                            </div>
                                            <div class="sm:col-span-1">
                                                <dt class="text-sm font-medium text-gray-500">Méthode de paiement</dt>
                                                <dd class="mt-1 text-sm text-gray-900">
                                                    <?php 
                                                    $paymentMethods = [
                                                        'mobile_money' => 'Mobile Money',
                                                        'wave' => 'Wave',
                                                        'card' => 'Carte bancaire',
                                                        'cash' => 'Espèces'
                                                    ];
                                                    echo $paymentMethods[$ticketInfo['payment_method']] ?? ucfirst($ticketInfo['payment_method']);
                                                    ?>
                                                </dd>
                                            </div>
                                            <div class="sm:col-span-1">
                                                <dt class="text-sm font-medium text-gray-500">Montant</dt>
                                                <dd class="mt-1 text-sm font-medium text-gray-900">
                                                    <?php echo number_format($ticketInfo['amount'], 0, ',', ' '); ?> FCFA
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <?php if ($ticketInfo['is_winner'] && !empty($ticketInfo['prize_name'])): ?>
                                    <!-- Détails du lot gagnant -->
                                    <div class="md:col-span-2">
                                        <h3 class="text-lg font-medium text-gray-900 mb-4">Lot gagnant</h3>
                                        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                                            <div class="flex items-start">
                                                <?php if (!empty($ticketInfo['prize_image'])): ?>
                                                    <div class="flex-shrink-0 h-16 w-16 rounded-md overflow-hidden">
                                                        <img class="h-full w-full object-cover" src="<?php echo htmlspecialchars($ticketInfo['prize_image']); ?>" alt="<?php echo htmlspecialchars($ticketInfo['prize_name']); ?>">
                                                    </div>
                                                <?php else: ?>
                                                    <div class="flex-shrink-0 h-16 w-16 rounded-full bg-yellow-100 flex items-center justify-center">
                                                        <i class="fas fa-gift text-yellow-600 text-2xl"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="ml-4">
                                                    <h4 class="text-lg font-medium text-yellow-800">
                                                        <?php echo htmlspecialchars($ticketInfo['prize_name']); ?>
                                                    </h4>
                                                    <?php if (!empty($ticketInfo['prize_description'])): ?>
                                                        <p class="mt-1 text-sm text-yellow-700">
                                                            <?php echo nl2br(htmlspecialchars($ticketInfo['prize_description'])); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="mt-8 pt-6 border-t border-gray-200 flex justify-end">
                                <?php if (!$ticketInfo['is_validated']): ?>
                                    <form method="POST" class="inline-block">
                                        <input type="hidden" name="ticket_code" value="<?php echo htmlspecialchars($ticketInfo['ticket_code']); ?>">
                                        <button 
                                            type="submit" 
                                            name="validate_ticket" 
                                            value="1"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                        >
                                            <i class="fas fa-check-circle mr-2"></i> Valider ce ticket
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-green-800 bg-green-100">
                                        <i class="fas fa-check-circle mr-2"></i> Ticket validé le <?php echo date('d/m/Y à H:i', strtotime($ticketInfo['validated_at'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
