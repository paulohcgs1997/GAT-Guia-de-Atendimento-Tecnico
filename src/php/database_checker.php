<?php
/**
 * Verificador de Estrutura do Banco de Dados
 * Lê arquivos SQL da pasta install e verifica quais precisam ser aplicados
 */

session_start();
require_once __DIR__ . '/../config/conexao.php';

// Verificar autenticação e permissão de admin
if (!isset($_SESSION['user_id']) || $_SESSION['perfil'] != '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$response = [
    'success' => true,
    'needs_update' => false,
    'missing_columns' => [],
    'missing_tables' => [],
    'updates_available' => []
];

try {
    // ========== LER ARQUIVOS SQL DA PASTA INSTALL ==========
    
    $install_dir = __DIR__ . '/../../install';
    $sql_files = glob($install_dir . '/*.sql');
    
    // Ignorar arquivo principal database.sql
    $sql_files = array_filter($sql_files, function($file) {
        return basename($file) !== 'database.sql';
    });
    
    // ========== BUSCAR TABELAS E COLUNAS EXISTENTES ==========
    
    $existing_tables = [];
    $tables_query = $mysqli->query("SHOW TABLES");
    while ($row = $tables_query->fetch_array()) {
        $table_name = $row[0];
        $existing_tables[$table_name] = [];
        
        // Buscar colunas da tabela
        $columns_query = $mysqli->query("SHOW COLUMNS FROM `$table_name`");
        while ($col = $columns_query->fetch_assoc()) {
            $existing_tables[$table_name][] = $col['Field'];
        }
    }
    
    // ========== ANALISAR CADA ARQUIVO SQL ==========
    
    foreach ($sql_files as $sql_file) {
        $filename = basename($sql_file);
        $sql_content = file_get_contents($sql_file);
        
        // Extrair comentários do início do arquivo (descrição)
        preg_match_all('/^--\s*(.+)$/m', $sql_content, $comments);
        $description = implode(' ', $comments[1]);
        
        // Detectar tipo de operação e tabelas afetadas
        $needs_update = false;
        $tables_affected = [];
        $missing_items = [];
        
        // Verificar ALTER TABLE
        if (preg_match_all('/ALTER\s+TABLE\s+`?(\w+)`?\s+ADD\s+(?:COLUMN\s+)?`?(\w+)`?/i', $sql_content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $table = $matches[1][$i];
                $column = $matches[2][$i];
                
                if (!isset($existing_tables[$table])) {
                    $needs_update = true;
                    $missing_items[] = "Tabela '$table' não existe";
                    $tables_affected[] = $table;
                } elseif (!in_array($column, $existing_tables[$table])) {
                    $needs_update = true;
                    $missing_items[] = "Coluna '$column' em '$table'";
                    $tables_affected[] = $table;
                }
            }
        }
        
        // Verificar CREATE TABLE
        if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sql_content, $matches)) {
            foreach ($matches[1] as $table) {
                if (!isset($existing_tables[$table])) {
                    $needs_update = true;
                    $missing_items[] = "Tabela '$table' não existe";
                    $tables_affected[] = $table;
                }
            }
        }
        
        // Se precisa atualizar, adicionar à lista
        if ($needs_update) {
            $response['needs_update'] = true;
            
            // Determinar prioridade baseado no nome do arquivo
            $priority = 'medium';
            if (strpos($filename, 'status') !== false || strpos($filename, 'users') !== false) {
                $priority = 'high';
            }
            
            $response['updates_available'][] = [
                'id' => pathinfo($filename, PATHINFO_FILENAME),
                'name' => ucwords(str_replace(['_', 'update', 'add'], [' ', '', ''], pathinfo($filename, PATHINFO_FILENAME))),
                'description' => !empty($description) ? $description : 'Atualização de banco de dados',
                'tables_affected' => array_unique($tables_affected),
                'file' => $filename,
                'priority' => $priority,
                'missing_items' => $missing_items
            ];
            
            // Adicionar aos missing_columns para compatibilidade
            foreach ($missing_items as $item) {
                if (strpos($item, 'Coluna') !== false) {
                    $response['missing_columns'][] = $item;
                } elseif (strpos($item, 'Tabela') !== false) {
                    $response['missing_tables'][] = str_replace(["Tabela '", "' não existe"], '', $item);
                }
            }
        }
    }
    
    // ========== INFORMAÇÕES ADICIONAIS ==========
    
    $response['database_info'] = [
        'name' => $mysqli->get_connection_stats()['db'] ?? 'N/A',
        'total_tables' => count($existing_tables),
        'sql_files_checked' => count($sql_files),
        'updates_pending' => count($response['updates_available'])
    ];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Erro ao verificar banco de dados: ' . $e->getMessage();
    error_log('Database Checker Error: ' . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
