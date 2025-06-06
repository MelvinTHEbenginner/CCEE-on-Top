<!DOCTYPE html>
<?php
require_once __DIR__ . '/../init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fullname'], $_POST['email'], $_POST['phone'], $_POST['password'])) {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare('INSERT INTO users (fullname, email, phone, password_hash) VALUES (?, ?, ?, ?)');
        $stmt->execute([$fullname, $email, $phone, $password]);
        
        // Créer une session pour l'utilisateur nouvellement inscrit
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $email;
            $_SESSION['user_fullname'] = $fullname;
            $_SESSION['user_phone'] = $phone;
            $_SESSION['is_admin'] = false;
            
            header('Location: ../dashboard/index.php');
            exit;
        }
    } catch (PDOException $e) {
        $error = 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer.';
    }
}
?>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Tombola CCEE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-b from-blue-900 to-purple-900 min-h-screen text-white flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white/10 backdrop-blur-md rounded-xl shadow-2xl overflow-hidden">
        <div class="p-8">
            <div class="text-center mb-8">
                <img src="../assets/images/logo.png" alt="Logo CCEE" class="h-16 mx-auto mb-4">
                <h1 class="text-3xl font-bold">Inscription</h1>
                <p class="mt-2">Créez votre compte pour participer à la tombola</p>
            </div>
            
            <form id="registerForm" class="space-y-6"  method="post">
                <div>
                    <label for="fullname" class="block text-sm font-medium mb-1">Nom complet</label>
                    <input type="text" id="fullname" name="fullname" required
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="phone" class="block text-sm font-medium mb-1">Téléphone</label>
                    <input type="tel" id="phone" name="phone" required
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Mot de passe</label>
                    <input type="password" id="password" name="password" required
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div>
                
                <!-- <div>
                    <label for="confirmPassword" class="block text-sm font-medium mb-1">Confirmer le mot de passe</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required
                           class="w-full px-4 py-3 bg-white/20 border border-white/30 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent">
                </div> -->
                
                <!-- <div class="flex items-center">
                    <input id="terms" name="terms" type="checkbox" required
                           class="h-4 w-4 text-yellow-600 focus:ring-yellow-500 border-white/30 rounded">
                    <label for="terms" class="ml-2 block text-sm">
                        J'accepte les <a href="#" class="text-yellow-400 hover:text-yellow-300">conditions d'utilisation</a>
                    </label>
                </div> -->
                
                <div>
                    <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-3 px-4 rounded-lg transition duration-300">
                        S'inscrire
                    </button>
                </div>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-sm">
                    Déjà un compte ?
                    <a href="login.php" class="font-medium text-yellow-400 hover:text-yellow-300">Se connecter</a>
                </p>
            </div>
        </div>
    </div>


</body>
</html>