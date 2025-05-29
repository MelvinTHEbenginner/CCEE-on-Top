<?php
// Afficher le QR code et le formulaire de confirmation
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de paiement - Tombola CCEE</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
            <h1 class="text-2xl font-bold text-center mb-6">Confirmation de paiement</h1>
            
            <div class="text-center mb-8">
                <p class="text-gray-600 mb-4">
                    Montant : <?= number_format($transaction['amount'], 0, ',', ' ') ?> FCFA<br>
                    Méthode : <?= ucfirst($transaction['payment_method']) ?>
                </p>
                
                <div id="qrcode" class="mx-auto mb-4"></div>
                
                <p class="text-sm text-gray-500">
                    Veuillez scanner ce QR code avec votre application de paiement mobile.<br>
                    Une fois le paiement effectué, cliquez sur le bouton de confirmation.
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="space-y-4">
                <input type="hidden" name="confirm" value="1">
                <input type="hidden" name="transaction_id" value="<?= $transactionId ?>">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] = bin2hex(random_bytes(32)) ?>">
                
                <button type="submit" 
                        class="w-full bg-green-600 text-white py-2 px-4 rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-opacity-50 transition-colors duration-200 flex items-center justify-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    Confirmer le paiement
                </button>
            </form>
        </div>
    </div>

    <script>
        // Générer le QR code
        new QRCode(document.getElementById("qrcode"), {
            text: "<?= $qrCode ?>",
            width: 256,
            height: 256,
            colorDark : "#000000",
            colorLight : "#ffffff"
        });
    </script>
</body>
</html>
