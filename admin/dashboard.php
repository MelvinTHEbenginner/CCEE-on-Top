<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../init.php';

// Vérifier les droits d'administration
requireAdmin();

// Récupérer les statistiques
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_transactions' => $pdo->query("SELECT COUNT(*) FROM transactions")->fetchColumn(),
    'pending_transactions' => $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'en_attente'")->fetchColumn(),
    'total_tickets' => $pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn()
];

// Dernières transactions
$stmt = $pdo->query("
    SELECT t.*, u.fullname, u.email 
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    ORDER BY t.transaction_date DESC
    LIMIT 5
");
$recent_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord administrateur - CCEE Tombola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-blue-900 text-white w-64 min-h-screen p-4">
            <div class="p-4">
                <h1 class="text-2xl font-bold">Admin CCEE</h1>
                <p class="text-blue-200">Tableau de bord</p>
            </div>
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-white bg-blue-800 rounded-lg mb-2">
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
                <h1 class="text-2xl font-bold text-gray-800">Tableau de bord</h1>
                <div class="text-sm text-gray-500">
                    Connecté en tant que <span class="font-medium text-blue-600"><?= htmlspecialchars($_SESSION['user_fullname']) ?></span>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Utilisateurs</p>
                            <p class="text-2xl font-semibold"><?= $stats['total_users'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-exchange-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Transactions</p>
                            <p class="text-2xl font-semibold"><?= $stats['total_transactions'] ?></p>
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
                            <p class="text-2xl font-semibold"><?= $stats['pending_transactions'] ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                            <i class="fas fa-ticket-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Tickets vendus</p>
                            <p class="text-2xl font-semibold"><?= $stats['total_tickets'] ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dernières transactions -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Dernières transactions</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Utilisateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Montant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_transactions as $transaction): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">#<?= $transaction['id'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($transaction['fullname']) ?></div>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($transaction['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= number_format($transaction['amount'], 0, ',', ' ') ?> FCFA
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
                                    <?= (new DateTime($transaction['transaction_date']))->format('d/m/Y H:i') ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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
                                Affichage des <span class="font-medium">5</span> dernières transactions
                            </p>
                        </div>
                        <div>
                            <a href="transactions.php" class="text-sm font-medium text-blue-600 hover:text-blue-500">
                                Voir toutes les transactions <span aria-hidden="true">&rarr;</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
