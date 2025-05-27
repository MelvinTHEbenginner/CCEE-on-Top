<?php
// Inclure le fichier d'initialisation
require_once __DIR__ . '/../init.php';

// Vérifier les droits d'administration
requireAdmin();

// Récupérer les paramètres de filtrage
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$user_id = $_GET['user_id'] ?? '';

// Construire la requête avec les filtres
$query = "
    SELECT 
        t.id as ticket_id,
        t.ticket_code,
        t.is_used,
        t.used_at,
        t.created_at,
        u.fullname as user_name,
        u.email as user_email,
        u.phone as user_phone,
        tr.id as transaction_id,
        tr.amount,
        tr.status as transaction_status,
        tr.payment_method,
        tr.transaction_date
    FROM tickets t
    JOIN transactions tr ON t.transaction_id = tr.id
    JOIN users u ON tr.user_id = u.id
    WHERE 1=1
";

$params = [];

if (!empty($status)) {
    $query .= " AND tr.status = ?";
    $params[] = $status;
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

$query .= " ORDER BY t.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Définir les en-têtes pour le téléchargement du fichier
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=tickets_export_' . date('Y-m-d_His') . '.csv');

// Créer un pointeur de fichier de sortie
$output = fopen('php://output', 'w');

// Ajouter l'en-tête BOM pour Excel
fputs($output, "\xEF\xBB\xBF");

// En-têtes du CSV
$headers = [
    'ID Ticket',
    'Code',
    'Statut',
    'Date d\'utilisation',
    'Date de création',
    'Nom du participant',
    'Email',
    'Téléphone',
    'ID Transaction',
    'Montant (FCFA)',
    'Statut Transaction',
    'Méthode de paiement',
    'Date de la transaction'
];

// Écrire l'en-tête
fputcsv($output, $headers, ';');

// Écrire les données
foreach ($tickets as $ticket) {
    $row = [
        $ticket['ticket_id'],
        $ticket['ticket_code'],
        $ticket['is_used'] ? 'Utilisé' : 'Non utilisé',
        $ticket['used_at'] ? (new DateTime($ticket['used_at']))->format('d/m/Y H:i') : '',
        (new DateTime($ticket['created_at']))->format('d/m/Y H:i'),
        $ticket['user_name'],
        $ticket['user_email'],
        $ticket['user_phone'],
        $ticket['transaction_id'],
        number_format($ticket['amount'], 0, ',', ' '),
        $this->getStatusText($ticket['transaction_status']),
        strtoupper($ticket['payment_method']),
        (new DateTime($ticket['transaction_date']))->format('d/m/Y H:i')
    ];
    
    fputcsv($output, $row, ';');
}

// Fermer le pointeur de fichier
fclose($output);

exit;

/**
 * Convertit le statut de la transaction en texte lisible
 */
function getStatusText($status) {
    $statuses = [
        'complete' => 'Terminée',
        'en_attente' => 'En attente',
        'rejetée' => 'Rejetée'
    ];
    
    return $statuses[$status] ?? $status;
}
