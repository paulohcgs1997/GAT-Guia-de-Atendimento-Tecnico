# 📋 Guia de Atualização e Restauração do Sistema

## 🔄 Sistema de Atualização Automática

O sistema agora possui um robusto mecanismo de atualização que:

### ✅ **Arquivos Protegidos** (Nunca são sobrescritos)
- `src/config/conexao.php` - Configuração do banco de dados
- `src/config/github_config.php` - Token e configurações do GitHub  
- `uploads/` - Avatares de usuários
- `src/uploads/` - Mídia do sistema (departamentos, tutoriais, steps)
- `backups/` - Todos os backups do sistema
- `.git/` - Repositório Git (se existir)
- `.last_update` - Informações da última atualização
- `version.json` - Informações de versão

### 🔄 **Processo de Atualização**

1. **Backup Automático** - Antes de qualquer atualização, um backup completo é criado automaticamente
2. **Remoção Segura** - Todos os arquivos antigos são removidos (EXCETO os protegidos)
3. **Instalação Nova** - Os arquivos da nova versão são instalados
4. **Migração de Banco** - SQLs de atualização são aplicados automaticamente
5. **Verificação** - Sistema valida a integridade pós-atualização

### ⚠️ **Importante**

Após atualização do servidor, se a seleção de branch não estiver funcionando:

1. Acesse: **Configurações → Atualizações do Sistema**
2. Clique no botão de **Configurações Avançadas** (⚙️)
3. Selecione o branch desejado novamente
4. Clique em **Salvar**

O sistema armazena a seleção de branch no banco de dados (`system_config` → `github_branch`), então ela deve persistir entre atualizações.

---

## 🔙 Sistema de Restauração de Backup

### **Criando um Backup Manual**

1. Acesse: **Configurações → Gerenciamento de Backups**
2. Clique em **Criar Backup Manual**
3. Aguarde a conclusão (pode levar alguns minutos)
4. O backup será salvo em `backups/backup_manual_YYYY-MM-DD_HH-MM-SS.zip`

### **Restaurando um Backup**

⚠️ **ATENÇÃO**: A restauração substitui TODOS os arquivos do sistema!

1. Acesse: **Configurações → Gerenciamento de Backups**
2. Localize o backup desejado na lista
3. Clique em **Restaurar**
4. Digite exatamente: `RESTAURAR` (em maiúsculas)
5. Aguarde o processo (pode levar vários minutos)

**O sistema irá:**
- ✅ Criar um backup de segurança do estado atual
- ✅ Extrair o backup selecionado  
- ✅ Preservar configurações (`conexao.php`, `github_config.php`)
- ✅ Preservar arquivos de mídia (`uploads/`)
- ✅ Preservar backups existentes
- ✅ Substituir todos os outros arquivos
- ✅ Recarregar a página automaticamente

### **Backups Automáticos**

O sistema cria backups automáticos:
- ✅ Antes de cada atualização
- ✅ Antes de cada restauração (backup de segurança)

**Retenção**: Apenas os **3 backups mais recentes** são mantidos. Os mais antigos são excluídos automaticamente.

---

## 🛠️ **Resolução de Problemas**

### Seleção de Branch não funciona após atualização

**Causa**: A configuração de branch está no banco de dados, não no arquivo `github_config.php`.

**Solução**:
```sql
-- Verificar branch atual no banco
SELECT * FROM system_config WHERE config_key = 'github_branch';

-- Alterar manualmente se necessário
UPDATE system_config SET config_value = 'seu-branch' WHERE config_key = 'github_branch';
```

Ou pela interface:
1. **Configurações → Atualizações → Configurações Avançadas (⚙️)**
2. Selecionar o branch desejado
3. Salvar

### Erro: "Token do GitHub não configurado"

**Solução**:
1. Verifique se o arquivo `src/config/github_config.php` existe
2. Verifique se o token está definido corretamente
3. Se necessário, gere um novo token em: https://github.com/settings/tokens

### Backup falhou ou corrompido

**Solução**:
1. Verifique se há espaço em disco suficiente
2. Verifique permissões da pasta `backups/` (deve ser 755)
3. Consulte o log: `backups/backup_debug.log`

### Restauração falhou

**Solução**:
1. Um backup de segurança foi criado antes da tentativa
2. Consulte o log: `backups/backup_debug.log`
3. Tente restaurar o backup de segurança (`safety_before_restore_*`)

---

## 📁 **Estrutura de Arquivos**

```
📦 GAT-Sistema/
├── 📁 backups/                  # Backups do sistema
│   ├── backup_YYYY-MM-DD.zip   # Backup automático de atualização
│   ├── backup_manual_*.zip      # Backup criado manualmente
│   ├── safety_before_restore_*.zip  # Backup de segurança
│   └── backup_debug.log         # Log de debug dos backups
├── 📁 src/
│   ├── 📁 config/
│   │   ├── conexao.php          # ⚠️ PROTEGIDO - Config do banco
│   │   └── github_config.php    # ⚠️ PROTEGIDO - Config do GitHub
│   └── 📁 uploads/              # ⚠️ PROTEGIDO - Mídia do sistema
│       ├── departamentos/       # Logos de departamentos
│       └── config/              # Configurações de mídia
├── 📁 uploads/                  # ⚠️ PROTEGIDO - Avatares de usuários
│   └── avatars/                 # Fotos de perfil
├── .last_update                 # Info da última atualização aplicada
└── version.json                 # Versão atual do sistema
```

---

## 🔐 **Segurança**

- ✅ Apenas administradores podem atualizar ou restaurar o sistema
- ✅ Todos os backups são criados com timestamp único
- ✅ Backup de segurança automático antes de qualquer restauração
- ✅ Validação de integridade do backup antes da restauração
- ✅ Preservação automática de configurações sensíveis
- ✅ Log detalhado de todas as operações

---

## 📝 **Logs e Monitoramento**

- **Atualizações**: Verifique o error_log do PHP
- **Backups**: `backups/backup_debug.log`
- **Banco de Dados**: Tabela `system_config` armazena configurações

---

## 🆘 **Suporte**

Em caso de problemas graves:

1. ✅ **Restaure o backup mais recente**
2. ✅ **Verifique os logs** (`backup_debug.log` e error_log)
3. ✅ **Verifique permissões** de arquivos e diretórios
4. ✅ **Consulte a documentação** do projeto no GitHub

### 📚 **Documentação Adicional**

- 📋 [`COMO_ADICIONAR_SQL.md`](COMO_ADICIONAR_SQL.md) - Como criar atualizações SQL
- 📂 [`ESTRUTURA_UPLOADS.md`](ESTRUTURA_UPLOADS.md) - Estrutura de diretórios de upload
- 🔍 [`verificar_sistema.sql`](verificar_sistema.sql) - Script de verificação manual do banco

---

**Última atualização:** 17/01/2025
