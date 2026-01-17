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
            
            if ($mysqli->connect_errno) {
                throw new Exception('Erro ao reconectar: ' . $mysqli->connect_error);
            }
            
            error_log('========== INSTALAÇÃO: INICIANDO ATUALIZAÇÕES SQL ==========');
            
            // Buscar arquivos de atualização na pasta install/update_sql primeiro
            $update_sql_dir = __DIR__ . '/update_sql';
            $all_update_files = [];
            
            error_log('Instalação: Verificando pasta: ' . $update_sql_dir);
            
            if (is_dir($update_sql_dir)) {
                // Buscar TODOS os arquivos SQL na pasta update_sql
                $all_update_files = glob($update_sql_dir . '/*.sql');
                error_log('Instalação: Encontrados em update_sql/: ' . count($all_update_files));
                
                foreach ($all_update_files as $file) {
                    error_log('Instalação: Arquivo encontrado: ' . basename($file));
                }
            } else {
                error_log('Instalação: Pasta update_sql NÃO EXISTE!');
            }
            
            // Se não encontrou nada em update_sql, buscar na pasta install (fallback)
            if (empty($all_update_files)) {
                error_log('Instalação: Fazendo fallback para pasta install/');
                
                $update_files = glob(__DIR__ . '/update_*.sql');
                $add_files = glob(__DIR__ . '/add_*.sql');
                
                error_log('Instalação: update_*.sql: ' . count($update_files));
                error_log('Instalação: add_*.sql: ' . count($add_files));
                
                // Mesclar os dois arrays e filtrar database.sql
                $all_update_files = array_merge($update_files, $add_files);
                $all_update_files = array_filter($all_update_files, function($file) {
                    return basename($file) !== 'database.sql';
                });
            }
            
            error_log('Instalação: TOTAL de arquivos a processar: ' . count($all_update_files));
            
            if (empty($all_update_files)) {
                $updates_errors[] = "⚠️ Nenhum arquivo SQL de atualização encontrado!";
                error_log('Instalação: AVISO - Nenhum arquivo encontrado para aplicar!');
            }
            
            foreach ($all_update_files as $update_file) {
                $filename = basename($update_file);
                
                error_log("========== Processando: $filename ==========");
                
                try {
                    // Ler conteúdo do arquivo
                    $sql_content = file_get_contents($update_file);
                    
                    if ($sql_content === false) {
                        $error_msg = "$filename: Não foi possível ler o arquivo";
                        $updates_errors[] = $error_msg;
                        error_log("Instalação: ERRO - $error_msg");
                        continue;
                    }
                    
                    error_log("Instalação: Arquivo lido com sucesso, tamanho: " . strlen($sql_content) . " bytes");
                    
                    // Remover comentários SQL
                    $sql_content = preg_replace('/--[^\n]*\n/', "\n", $sql_content);
                    $sql_content = preg_replace('/\/\*.*?\*\//s', '', $sql_content);
                    
                    // Dividir queries por ponto e vírgula
                    $queries = array_filter(array_map('trim', preg_split('/;[\s]*(\n|$)/', $sql_content)));
                    
                    error_log("Instalação: Dividido em " . count($queries) . " query(ies)");
                    
                    $executed = 0;
                    $skipped = 0;
                    $errors = 0;
                    
                    foreach ($queries as $idx => $query) {
                        if (empty($query) || strlen($query) < 5) {
                            error_log("Instalação: Query #$idx ignorada (vazia ou muito curta)");
                            continue;
                        }
                        
                        error_log("Instalação: Executando query #$idx: " . substr($query, 0, 100) . "...");
                        
                        if ($mysqli->query($query)) {
                            $executed++;
                            error_log("Instalação: ✅ Query #$idx executada com sucesso");
                        } else {
                            $error = $mysqli->error;
                            error_log("Instalação: ❌ Erro na query #$idx: $error");
                            
                            // Ignorar apenas erros específicos que não são críticos
                            if (stripos($error, 'Duplicate column name') !== false || 
                                stripos($error, 'already exists') !== false ||
                                stripos($error, 'Duplicate key name') !== false) {
                                $skipped++;
                                error_log("Instalação: ⏭️ Erro não-crítico ignorado (já existe)");
                            } else {
                                $errors++;
                                $updates_errors[] = "$filename [Query #$idx]: $error";
                                error_log("Instalação: ⚠️ ERRO CRÍTICO registrado");
                            }
                        }
                    }
                    
                    // Log do resultado final deste arquivo
                    error_log("Instalação: Resultado $filename - Executadas: $executed, Ignoradas: $skipped, Erros: $errors");
                    
                    if ($executed > 0) {
                        $updates_log[] = "✅ $filename: $executed comando(s) executado(s)";
                    }
                    
            error_log('========== INSTALAÇÃO: ATUALIZAÇÕES FINALIZADAS ==========');
            error_log('Total de logs: ' . count($updates_log));
            error_log('Total de erros: ' . count($updates_errors));
            
        } catch (Exception $e) {
            $error_msg = "Erro ao aplicar atualizações: " . $e->getMessage();
            $updates_errors[] = $error_msg;
            error_log("Instalação: ERRO GERAL - $error_msg");
            error_log("Instalação: Stack trace: " . $e->getTraceAsString());
        }
        
        // Preparar mensagem de resposta com informações detalhadas
        $message = '🎉 Sistema instalado com sucesso!';
        
        $total_files = isset($all_update_files) ? count($all_update_files) : 0;
        
        if ($total_files > 0) {
            $message .= "\n\n📦 Arquivos SQL encontrados: $total_files";
        } else {
            $message .= "\n\n⚠️ ATENÇÃO: Nenhum arquivo SQL de atualização foi encontrado!";
            $message .= "\n📂 Verifique se a pasta install/update_sql/ existe e contém os arquivos.";
        }
        
        if (count($updates_log) > 0) {
            $message .= "\n\n✅ Atualizações aplicadas:\n" . implode("\n", $updates_log);
        } else if ($total_files > 0) {
            $message .= "\n\n⚠️ Nenhuma atualização foi aplicada (todos os arquivos falharam ou já existiam).";
        }
        
        if (count($updates_errors) > 0) {
            $message .= "\n\n⚠️ Erros detectados (" . count($updates_errors) . "):\n" . implode("\n", array_slice($updates_errors, 0, 5));
            
            if (count($updates_errors) > 5) {
                $message .= "\n... e mais " . (count($updates_errors) - 5) . " erro(s)";
            }
            
            $message .= "\n\n💡 Execute manualmente o arquivo install/fix_usuarios_structure.sql no seu banco de dados.";
            $message .= "\nOu acesse: Configurações → Verificador de Banco de Dados";
        }
        
        error_log("Instalação: Mensagem final preparada");
        error_log("Instalação: ========== FIM ==========");
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'updates_applied' => count($updates_log),
            'updates_errors' => count($updates_errors),
            'sql_files_found' => $total_files,
            'debug_info' => [
                'update_sql_dir_exists' => isset($update_sql_dir) && is_dir($update_sql_dir),
                'update_sql_dir_path' => isset($update_sql_dir) ? $update_sql_dir : 'N/A',
                'files_found' => $total_files
            ]
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
