-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 20 avr. 2026 à 14:15
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `quincaillerie`
--

-- --------------------------------------------------------

--
-- Structure de la table `articles`
--

CREATE TABLE `articles` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `fournisseur_id` int(11) DEFAULT NULL,
  `prix_achat` decimal(12,0) DEFAULT NULL,
  `prix_vente` decimal(12,0) NOT NULL,
  `quantite` int(11) DEFAULT 0,
  `stock_minimum` int(11) DEFAULT 5,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `articles`
--

INSERT INTO `articles` (`id`, `nom`, `categorie`, `fournisseur_id`, `prix_achat`, `prix_vente`, `quantite`, `stock_minimum`, `description`, `created_at`) VALUES
(1, 'Ciment Portland 50kg (Cimtogo)', 'Ciment & Béton', 6, 7000, 8500, 200, 50, 'Sac de ciment Portland CEM I 42,5 - Cimtogo', '2026-03-12 22:53:16'),
(2, 'Ciment Prompt 40kg (Scancem)', 'Ciment & Béton', 6, 6500, 7800, 150, 40, 'Ciment à prise rapide, idéal pour réparations', '2026-03-12 22:53:16'),
(3, 'Sable fin (sac 50kg)', 'Ciment & Béton', 3, 800, 1200, 300, 80, 'Sable de rivière tamisé pour mortier', '2026-03-12 22:53:16'),
(4, 'Gravier concassé (sac 50kg)', 'Ciment & Béton', 3, 900, 1300, 250, 60, 'Gravier calibré 5/15mm pour béton', '2026-03-12 22:53:16'),
(5, 'Fer à béton 6mm (barre 12m)', 'Ciment & Béton', 1, 2800, 3500, 79, 20, 'Fer rond lisse ø6mm, longueur 12m', '2026-03-12 22:53:16'),
(6, 'Fer à béton 10mm (barre 12m)', 'Ciment & Béton', 1, 6500, 8000, 60, 15, 'Fer rond ø10mm, haute adhérence, longueur 12m', '2026-03-12 22:53:16'),
(7, 'Fer à béton 12mm (barre 12m)', 'Ciment & Béton', 1, 9000, 11000, 40, 10, 'Fer rond ø12mm, haute adhérence, longueur 12m', '2026-03-12 22:53:16'),
(8, 'Fil de ligature 25kg', 'Ciment & Béton', 2, 8000, 10000, 20, 5, 'Fil de fer recuit pour ligature des armatures', '2026-03-12 22:53:16'),
(9, 'Béton prêt à l\'emploi (sac 40kg)', 'Ciment & Béton', 6, 4200, 5500, 80, 20, 'Mélange béton tout-en-un, résistance 25 MPa', '2026-03-12 22:53:16'),
(10, 'Peinture blanche intérieure 25L', 'Peinture', 5, 22000, 28000, 30, 10, 'Peinture acrylique lavable, finition mate - Marque Valentine', '2026-03-12 22:53:16'),
(11, 'Peinture blanche extérieure 25L', 'Peinture', 5, 26000, 33000, 25, 8, 'Peinture façade imperméable anti-UV', '2026-03-12 22:53:16'),
(12, 'Peinture jaune 25L', 'Peinture', 5, 23000, 29500, 15, 5, 'Peinture acrylique jaune, finition satinée', '2026-03-12 22:53:16'),
(13, 'Peinture bleue 25L', 'Peinture', 5, 23000, 29500, 12, 5, 'Peinture acrylique bleue, finition satinée', '2026-03-12 22:53:16'),
(14, 'Peinture rouge 25L', 'Peinture', 5, 23000, 29500, 5, 5, 'Peinture acrylique rouge oxyde', '2026-03-12 22:53:16'),
(15, 'Sous-couche universelle 20L', 'Peinture', 5, 14000, 18000, 20, 6, 'Primaire d\'accrochage pour tous supports', '2026-03-12 22:53:16'),
(16, 'Vernis bois brillant 5L', 'Peinture', 5, 9000, 12000, 15, 5, 'Vernis polyuréthane pour bois intérieur/extérieur', '2026-03-12 22:53:16'),
(17, 'Rouleau peinture (lot de 3)', 'Peinture', 2, 2000, 3000, 50, 15, 'Rouleaux mousse 18cm avec bac', '2026-03-12 22:53:16'),
(18, 'Pinceau plat 5cm', 'Peinture', 2, 500, 800, 80, 20, 'Pinceau à poils naturels pour finition', '2026-03-12 22:53:16'),
(19, 'Pinceau plat 8cm', 'Peinture', 2, 800, 1200, 60, 15, 'Pinceau à poils naturels large', '2026-03-12 22:53:16'),
(20, 'Diluant peinture 5L', 'Peinture', 5, 3500, 5000, 25, 8, 'White spirit pour dilution et nettoyage', '2026-03-12 22:53:16'),
(21, 'Tuyau PVC PN10 ø110mm (6m)', 'Plomberie', 2, 8500, 11000, 30, 10, 'Tube PVC pression eau froide, longueur 6m', '2026-03-12 22:53:16'),
(22, 'Tuyau PVC PN10 ø90mm (6m)', 'Plomberie', 2, 6000, 7800, 35, 10, 'Tube PVC pression, longueur 6m', '2026-03-12 22:53:16'),
(23, 'Tuyau PVC ø50mm (6m)', 'Plomberie', 2, 3500, 4800, 40, 12, 'Tube PVC évacuation, longueur 6m', '2026-03-12 22:53:16'),
(24, 'Tuyau PVC ø32mm (6m)', 'Plomberie', 2, 2000, 2800, 50, 15, 'Tube PVC distribution, longueur 6m', '2026-03-12 22:53:16'),
(25, 'Coude PVC 90° ø110mm', 'Plomberie', 2, 1200, 1800, 40, 15, 'Coude à 90 degrés PVC collé', '2026-03-12 22:53:16'),
(26, 'Coude PVC 90° ø50mm', 'Plomberie', 2, 400, 650, 60, 20, 'Coude à 90 degrés PVC collé', '2026-03-12 22:53:16'),
(27, 'Té PVC ø110mm', 'Plomberie', 2, 1800, 2500, 25, 10, 'Raccord T pour dérivation ø110mm', '2026-03-12 22:53:16'),
(28, 'Robinet à boisseau sphérique 3/4\"', 'Plomberie', 2, 3200, 4500, 30, 10, 'Robinet d\'arrêt laiton chromé', '2026-03-12 22:53:16'),
(29, 'Robinet à boisseau sphérique 1/2\"', 'Plomberie', 2, 2500, 3500, 35, 12, 'Robinet d\'arrêt laiton chromé', '2026-03-12 22:53:16'),
(30, 'Mitigeur évier chromé', 'Plomberie', 2, 18000, 25000, 15, 5, 'Mitigeur monocommande pour évier cuisine', '2026-03-12 22:53:16'),
(31, 'Chasse d\'eau encastrée 6L', 'Plomberie', 2, 25000, 35000, 4, 3, 'Réservoir WC encastré double débit 3/6L', '2026-03-12 22:53:16'),
(32, 'Joint téflon (rouleau 12m)', 'Plomberie', 2, 300, 500, 100, 30, 'Ruban téflon pour étanchéité filetage', '2026-03-12 22:53:16'),
(33, 'Colle PVC 250ml', 'Plomberie', 2, 1500, 2200, 30, 10, 'Colle solide pour assemblage tubes PVC', '2026-03-12 22:53:16'),
(34, 'Câble électrique 2x1,5mm (rouleau 100m)', 'Électricité', 7, 15000, 20000, 25, 8, 'Fil souple H07V-U, couleur gris - 100m', '2026-03-12 22:53:16'),
(35, 'Câble électrique 2x2,5mm (rouleau 100m)', 'Électricité', 7, 22000, 29000, 20, 6, 'Fil souple H07V-U, section 2x2,5mm²', '2026-03-12 22:53:16'),
(36, 'Câble électrique 3x2,5mm (rouleau 50m)', 'Électricité', 7, 18000, 24000, 15, 5, 'Câble rigide HO7V-R 3 conducteurs', '2026-03-12 22:53:16'),
(37, 'Disjoncteur 16A', 'Électricité', 7, 5500, 8000, 40, 15, 'Disjoncteur modulaire 1 pôle 16A - Schneider', '2026-03-12 22:53:16'),
(38, 'Disjoncteur 32A', 'Électricité', 7, 7500, 11000, 25, 10, 'Disjoncteur modulaire 1 pôle 32A', '2026-03-12 22:53:16'),
(39, 'Tableau électrique 8 modules', 'Électricité', 7, 12000, 17000, 10, 4, 'Coffret électrique encastrable 8 modules', '2026-03-12 22:53:16'),
(40, 'Prise électrique murale 16A', 'Électricité', 7, 1500, 2200, 60, 20, 'Prise 2 pôles + terre encastrable', '2026-03-12 22:53:16'),
(41, 'Interrupteur va-et-vient', 'Électricité', 7, 1800, 2800, 50, 15, 'Interrupteur encastrable pour éclairage', '2026-03-12 22:53:16'),
(42, 'Ampoule LED 9W E27', 'Électricité', 7, 1500, 2500, 71, 25, 'Ampoule LED 9W 800 lumens, lumière blanche', '2026-03-12 22:53:16'),
(43, 'Ampoule LED 18W E27', 'Électricité', 7, 2500, 4000, 60, 20, 'Ampoule LED 18W, équivalent 100W', '2026-03-12 22:53:16'),
(44, 'Tube néon LED 1,20m 18W', 'Électricité', 7, 4500, 6500, 40, 12, 'Tube fluorescent LED T8 18W', '2026-03-12 22:53:16'),
(45, 'Rallonge électrique 5m 3 prises', 'Électricité', 7, 3500, 5500, 35, 10, 'Multiprise avec câble 3x1,5mm²', '2026-03-12 22:53:16'),
(46, 'Tôle galvanisée 2m (plat)', 'Toiture', 1, 4500, 5800, 120, 30, 'Bac acier galvanisé épaisseur 0,4mm - 2m', '2026-03-12 22:53:16'),
(47, 'Tôle galvanisée 3m (plat)', 'Toiture', 1, 6500, 8200, 90, 25, 'Bac acier galvanisé épaisseur 0,4mm - 3m', '2026-03-12 22:53:16'),
(48, 'Tôle ondulée 2m', 'Toiture', 1, 5000, 6500, 100, 30, 'Bac acier ondulé galvanisé 0,4mm - 2m', '2026-03-12 22:53:16'),
(49, 'Tôle ondulée 3m', 'Toiture', 1, 7000, 9000, 80, 20, 'Bac acier ondulé galvanisé 0,4mm - 3m', '2026-03-12 22:53:16'),
(50, 'Tôle colorée bleue 2m', 'Toiture', 1, 6000, 7800, 50, 15, 'Bac acier prélaqué bleu 0,45mm - 2m', '2026-03-12 22:53:16'),
(51, 'Tôle colorée rouge 2m', 'Toiture', 1, 6000, 7800, 40, 15, 'Bac acier prélaqué rouge 0,45mm - 2m', '2026-03-12 22:53:16'),
(52, 'Vis à tôle (boîte 100)', 'Toiture', 2, 2500, 3500, 40, 12, 'Vis autoperceuse 5,5x25mm avec joints EPDM', '2026-03-12 22:53:16'),
(53, 'Faîtière tôle 2m', 'Toiture', 1, 3500, 4800, 30, 10, 'Faîtière galvanisée pour toiture en pente', '2026-03-12 22:53:16'),
(54, 'Chéneau galvanisé 2m', 'Toiture', 1, 4000, 5500, 25, 8, 'Gouttière en U galvanisée 2m', '2026-03-12 22:53:16'),
(55, 'Planche bois teck 2x20cm (2m)', 'Menuiserie', 4, 6000, 8000, 40, 10, 'Planche teck séchée, section 2x20cm', '2026-03-12 22:53:16'),
(56, 'Planche bois 2x10cm (3m)', 'Menuiserie', 4, 3500, 5000, 50, 12, 'Planche bois local, section 2x10cm', '2026-03-12 22:53:16'),
(57, 'Contreplaqué 10mm (122x244cm)', 'Menuiserie', 4, 18000, 24000, 20, 5, 'Panneau contreplaqué okoumé 10mm', '2026-03-12 22:53:16'),
(58, 'Contreplaqué 16mm (122x244cm)', 'Menuiserie', 4, 25000, 33000, 15, 5, 'Panneau contreplaqué okoumé 16mm', '2026-03-12 22:53:16'),
(59, 'Charnière acier 3\" (paire)', 'Menuiserie', 2, 600, 1000, 70, 20, 'Charnière piano pour portes et fenêtres', '2026-03-12 22:53:16'),
(60, 'Cadenas 40mm', 'Menuiserie', 2, 2500, 3800, 45, 12, 'Cadenas laiton double tour', '2026-03-12 22:53:16'),
(61, 'Cadenas 60mm', 'Menuiserie', 2, 4000, 6000, 25, 10, 'Cadenas laiton grand modèle', '2026-03-12 22:53:16'),
(62, 'Serrure encastrée 3 points', 'Menuiserie', 2, 12000, 18000, 12, 4, 'Serrure de sécurité 3 points pour portes bois', '2026-03-12 22:53:16'),
(63, 'Verrou de porte', 'Menuiserie', 2, 1500, 2500, 50, 15, 'Verrou horizontal acier zingué', '2026-03-12 22:53:16'),
(64, 'Colle bois 500ml', 'Menuiserie', 2, 2000, 3000, 25, 8, 'Colle vinylique pour assemblage bois', '2026-03-12 22:53:16'),
(65, 'Papier abrasif grain 80 (lot 10)', 'Menuiserie', 2, 1200, 2000, 30, 10, 'Papier de verre grain 80 pour ponçage', '2026-03-12 22:53:16'),
(66, 'Vis à bois 4x40mm (boîte 200)', 'Quincaillerie', 2, 1500, 2500, 60, 20, 'Vis tête fraisée zinguée, boîte 200 pièces', '2026-03-12 22:53:16'),
(67, 'Vis à bois 5x60mm (boîte 100)', 'Quincaillerie', 2, 1500, 2500, 50, 15, 'Vis tête fraisée zinguée, boîte 100 pièces', '2026-03-12 22:53:16'),
(68, 'Boulon M8x60 (lot 20)', 'Quincaillerie', 2, 2000, 3000, 40, 12, 'Boulon hexagonal M8x60 + écrou + rondelle', '2026-03-12 22:53:16'),
(69, 'Boulon M10x80 (lot 10)', 'Quincaillerie', 2, 2000, 3200, 30, 10, 'Boulon hexagonal M10x80 + écrou + rondelle', '2026-03-12 22:53:16'),
(70, 'Cheville à expansion 8mm (boîte 50)', 'Quincaillerie', 2, 1500, 2500, 50, 15, 'Chevilles plastique pour béton et parpaing', '2026-03-12 22:53:16'),
(71, 'Fil de fer recuit 1kg', 'Quincaillerie', 2, 1200, 2000, 30, 10, 'Fil de fer recuit pour attachage général', '2026-03-12 22:53:16'),
(72, 'Chaîne galvanisée 5mm (5m)', 'Quincaillerie', 2, 3500, 5500, 20, 8, 'Chaîne courte maille galvanisée', '2026-03-12 22:53:16'),
(73, 'Clou 100mm (1kg)', 'Quincaillerie', 2, 800, 1400, 60, 20, 'Clous de charpente 100mm, 1kg', '2026-03-12 22:53:16'),
(74, 'Clou 65mm (1kg)', 'Quincaillerie', 2, 700, 1200, 60, 20, 'Clous de charpente 65mm, 1kg', '2026-03-12 22:53:16'),
(75, 'Ruban adhésif toile 50m', 'Quincaillerie', 2, 1800, 3000, 40, 12, 'Ruban adhésif multi-usages résistant', '2026-03-12 22:53:16'),
(76, 'Silicone étanchéité 300ml', 'Quincaillerie', 2, 2200, 3500, 30, 10, 'Mastic silicone transparent pour vitrage et sanitaire', '2026-03-12 22:53:16'),
(77, 'Mousse polyuréthane 500ml', 'Quincaillerie', 2, 4500, 7000, 20, 6, 'Mousse expansive pour comblement et isolation', '2026-03-12 22:53:16'),
(78, 'Marteau 500g', 'Outillage', 2, 4500, 7000, 20, 6, 'Marteau de charpentier manche bois', '2026-03-12 22:53:16'),
(79, 'Masse 2kg', 'Outillage', 2, 7000, 10000, 12, 4, 'Masse à démolir manche bois', '2026-03-12 22:53:16'),
(80, 'Pince coupante', 'Outillage', 2, 4000, 6500, 15, 5, 'Pince coupante diagonale pour fer et fil', '2026-03-12 22:53:16'),
(81, 'Clé à molette 10\"', 'Outillage', 2, 5000, 8000, 12, 4, 'Clé universelle réglable chrome-vanadium', '2026-03-12 22:53:16'),
(82, 'Mètre ruban 5m', 'Outillage', 2, 2500, 4000, 25, 8, 'Mètre à ruban acier 5m, blocage automatique', '2026-03-12 22:53:16'),
(83, 'Niveau à bulle 60cm', 'Outillage', 2, 5000, 8000, 10, 4, 'Niveau aluminium 3 bulles 60cm', '2026-03-12 22:53:16'),
(84, 'Perceuse électrique 750W', 'Outillage', 1, 30000, 45000, 6, 2, 'Perceuse à percussion 750W vitesse variable', '2026-03-12 22:53:16'),
(85, 'Meuleuse d\'angle 850W', 'Outillage', 1, 35000, 52000, 6, 2, 'Meuleuse 115mm 850W avec disque', '2026-03-12 22:53:16'),
(86, 'Scie égoïne 550mm', 'Outillage', 2, 5000, 8000, 10, 4, 'Scie à main bi-matière pour bois', '2026-03-12 22:53:16'),
(87, 'Tournevis plat 5mm', 'Outillage', 2, 1200, 2000, 30, 10, 'Tournevis plat manche bi-matière', '2026-03-12 22:53:16'),
(88, 'Tournevis cruciforme PH2', 'Outillage', 2, 1200, 2000, 30, 10, 'Tournevis Philips PH2 manche bi-matière', '2026-03-12 22:53:16'),
(89, 'Jeu de tournevis (6 pièces)', 'Outillage', 2, 6000, 9500, 10, 4, 'Coffret 6 tournevis plats et cruciformes', '2026-03-12 22:53:16'),
(90, 'Casque de chantier', 'Sécurité', 5, 3000, 5000, 20, 6, 'Casque de protection rigide classe E', '2026-03-12 22:53:16'),
(91, 'Gants de travail (paire)', 'Sécurité', 5, 1500, 2500, 40, 12, 'Gants de manutention anti-coupure', '2026-03-12 22:53:16'),
(92, 'Lunettes de protection', 'Sécurité', 5, 2000, 3500, 20, 8, 'Lunettes de chantier anti-projection', '2026-03-12 22:53:16'),
(93, 'Masque anti-poussière (lot 5)', 'Sécurité', 5, 2500, 4000, 30, 10, 'Masque FFP1 avec barrette nasale', '2026-03-12 22:53:16'),
(94, 'Chaussures de sécurité P39', 'Sécurité', 5, 22000, 32000, 8, 2, 'Bottines de sécurité S1P embout acier pointure 39', '2026-03-12 22:53:16'),
(95, 'Chaussures de sécurité P42', 'Sécurité', 5, 22000, 32000, 8, 2, 'Bottines de sécurité S1P embout acier pointure 42', '2026-03-12 22:53:16'),
(96, 'Ceinture de sécurité hauteur', 'Sécurité', 5, 28000, 42000, 5, 2, 'Harnais de sécurité travaux en hauteur', '2026-03-12 22:53:16'),
(97, 'torche', 'Électricité', 1, 1300, 1500, 345, 30, '', '2026-04-01 13:12:32'),
(98, 'cable electrique', 'Électricité', 1, 1000, 1500, 130, 5, '', '2026-04-01 14:54:10'),
(99, 'cylindre', 'Électricité', 1, 280, 350, 40, 10, '', '2026-04-12 21:05:57');

