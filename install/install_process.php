<?php
// install_process.php - Processa a instalação do sistema
header('Content-Type: application/json');

// Recebe os dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Função para criar arquivo de configuração
function createConfigFile($host, $dbname, $user, $pass) {
    $configPath = dirname(__DIR__) . '/src/config/conexao.php';
    
    // Escapa aspas simples nos valores
    $host = str_replace("'", "\\'", $host);
    $dbname = str_replace("'", "\\'", $dbname);
    $user = str_replace("'", "\\'", $user);
    $pass = str_replace("'", "\\'", $pass);
    
    $content = "<?php
// Configurações de Conexão com Banco de Dados
// Gerado automaticamente pelo instalador

define('DB_HOST', '{$host}');
define('DB_NAME', '{$dbname}');
define('DB_USER', '{$user}');
define('DB_PASS', '{$pass}');

// Chave de segurança para hash de sessão
define('SYSTEM_SESSION_KEY', 'gat_secure_key_' . md5('gat_system_' . '{$dbname}'));

// Evitar reconexão se já existir
if (!isset(\$GLOBALS['mysqli']) || !(\$GLOBALS['mysqli'] instanceof mysqli)) {
    try {
        \$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (\$mysqli->connect_errno) {
            error_log('Erro de conexão MySQL: ' . \$mysqli->connect_error);
            die('Erro de conexão com o banco de dados. Código: ' . \$mysqli->connect_errno);
        }
        
        \$mysqli->set_charset('utf8mb4');
        
        // Armazenar no GLOBALS para garantir disponibilidade
        \$GLOBALS['mysqli'] = \$mysqli;
    } catch (Exception \$e) {
        error_log('Exceção MySQL: ' . \$e->getMessage());
        die('Erro ao conectar ao banco de dados: ' . \$e->getMessage());
    }
} else {
    // Reutilizar conexão existente
    \$mysqli = \$GLOBALS['mysqli'];
}
?>";
    
    return file_put_contents($configPath, $content);
}

// Função para criar arquivo de flag de instalação
function createInstallFlag() {
    $flagPath = dirname(__DIR__) . '/install/.installed';
    return file_put_contents($flagPath, date('Y-m-d H:i:s'));
}

// Função para criar arquivo de configuração do GitHub
function createGitHubConfigFile($token) {
    $configPath = dirname(__DIR__) . '/src/config/github_config.php';
    
    // Escapa aspas simples no token
    $token = str_replace("'", "\\'", $token);
    
    $content = "<?php
/**
 * Configuração do GitHub para Sistema de Atualizações
 * Configurado automaticamente durante a instalação
 */

// GitHub Personal Access Token
define('GITHUB_TOKEN', '{$token}');

// Proprietário do repositório
define('GITHUB_OWNER', 'paulohcgs1997');

// Nome do repositório
define('GITHUB_REPO', 'GAT-Guia-de-Atendimento-Tecnico');

// Branch para atualizações (sempre 'main')
define('GITHUB_BRANCH', 'main');
";
    
    return file_put_contents($configPath, $content);
}

