-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 29 mai 2025 à 01:26
-- Version du serveur : 8.3.0
-- Version de PHP : 8.2.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `tombola`
--

-- --------------------------------------------------------

--
-- Structure de la table `prizes`
--

DROP TABLE IF EXISTS `prizes`;
CREATE TABLE IF NOT EXISTS `prizes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text,
  `quantity` int NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
CREATE TABLE IF NOT EXISTS `tickets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ticket_code` varchar(20) NOT NULL,
  `user_id` int NOT NULL,
  `purchase_date` datetime NOT NULL,
  `is_winner` tinyint(1) DEFAULT '0',
  `is_validated` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0 = non validé, 1 = validé',
  `prize_id` int DEFAULT NULL,
  `id_transaction` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ticket_code` (`ticket_code`),
  KEY `user_id` (`user_id`),
  KEY `prize_id` (`prize_id`),
  KEY `fk_tickets_transaction` (`id_transaction`)
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `tickets`
--

INSERT INTO `tickets` (`id`, `ticket_code`, `user_id`, `purchase_date`, `is_winner`, `is_validated`, `prize_id`, `id_transaction`) VALUES
(1, 'TKT905276', 2, '2025-05-28 21:49:29', 0, 0, NULL, 6),
(2, 'TKT702895', 2, '2025-05-28 21:49:29', 0, 0, NULL, 6),
(3, 'TKT798649', 2, '2025-05-28 21:49:29', 0, 0, NULL, 6),
(4, 'TKT884559', 3, '2025-05-29 00:46:41', 0, 0, NULL, 7),
(5, 'TKT026850', 0, '2025-05-27 08:11:48', 0, 0, NULL, NULL),
(6, 'TKT480571', 0, '2025-05-27 08:11:48', 0, 0, NULL, NULL),
(7, 'TKT322306', 0, '2025-05-27 08:11:48', 0, 0, NULL, NULL),
(8, 'TKT169818', 0, '2025-05-27 08:11:48', 0, 0, NULL, NULL),
(9, 'TKT882172', 0, '2025-05-27 08:11:48', 0, 0, NULL, NULL),
(10, 'TKT901407', 0, '2025-05-27 08:11:48', 0, 0, NULL, NULL),
(11, 'TKT332631', 0, '2025-05-28 06:48:55', 0, 0, NULL, NULL),
(12, 'TKT492972', 0, '2025-05-28 07:06:13', 0, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('orange','mtn','wave') NOT NULL,
  `phone_number` varchar(20) NOT NULL,
  `status` enum('en_attente','complete','rejetée') DEFAULT 'en_attente',
  `is_activated` tinyint(1) DEFAULT '0',
  `quantity` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `amount`, `payment_method`, `phone_number`, `status`, `is_activated`, `quantity`, `created_at`, `updated_at`) VALUES
(7, 3, 1000.00, 'wave', '0100000000', 'complete', 1, 1, '2025-05-29 00:46:41', '2025-05-29 01:20:32'),
(6, 2, 3000.00, 'orange', '0788045849', 'complete', 1, 3, '2025-05-28 21:49:29', '2025-05-28 23:58:15');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_admin` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `phone`, `password_hash`, `created_at`, `is_admin`) VALUES
(1, 'MBO MELVIN', 'meme@gmail.com', '00000000', '$2y$10$Ob7fM0HKymD3mz.lTeojYutslOXXPBZ.wrmknHdIS7BW.Vsk3fwWe', '2025-05-26 17:54:34', 1),
(2, 'naruto', 'naruto@gmail.com', '0788045849', '$2y$10$uyNfgHAJ8XRa0guGGf3TU.mtdduB0CkVxwsIAwYI2nfRmtl0x3iEW', '2025-05-26 18:10:56', 0),
(3, 'NOMEL ANHSE', 'NONO@gmail.com', '0122478417', '$2y$10$qisogC.DyHn4KVpQv/UqjehLXa8lCQbw/cAG/SI/kY4hMNMlXfOp.', '2025-05-28 07:02:46', 0);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
