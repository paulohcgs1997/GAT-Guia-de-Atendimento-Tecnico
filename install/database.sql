-- GAT - Sistema de Tutoriais
-- Estrutura do Banco de Dados
-- Versão: 1.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Estrutura para tabela `perfil`
-- --------------------------------------------------------

CREATE TABLE `perfil` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NOT NULL COMMENT 'Tipo do perfil',
  `permission` varchar(10) NOT NULL COMMENT 'Formato: ver,editar,aprovar',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `perfil` (`id`, `type`, `permission`) VALUES
(1, 'admin', '1,1,1'),
(2, 'criador', '1,1,0'),
(3, 'departamento', '1,0,1'),
(4, 'colaborador', '1,0,0');

-- --------------------------------------------------------
-- Estrutura para tabela `departaments`
-- --------------------------------------------------------

CREATE TABLE `departaments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` text DEFAULT NULL,
  `src` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `usuarios`
-- --------------------------------------------------------

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  `perfil` int(11) NOT NULL,
  `departamento` int(11) DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user` (`user`),
  KEY `perfil` (`perfil`),
  KEY `idx_user` (`user`),
  KEY `idx_active` (`active`),
  CONSTRAINT `usuarios_ibfk_1` FOREIGN KEY (`perfil`) REFERENCES `perfil` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `hash_login`
-- --------------------------------------------------------

CREATE TABLE `hash_login` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `login_hash` varchar(255) NOT NULL,
  `validity` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `steps`
-- --------------------------------------------------------

CREATE TABLE `steps` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `html` text DEFAULT NULL,
  `src` text DEFAULT NULL,
  `questions` varchar(255) NOT NULL DEFAULT '',
  `last_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accept` tinyint(1) DEFAULT 0,
  `last_accept` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `original_id` int(11) DEFAULT NULL,
  `is_clone` tinyint(1) DEFAULT 0,
  `rejection_reason` text DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `questions`
-- --------------------------------------------------------

CREATE TABLE `questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `text` text NOT NULL,
  `proximo` varchar(50) NOT NULL COMMENT 'ID do próximo step, "next_block" ou "bloco_X"',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `blocos`
-- --------------------------------------------------------

CREATE TABLE `blocos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `id_step` varchar(255) DEFAULT NULL,
  `last_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accept` tinyint(1) DEFAULT 0,
  `last_accept` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `original_id` int(11) DEFAULT NULL,
  `is_clone` tinyint(1) DEFAULT 0,
  `rejection_reason` text DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `departamento` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `services`
-- --------------------------------------------------------

CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `departamento` int(11) NOT NULL,
  `blocos` varchar(255) DEFAULT NULL,
  `word_keys` text DEFAULT NULL,
  `last_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `accept` tinyint(1) DEFAULT 0,
  `last_accept` datetime DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  `original_id` int(11) DEFAULT NULL,
  `is_clone` tinyint(1) DEFAULT 0,
  `rejection_reason` text DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `reject_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_active` (`active`),
  KEY `idx_departamento` (`departamento`),
  CONSTRAINT `services_ibfk_1` FOREIGN KEY (`departamento`) REFERENCES `departaments` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `links`
-- --------------------------------------------------------

CREATE TABLE `links` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `url` text NOT NULL,
  `last_modification` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Estrutura para tabela `system_config`
-- --------------------------------------------------------

CREATE TABLE `system_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `config_type` enum('text','image','file') DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

INSERT INTO `system_config` (`config_key`, `config_value`, `config_type`) VALUES
('system_name', 'GAT - Sistema de Tutoriais', 'text'),
('system_logo', NULL, 'image'),
('system_favicon', NULL, 'image'),
('system_description', 'Sistema de Gestão de Tutoriais', 'text'),
('system_email', NULL, 'text'),
('system_phone', NULL, 'text');

COMMIT;
