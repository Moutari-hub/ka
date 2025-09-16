-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mar. 16 sep. 2025 à 14:29
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
-- Base de données : `nigertelecom_missions`
--

-- --------------------------------------------------------

--
-- Structure de la table `commentaires`
--

CREATE TABLE `commentaires` (
  `id` int(11) NOT NULL,
  `mission_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `action` enum('validation','rejet') NOT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_created` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `missions`
--

CREATE TABLE `missions` (
  `id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `type_mission` varchar(100) NOT NULL,
  `zone_mission` varchar(100) NOT NULL,
  `logistique` text DEFAULT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `propose_par` int(11) NOT NULL,
  `statut` enum('En attente','En attente Manager','En attente validation DG','En cours','Lancée','Pris en charge par DF','Pris en charge par RH','Validée par DG','Rejetée') NOT NULL DEFAULT 'En attente',
  `montant_prevu` decimal(15,2) DEFAULT 0.00,
  `montant_utilise` decimal(15,2) DEFAULT 0.00,
  `manager_validation` tinyint(1) DEFAULT NULL,
  `dir_service_validation` tinyint(1) DEFAULT NULL,
  `dg_validation` tinyint(1) DEFAULT NULL,
  `lancement` tinyint(1) DEFAULT 0,
  `commentaire_manager` text DEFAULT NULL,
  `rh_preparer` tinyint(1) DEFAULT NULL,
  `df_validation` tinyint(4) DEFAULT NULL,
  `signature_manager` varchar(255) DEFAULT NULL,
  `commentaire_dir` text DEFAULT NULL,
  `signature_dir` varchar(255) DEFAULT NULL,
  `commentaire_dg` text DEFAULT NULL,
  `signature_dg` varchar(255) DEFAULT NULL,
  `commentaire_rh` text DEFAULT NULL,
  `df_valide` tinyint(1) DEFAULT 0,
  `commentaire_df` text DEFAULT NULL,
  `personnels` text DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `date_proposition` datetime NOT NULL DEFAULT current_timestamp(),
  `df_preparer` tinyint(1) DEFAULT NULL,
  `dg_valide_final` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `missions`
--

INSERT INTO `missions` (`id`, `titre`, `description`, `type_mission`, `zone_mission`, `logistique`, `date_debut`, `date_fin`, `propose_par`, `statut`, `montant_prevu`, `montant_utilise`, `manager_validation`, `dir_service_validation`, `dg_validation`, `lancement`, `commentaire_manager`, `rh_preparer`, `df_validation`, `signature_manager`, `commentaire_dir`, `signature_dir`, `commentaire_dg`, `signature_dg`, `commentaire_rh`, `df_valide`, `commentaire_df`, `personnels`, `service_id`, `date_proposition`, `df_preparer`, `dg_valide_final`) VALUES
(60, 'mission une', '', '', '', 'voiture', '2025-08-29', '2025-08-30', 8, 'En attente', 1212.00, 0.00, 1, 1, NULL, 1, '', 1, NULL, NULL, 'jrgh', NULL, '', NULL, NULL, 1, '', '16', 1, '2025-08-26 18:19:53', NULL, 1),
(61, 'mission de service', 'on doit faire un depannage', 'depannage', 'zinder', 'voiture', '2025-08-26', '2025-08-31', 15, '', 114.00, 0.00, 1, 1, NULL, 1, '', 1, NULL, NULL, 'jg', NULL, 'ok', NULL, '', 1, '', '6', 1, '2025-08-26 19:01:26', 0, 1),
(62, 'mission e', 'truc', 'audit', 'madac', 'cwx', '2025-08-26', '2025-08-28', 15, '', 55.00, 0.00, 1, 1, NULL, 1, '', 1, NULL, NULL, '', NULL, '', NULL, '', 1, '', '18', 1, '2025-08-26 19:12:53', 0, 1),
(63, 'zerezf', 'ze', 'fez', 'zer', NULL, '0000-00-00', '0000-00-00', 15, 'Rejetée', 0.00, 0.00, 0, NULL, NULL, 0, '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0, NULL, NULL, 1, '2025-08-26 20:00:53', NULL, NULL),
(64, 'mission detresse', 'travail', 'instalation', 'katako', 'train', '2025-09-05', '2025-08-28', 12, '', 1450.00, 0.00, 1, 1, NULL, 1, '', 1, NULL, NULL, '', NULL, 'ok', NULL, '', 1, '', '5', 2, '2025-08-31 23:41:13', 0, 1),
(65, 'DAF ESSAIE', 'reception de recette', 'reception', 'tunise', 'TRAIN', '2025-09-11', '2025-09-20', 26, 'En cours', 4555520.00, 0.00, 1, 1, NULL, 1, 'OK', 1, NULL, NULL, '', NULL, '', NULL, '', 1, '', '19,12', 3, '2025-09-02 17:39:23', 0, 1),
(66, 'formation', 'mettre a niveau le nouveau personnel', 'formation nouvelle-recrue', 'zinder', NULL, '0000-00-00', '0000-00-00', 26, 'Lancée', 0.00, 0.00, 1, 1, NULL, 1, '', NULL, NULL, NULL, '', NULL, '', NULL, NULL, 0, NULL, NULL, 3, '2025-09-03 15:06:02', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `mission_personnels`
--

CREATE TABLE `mission_personnels` (
  `mission_id` int(11) NOT NULL,
  `personnel_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `mission_id` int(11) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_envoi` datetime NOT NULL DEFAULT current_timestamp(),
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `mission_id`, `role_id`, `message`, `lu`, `date_envoi`, `date_creation`) VALUES
(2, 0, NULL, 3, 'Mission validée par Manager, en attente Directeur de Service.', 0, '2025-08-04 14:19:26', '2025-08-04 14:19:26'),
(3, 0, NULL, 3, 'Mission validée par Manager, en attente Directeur de Service.', 1, '2025-08-04 14:32:23', '2025-08-04 14:32:23'),
(4, 0, NULL, 3, 'Mission validée par Manager, en attente Directeur de Service.', 1, '2025-08-04 14:32:45', '2025-08-04 14:32:45'),
(5, 0, NULL, 4, 'Mission validée par Directeur de Service, en attente DG.', 1, '2025-08-04 14:33:15', '2025-08-04 14:33:15'),
(6, 0, NULL, 5, 'Mission lancée par DG, en attente RH.', 0, '2025-08-04 14:33:31', '2025-08-04 14:33:31'),
(7, 0, NULL, 6, 'Mission préparée par RH, en attente DF.', 1, '2025-08-04 14:33:50', '2025-08-04 14:33:50'),
(8, 0, NULL, 1, 'Mission validée par DF, en cours.', 1, '2025-08-04 14:34:12', '2025-08-04 14:34:12'),
(9, 0, NULL, 4, 'Mission validée par Directeur de Service, en attente DG.', 0, '2025-08-04 15:29:28', '2025-08-04 15:29:28'),
(10, 0, NULL, 5, 'Mission lancée par DG, en attente RH.', 0, '2025-08-04 15:29:42', '2025-08-04 15:29:42'),
(11, 0, NULL, 6, 'Mission préparée par RH, en attente DF.', 0, '2025-08-04 15:30:09', '2025-08-04 15:30:09'),
(12, 0, NULL, 1, 'Mission validée par DF, en cours.', 1, '2025-08-04 15:30:26', '2025-08-04 15:30:26'),
(13, 0, NULL, 3, 'Mission validée par Manager, en attente Directeur de Service.', 1, '2025-08-04 15:43:57', '2025-08-04 15:43:57'),
(14, 0, NULL, 4, 'Mission validée par Directeur de Service, en attente DG.', 0, '2025-08-04 15:44:13', '2025-08-04 15:44:13'),
(15, 0, NULL, 5, 'Mission lancée par DG, en attente RH.', 1, '2025-08-04 15:45:34', '2025-08-04 15:45:34'),
(16, 4, NULL, 6, 'Mission \',ujy\' préparée par RH, en attente de validation financière.', 0, '2025-08-04 15:46:47', '2025-08-04 15:46:47'),
(17, 0, NULL, 4, 'Mission validée par Directeur de Service, en attente DG.', 0, '2025-08-04 15:52:57', '2025-08-04 15:52:57'),
(18, 0, NULL, 5, 'Mission lancée par DG, en attente RH.', 0, '2025-08-04 15:53:34', '2025-08-04 15:53:34'),
(19, 4, NULL, 6, 'Mission \';jb\' préparée par RH, en attente de validation financière.', 0, '2025-08-04 15:54:07', '2025-08-04 15:54:07'),
(20, 0, NULL, 4, 'Mission validée par Directeur de Service, en attente DG.', 0, '2025-08-05 17:57:40', '2025-08-05 17:57:40'),
(21, 0, NULL, 3, 'Mission validée par Manager, en attente Directeur de Service.', 0, '2025-08-08 15:51:24', '2025-08-08 15:51:24'),
(22, 0, NULL, 4, 'Mission validée par Directeur de Service, en attente DG.', 0, '2025-08-08 15:52:51', '2025-08-08 15:52:51'),
(23, 0, NULL, 5, 'Mission lancée par DG, en attente RH.', 0, '2025-08-08 15:53:09', '2025-08-08 15:53:09'),
(24, 0, NULL, 6, 'Mission préparée par RH, en attente DF.', 0, '2025-08-08 15:53:28', '2025-08-08 15:53:28'),
(25, 0, NULL, 1, 'Mission validée par DF, en cours.', 0, '2025-08-08 15:53:56', '2025-08-08 15:53:56'),
(26, 0, NULL, 5, 'Mission lancée par DG, en attente RH.', 0, '2025-08-08 17:47:28', '2025-08-08 17:47:28'),
(27, 0, NULL, 6, 'Mission préparée par RH, en attente DF.', 0, '2025-08-08 17:47:43', '2025-08-08 17:47:43'),
(28, 0, NULL, 1, 'Mission validée par DF, en cours.', 0, '2025-08-08 17:47:57', '2025-08-08 17:47:57'),
(29, 0, NULL, 3, 'Mission validée par Manager, en attente Directeur de Service.', 0, '2025-08-11 17:37:31', '2025-08-11 17:37:31'),
(30, 0, NULL, 4, 'Mission validée par Directeur de Service, en attente DG.', 0, '2025-08-11 17:38:33', '2025-08-11 17:38:33'),
(31, 0, NULL, 5, 'Mission lancée par DG, en attente RH.', 0, '2025-08-11 17:55:41', '2025-08-11 17:55:41'),
(32, 0, NULL, 1, 'Mission validée par DF, en cours.', 0, '2025-08-11 19:01:16', '2025-08-11 19:01:16'),
(33, 0, NULL, 5, 'Mission validée par DG (proposée par RH), en attente du traitement RH.', 0, '2025-08-13 14:11:14', '2025-08-13 14:11:14'),
(34, 0, NULL, 5, 'Mission validée par DG (proposée par RH), en attente du traitement RH.', 0, '2025-08-13 14:11:25', '2025-08-13 14:11:25'),
(35, 0, NULL, 5, 'Mission validée par DG (proposée par RH), en attente du traitement RH.', 0, '2025-08-13 14:13:10', '2025-08-13 14:13:10'),
(36, 0, NULL, 5, 'Mission validée par DG, en attente RH.', 0, '2025-08-13 16:10:09', '2025-08-13 16:10:09');

-- --------------------------------------------------------

--
-- Structure de la table `personnels`
--

CREATE TABLE `personnels` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `poste` varchar(100) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `personnels`
--

INSERT INTO `personnels` (`id`, `nom`, `prenom`, `poste`, `service_id`, `email`, `telephone`) VALUES
(1, 'kk', 'kk', 'nn', 1, 'nn@gmail.com', 'eeee'),
(5, 'issaka', 'bala', 'operateur', 2, 'th@gmail.com', 'hgd'),
(6, 'idrissa', 'ismael', 'consultant', 3, 'sfhj@gmail.com', 'fsh'),
(7, 'Albert', 'sani', 'tecnicien', 2, 'rht@gmail.com', 'fdj'),
(9, 'ytuh', 'rh', 'hr', 1, 'rhg@gmail.com', 'fs'),
(10, 'david', 'steev', 'tecnicien', 2, 'tttt@gmail.com', ''),
(11, 'n,', 'v', 'vb', 1, 'b@gmail.com', ''),
(12, 'cherif', 'GHali', 'expert C', 3, 'fg@gmail.com', ''),
(15, 'h,ghn', 'gn', 'fgn', 1, 'gngn@gmail.com', 'fgn'),
(16, 'Ashley', 'karim', 'technicien dev', 1, 'yatdf@gmail.com', 'zdg'),
(17, 'hdncbNBCV', 'NBCN', 'AC', 1, 'BB@gmail.com', '66'),
(18, 'gv', 'gv', 'h', 1, 'hhh@gmail.com', '566'),
(19, 'Abarchi', 'nouhou', 'comptable', 3, 'nnnn@gmail.com', '999');

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `nom`) VALUES
(1, 'Chef de Service'),
(2, 'Manager'),
(3, 'Directeur de Service'),
(4, 'Directeur Général'),
(5, 'RH'),
(6, 'Directeur Financier');

-- --------------------------------------------------------

--
-- Structure de la table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `services`
--

INSERT INTO `services` (`id`, `nom`) VALUES
(2, 'DAS'),
(1, 'DDN'),
(3, 'DF');

-- --------------------------------------------------------

--
-- Structure de la table `types_mission`
--

CREATE TABLE `types_mission` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `nom_type` varchar(255) NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `types_mission`
--

INSERT INTO `types_mission` (`id`, `service_id`, `nom_type`, `date_creation`) VALUES
(1, 1, 'maintenance', '2025-08-11 16:10:30');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `matricule` varchar(50) DEFAULT NULL,
  `service_id` int(11) DEFAULT NULL,
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `role_id`, `matricule`, `service_id`, `date_creation`) VALUES
(3, 'dir', 'ser', 'DASDG@gmail.com', '$2y$10$FKTBXfH2FL2Xnf.JRegvaO.p6OhBGLrU4ngC.6zHSvLfPqE.Cxoi2', 3, NULL, 2, '2025-07-25 15:57:25'),
(8, 'rh', 'rh', 'RH@gmail.com', '$2y$10$FXQ/j2cPfjjlRO7d5DH4luv9RG2U4kMufSbE5GS.xdjK7Tbp6sYUa', 5, NULL, NULL, '2025-07-29 11:00:57'),
(12, 'kanis directeur (DDN)', 'niska directeur DDN', 'DASCHEF@gmail.com', '$2y$10$yVdfsx6FW8GkuHRA.WBY/eZZSK1B.7H1iZs0mwn3ibygQ.4kNvpMu', 1, NULL, 2, '2025-08-07 19:00:13'),
(13, 'kanis(MANAGER)', 'kanis(managere)ddn', 'DDNMANA@gmail.com', '$2y$10$GScA2PPcbpZ1tc6iihvHDut00BJ2r8Ps3fp8R9LlBJ7jjOC6Yh8ve', 2, NULL, 1, '2025-08-07 19:06:52'),
(14, 'kanis', 'kanis', 'DDNDG@gmail.com', '$2y$10$4GQr5Stmz8sVYZXFMqTAzeVhbDpqBD6NyxBP5UhpVGhnlUyVN7jwe', 3, NULL, 1, '2025-08-07 19:07:28'),
(15, 'kanis chef ddn', 'niska chef ddn', 'DDNCHEF@gmail.com', '$2y$10$c236uXOkbZS8Q7xtcQ0zQuTedKizjgw5MR5DRnWMSqm5uYfGn/JM.', 1, NULL, 1, '2025-08-08 15:38:27'),
(16, 'kanis', 'kanis', 'DASMANA@gmail.com', '$2y$10$Meg9JdUx.IKQqKIMfPTKKeBEvn3fQawByHlvvIg.qs8uFf0y4Kvw2', 2, NULL, 2, '2025-08-08 15:50:48'),
(18, 'rh1', '2', 'df1@gmail.com', '$2y$10$WGi29eGRkhiECfMdlzH6TeHRxftduJbq6tdhctmyoFheyC1UecKBG', 5, NULL, NULL, '2025-08-25 22:55:55'),
(19, 'df', 'df', 'df@gmail.com', '$2y$10$fowf.ocE/5adWC.pG5ga9uXFk.Jg95B4QWTmBzPlJBOnPjJtTErdq', 6, NULL, NULL, '2025-08-25 23:04:58'),
(21, 'DF4', 'DF4', 'DF2@gmail.com', '$2y$10$31Eft3bjrF2PAay24cLfz.qLc4Xgts4Dow.JbhLQYRuZ/HWodtHQ2', 6, NULL, NULL, '2025-08-26 01:52:15'),
(23, 'DG', 'Temp', 'temp@domain.com', 'password_hash', 4, NULL, NULL, '2025-08-26 19:51:53'),
(24, 'moctar', 'moctar', 'dg8@gmail.com', '$2y$10$YAe32XlVv4EP6en70qq/PuADSzJROvP9PpqbrwBEX7xLdeX44j8Hq', 4, NULL, NULL, '2025-08-26 19:58:16'),
(25, 'dg', 'dg', 'dg7@gmail.com', '$2y$10$btczkatkzos4BtK9Jq9XoewdDx0kNEB.Bo.1ZD.P1ehEp6U8AjdyO', 4, 'DG001', NULL, '2025-08-26 19:59:53'),
(26, 'IDE', 'KASSUM', 'DAFCHEF@gmail.com', '$2y$10$7TM.lN6QcPwKdkDTuT3UQeEkqR1rC6wIFqLkxjZx7rqXnj7zqg9M2', 1, NULL, 3, '2025-09-02 17:37:45'),
(27, 'KOREE', 'steev', 'DAFMANA@gmail.com', '$2y$10$sMS/jEr6wPCQ1pk/pOkdGumJ9hdEWpk9/hsWkTVN1ecmd6/bhJ6iK', 2, NULL, 3, '2025-09-02 17:41:08'),
(28, 'karim', 'big', 'DAFDG@gmail.com', '$2y$10$j6RQVO/gFZeF4PmT/qGZfepSVZXamaZRCXCYcL2wY/zTC5YlW8aL.', 3, NULL, 3, '2025-09-02 17:43:49');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD PRIMARY KEY (`id`),
  ADD KEY `mission_id` (`mission_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `missions`
--
ALTER TABLE `missions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `propose_par` (`propose_par`),
  ADD KEY `fk_missions_service` (`service_id`);

--
-- Index pour la table `mission_personnels`
--
ALTER TABLE `mission_personnels`
  ADD PRIMARY KEY (`mission_id`,`personnel_id`),
  ADD KEY `personnel_id` (`personnel_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `fk_notifications_missions` (`mission_id`);

--
-- Index pour la table `personnels`
--
ALTER TABLE `personnels`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `types_mission`
--
ALTER TABLE `types_mission`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_type_service` (`service_id`,`nom_type`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `matricule` (`matricule`),
  ADD KEY `fk_role` (`role_id`),
  ADD KEY `fk_utilisateurs_service` (`service_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `commentaires`
--
ALTER TABLE `commentaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT pour la table `missions`
--
ALTER TABLE `missions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT pour la table `personnels`
--
ALTER TABLE `personnels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT pour la table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `types_mission`
--
ALTER TABLE `types_mission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `commentaires`
--
ALTER TABLE `commentaires`
  ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `missions`
--
ALTER TABLE `missions`
  ADD CONSTRAINT `fk_missions_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `missions_ibfk_1` FOREIGN KEY (`propose_par`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `mission_personnels`
--
ALTER TABLE `mission_personnels`
  ADD CONSTRAINT `mission_personnels_ibfk_1` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mission_personnels_ibfk_2` FOREIGN KEY (`personnel_id`) REFERENCES `personnels` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_missions` FOREIGN KEY (`mission_id`) REFERENCES `missions` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `types_mission`
--
ALTER TABLE `types_mission`
  ADD CONSTRAINT `types_mission_ibfk_1` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD CONSTRAINT `fk_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `fk_utilisateurs_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
