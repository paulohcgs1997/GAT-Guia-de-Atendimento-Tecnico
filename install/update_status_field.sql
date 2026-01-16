-- Adiciona campo de status para gerenciar fluxo de aprovação
-- Status: 'draft' (rascunho), 'pending' (enviado para análise), 'approved' (aprovado), 'rejected' (rejeitado)

-- Adicionar campo status na tabela blocos (se não existir)
SET @dbname = DATABASE();
SET @tablename = 'blocos';
SET @columnname = 'status';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ENUM(''draft'', ''pending'', ''approved'', ''rejected'') DEFAULT ''draft'' AFTER `accept`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Adicionar campo status na tabela services (se não existir)
SET @tablename = 'services';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE `', @tablename, '` ADD COLUMN `', @columnname, '` ENUM(''draft'', ''pending'', ''approved'', ''rejected'') DEFAULT ''draft'' AFTER `accept`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Atualizar registros existentes baseado no campo accept (apenas se a coluna existe)
UPDATE `blocos` SET `status` = 'approved' WHERE `accept` = 1 AND `status` = 'draft';
UPDATE `blocos` SET `status` = 'pending' WHERE `accept` = 0 AND `rejection_reason` IS NULL AND `status` = 'draft';
UPDATE `blocos` SET `status` = 'rejected' WHERE `rejection_reason` IS NOT NULL AND `status` = 'draft';

UPDATE `services` SET `status` = 'approved' WHERE `accept` = 1 AND `status` = 'draft';
UPDATE `services` SET `status` = 'pending' WHERE `accept` = 0 AND `rejection_reason` IS NULL AND `status` = 'draft';
UPDATE `services` SET `status` = 'rejected' WHERE `rejection_reason` IS NOT NULL AND `status` = 'draft';
