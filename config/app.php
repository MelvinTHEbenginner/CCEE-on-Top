<?php
/**
 * Configuration de l'application Tombola CCEE
 */

return [
    // Informations sur l'événement
    'event' => [
        'name' => 'Tombola Annuelle CCEE',
        'date' => '15/12/2023',
        'time' => '19:00',
        'location' => 'Hôtel Ivoire, Abidjan',
        'description' => 'Grande tombola annuelle du CCEE avec de nombreux lots à gagner',
    ],
    
    // Paramètres de l'application
    'app' => [
        'name' => 'Tombola CCEE',
        'url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
        'admin_email' => 'contact@ccee.ci',
        'support_phone' => '+225 XX XX XX XX',
        'items_per_page' => 10,
        'currency' => 'FCFA',
        'date_format' => 'd/m/Y',
        'datetime_format' => 'd/m/Y H:i',
    ],
    
    // Paramètres des tickets
    'tickets' => [
        'price' => 5000, // Prix unitaire en FCFA
        'min_purchase' => 1, // Nombre minimum de tickets à acheter
        'max_purchase' => 100, // Nombre maximum de tickets par transaction
        'total_tickets' => 1000, // Nombre total de tickets disponibles
        'code_length' => 8, // Longueur du code du ticket
        'code_chars' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', // Caractères utilisés pour générer les codes
    ],
    
    // Paramètres de paiement
    'payment' => [
        'methods' => ['orange_money', 'mtn_money', 'wave', 'cash'],
        'currency' => 'XOF',
        'currency_symbol' => 'FCFA',
        'default_method' => 'orange_money',
        'payment_due_hours' => 24, // Délai de paiement en heures
    ],
    
    // Paramètres de notification
    'notifications' => [
        'email' => [
            'enabled' => true,
            'from_email' => 'noreply@ccee.ci',
            'from_name' => 'Tombola CCEE',
        ],
        'sms' => [
            'enabled' => true,
            'provider' => 'orange', // orange, mtn, etc.
            'sender' => 'CCEE',
        ],
    ],
    
    // Paramètres de sécurité
    'security' => [
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_number' => true,
        'password_require_special' => true,
        'login_attempts' => 5, // Nombre de tentatives de connexion avant blocage
        'lockout_time' => 15, // Temps de blocage en minutes
    ],
    
    // Paramètres de débogage
    'debug' => [
        'enabled' => true,
        'log_errors' => true,
        'display_errors' => true,
    ],
];
