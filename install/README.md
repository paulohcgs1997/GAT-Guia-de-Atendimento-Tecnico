# 🚀 Instalador do Sistema GAT

## Como Instalar

### 1. **Requisitos**
- PHP 7.4 ou superior
- MySQL/MariaDB 5.7 ou superior
- Extensão PHP mysqli habilitada
- Servidor web (Apache/Nginx)

### 2. **Passos para Instalação**

#### **Opção 1: Instalação via Interface Web (Recomendado)**

1. Acesse o diretório do projeto no navegador:
   ```
   http://localhost/GAT-testes/
   ```

2. Você será redirecionado automaticamente para o instalador

3. Siga os passos na tela:
   - **Passo 1**: Configure a conexão com o banco de dados
     - Host: `localhost` (padrão)
     - Nome do Banco: `gat` (ou outro nome)
     - Usuário: `root` (padrão)
     - Senha: (deixe vazio se não houver)
   
   - **Passo 2**: Crie o usuário administrador
     - Nome de usuário: `admin` (recomendado)
     - Senha: (mínimo 6 caracteres)
     - Confirme a senha

4. Aguarde a instalação concluir

5. Faça login com as credenciais criadas

#### **Opção 2: Instalação Manual via SQL**

1. Crie o banco de dados no MySQL:
   ```sql
   CREATE DATABASE gat CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Importe o arquivo SQL:
   ```bash
   mysql -u root -p gat < install/database.sql
   ```

3. Crie o usuário admin manualmente:
   ```sql
   USE gat;
   INSERT INTO usuarios (user, password, active, perfil) 
   VALUES ('admin', '$2y$10$0bhMxBq38I/zUW7SVwWVmuQ07YQZ7PT6XRVz9TkCrO/f6LZDBVzZ6', 1, 1);
   ```
   *Senha padrão: `admin123`*

4. Configure manualmente o arquivo `src/config/conexao.php`:
   ```php
   <?php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'gat');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   
   $mysqli = null;
   
   try {
       $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
       $mysqli->set_charset('utf8mb4');
       
       if ($mysqli->connect_errno) {
           error_log('Erro de conexão MySQL: ' . $mysqli->connect_error);
           die('Erro de conexão com o banco de dados.');
       }
   } catch (Exception $e) {
       error_log('Exceção MySQL: ' . $e->getMessage());
       die('Erro ao conectar ao banco de dados.');
   }
   ?>
   ```
   
   **Ou simplesmente copie o arquivo de exemplo:**
   ```bash
   cp src/config/conexao.example.php src/config/conexao.php
   # Depois edite com suas credenciais
   ```

5. Crie o arquivo de flag de instalação:
   ```bash
   echo "Instalado manualmente" > install/.installed
   ```

### 3. **Estrutura Criada**

Após a instalação, o sistema terá:

✅ **Banco de Dados** com as tabelas:
- `usuarios` - Usuários do sistema
- `perfil` - Perfis de acesso (Admin, Criador, Departamento, Colaborador)
- `departaments` - Departamentos
- `services` - Serviços/Tutoriais
- `blocos` - Blocos de tutoriais
- `steps` - Passos dos tutoriais
- `questions` - Perguntas dos tutoriais
- `links` - Links úteis
- `system_config` - Configurações do sistema
- `hash_login` - Tokens de sessão

✅ **Usuário Admin** com permissões totais

✅ **Arquivo de Configuração** `src/config/conexao.php`

✅ **Flag de Instalação** `install/.installed`

### 4. **Após a Instalação**

1. **Faça login** com o usuário admin criado

2. **Configure o sistema**:
   - Adicione departamentos
   - Crie usuários adicionais
   - Configure perfis de acesso

3. **Segurança**:
   - ⚠️ **IMPORTANTE**: Altere a senha padrão do admin
   - Considere remover ou proteger a pasta `/install` após instalação
   - Configure permissões adequadas nos arquivos

### 5. **Problemas Comuns**

#### ❌ Erro: "Call to a member function prepare() on null"
**Causa**: Arquivo de conexão não está gerando a variável `$mysqli` corretamente

**Solução**:
1. Execute o script de verificação:
   ```bash
   php check_system.php
   ```

2. Ou recrie manualmente o arquivo `src/config/conexao.php`:
   ```bash
   cp src/config/conexao.example.php src/config/conexao.php
   ```

3. Edite o arquivo e configure suas credenciais:
   ```php
   define('DB_HOST', 'localhost');  // ou seu host
   define('DB_NAME', 'gat');        // nome do seu banco
   define('DB_USER', 'root');       // seu usuário
   define('DB_PASS', 'senha');      // sua senha
   ```

#### ❌ Erro: "Não foi possível conectar ao banco de dados"
- Verifique se o MySQL está rodando
- Confira usuário e senha do banco
- Verifique se a extensão mysqli está habilitada no PHP

#### Erro: "Arquivo database.sql não encontrado"
- Certifique-se que está na pasta correta
- Verifique se o arquivo `install/database.sql` existe

#### Erro: "Não foi possível criar o arquivo de configuração"
- Verifique permissões de escrita na pasta `src/config/`
- No Linux/Mac: `chmod -R 755 src/config/`

#### Erro: "Usuário admin já existe"
- O sistema já foi instalado
- Use a opção de recuperação de senha ou acesse diretamente

### 6. **Desinstalação**

Para desinstalar completamente:

1. Remova o banco de dados:
   ```sql
   DROP DATABASE gat;
   ```

2. Delete os arquivos de configuração:
   ```bash
   rm src/config/conexao.php
   rm install/.installed
   ```

3. Limpe a pasta de uploads:
   ```bash
   rm -rf src/uploads/*
   ```

### 7. **Suporte**

Em caso de dúvidas ou problemas:
- Verifique os logs de erro do PHP
- Consulte a documentação do sistema
- Entre em contato com o suporte técnico

---

## Credenciais Padrão (instalação via interface)

- **Usuário**: Definido por você
- **Senha**: Definida por você

## Perfis Disponíveis

1. **Admin** (1,1,1) - Ver, Editar e Aprovar
2. **Criador** (1,1,0) - Ver e Editar
3. **Departamento** (1,0,1) - Ver e Aprovar
4. **Colaborador** (1,0,0) - Apenas Visualizar

---

**Versão**: 1.0  
**Data**: Janeiro 2026
