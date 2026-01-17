# 🆘 Guia de Solução de Problemas - Instalação

## ❌ Erro: "Unknown column 'nome_completo'"

### 🔍 Sintoma:
```
Fatal error: Uncaught mysqli_sql_exception: Unknown column 'nome_completo' in 'SELECT'
```

### 📋 Causa:
O sistema foi instalado mas as **atualizações SQL** em `install/update_sql/` não foram aplicadas automaticamente.

### ✅ Solução Rápida:

#### Opção 1: Via phpMyAdmin (Recomendado)
1. Acesse o **phpMyAdmin** do seu servidor
2. Selecione o banco de dados do GAT
3. Vá em **SQL**
4. Copie e cole o conteúdo do arquivo: [`install/fix_usuarios_structure.sql`](../install/fix_usuarios_structure.sql)
5. Clique em **Executar**
6. Atualize a página do sistema

#### Opção 2: Via Interface do Sistema
1. Acesse: `http://seu-dominio/viwer/gestao_configuracoes.php`
2. Vá na aba: **Verificador de Banco de Dados**
3. Clique em **Verificar Agora**
4. Clique em **Aplicar Atualizações**

#### Opção 3: Via Terminal (Linux/SSH)
```bash
mysql -u seu_usuario -p seu_banco < install/fix_usuarios_structure.sql
```

---

## ❌ Erro: Instalação não aplicou atualizações

### 🔍 Sintoma:
Sistema instalado mas faltam campos na tabela `usuarios`

### 📋 Causa:
A pasta `install/update_sql/` estava vazia ou os arquivos não foram lidos

### ✅ Solução:

1. **Verificar arquivos SQL:**
```bash
# Linux/Mac
ls -la install/update_sql/

# Windows PowerShell
Get-ChildItem install/update_sql/
```

Deve mostrar:
- `add_force_password_change.sql`
- `update_status_field.sql`
- `update_users_table.sql`

2. **Aplicar manualmente:**
Execute cada arquivo SQL no banco de dados na ordem:
```sql
-- 1. Estrutura básica
mysql -u usuario -p banco < install/update_sql/update_users_table.sql

-- 2. Campo de status
mysql -u usuario -p banco < install/update_sql/update_status_field.sql

-- 3. Forçar troca de senha
mysql -u usuario -p banco < install/update_sql/add_force_password_change.sql
```

---

## ❌ Erro: "Access denied for user"

### 🔍 Sintoma:
```
Access denied for user 'usuario'@'localhost'
```

### ✅ Solução:

1. **Verificar credenciais** em `src/config/conexao.php`
2. **Verificar permissões** do usuário MySQL:
```sql
SHOW GRANTS FOR 'usuario'@'localhost';
```
3. **Conceder permissões** se necessário:
```sql
GRANT ALL PRIVILEGES ON nome_banco.* TO 'usuario'@'localhost';
FLUSH PRIVILEGES;
```

---

## ❌ Erro: "Cannot connect to database"

### 🔍 Sintoma:
Sistema não consegue conectar ao banco de dados

### ✅ Solução:

1. **Verificar se MySQL está rodando:**
```bash
# Linux
sudo systemctl status mysql

# Windows (como Administrador)
net start MySQL
```

2. **Verificar configuração:**
- Arquivo: `src/config/conexao.php`
- Verificar: host, nome do banco, usuário, senha

3. **Testar conexão manualmente:**
```bash
mysql -h localhost -u usuario -p
```

---

## ❌ Erro: Permissões de arquivo

### 🔍 Sintoma:
```
Permission denied
```

### ✅ Solução (Linux/Ubuntu):
```bash
# Dar permissão de escrita
sudo chown -R www-data:www-data /caminho/para/gat/
sudo chmod -R 755 /caminho/para/gat/

# Permissões específicas
sudo chmod 755 uploads/
sudo chmod 755 src/uploads/
sudo chmod 755 backups/
sudo chmod 644 src/config/conexao.php
```

---

## 🔍 Verificação Completa do Sistema

Execute o script de diagnóstico:
```
http://seu-dominio/diagnostico.php
```

Ele verificará:
- ✅ Arquivos de configuração
- ✅ Diretórios e permissões
- ✅ Conexão com banco de dados
- ✅ Estrutura das tabelas
- ✅ Configurações do GitHub

---

## 📝 Logs e Debug

### Ver logs do PHP:
```bash
# Linux (Apache)
tail -f /var/log/apache2/error.log

# Linux (Nginx)
tail -f /var/log/nginx/error.log

# Windows (XAMPP)
# Verifique: C:\xampp\apache\logs\error.log
```

### Ver logs do instalador:
Os logs são gravados no error_log do PHP durante a instalação.

---

## 🔄 Reinstalação Completa

Se nada funcionar, reinstale do zero:

1. **Backup do banco (se houver dados importantes):**
```bash
mysqldump -u usuario -p banco_gat > backup_gat.sql
```

2. **Deletar banco e recriar:**
```sql
DROP DATABASE banco_gat;
CREATE DATABASE banco_gat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. **Deletar arquivos de configuração:**
```bash
rm src/config/conexao.php
rm src/config/github_config.php
rm install/.installed
```

4. **Reinstalar:**
Acesse: `http://seu-dominio/install/`

---

## 📞 Estrutura Esperada Após Instalação

### Arquivos que devem existir:
- ✅ `src/config/conexao.php`
- ✅ `src/config/github_config.php`
- ✅ `install/.installed`

### Tabelas no banco de dados:
- ✅ `usuarios` (com campos: nome_completo, email, telefone, foto, etc.)
- ✅ `perfil`
- ✅ `departamentos`
- ✅ `blocos`
- ✅ `system_config`
- ✅ `servicos`
- ✅ `steps`
- ✅ `questions`
- ✅ E outras...

### Verificar estrutura da tabela usuarios:
```sql
SHOW COLUMNS FROM usuarios;
```

**Campos esperados:**
- id
- username (ou user)
- password
- **nome_completo** ← DEVE EXISTIR
- **email** ← DEVE EXISTIR
- **telefone**
- **foto**
- perfil
- status
- force_password_change
- last_login
- **created_at**
- **updated_at**

---

## 📚 Arquivos de Referência

- [`install/fix_usuarios_structure.sql`](../install/fix_usuarios_structure.sql) - Correção rápida
- [`diagnostico.php`](../diagnostico.php) - Diagnóstico do sistema
- [`verificar_sistema.sql`](../verificar_sistema.sql) - Verificação manual
- [`COMO_ADICIONAR_SQL.md`](../COMO_ADICIONAR_SQL.md) - Guia de SQL

---

**Última atualização:** 17/01/2025

## 🆘 Ainda com problemas?

1. Execute o diagnóstico: `http://seu-dominio/diagnostico.php`
2. Verifique os logs do PHP
3. Consulte a documentação no GitHub
4. Abra uma issue com:
   - Mensagem de erro completa
   - Versão do PHP (`php -v`)
   - Versão do MySQL (`mysql --version`)
   - Sistema operacional
