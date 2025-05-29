<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../init.php';

// Vérifier les droits d'administration
requireAdmin();

// Récupérer les transactions en attente
$stmt = $pdo->query("
    SELECT t.*, u.fullname, u.phone, 
           (SELECT COUNT(*) FROM tickets WHERE id_transaction = t.id) as ticket_count
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    WHERE t.status = 'en_attente'
    ORDER BY t.transaction_date DESC
");
$pending_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notifications Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Notifications de Paiement</h1>
        
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Téléphone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Montant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Méthode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tickets</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($pending_transactions as $transaction): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">#<?= $transaction['id'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($transaction['fullname']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($transaction['phone'] ?? 'Non renseigné') ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($transaction['amount'], 0, ',', ' ') ?> FCFA</td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= strtoupper($transaction['payment_method']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $transaction['ticket_count'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($transaction['transaction_date'])) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap flex space-x-2">
                                <button onclick="updateTransaction(<?= $transaction['id'] ?>, 'complete')" 
                                        class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                                    Valider
                                </button>
                                <button onclick="updateTransaction(<?= $transaction['id'] ?>, 'rejetée')" 
                                        class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">
                                    Rejeter
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function updateTransaction(transactionId, action) {
        if (!confirm(`Êtes-vous sûr de vouloir ${action === 'complete' ? 'valider' : 'rejeter'} cette transaction ?`)) {
            return;
        }

        fetch('update_transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `transaction_id=${transactionId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Transaction mise à jour avec succès');
                location.reload();
            } else {
                alert('Erreur: ' + (data.message || 'Une erreur est survenue'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Erreur lors de la mise à jour de la transaction');
        });
    }
    </script>
</body>
</html>
