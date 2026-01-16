# 📚 GAT - Guia de Atendimento Técnico

[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4-blue)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-%3E%3D5.7-orange)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Status](https://img.shields.io/badge/Status-Em%20Desenvolvimento-yellow)](https://github.com)

Sistema web completo para gestão e visualização de guias de atendimento técnico, permitindo criar tutoriais interativos com passos sequenciais, perguntas condicionais e fluxos personalizados para diferentes departamentos.

---

## 🎯 Visão Geral

O **GAT** é uma plataforma desenvolvida para centralizar e organizar conhecimento técnico, facilitando o atendimento ao cliente através de tutoriais estruturados e interativos. O sistema permite que criadores desenvolvam guias passo a passo, que departamentos aprovem o conteúdo e que colaboradores acessem facilmente as informações necessárias.

### ✨ Principais Funcionalidades

#### 🔐 **Gestão de Usuários e Permissões**
- Sistema de autenticação seguro com hash de senha
- 4 níveis de perfil de acesso:
  - **Admin**: Controle total do sistema
  - **Criador**: Criação e edição de conteúdo
  - **Departamento**: Aprovação de conteúdo
  - **Colaborador**: Visualização apenas
- Controle de sessão e login persistente
- Registro de último acesso

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
- BroadcastChannel para comunicação entre abas

#### ⚙️ **Configurações do Sistema**
- Personalização do nome do sistema
- Upload de logo personalizado
- Upload de favicon
- Descrição do sistema
- Informações de contato (email e telefone)
- Gerenciamento de uploads e mídia

#### 📊 **Dashboard e Relatórios**
- Visão geral do sistema
- Contador de itens rejeitados
- Acesso rápido às funcionalidades
- Interface responsiva e moderna

---

## 🗂️ Estrutura do Banco de Dados

### Tabelas Principais

| Tabela | Descrição |
|--------|-----------|
| `usuarios` | Armazena informações dos usuários do sistema |
| `perfil` | Define níveis de acesso (admin, criador, departamento, colaborador) |
| `departaments` | Cadastro de departamentos da empresa |
| `services` | Serviços disponíveis para busca e atendimento |
| `blocos` | Tutoriais completos (conjunto de steps) |
| `steps` | Passos individuais dos tutoriais |
| `questions` | Perguntas para fluxo condicional |
| `links` | Links úteis (funcionalidade adicional) |
| `system_config` | Configurações personalizáveis do sistema |
| `hash_login` | Controle de sessões de usuário |

### Características do Banco
- Codificação UTF-8 (utf8mb4_unicode_ci)
- Suporte a emojis e caracteres especiais
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
   define('DB_USER', 'seu_usuario');
   define('DB_PASS', 'sua_senha');
   ```

4. **Crie usuário admin manualmente:**
   ```sql
   USE gat;
   INSERT INTO usuarios (user, password, active, perfil) 
   VALUES ('admin', '$2y$10$0bhMxBq38I/zUW7SVwWVmuQ07YQZ7PT6XRVz9TkCrO/f6LZDBVzZ6', 1, 1);
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

### Sistema de Perguntas Condicionais

As perguntas permitem criar fluxos não-lineares:

```
Step 1: "Problema X detectado"
  ↓
Pergunta: "Qual sintoma?"
  ├─ Resposta A → Step 2
  ├─ Resposta B → Step 5
  └─ Resposta C → Próximo Bloco
```

#### Tipos de Navegação:
- `next_step` - Próximo passo sequencial
- `step_X` - Saltar para step específico (ex: `step_42`)
- `next_block` - Avançar para próximo bloco
- `bloco_X` - Saltar para bloco específico (ex: `bloco_5`)

### Sistema de Clonagem

Quando um item aprovado precisa ser editado:
1. Sistema cria um clone do item
2. Clone é vinculado ao original (`original_id`)
3. Clone é marcado como `is_clone = 1`
4. Edições são feitas no clone
5. Após aprovação, clone substitui original
6. Versão antiga é mantida para histórico

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

---

## 🔐 Níveis de Acesso

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
│   ├── js/                   # Scripts JavaScript
│   │   └── search.js
│   │
│   ├── includes/             # Componentes compartilhados
│   │   ├── head_config.php   # Meta tags e configurações
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