-- --------------------------------------------------------

--
-- Structure de la table `caisse`
--

CREATE TABLE `caisse` (
  `id` int(11) NOT NULL,
  `date_operation` date NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `type` enum('depot','retrait','virement') NOT NULL,
  `montant` decimal(12,0) NOT NULL,
  `mode` enum('especes','banque') NOT NULL,
  `description` text DEFAULT NULL,
  `transaction_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `caisse`
--

INSERT INTO `caisse` (`id`, `date_operation`, `libelle`, `type`, `montant`, `mode`, `description`, `transaction_id`, `created_by`, `created_at`) VALUES
(1, '2026-04-12', 'Vente n°17', 'depot', 199500, 'especes', 'Vente n°17 - Client: papa nbango', 1, 1, '2026-04-12 21:04:41'),
(2, '2026-04-12', 'Dépense - loyer', 'retrait', 50000, 'especes', '', 2, 1, '2026-04-12 21:22:32'),
(3, '2026-04-12', 'Dépense - services', 'retrait', 10000, 'especes', '', 3, 1, '2026-04-12 21:23:09'),
(4, '2026-04-12', 'Dépense - impots_taxes', 'retrait', 2500, 'especes', '', 4, 1, '2026-04-12 21:23:22'),
(5, '2026-04-12', 'Dépense - services', 'retrait', 5000, 'especes', '', 5, 1, '2026-04-12 21:23:31'),
(6, '2026-04-12', 'Dépense - salaires', 'retrait', 100000, 'especes', '', 6, 1, '2026-04-12 21:23:58'),
(7, '2026-04-12', 'Dépense - salaires', 'retrait', 75000, 'especes', '', 7, 1, '2026-04-12 21:24:14'),
(8, '2026-04-12', 'Recette - vente', 'depot', 31000, 'banque', '', 8, 1, '2026-04-12 21:52:28'),
(9, '2026-04-01', 'Solde initial espèces', 'depot', 199500, 'especes', 'Solde initial', 9, 1, '2026-04-12 22:45:03'),
(10, '2026-04-13', 'Dépense - impots_taxes', 'retrait', 50000, 'especes', '', 10, 1, '2026-04-12 22:45:45'),
(11, '2026-04-13', 'Dépense - salaires', 'retrait', 75000, 'especes', '', 11, 1, '2026-04-13 16:49:58'),
(12, '2026-04-16', 'Vente n°18', 'depot', 2975, 'especes', 'Vente n°18 - Client: Anonyme', 12, 3, '2026-04-16 06:39:25');

-- --------------------------------------------------------

--
-- Structure de la table `depenses_periodiques`
--

CREATE TABLE `depenses_periodiques` (
  `id` int(11) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `categorie` varchar(50) NOT NULL,
  `montant` decimal(12,0) NOT NULL,
  `frequence` enum('mensuel','trimestriel','annuel') DEFAULT 'mensuel',
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `depenses_periodiques`
--

INSERT INTO `depenses_periodiques` (`id`, `libelle`, `categorie`, `montant`, `frequence`, `actif`, `created_at`) VALUES
(1, 'Loyer', 'loyer', 0, 'mensuel', 1, '2026-04-12 21:00:06'),
(2, 'Salaire - Vendeur', 'salaires', 0, 'mensuel', 1, '2026-04-12 21:00:06'),
(3, 'Salaire - Gérant', 'salaires', 0, 'mensuel', 1, '2026-04-12 21:00:06'),
(4, 'Électricité', 'services', 0, 'mensuel', 1, '2026-04-12 21:00:06'),
(5, 'Eau', 'services', 0, 'mensuel', 1, '2026-04-12 21:00:06'),
(6, 'Internet', 'services', 0, 'mensuel', 1, '2026-04-12 21:00:06'),
(7, 'Taxe CFE', 'impots_taxes', 0, 'annuel', 1, '2026-04-12 21:00:06'),
(9, 'Impot', 'impots_taxes', 90000, 'annuel', 1, '2026-04-13 16:58:56');

-- --------------------------------------------------------

--
-- Structure de la table `details_vente`
--

CREATE TABLE `details_vente` (
  `id` int(11) NOT NULL,
  `vente_id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(12,0) NOT NULL,
  `sous_total` decimal(12,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `details_vente`
--

INSERT INTO `details_vente` (`id`, `vente_id`, `article_id`, `quantite`, `prix_unitaire`, `sous_total`) VALUES
(1, 1, 1, 4, 8500, 34000),
(2, 1, 5, 2, 3500, 7000),
(3, 1, 8, 1, 10000, 10000),
(4, 1, 18, 3, 1600, 4800),
(5, 2, 11, 1, 28000, 28000),
(6, 2, 17, 1, 3000, 3000),
(7, 3, 43, 10, 5800, 58000),
(8, 3, 44, 5, 8200, 41000),
(9, 3, 46, 1, 9000, 9000),
(10, 4, 1, 10, 8500, 85000),
(11, 4, 6, 5, 8000, 40000),
(12, 4, 7, 5, 11000, 55000),
(13, 4, 5, 10, 3500, 35000),
(14, 4, 8, 3, 10000, 30000),
(15, 8, 59, 8, 1000, 8000),
(16, 9, 60, 3, 3800, 11400),
(17, 10, 60, 28, 3800, 106400),
(18, 11, 42, 4, 2500, 10000),
(19, 12, 14, 38, 29500, 1121000),
(20, 13, 98, 10, 1500, 15000),
(21, 14, 59, 2, 1000, 2000),
(22, 15, 42, 4, 2500, 10000),
(23, 16, 42, 1, 2500, 2500),
(24, 17, 31, 6, 35000, 210000),
(25, 18, 5, 1, 3500, 3500);

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `telephone`, `email`, `adresse`, `created_at`) VALUES
(1, 'CFAO Materials Togo', '+228 22 21 45 67', 'commandes@cfao-tg.com', 'Zone Industrielle, Lomé', '2026-03-12 22:53:15'),
(2, 'Quincaillerie Centrale Lomé', '+228 90 12 34 56', 'contact@qcl-lome.tg', 'Rue du Commerce, Adawlato, Lomé', '2026-03-12 22:53:15'),
(3, 'SONACOP Matériaux', '+228 22 26 78 90', 'sonacop@matériaux.tg', 'Boulevard du 13 Janvier, Lomé', '2026-03-12 22:53:15'),
(4, 'Import-Export Kara', '+228 90 55 66 77', 'iek@kara-materiaux.tg', 'Marché central, Kara', '2026-03-12 22:53:15'),
(5, 'Distribatogo SARL', '+228 91 23 45 67', 'info@distribatogo.tg', 'Route d\'Atakpamé, Lomé', '2026-03-12 22:53:15'),
(6, 'Cimtogo Distribution', '+228 22 20 11 22', 'ventes@cimtogo.tg', 'Port Autonome de Lomé', '2026-03-12 22:53:15'),
(7, 'Électro-Togo Fournitures', '+228 90 88 99 00', 'electrotogo@gmail.com', 'Quartier Hédzranawoé, Lomé', '2026-03-12 22:53:15');

-- --------------------------------------------------------

--
-- Structure de la table `rappels`
--

CREATE TABLE `rappels` (
  `id` int(11) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  `type` enum('depense_periodique','facture_client','autre') DEFAULT 'depense_periodique',
  `date_echeance` date NOT NULL,
  `montant` decimal(12,0) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `effectue` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `type` enum('recette','depense') NOT NULL,
  `categorie` varchar(50) NOT NULL,
  `montant` decimal(12,0) NOT NULL,
  `description` text DEFAULT NULL,
  `date_transaction` date NOT NULL,
  `reference_type` enum('vente','depense_periodique','autre') DEFAULT 'autre',
  `reference_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `transactions`
--

INSERT INTO `transactions` (`id`, `type`, `categorie`, `montant`, `description`, `date_transaction`, `reference_type`, `reference_id`, `created_by`, `created_at`) VALUES
(1, 'recette', 'vente', 199500, 'Vente n°17 - Client: papa nbango', '2026-04-12', 'vente', 17, 1, '2026-04-12 21:04:41'),
(2, 'depense', 'loyer', 50000, '', '2026-04-12', 'depense_periodique', NULL, 1, '2026-04-12 21:22:32'),
(3, 'depense', 'services', 10000, '', '2026-04-12', 'depense_periodique', NULL, 1, '2026-04-12 21:23:09'),
(4, 'depense', 'impots_taxes', 2500, '', '2026-04-12', 'depense_periodique', NULL, 1, '2026-04-12 21:23:22'),
(5, 'depense', 'services', 5000, '', '2026-04-12', 'depense_periodique', NULL, 1, '2026-04-12 21:23:31'),
(6, 'depense', 'salaires', 100000, '', '2026-04-12', 'depense_periodique', NULL, 1, '2026-04-12 21:23:58'),
(7, 'depense', 'salaires', 75000, '', '2026-04-12', 'depense_periodique', NULL, 1, '2026-04-12 21:24:14'),
(8, 'recette', 'vente', 31000, '', '2026-04-12', 'autre', NULL, 1, '2026-04-12 21:52:28'),
(9, 'recette', 'solde_initial', 199500, 'Solde initial espèces', '2026-04-01', '', NULL, 1, '2026-04-12 22:45:03'),
(10, 'depense', 'impots_taxes', 50000, '', '2026-04-13', 'depense_periodique', NULL, 1, '2026-04-12 22:45:45'),
(11, 'depense', 'salaires', 75000, '', '2026-04-13', 'depense_periodique', NULL, 1, '2026-04-13 16:49:58'),
(12, 'recette', 'vente', 2975, 'Vente n°18 - Client: Anonyme', '2026-04-16', 'vente', 18, 3, '2026-04-16 06:39:25');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('administrateur','gestionnaire','vendeur') DEFAULT 'vendeur',
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `password`, `role`, `actif`, `created_at`) VALUES
(1, 'Kofi Agbodjan', 'admin@quincastore.tg', 'admin123', 'administrateur', 1, '2026-03-12 22:53:15'),
(2, 'Ama Kpodji', 'gestionnaire@quincastore.tg', 'gestionnaire123', 'gestionnaire', 1, '2026-03-12 22:53:15'),
(3, 'Edem Dossou', 'edem.dossou@quincastore.tg', 'vendeur123', 'vendeur', 1, '2026-03-12 22:53:15'),
(4, 'Abla Mensah', 'abla.mensah@quincastore.tg', 'vendeur123', 'vendeur', 1, '2026-03-12 22:53:15'),
(5, 'Kossi Tchakpelou', 'kossi@quincastore.tg', 'vendeur123', 'vendeur', 1, '2026-03-12 22:53:15');

-- --------------------------------------------------------

--
-- Structure de la table `ventes`
--

CREATE TABLE `ventes` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `client_nom` varchar(150) DEFAULT NULL,
  `montant_total` decimal(12,0) DEFAULT 0,
  `statut` enum('validée','annulée') DEFAULT 'validée',
  `date_vente` timestamp NOT NULL DEFAULT current_timestamp(),
  `client_telephone` varchar(20) DEFAULT NULL,
  `paiement` enum('especes','mobile_money','carte','virement') DEFAULT 'especes',
  `remise` tinyint(4) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ventes`
--

INSERT INTO `ventes` (`id`, `utilisateur_id`, `client_nom`, `montant_total`, `statut`, `date_vente`, `client_telephone`, `paiement`, `remise`) VALUES
(1, 3, 'Kwame Afétor', 55800, 'validée', '2026-03-11 22:53:16', NULL, 'especes', 0),
(2, 4, 'Mawuli Sossah', 31000, 'validée', '2026-03-10 22:53:16', NULL, 'especes', 0),
(3, 3, 'Akua Tsigbe', 108000, 'validée', '2026-03-09 22:53:16', NULL, 'especes', 0),
(4, 5, 'Entreprise BATI', 245000, 'validée', '2026-03-07 22:53:16', NULL, 'especes', 0),
(5, 4, 'Yao Amouzou', NULL, 'validée', '2026-03-05 22:53:16', NULL, 'especes', 0),
(6, 3, NULL, NULL, 'validée', '2026-03-04 22:53:16', NULL, 'especes', 0),
(7, 5, 'Koffi Gbéka', NULL, 'validée', '2026-03-02 22:53:16', NULL, 'especes', 0),
(8, 4, 'mmmmmmmmm', 8000, 'validée', '2026-03-12 22:54:49', NULL, 'especes', 0),
(9, 4, 'mmmmmmmmm', 11400, 'validée', '2026-03-12 23:14:23', NULL, 'especes', 0),
(10, 4, 'shijo', 106400, 'validée', '2026-03-12 23:35:48', NULL, 'especes', 0),
(11, 1, 'samuzl', 10000, 'validée', '2026-04-01 13:10:56', NULL, 'especes', 0),
(12, 1, 'Samuzle', 1121000, 'validée', '2026-04-01 13:15:14', NULL, 'especes', 0),
(13, 1, 'samuel', 15000, 'validée', '2026-04-01 14:57:13', NULL, 'especes', 0),
(14, 5, 'sam', 2000, 'validée', '2026-04-07 08:25:21', '+228 99330517', 'mobile_money', 0),
(15, 1, 'shijo', 9000, 'validée', '2026-04-11 19:18:29', '+22899449731', 'especes', 10),
(16, 1, 'gh', 2500, 'validée', '2026-04-11 20:34:28', '', 'especes', 0),
(17, 1, 'papa nbango', 199500, 'validée', '2026-04-12 21:04:40', '+228 99330517', 'especes', 5),
(18, 3, '', 2975, 'validée', '2026-04-16 06:39:25', '', 'mobile_money', 15);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `articles`
--
ALTER TABLE `articles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`);

--
-- Index pour la table `caisse`
--
ALTER TABLE `caisse`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaction_id` (`transaction_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `depenses_periodiques`
--
ALTER TABLE `depenses_periodiques`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `details_vente`
--
ALTER TABLE `details_vente`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vente_id` (`vente_id`),
  ADD KEY `article_id` (`article_id`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `rappels`
--
ALTER TABLE `rappels`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `articles`
--
ALTER TABLE `articles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT pour la table `caisse`
--
ALTER TABLE `caisse`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `depenses_periodiques`
--
ALTER TABLE `depenses_periodiques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `details_vente`
--
ALTER TABLE `details_vente`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `rappels`
--
ALTER TABLE `rappels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `ventes`
--
ALTER TABLE `ventes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `articles`
--
ALTER TABLE `articles`
  ADD CONSTRAINT `articles_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `caisse`
--
ALTER TABLE `caisse`
  ADD CONSTRAINT `caisse_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `caisse_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `details_vente`
--
ALTER TABLE `details_vente`
  ADD CONSTRAINT `details_vente_ibfk_1` FOREIGN KEY (`vente_id`) REFERENCES `ventes` (`id`),
  ADD CONSTRAINT `details_vente_ibfk_2` FOREIGN KEY (`article_id`) REFERENCES `articles` (`id`);

--
-- Contraintes pour la table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `ventes`
--
ALTER TABLE `ventes`
  ADD CONSTRAINT `ventes_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
