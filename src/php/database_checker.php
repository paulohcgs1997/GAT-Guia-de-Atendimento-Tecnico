<?php
/**
 * Verificador de Estrutura do Banco de Dados
 * Compara a estrutura atual com a esperada e identifica diferenças
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
    // ========== DEFINIR ESTRUTURA ESPERADA ==========
    
    $expected_structure = [
        'blocos' => [
            'status' => "ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft'"
        ],
        'services' => [
            'status' => "ENUM('draft', 'pending', 'approved', 'rejected') DEFAULT 'draft'"
        ],
        'usuarios' => [
            'force_password_change' => "TINYINT(1) DEFAULT 0"
        ],
        'steps' => [
            // Adicione aqui novos campos para steps se necessário
        ],
        'questions' => [
            // Adicione aqui novos campos para questions se necessário
        ]
    ];
    
    // ========== VERIFICAR TABELAS EXISTENTES ==========
    
    $tables_query = $mysqli->query("SHOW TABLES");
    $existing_tables = [];
    while ($row = $tables_query->fetch_array()) {
        $existing_tables[] = $row[0];
    }
    
    // ========== VERIFICAR COLUNAS EM CADA TABELA ==========
    
    foreach ($expected_structure as $table => $expected_columns) {
        // Verificar se a tabela existe
        if (!in_array($table, $existing_tables)) {
            $response['missing_tables'][] = $table;
            $response['needs_update'] = true;
            continue;
        }
        
        // Verificar colunas da tabela
        $columns_query = $mysqli->query("SHOW COLUMNS FROM `$table`");
        $existing_columns = [];
        
        while ($col = $columns_query->fetch_assoc()) {
            $existing_columns[] = $col['Field'];
        }
        
        // Verificar quais colunas estão faltando
        foreach ($expected_columns as $column_name => $column_definition) {
            if (!in_array($column_name, $existing_columns)) {
                $response['missing_columns'][] = [
                    'table' => $table,
                    'column' => $column_name,
                    'definition' => $column_definition
                ];
                $response['needs_update'] = true;
            }
        }
    }
    
    // ========== GERAR LISTA DE ATUALIZAÇÕES DISPONÍVEIS ==========
    
    $updates_map = [
        'status_field' => [
            'id' => 'status_field',
            'name' => 'Sistema de Status para Tutoriais e Serviços',
            'description' => 'Adiciona campo status (draft, pending, approved, rejected) para melhor controle do fluxo de aprovação',
            'tables_affected' => ['blocos', 'services'],
            'file' => 'update_status_field.sql',
            'priority' => 'high'
        ],
        'force_password_change' => [
            'id' => 'force_password_change',
            'name' => 'Sistema de Troca de Senha Obrigatória',
            'description' => 'Adiciona campo force_password_change para forçar usuários a trocarem senha no primeiro login',
            'tables_affected' => ['usuarios'],
            'file' => 'add_force_password_change.sql',
            'priority' => 'medium'
        ]
    ];
    
    // Verificar quais atualizações são necessárias
    $missing_tables_set = array_flip($response['missing_tables']);
    
    foreach ($response['missing_columns'] as $missing) {
        $table = $missing['table'];
        $column = $missing['column'];
        
        // Mapear coluna para update
        if ($column === 'status' && ($table === 'blocos' || $table === 'services')) {
            if (!in_array($updates_map['status_field'], $response['updates_available'], true)) {
                $response['updates_available'][] = $updates_map['status_field'];
            }
        } elseif ($column === 'force_password_change' && $table === 'usuarios') {
            if (!in_array($updates_map['force_password_change'], $response['updates_available'], true)) {
                $response['updates_available'][] = $updates_map['force_password_change'];
            }
        }
    }
    
    // ========== INFORMAÇÕES ADICIONAIS ==========
    
    $response['database_info'] = [
        'name' => $mysqli->get_connection_stats()['db'] ?? 'N/A',
        'total_tables' => count($existing_tables),
        'checked_tables' => count($expected_structure)
    ];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = 'Erro ao verificar banco de dados: ' . $e->getMessage();
    error_log('Database Checker Error: ' . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
