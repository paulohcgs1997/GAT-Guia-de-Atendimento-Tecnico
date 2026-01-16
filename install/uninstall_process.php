<?php
// uninstall_process.php - Processa a desinstalação do sistema
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros no output (para não quebrar o JSON)

// Log de debug
$debugLog = [];

// Recebe os dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$debugLog[] = "Input recebido: " . json_encode($input);

$host = $input['db_host'] ?? 'localhost';
$dbname = $input['db_name'] ?? 'gat';
$user = $input['db_user'] ?? 'root';
$pass = $input['db_pass'] ?? '';

$debugLog[] = "Credenciais: host={$host}, db={$dbname}, user={$user}";

$errors = [];
$success = [];

try {
    // 1. Conectar ao MySQL e limpar/remover banco de dados
    try {
        $debugLog[] = "Tentando conectar ao MySQL...";
        $mysqli = new mysqli($host, $user, $pass);
        
        if ($mysqli->connect_errno) {
            throw new Exception('Erro de conexão: ' . $mysqli->connect_error);
        }
        
        $debugLog[] = "Conexão estabelecida com sucesso";
        
        // Verifica se o banco existe
        $result = $mysqli->query("SHOW DATABASES LIKE '{$dbname}'");
        $debugLog[] = "Verificando existência do banco '{$dbname}'...";
        
        if ($result && $result->num_rows > 0) {
            $debugLog[] = "Banco '{$dbname}' encontrado, iniciando remoção...";
            
            // Seleciona o banco
            if (!$mysqli->select_db($dbname)) {
                throw new Exception("Erro ao selecionar banco: " . $mysqli->error);
            }
            
            $debugLog[] = "Banco selecionado com sucesso";
            
            // Lista todas as tabelas
            $tables = [];
            $result = $mysqli->query("SHOW TABLES");
            
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                
                $debugLog[] = "Total de tabelas encontradas: " . count($tables);
                $debugLog[] = "Tabelas: " . implode(', ', $tables);
                
                // Desabilita checagem de foreign keys
                $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
                $debugLog[] = "Foreign key checks desabilitados";
                
                // Remove cada tabela individualmente
                $tablesDropped = 0;
                foreach ($tables as $table) {
                    $debugLog[] = "Removendo tabela: {$table}";
                    if ($mysqli->query("DROP TABLE IF EXISTS `{$table}`")) {
                        $tablesDropped++;
                        $debugLog[] = "  ✓ Tabela {$table} removida";
                    } else {
                        $debugLog[] = "  ✗ Erro ao remover {$table}: " . $mysqli->error;
                    }
                }
                
                // Reabilita checagem de foreign keys
                $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
                $debugLog[] = "Foreign key checks reabilitados";
                
                $success[] = "✓ {$tablesDropped} de " . count($tables) . " tabela(s) removida(s)";
            } else {
                $debugLog[] = "Erro ao listar tabelas: " . $mysqli->error;
            }
            
            // Remove o banco de dados
            $debugLog[] = "Tentando remover banco de dados '{$dbname}'...";
            if ($mysqli->query("DROP DATABASE IF EXISTS `{$dbname}`")) {
                $success[] = "✓ Banco de dados '{$dbname}' removido com sucesso";
                $debugLog[] = "✓ Banco removido com sucesso";
            } else {
                $errors[] = "✗ Erro ao remover banco: " . $mysqli->error;
                $debugLog[] = "✗ Erro ao remover banco: " . $mysqli->error;
            }
        } else {
            $success[] = "ℹ️ Banco de dados '{$dbname}' não encontrado (já removido ou nunca existiu)";
            $debugLog[] = "Banco '{$dbname}' não encontrado";
        }
        
        $mysqli->close();
        $debugLog[] = "Conexão fechada";
    } catch (Exception $e) {
        $errors[] = "✗ Erro ao conectar/remover banco: " . $e->getMessage();
        $debugLog[] = "EXCEÇÃO: " . $e->getMessage();
    }
    
    // 2. Remover arquivo de configuração
    $configPath = dirname(__DIR__) . '/src/config/conexao.php';
    if (file_exists($configPath)) {
        if (unlink($configPath)) {
            $success[] = "✓ Arquivo de configuração removido";
        } else {
            $errors[] = "✗ Erro ao remover arquivo de configuração";
        }
    } else {
        $success[] = "✓ Arquivo de configuração não existia";
    }
    
    // 3. Remover flag de instalação
    $flagPath = __DIR__ . '/.installed';
    if (file_exists($flagPath)) {
        if (unlink($flagPath)) {
            $success[] = "✓ Flag de instalação removida";
        } else {
            $errors[] = "✗ Erro ao remover flag de instalação";
        }
    } else {
        $success[] = "✓ Flag de instalação não existia";
    }
    
    // 4. Tentar limpar sessões (opcional)
    $sessionPath = session_save_path();
    if (!empty($sessionPath) && is_dir($sessionPath)) {
        $sessions = glob($sessionPath . '/sess_*');
        $sessionsCleaned = 0;
        foreach ($sessions as $session) {
            if (@unlink($session)) {
                $sessionsCleaned++;
            }
        }
        if ($sessionsCleaned > 0) {
            $success[] = "✓ {$sessionsCleaned} sessão(ões) limpa(s)";
        }
    }
    
    // Verifica se houve erros críticos
    if (count($errors) > 0) {
        echo json_encode([
            'success' => false,
            'message' => implode("\n", array_merge($success, $errors)),
            'errors' => $errors,
            'partial_success' => $success,
            'debug' => $debugLog
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => implode("\n", $success),
            'debug' => $debugLog
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro fatal durante desinstalação: ' . $e->getMessage(),
        'debug' => $debugLog
    ]);
}
?>
