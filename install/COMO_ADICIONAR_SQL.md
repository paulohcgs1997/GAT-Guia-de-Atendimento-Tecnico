# 🗄️ Como Adicionar Atualizações SQL ao Sistema

## 📁 Localização

Coloque os arquivos SQL de atualização em:
```
install/update_sql/
```

---

## ✅ Tipos de SQL Detectados Automaticamente

O sistema **detecta automaticamente** os seguintes tipos de comandos SQL:

### 1️⃣ **ALTER TABLE ADD COLUMN**
Adiciona novas colunas a tabelas existentes.

**Exemplo:**
```sql
-- Adicionar campo de status ao usuário
ALTER TABLE usuarios ADD COLUMN status VARCHAR(20) DEFAULT 'active';
```

### 2️⃣ **CREATE TABLE**
Cria novas tabelas no banco de dados.

**Exemplo:**
```sql
-- Criar tabela de notificações
CREATE TABLE IF NOT EXISTS notificacoes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    mensagem TEXT NOT NULL,
    lida TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 3️⃣ **INSERT com WHERE NOT EXISTS**
Scripts de correção/manutenção que inserem dados apenas se não existirem.

**Exemplo:**
```sql
-- Garantir que o branch padrão existe
INSERT INTO system_config (config_key, config_value)
SELECT 'github_branch', 'main'
WHERE NOT EXISTS (
    SELECT 1 FROM system_config WHERE config_key = 'github_branch'
);
```

### 4️⃣ **Scripts de Verificação**
Arquivos com nome contendo: `verificar`, `diagnostico`, `check`, `fix`, `correcao`

**ATENÇÃO:** Scripts apenas com **SELECT** não devem estar em `update_sql/`. Coloque-os na raiz do projeto.

---

## ❌ O que NÃO colocar em update_sql/

### ❌ Scripts de Consulta (SELECT apenas)
```sql
-- ❌ NÃO colocar em update_sql/
SELECT * FROM usuarios;
SHOW TABLES;
```

**Onde colocar:** Raiz do projeto (`verificar_sistema.sql`)

### ❌ Script de Instalação Inicial
```sql
-- ❌ database.sql NUNCA vai para update_sql/
```

**Onde fica:** `install/database.sql` (instalação inicial apenas)

### ❌ Arquivos de Backup
```sql
-- ❌ NÃO colocar backups em update_sql/
```

**Onde colocar:** `backups/`

---

## 📝 Convenção de Nomenclatura

Use nomes descritivos que indicam o que a atualização faz:

### ✅ Bons Exemplos:
```
add_force_password_change.sql
update_status_field.sql
update_users_table.sql
create_notifications_table.sql
fix_user_permissions.sql
add_email_verification.sql
```

### ❌ Evite:
```
update.sql              (muito genérico)
teste.sql               (não é atualização)
backup_20250117.sql     (não é atualização)
verificar_sistema.sql   (é diagnóstico, não atualização)
```

---

## 📋 Estrutura Recomendada de Arquivo SQL

```sql
-- ========================================
-- Descrição: Adicionar campo de status ao usuário
-- Autor: Seu Nome
-- Data: 17/01/2025
-- ========================================

-- Verificar se a coluna já existe antes de adicionar
-- (evita erros se o script for executado múltiplas vezes)

ALTER TABLE usuarios 
ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active' 
AFTER perfil;

-- Adicionar índice se necessário
-- CREATE INDEX idx_status ON usuarios(status);

-- Comentários explicativos
-- Este campo permite marcar usuários como ativos, inativos ou suspensos
```

---

## 🔄 Processo de Detecção

O sistema verifica automaticamente:

1. **Lê todos os arquivos** `.sql` em `install/update_sql/`
2. **Analisa o conteúdo** procurando por:
   - `ALTER TABLE ... ADD COLUMN`
   - `CREATE TABLE`
   - `INSERT ... WHERE NOT EXISTS`
3. **Compara com o banco** atual:
   - Tabelas existem?
   - Colunas existem?
4. **Lista as atualizações** necessárias
5. **Permite aplicar** via interface

---

## 🖥️ Como Verificar se foi Detectado

### Via Interface (Recomendado)
1. Acesse: **Configurações → Verificador de BD**
2. Clique em **Verificar Agora**
3. Veja as atualizações disponíveis

### Via Diagnóstico
Acesse: `http://seu-dominio/diagnostico.php`

