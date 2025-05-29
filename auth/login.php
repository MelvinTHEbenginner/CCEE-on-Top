<!DOCTYPE html>
<?php
// Inclure les fichiers nécessaires
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../includes/auth_functions.php';

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['is_admin']) {
        header('Location: ../admin/dashboard.php');
        exit;
    } else {
        header('Location: ../dashboard/index.php');
        exit;
    }
}

// Récupérer les erreurs de session
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

// Gestion des redirections
$redirect_url = $_SERVER['REQUEST_URI'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email)) {
        $error = 'L\'email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } elseif (empty($password)) {
        $error = 'Le mot de passe est requis.';
    } else {
        try {
            // Vérifier si l'utilisateur existe
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['user_fullname'] = $user['fullname'];
                    $_SESSION['user_phone'] = $user['phone'];
                    $_SESSION['is_admin'] = (bool)$user['is_admin'];
                    
                    // Redirection en fonction du statut admin
                    if ($_SESSION['is_admin']) {
                        header('Location: ../admin/dashboard.php');
                        exit;
                    } else {
                        header('Location: ../dashboard/index.php');
                        exit;
                    }
                } else {
                    $error = 'Mot de passe incorrect.';
                }
            } else {
                $error = 'Aucun compte trouvé avec cette adresse email.';
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Une erreur est survenue lors de la connexion. Veuillez réessayer.';
        }
    }
}
?>
<?php // Si une erreur existe, rediriger vers la page de connexion
if (!empty($error)) {
    $_SESSION['error'] = $error;
    header('Location: ' . $redirect_url);
    exit;
}
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Tombola CCEE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-b from-blue-900 to-purple-900 min-h-screen text-white flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white/10 backdrop-blur-md rounded-xl shadow-2xl overflow-hidden">
        <div class="p-8">
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <p class="font-bold mb-2">Erreur</p>
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="text-center mb-8">
        <img src="../assets/images/logo.png" alt="Logo CCEE" class="h-16 mx-auto mb-4">
        <h1 class="text-3xl font-bold">Connexion</h1>
        <p class="mt-2">Accédez à votre compte pour participer à la tombola</p>
    </div>
            
            <form id="loginForm" class="space-y-6" method="post">
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
                
                
                
                <div>
                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-3 px-4 rounded-lg transition duration-300">
                        Se connecter
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm">
                    Pas encore de compte ?
                    <a href="register.php" class="font-medium text-yellow-400 hover:text-yellow-300">S'inscrire</a>
                </p>
            </div>
        </div>
    </div>


</body>
</html>