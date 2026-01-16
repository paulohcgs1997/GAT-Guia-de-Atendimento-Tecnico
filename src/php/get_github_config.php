<?php
/**
 * Buscar configuração do repositório GitHub
 */
session_start();
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

// Verificar autenticação e permissão de admin
if (!isset($_SESSION['user_id']) || $_SESSION['perfil'] != '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

try {
    // Tentar detectar do .git/config primeiro
    $root_dir = realpath(__DIR__ . '/../..');
    $git_config = $root_dir . '/.git/config';
    
    $config = null;
    
    if (file_exists($git_config)) {
        $config_content = file_get_contents($git_config);
        
        // Detectar URL do GitHub
        if (preg_match('/github\.com[\/:]([^\/]+)\/([^\s\.]+)/i', $config_content, $matches)) {
            $config = [
                'owner' => $matches[1],
                'repo' => str_replace('.git', '', $matches[2]),
                'source' => 'git'
            ];
        }
    }
    
    // Se não detectou, buscar do banco
    if (!$config) {
        $sql = "SELECT config_value FROM system_config WHERE config_key = 'github_repository'";
        $result = $mysqli->query($sql);
        
        if ($result && $row = $result->fetch_assoc()) {
            $repo_data = json_decode($row['config_value'], true);
            if ($repo_data && isset($repo_data['owner']) && isset($repo_data['repo'])) {
                $config = [
                    'owner' => $repo_data['owner'],
                    'repo' => $repo_data['repo'],
                    'source' => 'database'
                ];
            }
        }
    }
    
    if ($config) {
        echo json_encode([
            'success' => true,
            'config' => $config
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Repositório não configurado'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar configuração: ' . $e->getMessage()
    ]);
}
?>
