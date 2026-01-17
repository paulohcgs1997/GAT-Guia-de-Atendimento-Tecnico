# 📚 GAT - Guia de Atendimento Técnico

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-%3E%3D5.7-orange)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Produção-brightgreen)](https://github.com)

Sistema web completo para gestão e visualização de guias de atendimento técnico com tutoriais interativos, fluxo condicional, sistema de aprovação, atualizações automáticas via GitHub e gerenciamento de backups.

---

## 🎯 Visão Geral

O **GAT** é uma plataforma desenvolvida para centralizar e organizar conhecimento técnico, facilitando o atendimento através de tutoriais estruturados e interativos. O sistema oferece criação de conteúdo, fluxo de aprovação, atualizações automáticas e gestão completa de backups.

### ✨ Principais Funcionalidades

#### 🔐 **Gestão de Usuários e Permissões**
- Sistema de autenticação seguro com hash bcrypt
- 4 níveis de perfil de acesso:
  - **Admin**: Controle total do sistema
  - **Criador**: Criação e edição de conteúdo
  - **Departamento**: Aprovação de conteúdo
  - **Colaborador**: Visualização apenas
- Controle de sessão e login persistente
- Registro de último acesso
- Verificação de disponibilidade de username em tempo real
- Edição em lote de usuários
- Troca de senha obrigatória no primeiro login

#### 📝 **Criação de Tutoriais (Blocos)**
- Editor de conteúdo HTML para tutoriais detalhados
- Upload de imagens e mídia para ilustrar procedimentos
- Organização em blocos (tutoriais) compostos por steps (passos)
- Versionamento através de sistema de clonagem
- Vinculação a departamentos específicos
- Sistema de aprovação antes da publicação

#### 🛠️ **Gestão de Serviços**
- Criação de serviços que agrupam tutoriais relacionados
- Sistema de busca por palavras-chave
- Vinculação a departamentos
- Descrição detalhada de cada serviço
- Status de aprovação e rejeição
- Associação de múltiplos blocos (tutoriais)

#### 📖 **Steps (Passos dos Tutoriais)**
- Criação de passos individuais com conteúdo HTML
- Upload de imagens específicas para cada passo
- Sistema de perguntas condicionais
- Navegação não-linear baseada em respostas
- Fluxo personalizado entre passos
- Clonagem para edição sem perder versão aprovada

#### ❓ **Sistema de Perguntas**
- Criação de perguntas para ramificação de fluxo
- Definição de próximo passo baseado na resposta
- Suporte a:
  - Prosseguir para próximo passo sequencial
  - Saltar para passo específico
  - Avançar para próximo bloco
  - Saltar para bloco específico

#### ✅ **Sistema de Aprovação**
- Fluxo de aprovação para serviços e tutoriais
- Histórico de aprovações com data
- Sistema de rejeição com motivo detalhado
- Notificação de itens rejeitados
- Contador de itens pendentes
- Reabertura de itens rejeitados para correção

#### 🏢 **Gestão de Departamentos**
- Cadastro de departamentos da empresa
- Upload de logo por departamento
- Vínculo de serviços e tutoriais
- Controle de acesso por departamento

#### 🔍 **Sistema de Busca Inteligente**
- Busca por palavras-chave nos serviços
- Filtro por departamento
- Sugestões em tempo real
- Integração com sistema de visualização

#### 👁️ **Visualizador Interativo**
- Interface intuitiva para seguir tutoriais
- Navegação por passos sequenciais
- Sistema de perguntas interativas
- Visualização de imagens e mídia
- Suporte a múltiplos blocos encadeados
- Modo de preview para criadores
- **Guias organizadas com Bootstrap Tabs**:
  - 📋 Informações do Sistema
  - 🎨 Identidade Visual
  - 🔍 Verificador de Banco de Dados
  - ☁️ Atualizações Automáticas
  - 💾 Gerenciamento de Backups
- Personalização completa (nome, logo, favicon, descrição, contatos)
- Upload de imagens com preview em tempo real

#### 🔄 **Sistema de Atualizações Automáticas**
- Integração com GitHub API
- Verificação automática de novas versões
- Visualização de changelog antes de atualizar
- Backup automático antes de cada atualização
- Download e instalação com um clique
- Preservação de configurações e uploads
- Sistema de tokens criptografados
- Configuração de repositório e branch

#### 💾 **Gerenciamento de Backups**
- Criação de backups manuais em ZIP
- Backups automáticos antes de atualizações
- Listagem de backups com data, tamanho e tipo
- Restauração com um clique
- Backup de segurança antes de restaurar
- Manutenção automática dos 3 backups mais recentes
- Exclusão de backups antigos
- Log detalhado de operações

#### 🗄️ **Verificador de Banco de Dados**
- Verificação automática de estrutura do banco
- Detecção de tabelas e colunas faltantes
- Aplicação de migrações com um clique
- Sistema de versionamento de schema
- Logs de aplicação de updates
- Interface visual com status detalhado
- Descrição do sistema
- Informações de contato (email e telefone)
- Gerenciamento de uploads e mídia

#### 📊 **Dashboard e Relatórios**
- Visão geral do sistema
- Contador de itens rejeitados
- Acesso rápido às funcionalidades
- Interface responsiva e moderna

---

#### Requisitos Críticos (Obrigatórios)
- **PHP** 7.4 ou superior
- **MySQL/MariaDB** 5.7 ou superior
- **Servidor Web** Apache, Nginx ou IIS
- **Extensões PHP Críticas**:
  - `pdo` e `pdo_mysql` - Conexão com banco de dados
  - `mysqli` - Operações com MySQL
  - `zip` - Backups e atualizações (⚠️ **Essencial**)
  - `json` - Manipulação de dados JSON
  - `curl` - Atualizações do GitHub
  - `openssl` - Conexões HTTPS seguras

#### Requisitos Recomendados (Opcionais)
- **Extensões PHP Recomendadas**:
O GAT possui um instalador inteligente que verifica todos os requisitos automaticamente.

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/seu-usuario/GAT-Guia-de-Atendimento-Tecnico.git
   cd GAT-Guia-de-Atendimento-Tecnico
   ```

2. **Configure o servidor web** para apontar para o diretório do projeto

3. **Acesse o instalador:**
   ```
   http://localhost/GAT-Guia-de-Atendimento-Tecnico/
   ```

4. **Siga o assistente de instalação:**
   
   **Passo 0: Verificação de Requisitos** 🔍
   -Desinstalação

O sistema possui um desinstalador integrado:

1. **Acesse o desinstalador:**
   ```
   http://localhost/GAT-Guia-de-Atendimento-Tecnico/install/uninstall.php
   ```

2. **Confirme a desinstalação:**
   - Sistema remove todas as tabelas do banco
   - Deleta arquivo de configuração
   - Remove flag de instalação
   - Preserva backups para segurança

3. **Limpeza completa (opcional):**
   ```bash
   # Remove uploads
   rm -rf uploads/*
   
   # Remove backups
   rm -rf backups/*
   
   # Remove banco de dados
   mysql -u root -p -e "DROP DATABASE IF EXISTS gat;"res especiais
- Constraints para integridade referencial
- Índices otimizados para consultas
- Sistema de soft delete (campo `active`)
- Versionamento através de clonagem (`is_clone`, `original_id`)
- Sistema de aprovação (`accept`, `last_accept`)
- Sistema de rejeição (`rejection_reason`, `rejected_by`, `reject_date`)

---

## 🚀 Instalação

### Requisitos do Sistema

- **PHP** 7.4 ou superior
- **MySQL/MariaDB** 5.7 ou superior
- **Servidor Web** Apache ou Nginx
- **Extensões PHP**:
  - mysqli
  - json
  - session
  - gd (para manipulação de imagens)

### Instalação Rápida (Recomendado)

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/seu-usuario/GAT-Guia-de-Atendimento-Tecnico.git
   cd GAT-Guia-de-Atendimento-Tecnico
   ```

2. **Configure o servidor web** para apontar para o diretório do projeto

3. **Acesse o instalador:**
   ```
   http://localhost/GAT-Guia-de-Atendimento-Tecnico/
   ```

4. **Siga o assistente de instalação:**
   - **Passo 1**: Configure a conexão com o banco de dados
   - **Passo 2**: Crie o usuário administrador
   - **Passo 3**: Finalize e faça login

### Instalação Manual

1. **Crie o banco de dados:**
   ```sql
   CREATE DATABASE gat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Importe a estrutura:**
   ```bash
   mysql -u root -p gat < install/database.sql
   ```

3. **Configure a conexão:**
   ```bash
   cp src/config/conexao.example.php src/config/conexao.php
   ```
   
   Edite `src/config/conexao.php` com suas credenciais:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'gat');
  **Aba Informações do Sistema**: Nome, descrição, contatos
- **Aba Identidade Visual**: Logo e favicon com preview
- **Aba Verificador de BD**: Garanta que estrutura está atualizada
- **Aba Atualizações**: Configure repositório GitHub (opcional)
- **Aba Backups**: Crie primeiro backup manual
   ```

4. **Crie usuário admin manualmente:**
   ```sql
   USE gat;
   INSERT INTO usuarios (user, password, active, perfil) 
  Sistema verifica disponibilidade de username em tempo real
- Vincule usuários aos departamentos
- Use edição em lote para múltiplos usuários
- Ative/desative usuários em massa
- Usuários devem trocar senha no primeiro login8I/zUW7SVwWVmuQ07YQZ7PT6XRVz9TkCrO/f6LZDBVzZ6', 1, 1);
   ```
   *Senha padrão: `admin123`*

5. **Marque como instalado:**
   ```bash
   echo "Instalado manualmente" > install/.installed
   ```

6. **Configure permissões:**
   ```bash
   chmod 755 -R .
   chmod 777 -R src/uploads/
   ```

---

## 📖 Uso do Sistema

### Primeiro Acesso

1. Acesse `http://seu-dominio/`
2. Faça login com as credenciais criadas na instalação
3. Você será redirecionado para o Dashboard

### Fluxo de Trabalho Recomendado

#### 1️⃣ **Configuração Inicial (Admin)**
- Acesse **Gestão → Configurações**
- Personalize nome, logo e favicon do sistema
- Configure informações de contato

#### 2️⃣ **Cadastro de Departamentos (Admin)**
- Acesse **Gestão → Departamentos**
- Cadastre os departamentos da empresa
- Faça upload dos logos de cada departamento

#### 3️⃣ **Criação de Usuários (Admin)**
- Acesse **Gestão → Usuários**
- Crie usuários com diferentes perfis
- Vincule usuários aos departamentos

#### 4️⃣ **Criação de Steps (Criador)**
- Acesse **Gestão → Tutoriais**
- Crie os passos individuais (steps)
- Adicione conteúdo HTML e imagens
- Configure perguntas se necessário

#### 5️⃣ **Montagem de Blocos (Criador)**
- Na mesma tela de **Gestão → Tutoriais**
- Crie blocos e associe os steps
- Defina a ordem dos passos
- Vincule ao departamento

#### 🔟 **Manutenção do Sistema (Admin)**
- **Verificar Banco de Dados**: Acesse regularmente o verificador
- **Aplicar Atualizações**: Mantenha sistema atualizado via GitHub
- **Gerenciar Backups**: Crie backups antes de mudanças importantes
- **Restaurar SAtualizações Automáticas

O GAT se atualiza automaticamente via GitHub:

#### Configuração
1. Acesse **Configurações → Atualizações**
2. Configure o repositório GitHub
3. Insira o Personal Access Token
4. Sistema verifica automaticamente novas versões

#### Fluxo de Atualização
1. **Verificação**: Sistema consulta GitHub API
2. **Comparação**: Compara hash do último commit
3. **Changelog**: Exibe lista de mudanças
4. **Backup**: Cria backup automático antes de atualizar
5. **Download**: Baixa ZIP do repositório
6. **Extração**: Extrai arquivos
7. **Instalação**: Copia arquivos (preserva config e uploads)
8. **Banco**: Aplica migrações automaticamente
9. **Conclusão**: Sistema atualizado e funcional

#### Recursos
- ✅ Backup automático antes de cada atualização
- ✅ Preservação de `conexao.php` e `github_config.php`
- ✅ Preservação de pasta `backups/`
- ✅ Visualização de changelog completo
- ✅ Aplicação automática de migrações SQL
- ✅ Logs detalhados de todo o processo
- ✅ Rollback via restauração de backup

### Sistema de Backups

Gerenciamento completo de backups do sistema:

#### Tipos de Backup
- **Manual**: Criado pelo admin quando necessário
- **Automático**: Criado antes de cada atualização

#### Recursos
- ✅ Backups em formato ZIP
- ✅ Listagem com data, tamanho e tipo
- ✅ Restauração com confirmação
- ✅ Backup de segurança antes de restaurar
- ✅ Manutenção automática (mantém 3 mais recentes)
- ✅ Exclusão de backups antigos
- ✅ Logs detalhados (backup_debug.log)

#### Estrutura do Backup
```
backup_YYYY-MM-DD_HH-MM-SS.zip
├── src/
│   ├── config/ (exceto conexao.php)
│   ├── css/
│   ├── js/
│   ├── includes/
│   ├── php/
│   └── uploads/
├── viwer/
├── install/
└── index.php
```

### Verificador de Banco de Dados

Sistema inteligente de verificação e atualização:

#### Funcionalidades
- ✅ Detecta tabelas faltantes
- ✅ Detecta colunas faltantes
- ✅ Lista atualizações disponíveis
- ✅ Aplica migrações com um clique
- ✅ Versionamento de schema
- ✅ Logs de aplicação

#### Arquivos de Migração
```
install/
├── database.sql                    # Schema completo
├── update_users_table.sql         # Adiciona force_password_change
├── update_status_field.sql        # Adiciona campo status
└── add_force_password_change.sql  # Migração específica
```

### Sistema de istema**: Use backups em caso de problemas

#### 6️⃣ **Criação de Serviços (Criador)**
- Acesse **Gestão → Serviços**
- Crie serviços e vincule blocos
- Adicione palavras-chave para busca
- Escreva descrição detalhada

#### 7️⃣ **Aprovação (Departamento/Admin)**
- Acesse **Gestão → Aprovações**
- Revise serviços e tutoriais pendentes
- Aprove ou rejeite com motivo

#### 8️⃣ **Correção de Rejeitados (Criador)**
- Acesse **Gestão → Itens Reprovados**
- Visualize motivos de rejeição
- Edite e reenvie para aprovação

#### 9️⃣ **Uso pelo Colaborador**
- Acesse o sistema e faça busca
- Selecione o serviço desejado
- Siga o tutorial passo a passo
- Responda perguntas quando solicitado

---

## 🎨 Funcionalidades Detalhadas
e Desinstalador
│   ├── index.php              # Interface de instalação
│   ├── install_process.php    # Processamento da instalação
│   ├── uninstall.php          # Interface de desinstalação
│   ├── uninstall_process.php  # Processamento da desinstalação
│   ├── check_requirements.php # Verificador de requisitos
│   ├── database.sql           # Estrutura completa do banco
│   ├── update_*.sql           # Migrações de banco
│   ├── .installed             # Flag de instalação concluída
│   └── generate_encrypted_token.html  # Gerador de token
Step 1: "Problema X detectado"
  ↓
Pergunta: "Qual sintoma?"
  ├─ Resposta A → Step 2
  ├─ Respostavatars/          # Avatares de usuários
│       └── config/           # Logos e favicon do sistema
│
├── backups/                  # Backups do sistema (ignorado no git)
│   ├── backup_*.zip         # Arquivos de backup
│   ├── backup_debug.log     # Log de operações
│   └── register.php          # Registro de usuários
│   ├── change_password.php   # Troca de senha obrigatória
│   ├── perfil.php            # Perfil do usuário
│   ├── gestao.php            # Menu de gestão
│   ├── gestao_users.php      # Gestão de usuários
│   ├── gestao_departamentos.php # Gestão de departamentos
│   ├── gestao_services.php   # Gestão de serviços
│   ├── gestao_blocos.php     # Gestão de blocos/tutoriais
│   ├── gestao_configuracoes.php # Configurações do sistema
│   ├── aprovacoes.php        # Sistema de aprovação
│   ├── gestao_reprovados.php # Itens rejeitados
│   ├── viwer.php             # Visualizador de tutoriais
│   ├── preview_tutorial.php  # Preview antes de publicar
│   │
│   └── includes/
│       ├── quick_menu.php    # Menu rápido de navegação
│       └── includes.php      # Funções auxiliares
### Sistema de Clonagem

Quando um item aprovado precisa ser editado:
1. Sistema cria um clone do item
2. Clone├── check_updates.php # Verificador de atualizações
│   │   ├── apply_update.php  # Aplicador de atualizações
│   │   ├── database_checker.php # Verificador de BD
│   │   ├── apply_migration.php  # Aplicador de migrações

#### Aba: Informações do Sistema
- Nome do sistema
- Descrição
- Email de contato
- Telefone de contato

#### Aba: Identidade Visual
- Logo (aparece no cabeçalho) - PNG transparente recomendado
- Favicon (ícone do navegador) -uploads/`:
- `avatars/` - Avatares dos usuários
- `config/` - Logo e favicon do sistema

**Importante**: 
- O diretório `uploads/` deve ter permissão de escrita
- Tamanho máximo recomendado: 50MB por arquivo
- Configure no php.ini: `upload_max_filesize` e `post_max_size`

### Habilitando Extensão ZIP

A extensão ZIP é **crítica** para backups e atualizações:

#### Windows
1. Localize o arquivo `php.ini`:
   ```bash
   php --ini
   ```

2. Edite o `php.ini` e remova o `;` da linha:
   ```ini
   ;extension=zip
   ```
   Para:
   ```ini
   extension=zip
   ```

3. Reinicie o servidor web (Apache/IIS)

#### Linux
```bash
# Ubuntu/Debian
sudo apt-get install php-zip

# CentOS/RHEL
sudo yum install php-zip

# Reinicie o servidor
sudo systemctl restart apache2
# ou
sudo systemctl restart nginx
```

#### Verificação
```bash
php -m | grep zip
```: Extensão ZIP Não Encontrada

**Problema**: "Class ZipArchive not found" ou erro ao criar/restaurar backup

**Solução**:
1. Verifique se ZIP está habilitado:
   ```bash
   php -m | grep zip
   ```

2. Se não aparecer "zip", habilite no php.ini:
   ```ini
   extension=zip
   ```

3. Reinicie o servidor web:
   ```bash
   # Windows (Apache)
   Reinicie via Services.msc
   
   # Linux
   sudo systemctl restart apache2
   ```

4. Verifique novamente:
   ```bash
   php -m | grep zip

### Erro ao Atualizar Sistema

**Problema**: Atualização falha ou retorna erro

**Solução**:
1. Verifique se extensão ZIP está habilitada
2. Confirme que cURL está instalado
3. Verifique se OpenSSL está habilitado (para HTTPS)
4. Teste o token do GitHub:
   ```bash
   curl -H "Authorization: token SEU_TOKEN" https://api.github.com/user
   ```
5. Verifique logs em `backups/backup_debug.log`
6. Se falhar, restaure backup anterior

### Erro no Verificador de Banco de Dados

**Problema**: Migrações não são aplicadas

**Solução**:
1. Verifique permissões do usuário MySQL
2. Confirme que arquivos SQL existem em `install/`
3. Execute manualmente se necessário:
   ```bash
   mysql -u root -p gat < install/update_users_table.sql
   ```
4. Verifique logs do PHP para erros SQL
   ```

**No✅ Funcionalidades Implementadas (v2.0)

- [x] Sistema de atualizações automáticas via GitHub
- [x] Gerenciamento completo de backups
- [x] Verificador de banco de dados com migrações
- [x] Sistema de requisitos no instalador
- [x] Edição em lote de usuários
- [x] Verificação de username em tempo real
- [x] Troca de senha obrigatória no primeiro login
- [x] Sistema de configurações em abas (Bootstrap)
- [x] Preview de imagens em tempo real
- [x] Logs detalhados de operações

### 🚧 Em Desenvolvimento

- [ ] Dashboard com estatísticas e gráficos
- [ ] Sistema de notificações em tempo real
- [ ] Histórico de versões de tutoriais
- [ ] Modo escuro / temas personalizáveis

### 📋 Funcionalidades Planejadas

- [ ] Sistema de comentários em tutoriais
- [ ] Exportação de tutoriais em PDF
- [ ] Sistema de favoritos
- [ ] Busca avançada com filtros múltiplos
- [ ] Notificações por email
- [ ] API REST para integrações
- [ ] Sistema de tags
- [ ] PWA (Progressive Web App)
- [ ] Suporte nativo a vídeos
- [ ] Sistema de avaliação (5 estrelas)
- [ ] Chatbot com IA para sugestões
- [ ] Integração com Slack/Teams
- [ ] Exportação de relatórios
- [ ] Auditoria de acesso
- Exclusão de backups antigos
### Sistema de Rejeição

Fluxo de rejeição:
1. Aprovador rejeita item e informa motivo
2. Sistema registra:
   - `rejection_reason` - Motivo detalhado
   - `rejected_by` - ID do aprovador
   - `reject_date` - Data/hora da rejeição
3. Criador visualiza na lista de reprovados
4. Criador edita e reenvia
5. Sistema limpa campos de rejeição

🎉 **Versão 2.0 - Produção** - Sistema completo e funcional

### Versões

#### v2.0 - Janeiro 2026
- ✨ Sistema de atualizações automáticas via GitHub
- ✨ Gerenciamento completo de backups (criar/restaurar/excluir)
- ✨ Verificador de banco de dados com aplicação de migrações
- ✨ Instalador com verificação de requisitos
- ✨ Edição em lote de usuários
- ✨ Verificação de username em tempo real
- ✨ Troca de senha obrigatória no primeiro login
- ✨ Sistema de configurações reorganizado em abas
- 🐛 Correções de segurança e estabilidade
- 📚 Documentação completa atualizada

#### v1.0 - Lançamento Inicial
- ✨ Sistema de tutoriais interativos
- ✨ Fluxo de aprovação
- ✨ Gestão de usuários e departamentos
- ✨ Sistema de perguntas condicionais
- ✨ Busca e visualização

**Última Atualização**: 17 de Janeiro de

### Admin (Perfil 1)
- ✅ Visualizar todos os conteúdos
- ✅ Criar/editar serviços e tutoriais
- ✅ Aprovar/rejeitar conteúdo
- ✅ Gerenciar usuários
- ✅ Gerenciar departamentos
- ✅ Configurar sistema

### Criador (Perfil 2)
- ✅ Visualizar conteúdos
- ✅ Criar/editar serviços e tutoriais
- ❌ Aprovar conteúdo
- ❌ Gerenciar usuários
- ✅ Corrigir itens rejeitados

### Departamento (Perfil 3)
- ✅ Visualizar conteúdos
- ❌ Criar/editar conteúdo
- ✅ Aprovar/rejeitar conteúdo
- ❌ Gerenciar sistema

### Colaborador (Perfil 4)
- ✅ Visualizar conteúdos aprovados
- ❌ Criar/editar conteúdo
- ❌ Aprovar conteúdo
- ❌ Gerenciar sistema

---

## 📁 Estrutura de Arquivos

```
GAT-Guia-de-Atendimento-Tecnico/
├── install/                    # Instalador do sistema
│   ├── index.php              # Interface de instalação
│   ├── install_process.php    # Processamento da instalação
│   ├── database.sql           # Estrutura do banco
│   ├── .installed             # Flag de instalação concluída
│   └── README.md              # Documentação de instalação
│
├── src/                       # Código fonte
│   ├── config/               # Configurações
│   │   ├── conexao.php       # Conexão com banco
│   │   └── conexao.example.php
│   │
│   ├── css/                  # Estilos
│   │   └── style.css
│   │
│   ├── js/   e Recursos

### 📖 Documentação
- **README**: Este arquivo contém toda a documentação necessária
- **Instalador**: Sistema de instalação com verificação automática
- **Verificador de BD**: Ferramenta integrada para manutenção

### 🐛 Reportar Problemas
- **GitHub Issues**: Relate bugs e sugira melhorias
- **Logs**: Verifique `backups/backup_debug.log` para debug

### 💬 Comunidade
- **Contribuições**: Pull requests são bem-vindos
- **Discussões**: Use GitHub Discussions para dúvidas

### ⚙️ Ferramentas de Debug
- **Verificador de Requisitos**: `install/check_requirements.php`
- **Verificador de BD**: Configurações → Verificador de BD
- **Logs de Backup**: `backups/backup_debug.log`
- **Logs do PHP**: Verifique error_log do servidora tags e configurações
│   │   └── header.php        # Cabeçalho do sistema
│   │
│   ├── php/                  # Backend PHP
│   │   ├── login.php         # Autenticação
│   │   ├── crud_*.php        # Operações CRUD
│   │   ├── get_*.php         # APIs de consulta
│   │   ├── approve_items.php # Sistema de aprovação
│   │   ├── media_manager.php # Gerenciamento de mídia
│   │   └── ...
│   │
│   └── uploads/              # Arquivos enviados (ignorado no git)
│       ├── config/           # Logos e favicon
│       └── departamentos/    # Logos dos departamentos
│
├── viwer/                    # Interface do usuário
│   ├── dashboard.php         # Painel principal
│   ├── login.php             # Tela de login
│   ├── gestao.php            # Menu de gestão
│   ├── gestao_*.php          # Páginas de gestão
│   ├── aprovacoes.php        # Sistema de aprovação
│   ├── gestao_reprovados.php # Itens rejeitados
│   ├── viwer.php             # Visualizador de tutoriais
│   ├── preview_tutorial.php  # Preview antes de publicar
│   │
│   └── includes/
│       └── quick_menu.php    # Menu rápido de navegação
│
├── index.php                 # Ponto de entrada
├── .gitignore               # Arquivos ignorados
└── README.md                # Este arquivo
```

---

## 🔧 Configuração Avançada

### Personalização do Sistema

Todas as configurações podem ser alteradas em **Gestão → Configurações**:
- Nome do sistema
- Logo (aparece no cabeçalho)
- Favicon (ícone do navegador)
- Descrição
- Email de contato
- Telefone de contato

### Uploads e Mídia

Os arquivos são armazenados em `src/uploads/`:
- `config/` - Logo e favicon do sistema
- `departamentos/` - Logos dos departamentos
- Demais pastas conforme necessidade

**Importante**: O diretório `uploads/` deve ter permissão de escrita (777 no Linux).

### Segurança

#### Senhas
- Todas as senhas são criptografadas com `password_hash()` do PHP
- Algoritmo bcrypt com custo 10
- Nunca são armazenadas em texto plano

#### Sessões
- Timeout automático de inatividade
- Validação de hash de login
- Proteção contra session fixation

#### SQL Injection
- Uso de prepared statements
- Validação de entrada de dados
- Escape de strings em HTML

#### XSS
- Uso de `htmlspecialchars()` em saídas
- Validação de conteúdo HTML em tutoriais
- Sanitização de uploads

---

## 🐛 Solução de Problemas

### Erro de Conexão com Banco

**Problema**: "Erro de conexão com o banco de dados"

**Solução**:
1. Verifique as credenciais em `src/config/conexao.php`
2. Confirme que o banco está rodando
3. Teste a conexão manualmente
4. Verifique as permissões do usuário MySQL

### Erro de Upload de Imagens

**Problema**: Imagens não são salvas

**Solução**:
1. Verifique permissões da pasta `src/uploads/`
2. Confirme `upload_max_filesize` no php.ini
3. Verifique `post_max_size` no php.ini
4. Confirme extensão GD habilitada

### Erro "Sistema já instalado"

**Problema**: Preciso reinstalar o sistema

**Solução**:
1. Delete o arquivo `install/.installed`
2. Delete o banco de dados
3. Acesse o instalador novamente

### Sessão Expira Rapidamente

**Problema**: Deslogado constantemente

**Solução**:
1. Verifique `session.gc_maxlifetime` no php.ini
2. Aumente o valor (ex: 3600 para 1 hora)
3. Reinicie o servidor web

---

## 🤝 Contribuindo

Contribuições são bem-vindas! Para contribuir:

1. Faça um Fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/MinhaFeature`)
3. Commit suas mudanças (`git commit -m 'Adiciona MinhaFeature'`)
4. Push para a branch (`git push origin feature/MinhaFeature`)
5. Abra um Pull Request

### Padrões de Código
- Indentação: 4 espaços
- Nomes de variáveis: camelCase
- Nomes de arquivos: snake_case
- Comentários em português
- Sempre use prepared statements

---

## 📝 Roadmap

### Funcionalidades Planejadas

- [ ] Sistema de comentários em tutoriais
- [ ] Histórico de versões detalhado
- [ ] Exportação de tutoriais em PDF
- [ ] Sistema de favoritos
- [ ] Busca avançada com filtros
- [ ] Dashboard com estatísticas
- [ ] Notificações por email
- [ ] API REST para integrações
- [ ] Sistema de tags
- [ ] Modo escuro
- [ ] PWA (Progressive Web App)
- [ ] Suporte a vídeos nos tutoriais
- [ ] Sistema de avaliação de tutoriais
- [ ] Chatbot para sugestões

---

## 📄 Licença

Este projeto está sob a licença MIT. Veja o arquivo [LICENSE](LICENSE) para mais detalhes.

---

## 👥 Autores

- **Desenvolvedor Principal** - Desenvolvimento inicial e manutenção

---

## 🙏 Agradecimentos

- Comunidade PHP pela excelente documentação
- Contribuidores do projeto
- Todos que utilizam e testam o sistema

---

## 📞 Suporte

- **Documentação**: [Wiki do Projeto](https://github.com/seu-usuario/GAT/wiki)
- **Issues**: [GitHub Issues](https://github.com/seu-usuario/GAT/issues)
- **Email**: contato@seudominio.com

---

## 📊 Status do Projeto

🚧 **Em Desenvolvimento Ativo** - Novas funcionalidades sendo adicionadas regularmente

**Última Atualização**: Janeiro 2026

---

<div align="center">

**[⬆ Voltar ao Topo](#-gat---guia-de-atendimento-técnico)**

Feito com ❤️ para facilitar o atendimento técnico

</div>
