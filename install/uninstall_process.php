<?php
// uninstall_process.php - Processa a desinstalação do sistema
header('Content-Type: application/json');

// Recebe os dados JSON
$input = json_decode(file_get_contents('php://input'), true);

$host = $input['db_host'] ?? 'localhost';
$dbname = $input['db_name'] ?? 'gat';
$user = $input['db_user'] ?? 'root';
$pass = $input['db_pass'] ?? '';

$errors = [];
$success = [];

try {
    // 1. Conectar ao MySQL e limpar/remover banco de dados
    try {
        $mysqli = new mysqli($host, $user, $pass);
        
        if ($mysqli->connect_errno) {
            throw new Exception('Erro de conexão: ' . $mysqli->connect_error);
        }
        
        // Verifica se o banco existe
        $result = $mysqli->query("SHOW DATABASES LIKE '{$dbname}'");
        
        if ($result && $result->num_rows > 0) {
            // Seleciona o banco
            $mysqli->select_db($dbname);
            
            // Lista todas as tabelas
            $tables = [];
            $result = $mysqli->query("SHOW TABLES");
            
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                
                // Desabilita checagem de foreign keys
                $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");
                
                // Remove cada tabela individualmente
                $tablesDropped = 0;
                foreach ($tables as $table) {
                    if ($mysqli->query("DROP TABLE IF EXISTS `{$table}`")) {
                        $tablesDropped++;
                    }
                }
                
                // Reabilita checagem de foreign keys
                $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");
                
                $success[] = "✓ {$tablesDropped} tabela(s) removida(s)";
            }
            
            // Remove o banco de dados
            if ($mysqli->query("DROP DATABASE IF EXISTS `{$dbname}`")) {
                $success[] = "✓ Banco de dados '{$dbname}' removido com sucesso";
            } else {
                $errors[] = "✗ Erro ao remover banco: " . $mysqli->error;
            }
        } else {
            $success[] = "✓ Banco de dados '{$dbname}' não existia";
        }
        
        $mysqli->close();
    } catch (Exception $e) {
        $errors[] = "✗ Erro ao conectar/remover banco: " . $e->getMessage();
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
            'partial_success' => $success
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => implode("\n", $success)
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro fatal durante desinstalação: ' . $e->getMessage()
    ]);
}
?>
