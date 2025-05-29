<?php
session_start();
require_once __DIR__ . '/../init.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$stmt = $pdo->prepare('SELECT fullname, email FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Traitement du formulaire de mise à jour
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Vérifier si les champs obligatoires sont remplis
    if (empty($fullname) || empty($email)) {
        $message = "Le nom complet et l'email sont obligatoires.";
        $messageType = 'error';
    } else {
        // Mettre à jour le nom et l'email
        $stmt = $pdo->prepare('UPDATE users SET fullname = ?, email = ? WHERE id = ?');
        $stmt->execute([$fullname, $email, $_SESSION['user_id']]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Informations mises à jour avec succès.";
            $messageType = 'success';
            
            // Mettre à jour les informations en session
            $user['fullname'] = $fullname;
            $user['email'] = $email;
        } else {
            $message = "Erreur lors de la mise à jour des informations.";
            $messageType = 'error';
        }

        // Traitement du changement de mot de passe si demandé
        if (!empty($current_password) && !empty($new_password)) {
            // Vérifier le mot de passe actuel
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user_data = $stmt->fetch();
            
            if (password_verify($current_password, $user_data['password_hash'])) {
                if ($new_password === $confirm_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Mettre à jour le mot de passe
                    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        $message = "Mot de passe mis à jour avec succès.";
                        $messageType = 'success';
                    } else {
                        $message = "Erreur lors de la mise à jour du mot de passe.";
                        $messageType = 'error';
                    }
                } else {
                    $message = "Les nouveaux mots de passe ne correspondent pas.";
                    $messageType = 'error';
                }
            } else {
                $message = "Le mot de passe actuel est incorrect.";
                $messageType = 'error';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Tombola CCEE</title>
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
                    <span class="font-bold text-xl">CCEE</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="hover:text-yellow-300">Tableau de bord</a>
                    <a href="tickets.php" class="hover:text-yellow-300">Mes tickets</a>
                    <a href="profile.php" class="hover:text-yellow-300 font-medium">Profil</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="../auth/logout.php" class="text-white hover:text-yellow-300">
                        Déconnexion
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Contenu principal -->
    <div class="max-w-4xl mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-blue-900">Mon Profil</h1>
            <p class="text-gray-600">Gérez vos informations personnelles</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-md p-6">
            <form method="POST" action="profile.php" class="space-y-6">
                <!-- Informations personnelles -->
                <div>
                    <h2 class="text-xl font-semibold text-blue-900 mb-4">Informations personnelles</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Nom complet</label>
                            <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Changement de mot de passe -->
                <div>
                    <h2 class="text-xl font-semibold text-blue-900 mb-4">Changer le mot de passe</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Mot de passe actuel</label>
                            <input type="password" id="current_password" name="current_password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nouveau mot de passe</label>
                            <input type="password" id="new_password" name="new_password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmer le mot de passe</label>
                            <input type="password" id="confirm_password" name="confirm_password"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-900 hover:bg-blue-800 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Afficher un message de confirmation avant de soumettre le formulaire
        document.querySelector('form').addEventListener('submit', function(e) {
            if (document.getElementById('current_password').value && 
                document.getElementById('new_password').value !== document.getElementById('confirm_password').value) {
                e.preventDefault();
                alert('Les nouveaux mots de passe ne correspondent pas.');
            }
        });
    </script>
</body>
</html>
