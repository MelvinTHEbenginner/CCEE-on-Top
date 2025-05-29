<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/auth_functions.php';
require_once __DIR__ . '/../includes/test_functions.php';

// Démarrer la session si nécessaire
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Vérifier les droits d'administration
    requireAdmin();

    // Initialisation des variables
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';

    // Initialisation sécurisée des statistiques
    $defaultStats = [
        'total' => 0,
        'pending' => 0,
        'completed' => 0,
        'rejected' => 0
    ];

    // Récupérer les statistiques des transactions
    $stats = $defaultStats;

    try {
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'en_attente' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'complete' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'rejetée' THEN 1 ELSE 0 END) as rejected
        FROM transactions";

        $stmt = $pdo->query($query);
        if ($statsData = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stats = array_merge($defaultStats, array_map('intval', $statsData));
        }
    } catch (PDOException $e) {
        error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
    }

    // Gestion des actions
    if (isset($_POST['action'])) {
        $transactionId = $_POST['transaction_id'] ?? null;
        
        if (!$transactionId) {
            throw new Exception("ID de transaction invalide.");
        }

        // Vérifier si la transaction existe et est en attente
        $stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'en_attente'");
        $stmt->execute([$transactionId]);
        $transaction = $stmt->fetch();

        if (!$transaction) {
            throw new Exception("Transaction non trouvée ou déjà traitée.");
        }

        if ($_POST['action'] === 'approve') {
            // Utiliser la fonction confirmPayment pour gérer tout le processus
            if (confirmPayment($transactionId)) {
                $_SESSION['success'] = "Transaction approuvée avec succès. Les tickets ont été assignés.";
            } else {
                throw new Exception("Erreur lors de la confirmation du paiement.");
            }
        } elseif ($_POST['action'] === 'reject') {
            // Mettre à jour le statut de la transaction comme rejetée
            $stmt = $pdo->prepare("
                UPDATE transactions 
                SET status = 'rejetée',
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$transactionId]);
            $_SESSION['success'] = "Transaction rejetée avec succès.";
        } else {
            throw new Exception("Action non supportée.");
        }

        // Redirection après traitement
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
        exit;
    }

    // Filtres et recherche
    $status = $_GET['status'] ?? '';
    $userId = $_GET['user_id'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';

    $query = "
        SELECT t.*, u.fullname, u.email, u.phone,
               COUNT(DISTINCT tk.id) as ticket_count
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN tickets tk ON tk.id_transaction = t.id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($status)) {
        $query .= " AND t.status = ?";
        $params[] = $status;
    }

    if (!empty($userId)) {
        $query .= " AND t.user_id = ?";
        $params[] = $userId;
    }

    if (!empty($startDate)) {
        $query .= " AND t.created_at >= ?";
        $params[] = $startDate . ' 00:00:00';
    }

    if (!empty($endDate)) {
        $query .= " AND t.created_at <= ?";
        $params[] = $endDate . ' 23:59:59';
    }

    $query .= " 
        GROUP BY t.id, u.fullname, u.email, u.phone
        ORDER BY t.created_at DESC
    ";

    // Récupérer les transactions
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Transactions error: " . $e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des transactions - Admin CCEE Tombola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-blue-900 text-white w-64 min-h-screen p-4">
            <div class="p-4">
                <h1 class="text-2xl font-bold">Admin CCEE</h1>
                <p class="text-blue-200">Gestion des transactions</p>
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
                <a href="transactions.php" class="flex items-center px-4 py-3 text-white bg-blue-800 rounded-lg mb-2">
                    <i class="fas fa-exchange-alt mr-3"></i>
                    Transactions
                </a>
                <a href="tickets.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-800 rounded-lg mb-2">
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
                <h1 class="text-2xl font-bold text-gray-800">Gestion des transactions</h1>
                <div class="text-sm text-gray-500">
                    Connecté en tant que <span class="font-medium text-blue-600"><?= htmlspecialchars($_SESSION['user_fullname']) ?></span>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($success) ?></span>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline"><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-exchange-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total</p>
                            <p class="text-2xl font-semibold"><?= isset($stats['total']) ? $stats['total'] : 0 ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">En attente</p>
                            <p class="text-2xl font-semibold"><?= isset($stats['pending']) ? $stats['pending'] : 0 ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Complétées</p>
                            <p class="text-2xl font-semibold"><?= isset($stats['completed']) ? $stats['completed'] : 0 ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 text-red-600">
                            <i class="fas fa-times-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Rejetées</p>
                            <p class="text-2xl font-semibold"><?= isset($stats['rejected']) ? $stats['rejected'] : 0 ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <form method="get" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
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
                                       placeholder="Nom, email ou ID de transaction">
                                <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <a href="transactions.php" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Réinitialiser
                        </a>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Appliquer les filtres
                        </button>
                    </div>
                </form>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Liste des transactions</h2>
                    <p class="mt-1 text-sm text-gray-500">Gérez les transactions des utilisateurs</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Détails</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    #<?= $transaction['id'] ?>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($transaction['fullname']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($transaction['email'] ?? '') ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($transaction['phone'] ?? '') ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900"><?= number_format($transaction['amount'], 0, ',', ' ') ?> FCFA</div>
                                    <div class="text-sm text-gray-500"><?= $transaction['ticket_count'] ?> ticket(s)</div>
                                    <div class="text-xs text-gray-500">
                                        <?= !empty($transaction['payment_method']) ? strtoupper($transaction['payment_method']) : 'N/A' ?>
                                        <?php if (!empty($transaction['payment_reference'])): ?>
                                            <br>Ref: <?= htmlspecialchars($transaction['payment_reference']) ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusClasses = [
                                        'complete' => 'bg-green-100 text-green-800',
                                        'en_attente' => 'bg-yellow-100 text-yellow-800',
                                        'rejetée' => 'bg-red-100 text-red-800'
                                    ][$transaction['status']] ?? 'bg-gray-100 text-gray-800';
                                    
                                    $statusText = [
                                        'complete' => 'Terminée',
                                        'en_attente' => 'En attente',
                                        'rejetée' => 'Rejetée'
                                    ][$transaction['status']] ?? $transaction['status'];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $statusClasses ?>">
                                        <?= $statusText ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                    $date = !empty($transaction['transaction_date']) ? $transaction['transaction_date'] : 'now';
                                    echo (new DateTime($date))->format('d/m/Y H:i');
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($transaction['status'] === 'en_attente'): ?>
                                        <form method="post" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver cette transaction ?');">
                                            <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="text-green-600 hover:text-green-900 mr-3" title="Approuver">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="post" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette transaction ? Les tickets associés seront supprimés.');">
                                            <input type="hidden" name="transaction_id" value="<?= $transaction['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Rejeter">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400">Aucune action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($transactions)): ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                    Aucune transaction trouvée avec les critères sélectionnés.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
