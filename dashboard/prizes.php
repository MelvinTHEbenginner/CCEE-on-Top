<?php
require_once __DIR__ . '/../init.php';
requireAdmin();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Validation des données
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $quantity = (int)$_POST['quantity'];
                
                // Gestion de l'upload d'image
                $image_url = null;
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = __DIR__ . '/../uploads/prizes/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = uniqid('prize_') . '.' . $file_extension;
                        $filepath = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                            $image_url = 'uploads/prizes/' . $filename;
                        }
                    }
                }
                
                // Insertion en base de données
                try {
                    $stmt = $pdo->prepare("INSERT INTO prizes (name, description, quantity, image_url) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $quantity, $image_url]);
                    set_flash_message('success', 'Prix ajouté avec succès !');
                } catch (PDOException $e) {
                    set_flash_message('error', 'Erreur lors de l\'ajout du prix : ' . $e->getMessage());
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $name = sanitize($_POST['name']);
                $description = sanitize($_POST['description']);
                $quantity = (int)$_POST['quantity'];
                
                try {
                    $stmt = $pdo->prepare("UPDATE prizes SET name = ?, description = ?, quantity = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $quantity, $id]);
                    
                    // Gestion de l'upload d'image si une nouvelle image est fournie
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = __DIR__ . '/../uploads/prizes/';
                        $file_extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                        
                        if (in_array($file_extension, $allowed_extensions)) {
                            $filename = uniqid('prize_') . '.' . $file_extension;
                            $filepath = $upload_dir . $filename;
                            
                            if (move_uploaded_file($_FILES['image']['tmp_name'], $filepath)) {
                                // Supprimer l'ancienne image si elle existe
                                $stmt = $pdo->prepare("SELECT image_url FROM prizes WHERE id = ?");
                                $stmt->execute([$id]);
                                $old_image = $stmt->fetchColumn();
                                
                                if ($old_image && file_exists(__DIR__ . '/../' . $old_image)) {
                                    unlink(__DIR__ . '/../' . $old_image);
                                }
                                
                                // Mettre à jour l'URL de l'image
                                $stmt = $pdo->prepare("UPDATE prizes SET image_url = ? WHERE id = ?");
                                $stmt->execute(['uploads/prizes/' . $filename, $id]);
                            }
                        }
                    }
                    
                    set_flash_message('success', 'Prix mis à jour avec succès !');
                } catch (PDOException $e) {
                    set_flash_message('error', 'Erreur lors de la mise à jour du prix : ' . $e->getMessage());
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                try {
                    // Supprimer l'image associée
                    $stmt = $pdo->prepare("SELECT image_url FROM prizes WHERE id = ?");
                    $stmt->execute([$id]);
                    $image_url = $stmt->fetchColumn();
                    
                    if ($image_url && file_exists(__DIR__ . '/../' . $image_url)) {
                        unlink(__DIR__ . '/../' . $image_url);
                    }
                    
                    // Supprimer le prix
                    $stmt = $pdo->prepare("DELETE FROM prizes WHERE id = ?");
                    $stmt->execute([$id]);
                    set_flash_message('success', 'Prix supprimé avec succès !');
                } catch (PDOException $e) {
                    set_flash_message('error', 'Erreur lors de la suppression du prix : ' . $e->getMessage());
                }
                break;
        }
    }
    redirect('/dashboard/prizes.php');
}

// Récupération des prix
try {
    $stmt = $pdo->query("SELECT * FROM prizes ORDER BY id DESC");
    $prizes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    set_flash_message('error', 'Erreur lors de la récupération des prix : ' . $e->getMessage());
    $prizes = [];
}

// Affichage de la page
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <h1>Gestion des Prix</h1>
    
    <?php display_flash_messages(); ?>
    
    <!-- Formulaire d'ajout -->
    <div class="card mb-4">
        <div class="card-header">
            <h2 class="h5 mb-0">Ajouter un prix</h2>
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label for="name" class="form-label">Nom du prix</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="quantity" class="form-label">Quantité disponible</label>
                    <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                </div>
                
                <div class="mb-3">
                    <label for="image" class="form-label">Image</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/*">
                </div>
                
                <button type="submit" class="btn btn-primary">Ajouter</button>
            </form>
        </div>
    </div>
    
    <!-- Liste des prix -->
    <div class="card">
        <div class="card-header">
            <h2 class="h5 mb-0">Liste des prix</h2>
        </div>
        <div class="card-body">
            <?php if (empty($prizes)): ?>
                <p class="text-muted">Aucun prix n'a été ajouté pour le moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Quantité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prizes as $prize): ?>
                                <tr>
                                    <td>
                                        <?php if ($prize['image_url']): ?>
                                            <img src="/<?php echo htmlspecialchars($prize['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($prize['name']); ?>" 
                                                 style="max-width: 50px;">
                                        <?php else: ?>
                                            <span class="text-muted">Pas d'image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($prize['name']); ?></td>
                                    <td><?php echo htmlspecialchars($prize['description']); ?></td>
                                    <td><?php echo htmlspecialchars($prize['quantity']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editModal<?php echo $prize['id']; ?>">
                                            Modifier
                                        </button>
                                        <form action="" method="post" class="d-inline" 
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce prix ?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $prize['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Supprimer</button>
                                        </form>
                                    </td>
                                </tr>
                                
                                <!-- Modal de modification -->
                                <div class="modal fade" id="editModal<?php echo $prize['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Modifier le prix</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form action="" method="post" enctype="multipart/form-data">
                                                    <input type="hidden" name="action" value="edit">
                                                    <input type="hidden" name="id" value="<?php echo $prize['id']; ?>">
                                                    
                                                    <div class="mb-3">
                                                        <label for="edit_name<?php echo $prize['id']; ?>" class="form-label">Nom du prix</label>
                                                        <input type="text" class="form-control" 
                                                               id="edit_name<?php echo $prize['id']; ?>" 
                                                               name="name" 
                                                               value="<?php echo htmlspecialchars($prize['name']); ?>" 
                                                               required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="edit_description<?php echo $prize['id']; ?>" class="form-label">Description</label>
                                                        <textarea class="form-control" 
                                                                  id="edit_description<?php echo $prize['id']; ?>" 
                                                                  name="description" 
                                                                  rows="3"><?php echo htmlspecialchars($prize['description']); ?></textarea>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="edit_quantity<?php echo $prize['id']; ?>" class="form-label">Quantité disponible</label>
                                                        <input type="number" class="form-control" 
                                                               id="edit_quantity<?php echo $prize['id']; ?>" 
                                                               name="quantity" 
                                                               value="<?php echo htmlspecialchars($prize['quantity']); ?>" 
                                                               min="1" 
                                                               required>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label for="edit_image<?php echo $prize['id']; ?>" class="form-label">Nouvelle image</label>
                                                        <input type="file" class="form-control" 
                                                               id="edit_image<?php echo $prize['id']; ?>" 
                                                               name="image" 
                                                               accept="image/*">
                                                        <?php if ($prize['image_url']): ?>
                                                            <small class="form-text text-muted">
                                                                Laissez vide pour conserver l'image actuelle
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?> 