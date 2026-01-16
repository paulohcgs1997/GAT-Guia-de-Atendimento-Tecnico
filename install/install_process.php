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
        
        // Cria flag de instalação
        createInstallFlag();
        
        // ========== APLICAR ATUALIZAÇÕES AUTOMÁTICAS ==========
        $updates_log = [];
        $updates_errors = [];
        
        try {
            // Reconectar ao banco recém-criado
            $mysqli = new mysqli($host, $user, $pass, $dbname);
            $mysqli->set_charset('utf8mb4');
            
            // Buscar arquivos de atualização na pasta install
            $update_files = glob(__DIR__ . '/update_*.sql');
            
            foreach ($update_files as $update_file) {
                $filename = basename($update_file);
                
                try {
                    // Ler conteúdo do arquivo
                    $sql_content = file_get_contents($update_file);
                    
                    if ($sql_content === false) {
                        $updates_errors[] = "$filename: Não foi possível ler o arquivo";
                        continue;
                    }
                    
                    // Remover comentários
                    $sql_content = preg_replace('/--[^\n]*\n/', "\n", $sql_content);
                    
                    // Dividir queries
                    $queries = array_filter(array_map('trim', preg_split('/;[\s]*(\n|$)/', $sql_content)));
                    
                    $executed = 0;
                    $skipped = 0;
                    
                    foreach ($queries as $query) {
                        if (empty($query) || strlen($query) < 5) continue;
                        
                        if ($mysqli->query($query)) {
                            $executed++;
                        } else {
                            // Ignorar erros de coluna duplicada (não crítico)
                            if (stripos($mysqli->error, 'Duplicate column name') !== false || 
                                stripos($mysqli->error, 'Unknown column') !== false) {
                                $skipped++;
                            }
                        }
                    }
                    
                    if ($executed > 0 || $skipped > 0) {
                        $updates_log[] = "$filename: $executed executado(s), $skipped já existente(s)";
                    }
                    
                } catch (Exception $e) {
                    $updates_errors[] = "$filename: " . $e->getMessage();
                }
            }
            
            $mysqli->close();
            
        } catch (Exception $e) {
            $updates_errors[] = "Erro ao aplicar atualizações: " . $e->getMessage();
        }
        
        // Preparar mensagem de resposta
        $message = 'Sistema instalado com sucesso!';
        
        if (count($updates_log) > 0) {
            $message .= "\n\n✅ Atualizações aplicadas:\n" . implode("\n", $updates_log);
        }
        
        if (count($updates_errors) > 0) {
            $message .= "\n\n⚠️ Avisos durante atualizações:\n" . implode("\n", $updates_errors);
            $message .= "\n\nO sistema foi instalado normalmente. Você pode aplicar as atualizações manualmente em Configurações → Verificador de Banco de Dados.";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'updates_applied' => count($updates_log),
            'updates_errors' => count($updates_errors)
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