### Via Logs
Verifique o error_log do PHP para mensagens do database_checker.php

---

## ⚠️ Importante

### Testando Atualizações SQL

Antes de colocar em produção:

1. **Teste em desenvolvimento** primeiro
2. **Faça backup** do banco de dados
3. **Verifique a sintaxe** SQL
4. **Documente as mudanças**

### Ordem de Execução

Se múltiplas atualizações estão pendentes:
- Todas são **exibidas juntas**
- Você pode **aplicar todas de uma vez**
- Ou aplicar **uma por uma**

### Prioridades

O sistema define prioridades automaticamente:
- **Alta:** Atualizações de `status`, `users`
- **Média:** Maioria das atualizações
- **Baixa:** Scripts de verificação/diagnóstico

---

## 🛠️ Exemplos Práticos

### Exemplo 1: Adicionar Nova Coluna
```sql
-- Arquivo: add_email_verified.sql
-- Descrição: Adicionar verificação de email

ALTER TABLE usuarios 
ADD COLUMN email_verified TINYINT(1) DEFAULT 0 
AFTER email;

ALTER TABLE usuarios 
ADD COLUMN email_verified_at TIMESTAMP NULL 
AFTER email_verified;
```

### Exemplo 2: Criar Nova Tabela
```sql
-- Arquivo: create_activity_log.sql
-- Descrição: Criar tabela de log de atividades

CREATE TABLE IF NOT EXISTS activity_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Exemplo 3: Script de Correção
```sql
-- Arquivo: fix_missing_config.sql
-- Descrição: Garantir configurações essenciais

-- Adicionar github_branch se não existir
INSERT INTO system_config (config_key, config_value)
SELECT 'github_branch', 'main'
WHERE NOT EXISTS (
    SELECT 1 FROM system_config WHERE config_key = 'github_branch'
);

-- Adicionar sistema_version se não existir
INSERT INTO system_config (config_key, config_value)
SELECT 'sistema_version', '1.0.0'
WHERE NOT EXISTS (
    SELECT 1 FROM system_config WHERE config_key = 'sistema_version'
);
```

---

## 📚 Referências

**Arquivos do Sistema:**
- `src/php/database_checker.php` - Detecta atualizações pendentes
- `src/php/apply_migration.php` - Aplica as atualizações SQL
- `viwer/gestao_configuracoes.php` - Interface de configuração

**Documentação:**
- `GUIA_ATUALIZACAO.md` - Sistema de atualização completo
- `README.md` - Documentação geral

---

## 🆘 Problemas Comuns

### "Minha atualização não aparece"

**Causas possíveis:**
1. ❌ Arquivo tem apenas `SELECT` (não modifica estrutura)
2. ❌ Sintaxe SQL incorreta
3. ❌ Arquivo não está em `install/update_sql/`
4. ❌ Extensão não é `.sql`
5. ❌ Atualização já foi aplicada

**Solução:**
- Execute: `http://seu-dominio/diagnostico.php`
- Verifique os logs PHP
- Confirme que o arquivo tem `ALTER TABLE` ou `CREATE TABLE`

### "Erro ao aplicar atualização"

**Causas possíveis:**
1. ❌ Sintaxe SQL incorreta
2. ❌ Tabela não existe
3. ❌ Coluna já existe
4. ❌ Tipo de dado incompatível

**Solução:**
- Teste o SQL manualmente no phpMyAdmin
- Adicione `IF NOT EXISTS` ou `ADD COLUMN IF NOT EXISTS`
- Verifique o error_log

---

**Última atualização:** 17/01/2025
