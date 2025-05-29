<?php
require_once __DIR__ . '/../init.php';
requireAdmin();

// Récupération des tickets
try {
    $stmt = $pdo->query("
        SELECT t.*, u.fullname, tr.status as transaction_status, tr.payment_method
        FROM tickets t
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN transactions tr ON t.id_transaction = tr.id
        ORDER BY t.id DESC
    ");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_flash_message('error', 'Erreur lors de la récupération des tickets : ' . $e->getMessage());
    $tickets = [];
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'validate':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("UPDATE tickets SET is_validated = 1 WHERE id = ?");
                    $stmt->execute([$id]);
                    set_flash_message('success', 'Ticket validé avec succès !');
                } catch (PDOException $e) {
                    set_flash_message('error', 'Erreur lors de la validation du ticket : ' . $e->getMessage());
                }
                break;
                
            case 'invalidate':
                $id = (int)$_POST['id'];
                try {
                    $stmt = $pdo->prepare("UPDATE tickets SET is_validated = 0 WHERE id = ?");
                    $stmt->execute([$id]);
                    set_flash_message('success', 'Ticket invalidé avec succès !');
                } catch (PDOException $e) {
                    set_flash_message('error', 'Erreur lors de l\'invalidation du ticket : ' . $e->getMessage());
                }
                break;
        }
        redirect('/dashboard/tickets.php');
    }
}

// Affichage de la page
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1>Gestion des Tickets</h1>
    
    <?php display_flash_messages(); ?>
    
    <!-- Liste des tickets -->
    <div class="card">
        <div class="card-header">
            <h2 class="h5 mb-0">Liste des tickets</h2>
        </div>
        <div class="card-body">
            <?php if (empty($tickets)): ?>
                <p class="text-muted">Aucun ticket n'a été créé pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Utilisateur</th>
                                <th>Date d'achat</th>
                                <th>Statut Transaction</th>
                                <th>Méthode Paiement</th>
                                <th>Validation</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ticket['ticket_code']); ?></td>
                                    <td>
                                        <?php if ($ticket['user_id']): ?>
                                            <?php echo htmlspecialchars($ticket['fullname']); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non attribué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo format_date($ticket['purchase_date'], true); ?></td>
                                    <td>
                                        <?php if ($ticket['transaction_status']): ?>
                                            <span class="badge bg-<?php echo $ticket['transaction_status'] === 'complete' ? 'success' : 
                                                                          ($ticket['transaction_status'] === 'en_attente' ? 'warning' : 'danger'); ?>">
                                                <?php echo htmlspecialchars($ticket['transaction_status']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ticket['payment_method']): ?>
                                            <?php echo htmlspecialchars(ucfirst($ticket['payment_method'])); ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($ticket['is_validated']): ?>
                                            <span class="badge bg-success">Validé</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Non validé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!$ticket['is_validated']): ?>
                                            <form action="" method="post" class="d-inline">
                                                <input type="hidden" name="action" value="validate">
                                                <input type="hidden" name="id" value="<?php echo $ticket['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i> Valider
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form action="" method="post" class="d-inline">
                                                <input type="hidden" name="action" value="invalidate">
                                                <input type="hidden" name="id" value="<?php echo $ticket['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning">
                                                    <i class="fas fa-times"></i> Invalider
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="/dashboard/ticket_download.php?id=<?php echo $ticket['id']; ?>" 
                                           class="btn btn-sm btn-info">
                                            <i class="fas fa-download"></i> QR Code
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 