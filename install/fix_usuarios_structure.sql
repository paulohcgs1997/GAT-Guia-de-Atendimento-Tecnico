-- ========================================
-- Correção Rápida: Estrutura Completa da Tabela Usuarios
-- Execute este script se o sistema foi instalado sem as atualizações
-- ========================================

-- Verificar se as colunas já existem antes de adicionar
-- (evita erros se o script for executado múltiplas vezes)

-- 1. Adicionar nome_completo
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS nome_completo VARCHAR(200) NULL AFTER username;

-- 2. Adicionar email
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS email VARCHAR(200) NULL AFTER nome_completo;

-- 3. Adicionar telefone
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS telefone VARCHAR(20) NULL AFTER email;

-- 4. Adicionar foto
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS foto VARCHAR(500) NULL AFTER telefone;

-- 5. Adicionar created_at
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER last_login;

-- 6. Adicionar updated_at
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 7. Adicionar force_password_change
ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS force_password_change TINYINT(1) DEFAULT 0 AFTER status;

-- 8. Verificar/atualizar campo status (se necessário)
-- ALTER TABLE usuarios MODIFY COLUMN status VARCHAR(20) DEFAULT 'active';

-- 9. Atualizar usuários existentes com email padrão (se necessário)
UPDATE usuarios 
SET email = CONCAT(username, '@sistema.local') 
WHERE email IS NULL OR email = '';

-- ========================================
-- Verificação Final
-- ========================================

-- Mostrar estrutura da tabela usuarios
SHOW COLUMNS FROM usuarios;

-- Contar usuários
SELECT 
    COUNT(*) as total_usuarios,
    SUM(CASE WHEN email IS NOT NULL THEN 1 ELSE 0 END) as com_email,
    SUM(CASE WHEN nome_completo IS NOT NULL THEN 1 ELSE 0 END) as com_nome_completo
FROM usuarios;

SELECT '✅ Correção aplicada com sucesso!' AS status;
