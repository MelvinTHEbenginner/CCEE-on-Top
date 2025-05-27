<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../init.php';

// Vérifier les droits d'administration
requireAdmin();

// Gestion des actions
if (isset($_POST['action'])) {
    $userId = $_POST['user_id'] ?? null;
    
    if ($userId) {
        try {
            if ($_POST['action'] === 'toggle_admin') {
                $stmt = $pdo->prepare("UPDATE users SET is_admin = NOT is_admin WHERE id = ?");
                $stmt->execute([$userId]);
                $success = "Droits d'administrateur mis à jour avec succès.";
            } elseif ($_POST['action'] === 'delete' && $userId != $_SESSION['user_id']) {
                // Empêche l'auto-suppression
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $success = "Utilisateur supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $error = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}

// Récupérer la liste des utilisateurs
$search = $_GET['search'] ?? '';
$query = "SELECT id, fullname, email, phone, is_admin, created_at FROM users";
$params = [];

if (!empty($search)) {
    $query .= " WHERE fullname LIKE ? OR email LIKE ?";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm];
}

$query .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - Admin CCEE Tombola</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="bg-blue-900 text-white w-64 min-h-screen p-4">
            <div class="p-4">
                <h1 class="text-2xl font-bold">Admin CCEE</h1>
                <p class="text-blue-200">Gestion des utilisateurs</p>
            </div>
            <nav class="mt-8">
                <a href="dashboard.php" class="flex items-center px-4 py-3 text-blue-200 hover:bg-blue-800 rounded-lg mb-2">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Tableau de bord
                </a>
                <a href="users.php" class="flex items-center px-4 py-3 text-white bg-blue-800 rounded-lg mb-2">
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
                <h1 class="text-2xl font-bold text-gray-800">Gestion des utilisateurs</h1>
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

            <!-- Search and Filter -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <form method="get" class="flex items-center">
                    <div class="relative flex-1">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
                               class="w-full pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                               placeholder="Rechercher un utilisateur...">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <button type="submit" class="ml-3 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Rechercher
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="users.php" class="ml-2 text-gray-600 hover:text-gray-800">
                            Réinitialiser
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Users Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Liste des utilisateurs</h2>
                    <p class="mt-1 text-sm text-gray-500">Gérez les comptes utilisateurs et les droits d'administration</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Téléphone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inscrit le</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($user['fullname']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($user['email']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($user['phone'] ?? 'Non renseigné') ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= (new DateTime($user['created_at']))->format('d/m/Y') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <form method="post" class="inline-block">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <input type="hidden" name="action" value="toggle_admin">
                                        <button type="submit" class="<?= $user['is_admin'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' ?> text-xs font-semibold px-2.5 py-0.5 rounded">
                                            <?= $user['is_admin'] ? 'Oui' : 'Non' ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <form method="post" class="inline-block" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.');">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-gray-400">Vous</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
