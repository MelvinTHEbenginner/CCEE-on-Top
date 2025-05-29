<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/ticket_functions.php';

try {
    // Vérifier les droits d'administration
    requireAdmin();

    // Filtres et recherche
    $status = $_GET['status'] ?? '';
    $search = $_GET['search'] ?? '';
    $user_id = $_GET['user_id'] ?? '';

    $query = "
        SELECT 
            t.*, 
            u.fullname, 
            u.email, 
            u.phone,
            tr.amount, 
            tr.status as transaction_status, 
            tr.payment_method, 
            tr.created_at as transaction_date,
            p.name as prize_name,
            p.description as prize_description
        FROM tickets t
        LEFT JOIN transactions tr ON t.id_transaction = tr.id
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN prizes p ON t.prize_id = p.id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($status)) {
        if ($status === 'disponible') {
            $query .= " AND t.id_transaction IS NULL";
        } else {
            $query .= " AND tr.status = ?";
            $params[] = $status;
        }
    }

    if (!empty($search)) {
        $query .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR t.ticket_code = ? OR tr.id = ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $search, $search]);
    }

    if (!empty($user_id)) {
        $query .= " AND u.id = ?";
        $params[] = $user_id;
    }

    $query .= " ORDER BY t.date_creation DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    // Statistiques
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
        'validated' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE is_validated = 1")->fetchColumn(),
        'winners' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE is_winner = 1")->fetchColumn(),
        'pending' => $pdo->query("
            SELECT COUNT(*) 
            FROM tickets t
            JOIN transactions tr ON t.id_transaction = tr.id 
            WHERE tr.status = 'en_attente'
        ")->fetchColumn(),
    ];
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des tickets - Admin CCEE Tombola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-blue-900 text-white w-64 min-h-screen p-4">
            <div class="p-4">
                <h1 class="text-2xl font-bold">Admin CCEE</h1>
                <p class="text-blue-200">Gestion des tickets</p>
            </div>
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-800 rounded-lg mb-2">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Tableau de bord
                </a>
                <a href="users.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-800 rounded-lg mb-2">
                    <i class="fas fa-users mr-3"></i>
                    Utilisateurs
                </a>
                <a href="transactions.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-800 rounded-lg mb-2">
                    <i class="fas fa-exchange-alt mr-3"></i>
                    Transactions
                </a>
                <a href="tickets.php" class="flex items-center px-4 py-3 text-white bg-blue-800 rounded-lg mb-2">
                    <i class="fas fa-ticket-alt mr-3"></i>
                    Tickets
                </a>
                <a href="../auth/logout.php" class="flex items-center px-4 py-3 text-red-400 hover:bg-blue-800 rounded-lg mt-8">
                    <i class="fas fa-sign-out-alt mr-3"></i>
                    Déconnexion
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Gestion des tickets</h1>
                <div class="text-sm text-gray-500">
                    Connecté en tant que <span class="font-medium text-blue-600"><?= htmlspecialchars($_SESSION['user_fullname']) ?></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-ticket-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total des tickets</p>
                            <p class="text-2xl font-semibold"><?= $stats['total'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-trophy text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Validés</p>
                            <p class="text-2xl font-semibold"><?= $stats['validated'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">En attente de paiement</p>
                            <p class="text-2xl font-semibold"><?= $stats['pending'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <form method="get" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut de la transaction</label>
                            <select name="status" class="w-full border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Tous les statuts</option>
                                <option value="en_attente" <?= $status === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                                <option value="complete" <?= $status === 'complete' ? 'selected' : '' ?>>Complétées</option>
                                <option value="rejetée" <?= $status === 'rejetée' ? 'selected' : '' ?>>Rejetées</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Rechercher</label>
                            <div class="relative">
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                                       class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                                       placeholder="Code ticket, nom, email ou ID transaction">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                Appliquer
                            </button>
                        </div>
                    </div>
                    <?php if (!empty($search) || !empty($status)): ?>
                    <div class="flex justify-end">
                        <a href="tickets.php" class="text-sm text-blue-600 hover:text-blue-800">
                            Réinitialiser les filtres
                        </a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900">Liste des tickets</h2>
                        <p class="mt-1 text-sm text-gray-500">Gérez les tickets des participants</p>
                    </div>
                    <div>
                        <button onclick="exportToCSV()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <i class="fas fa-download mr-2"></i>
                            Exporter en CSV
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Détenteur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lot</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-mono font-medium text-gray-900">
                                        <?= htmlspecialchars($ticket['ticket_code']) ?>
                                        <?php if ($ticket['is_winner']): ?>
                                            <span class="ml-1 text-xs font-normal text-purple-600">
                                                <i class="fas fa-trophy"></i> Gagnant
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ticket['fullname']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($ticket['email']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($ticket['phone']) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">#<?= $ticket['id_transaction'] ?></div>
                                    <div class="text-sm text-gray-500"><?= number_format($ticket['amount'], 0, ',', ' ') ?> FCFA</div>
                                    <div class="text-xs text-gray-500">
                                        <?= strtoupper($ticket['payment_method']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if (!empty($ticket['prize_name'])): ?>
                                        <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ticket['prize_name']) ?></div>
                                        <div class="text-xs text-gray-500 truncate" style="max-width: 150px;" title="<?= htmlspecialchars($ticket['prize_description']) ?>">
                                            <?= htmlspecialchars(mb_substr($ticket['prize_description'], 0, 30)) ?><?= mb_strlen($ticket['prize_description']) > 30 ? '...' : '' ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            Pas de lot
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    // Statut du ticket
                                    if ($ticket['is_validated']) {
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $statusText = 'Validé';
                                    } elseif ($ticket['transaction_status'] === 'complete') {
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        $statusText = 'Payé';
                                    } elseif ($ticket['transaction_status'] === 'en_attente') {
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $statusText = 'En attente';
                                    } else {
                                        $statusClass = 'bg-red-100 text-red-800';
                                        $statusText = 'Invalide';
                                    }
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClass ?>">
                                        <?= $statusText ?>
                                    </span>
                                    <?php if ($ticket['is_used']): ?>
                                        <div class="text-xs text-gray-500 mt-1">
                                            Utilisé le <?= (new DateTime($ticket['used_at']))->format('d/m/Y H:i') ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= (new DateTime($ticket['created_at']))->format('d/m/Y H:i') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($ticket['transaction_status'] === 'complete' && !$ticket['is_validated']): ?>
                                        <button onclick="validateTicket('<?= $ticket['id'] ?>', '<?= htmlspecialchars(addslashes($ticket['ticket_code'])) ?>')" 
                                                class="text-green-600 hover:text-green-900 mr-3" 
                                                title="Valider le ticket">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <a href="check_ticket.php?ticket_code=<?= urlencode($ticket['ticket_code']) ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Vérifier le ticket">
                                        <i class="fas fa-search"></i>
                                    </a>
                                    <?php if ($ticket['is_winner'] && !empty($ticket['prize_name'])): ?>
                                        <span class="text-purple-600" title="Lot gagnant : <?= htmlspecialchars($ticket['prize_name']) ?>">
                                            <i class="fas fa-trophy"></i>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucun ticket trouvé avec les critères sélectionnés.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-200">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Précédent
                        </a>
                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Suivant
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Affichage de <span class="font-medium">1</span> à <span class="font-medium"><?= count($tickets) ?></span> sur <span class="font-medium"><?= count($tickets) ?></span> résultats
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de validation de ticket -->
    <div id="validateModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Valider le ticket</h3>
                <p class="text-gray-600 mb-6">Êtes-vous sûr de vouloir valider l'utilisation du ticket <span id="ticketCode" class="font-mono font-bold"></span> ?</p>
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeValidateModal()" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Annuler
                    </button>
                    <button type="button" onclick="confirmValidateTicket()" class="inline-flex justify-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Valider
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentTicketId = null;

    function validateTicket(ticketId, ticketCode) {
        currentTicketId = ticketId;
        document.getElementById('ticketCode').textContent = ticketCode;
        document.getElementById('validateModal').classList.remove('hidden');
    }

    function closeValidateModal() {
        document.getElementById('validateModal').classList.add('hidden');
        currentTicketId = null;
    }

    function confirmValidateTicket() {
        if (!currentTicketId) return;
        
        fetch('validate_ticket.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ticket_id=${currentTicketId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                alert('Erreur lors de la validation du ticket: ' + (data.message || 'Erreur inconnue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors de la validation du ticket');
        });
    }

    function exportToCSV() {
        // Récupérer les paramètres de filtrage actuels
        const params = new URLSearchParams(window.location.search);
        
        // Ouvrir une nouvelle fenêtre avec l'URL d'exportation
        window.open(`export_tickets.php?${params.toString()}`, '_blank');
    }
    </script>
</body>
</html>
