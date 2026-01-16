-- Adicionar campo para forçar troca de senha no primeiro login
-- Execute este script no banco de dados para aplicar a atualização

ALTER TABLE `usuarios` 
ADD COLUMN `force_password_change` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Se 1, força usuário a trocar senha no próximo login' 
AFTER `last_login`;

-- Atualizar usuários existentes para não forçar troca de senha
UPDATE `usuarios` SET `force_password_change` = 0 WHERE `force_password_change` IS NULL;
