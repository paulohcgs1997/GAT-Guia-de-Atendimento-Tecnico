<?php
/**
 * Aplicar Migrações/Atualizações no Banco de Dados
 * Executa os scripts SQL de atualização
 */

session_start();
require_once __DIR__ . '/../config/conexao.php';

// Verificar autenticação e permissão de admin
if (!isset($_SESSION['user_id']) || $_SESSION['perfil'] != '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem atualizar o banco de dados.']);
    exit;
}

$response = ['success' => false, 'message' => '', 'queries_executed' => []];

try {
    $migration_id = $_POST['migration_id'] ?? '';
    
    if (empty($migration_id)) {
        throw new Exception('ID da migração não informado');
    }
    
    // Definir migrações disponíveis
    $migrations = [
        'status_field' => [
            'file' => '../../install/update_status_field.sql',
            'description' => 'Adiciona campo status'
        ],
        'force_password_change' => [
            'file' => '../../install/add_force_password_change.sql',
            'description' => 'Adiciona campo force_password_change para troca obrigatória de senha'
        ]
    ];
    
    if (!isset($migrations[$migration_id])) {
        throw new Exception('Migração não encontrada');
    }
    
    $migration = $migrations[$migration_id];
    $sql_file = __DIR__ . '/' . $migration['file'];
    
    if (!file_exists($sql_file)) {
        throw new Exception("Arquivo SQL não encontrado: $sql_file");
    }
    
    // Ler o arquivo SQL
    $sql_content = file_get_contents($sql_file);
    
    if ($sql_content === false) {
        throw new Exception('Erro ao ler arquivo SQL');
    }
    
    // Remover comentários de linha
    $sql_content = preg_replace('/--[^\n]*\n/', "\n", $sql_content);
    
    // Dividir por comandos (ponto e vírgula no final da linha)
    $queries = array_filter(array_map('trim', preg_split('/;[\s]*(\n|$)/', $sql_content)));
    
    // Iniciar transação
    $mysqli->begin_transaction();
    
    $executed = 0;
    $errors = [];
    $skipped = 0;
    
    foreach ($queries as $query) {
        // Ignorar linhas vazias
        if (empty($query) || strlen($query) < 5) {
            continue;
        }
        
        try {
            if ($mysqli->query($query)) {
                $executed++;
                $response['queries_executed'][] = substr($query, 0, 80) . '...';
            } else {
                $error = $mysqli->error;
                
                // Erros que podem ser ignorados (não críticos)
                $ignorable_errors = [
                    'Duplicate column name',
                    'already exists',
                    'Unknown column' // Se tentar atualizar coluna que não existe ainda
                ];
                
                $should_ignore = false;
                foreach ($ignorable_errors as $ignorable) {
                    if (stripos($error, $ignorable) !== false) {
                        $should_ignore = true;
                        break;
                    }
                }
                
                if ($should_ignore) {
                    $skipped++;
                    $response['queries_executed'][] = "SKIPPED: " . substr($query, 0, 50) . '...';
                } else {
                    $errors[] = "Query: " . substr($query, 0, 100) . " - Erro: " . $error;
                }
            }
        } catch (Exception $e) {
            $error_msg = $e->getMessage();
            
            // Ignorar erros de coluna duplicada
            if (stripos($error_msg, 'Duplicate column name') !== false || 
                stripos($error_msg, 'Unknown column') !== false) {
                $skipped++;
                $response['queries_executed'][] = "SKIPPED: " . substr($query, 0, 50) . '...';
            } else {
                $errors[] = $error_msg;
            }
        }
    }
    
    if (count($errors) > 0) {
        $mysqli->rollback();
        throw new Exception('Erros críticos durante a execução: ' . implode('; ', $errors));
    }
    
    // Commit da transação
    $mysqli->commit();
    
    $response['success'] = true;
    $message_parts = [];
    if ($executed > 0) $message_parts[] = "$executed comando(s) executado(s)";
    if ($skipped > 0) $message_parts[] = "$skipped já existente(s)";
    
    $response['message'] = "Atualização aplicada com sucesso! " . implode(', ', $message_parts) . ".";
    
    // Log da ação
    error_log("Database Migration Applied: $migration_id by user #" . $_SESSION['user_id']);
    
} catch (Exception $e) {
    if (isset($mysqli) && $mysqli->connect_errno === 0) {
        $mysqli->rollback();
    }
    $response['message'] = $e->getMessage();
    error_log('Migration Error: ' . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