// Ação: Testar conexão com banco
if ($action === 'test_db') {
    $host = $input['db_host'] ?? 'localhost';
    $dbname = $input['db_name'] ?? 'gat';
    $user = $input['db_user'] ?? 'root';
    $pass = $input['db_pass'] ?? '';
    
    try {
        // Tenta conectar sem especificar o banco
        $mysqli = new mysqli($host, $user, $pass);
        
        if ($mysqli->connect_errno) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro de conexão: ' . $mysqli->connect_error
            ]);
            exit;
        }
        
        // Verifica se o banco já existe
        $result = $mysqli->query("SHOW DATABASES LIKE '{$dbname}'");
        $dbExists = $result && $result->num_rows > 0;
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conexão bem-sucedida!',
            'db_exists' => $dbExists
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Ação: Instalar sistema
if ($action === 'install') {
    $db = $input['db'] ?? [];
    $admin = $input['admin'] ?? [];
    
    $host = $db['db_host'] ?? 'localhost';
    $dbname = $db['db_name'] ?? 'gat';
    $user = $db['db_user'] ?? 'root';
    $pass = $db['db_pass'] ?? '';
    
    $adminUser = $admin['admin_user'] ?? 'admin';
    $adminPass = $admin['admin_pass'] ?? '';
    
    if (empty($adminPass)) {
        echo json_encode([
            'success' => false,
            'message' => 'Senha do administrador não pode ser vazia'
        ]);
        exit;
    }
    
    try {
        // Conecta ao MySQL
        $mysqli = new mysqli($host, $user, $pass);
        $mysqli->set_charset('utf8mb4');
        
        if ($mysqli->connect_errno) {
            throw new Exception('Erro de conexão: ' . $mysqli->connect_error);
        }
        
        // Cria o banco de dados
        $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $mysqli->select_db($dbname);
        
        // Lê e executa o SQL
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception('Arquivo database.sql não encontrado');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Remove comentários e divide em queries individuais
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Executa cada query
        $mysqli->multi_query($sql);
        
        // Aguarda todas as queries serem executadas
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        
        // Cria o hash da senha do admin
        $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT);
        
        // Insere o usuário admin
        $stmt = $mysqli->prepare("INSERT INTO usuarios (user, password, active, perfil) VALUES (?, ?, 1, 1)");
        $stmt->bind_param('ss', $adminUser, $passwordHash);
        
        if (!$stmt->execute()) {
            throw new Exception('Erro ao criar usuário admin: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
        
        // Cria o arquivo de configuração
        if (!createConfigFile($host, $dbname, $user, $pass)) {
            throw new Exception('Erro ao criar arquivo de configuração');
        }
        
        // Cria arquivo de configuração do GitHub (se token foi fornecido)
        $githubToken = $admin['github_token'] ?? '';
        if (!empty($githubToken)) {
            createGitHubConfigFile($githubToken);
        }
        
        // Cria flag de instalação
        createInstallFlag();
        
        // ========== APLICAR ATUALIZAÇÕES AUTOMÁTICAS ==========
        $updates_log = [];
        $updates_errors = [];
        
        try {
            // Reconectar ao banco recém-criado
            $mysqli = new mysqli($host, $user, $pass, $dbname);
            $mysqli->set_charset('utf8mb4');
            
            // Buscar arquivos de atualização na pasta install/update_sql primeiro
            $update_sql_dir = __DIR__ . '/update_sql';
            $all_update_files = [];
            
            if (is_dir($update_sql_dir)) {
                // Buscar TODOS os arquivos SQL na pasta update_sql
                $all_update_files = glob($update_sql_dir . '/*.sql');
            }
            
            // Se não encontrou nada em update_sql, buscar na pasta install (fallback)
            if (empty($all_update_files)) {
                $update_files = glob(__DIR__ . '/update_*.sql');
                $add_files = glob(__DIR__ . '/add_*.sql');
                
                // Mesclar os dois arrays e filtrar database.sql
                $all_update_files = array_merge($update_files, $add_files);
                $all_update_files = array_filter($all_update_files, function($file) {
                    return basename($file) !== 'database.sql';
                });
            }
            
            error_log('Instalação: Encontrados ' . count($all_update_files) . ' arquivos de atualização');
            
            foreach ($all_update_files as $update_file) {
                $filename = basename($update_file);
                
                try {
                    // Ler conteúdo do arquivo
                    $sql_content = file_get_contents($update_file);
                    
                    if ($sql_content === false) {
                        $updates_errors[] = "$filename: Não foi possível ler o arquivo";
                        continue;
                    }
                    
                    // Remover comentários SQL
                    $sql_content = preg_replace('/--[^\n]*\n/', "\n", $sql_content);
                    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
                    
                    // Dividir queries por ponto e vírgula
                    $queries = array_filter(array_map('trim', preg_split('/;[\s]*(\n|$)/', $sql_content)));
                    
                    $executed = 0;
                    $skipped = 0;
                    $errors = 0;
                    
                    foreach ($queries as $query) {
                        if (empty($query) || strlen($query) < 5) continue;
                        
                        if ($mysqli->query($query)) {
                            $executed++;
                            error_log("Instalação: Query executada com sucesso ($filename)");
                        } else {
                            $error = $mysqli->error;
                            error_log("Instalação: Erro ao executar query ($filename): $error");
                            
                            // Ignorar apenas erros específicos que não são críticos
                            if (stripos($error, 'Duplicate column name') !== false || 
                                stripos($error, 'already exists') !== false ||
                                stripos($error, 'Duplicate key name') !== false) {
                                $skipped++;
                                error_log("Instalação: Erro não-crítico ignorado ($filename)");
                            } else {
                                $errors++;
                                $updates_errors[] = "$filename: $error";
                            }
                        }
                    }
                    
                    if ($executed > 0) {
                        $updates_log[] = "✅ $filename: $executed comando(s) executado(s)";
                    }
                    
                    if ($skipped > 0) {
                        $updates_log[] = "⏭️ $filename: $skipped já existente(s)";
                    }
                    
                    if ($errors > 0) {
                        $updates_log[] = "⚠️ $filename: $errors erro(s)";
                    }
                    
                } catch (Exception $e) {
                    $error_msg = "$filename: " . $e->getMessage();
                    $updates_errors[] = $error_msg;
                    error_log("Instalação: Exception - $error_msg");
                }
            }
            
            $mysqli->close();
            
        } catch (Exception $e) {
            $updates_errors[] = "Erro ao aplicar atualizações: " . $e->getMessage();
            error_log("Instalação: Erro geral - " . $e->getMessage());
        }
        
        // Preparar mensagem de resposta
        $message = '🎉 Sistema instalado com sucesso!';
        
        if (count($updates_log) > 0) {
            $message .= "\n\n📦 Atualizações SQL aplicadas (" . count($all_update_files) . " arquivo(s)):\n" . implode("\n", $updates_log);
        } else {
            $message .= "\n\n⚠️ Nenhuma atualização SQL foi aplicada.";
        }
        
        if (count($updates_errors) > 0) {
            $message .= "\n\n⚠️ Avisos durante atualizações:\n" . implode("\n", $updates_errors);
            $message .= "\n\n💡 O sistema foi instalado. Você pode verificar e aplicar atualizações manualmente em:\nConfigurações → Verificador de Banco de Dados";
        }
        
        error_log("Instalação: Finalizada - " . count($updates_log) . " atualizações, " . count($updates_errors) . " erros");
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'updates_applied' => count($updates_log),
            'updates_errors' => count($updates_errors),
            'sql_files_found' => count($all_update_files)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Ação inválida
echo json_encode([
    'success' => false,
    'message' => 'Ação inválida'
]);
?>
