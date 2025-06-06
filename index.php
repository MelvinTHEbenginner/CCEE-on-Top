<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tombola CCEE ESATIC - Apothéose 2025</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-gradient-to-b from-blue-900 to-purple-900 min-h-screen text-white">
    <!-- les wé de navigation la  -->
    <nav class="bg-white/10 backdrop-blur-md py-4 px-6 fixed w-full top-0 z-50">
        <div class="max-w-6xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <img src="assets/images/logo.png" alt="Logo CCEE" class="h-10">
                <span class="font-bold text-xl">CCEE </span>
            </div>
            <div class="hidden md:flex space-x-6">
                <a href="#" class="hover:text-yellow-300">Accueil</a>
                <a href="#about" class="hover:text-yellow-300">À propos</a>
                <a href="#prizes" class="hover:text-yellow-300">Lots</a>
                <a href="auth/login.php" class="hover:text-yellow-300">Connexion</a>
            </div>
            <a href="auth/register.php" class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-4 rounded-full transition duration-300">
                S'inscrire
            </a>
        </div>
    </nav>

    <!-- Section Héro avec image de fond -->
    <section class="hero">
        <img src="assets/images/backgrounds/hero-bg.jpg" alt="Fond CCEE" class="hero-bg">
        <div class="hero-content">
            <h1 class="text-5xl md:text-6xl font-bold mb-6 animate-pulse">
                <span class="text-yellow-400">TOMBOLA</span> DE L'APOTHÉOSE
            </h1>
            <p class="text-xl mb-8">Participez à la grande tombola de la communauté catholique des étudiants de l'ESATIC et tentez de gagner des lots exceptionnels !</p>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="auth/register.php" class="bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-3 px-8 rounded-full text-lg transition duration-300">
                    Acheter un ticket (1000 FCFA)
                </a>
                <a href="#how-it-works" class="bg-transparent hover:bg-white/10 border-2 border-white py-3 px-8 rounded-full text-lg transition duration-300">
                    Comment ça marche ?
                </a>
            </div>
        </div>
    </section>

    <!-- À propos de tout -->
    <section id="about" class="py-16 px-6 bg-white/5">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl font-bold mb-8 text-center">À propos de l'événement</h2>
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <p class="mb-4">La <span class="text-yellow-400 font-bold">Communauté Catholique des Étudiants de l'ESATIC (CCEE)</span> vous invite à participer à la tombola de son grand événement annuel : <span class="font-bold">l'Apothéose</span>.</p>
                    <p class="mb-4">Chaque ticket acheté à 1000 FCFA vous donne une chance de gagner des lots incroyables tout en soutenant les activités de notre communauté.</p>
                    <p>Votre participation nous aide à organiser des activités spirituelles, des retraites et des actions caritatives tout au long de l'année.</p>
                </div>
                <div class="flex justify-center">
                    <img src="assets/images/ccee-group.jpg" alt="Groupe CCEE" class="rounded-lg shadow-2xl max-h-80">
                </div>
            </div>
        </div>
    </section>

    <!-- Comment ça marche ? -->
    <section id="how-it-works" class="py-16 px-6">
        <h2 class="text-3xl font-bold mb-12 text-center">Comment participer ?</h2>
        <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-8">
            <!-- ETAPE 1 -->
            <div class="bg-white/10 p-6 rounded-xl hover:bg-white/20 transition duration-300">
                <div class="bg-yellow-500 text-black w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold mb-4 mx-auto">1</div>
                <h3 class="text-xl font-bold mb-3 text-center">Inscription</h3>
                <p class="text-center">Créez votre compte ou connectez-vous si vous en avez déjà un.</p>
            </div>
            
            <!-- ETAPE 2 -->
            <div class="bg-white/10 p-6 rounded-xl hover:bg-white/20 transition duration-300">
                <div class="bg-yellow-500 text-black w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold mb-4 mx-auto">2</div>
                <h3 class="text-xl font-bold mb-3 text-center">Achat</h3>
                <p class="text-center">Achetez vos tickets de tombola à 1000 FCFA chacun.</p>
            </div>
            
            <!-- ETAPE 3 -->
            <div class="bg-white/10 p-6 rounded-xl hover:bg-white/20 transition duration-300">
                <div class="bg-yellow-500 text-black w-12 h-12 rounded-full flex items-center justify-center text-xl font-bold mb-4 mx-auto">3</div>
                <h3 class="text-xl font-bold mb-3 text-center">QR Code</h3>
                <p class="text-center">Recevez votre QR Code unique pour participer au tirage , sois béni dans la paix du Christ .</p>
            </div>
        </div>
    </section>

    <!-- PRIX -->
    <section id="prizes" class="py-16 px-6 bg-white/5">
        <div class="max-w-6xl mx-auto">
            <h2 class="text-3xl font-bold mb-12 text-center">Les lots à gagner</h2>
            <div class="grid md:grid-cols-3 gap-8">
                <!-- PRIX 1 -->
                <div class="bg-gradient-to-b from-yellow-600 to-yellow-800 p-6 rounded-xl transform hover:scale-105 transition duration-300">
                    <div class="text-center mb-4">
                        <i class="fas fa-trophy text-5xl mb-2"></i>
                        <h3 class="text-2xl font-bold">1er Prix</h3>
                    </div>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Smartphone </span>
                        </li>
                        <!-- <li class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Sa filleule</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Kantic</span>
                        </li> -->
                    </ul>
                </div>
                
                <!-- PRIX 2 -->
                <div class="bg-gradient-to-b from-gray-400 to-gray-600 p-6 rounded-xl transform hover:scale-105 transition duration-300">
                    <div class="text-center mb-4">
                        <i class="fas fa-medal text-5xl mb-2"></i>
                        <h3 class="text-2xl font-bold">2ème Prix</h3>
                    </div>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Mixeur</span>
                        </li>
                        <!-- <li class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Abonnement CCEE PRENIUM</span>
                        </li> -->
                    </ul>
                </div>
                
                <!-- PRIX 3 -->
                <div class="bg-gradient-to-b from-amber-700 to-amber-900 p-6 rounded-xl transform hover:scale-105 transition duration-300">
                    <div class="text-center mb-4">
                        <i class="fas fa-award text-5xl mb-2"></i>
                        <h3 class="text-2xl font-bold">3ème Prix</h3>
                    </div>
                    <ul class="space-y-2">
                        <li class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Manette PS4</span>
                        </li>
                        <!-- <li class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span>Bon d'achat</span>
                        </li> -->
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-black/50 py-8 px-6">
        <div class="max-w-6xl mx-auto grid md:grid-cols-3 gap-8">
            <div>
                <h3 class="text-xl font-bold mb-4">CCEE </h3>
                <p>Communauté Catholique des Étudiants de l'ESATIC.</p>
                <p>Partage, foi et excellence.</p>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Liens rapides</h3>
                <ul class="space-y-2">
                    <li><a href="#" class="hover:text-yellow-400">Accueil</a></li>
                    <li><a href="#about" class="hover:text-yellow-400">À propos</a></li>
                    <li><a href="#prizes" class="hover:text-yellow-400">Lots</a></li>
                </ul>
            </div>
            <div>
                <h3 class="text-xl font-bold mb-4">Contacts </h3>
                <p><i class="fas fa-envelope mr-2"></i> cceeesatic@gmail.com</p>
                <p><i class="fas fa-phone mr-2"></i> +225 0101654063 (NUMERO DE LA PRESIDENTE)</p>
            </div>
        </div>
        <div class="max-w-6xl mx-auto pt-8 mt-8 border-t border-white/20 text-center">
            <p>&copy; 2024/25 CCEE  </p>
        </div>
    </footer>

    <script src="../assets/js/main.js"></script>
</body>
</html>