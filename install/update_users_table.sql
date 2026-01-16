-- Adicionar novos campos à tabela usuarios para perfil completo

ALTER TABLE `usuarios` 
ADD COLUMN `nome_completo` VARCHAR(200) NULL AFTER `user`,
ADD COLUMN `email` VARCHAR(200) NULL AFTER `nome_completo`,
ADD COLUMN `telefone` VARCHAR(20) NULL AFTER `email`,
ADD COLUMN `foto` VARCHAR(500) NULL AFTER `telefone`,
ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `last_login`,
ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Atualizar usuários existentes com email padrão baseado no username
UPDATE `usuarios` SET `email` = CONCAT(`user`, '@sistema.local') WHERE `email` IS NULL;

-- Adicionar índice para email
ALTER TABLE `usuarios` ADD UNIQUE KEY `email` (`email`);
