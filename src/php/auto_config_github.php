<?php
/**
 * Auto-configuração do repositório GitHub
 * Tenta detectar automaticamente do .git/config e salvar no banco
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
    // Tentar detectar do .git/config
    $root_dir = realpath(__DIR__ . '/../..');
    $git_config = $root_dir . '/.git/config';
    
    if (!file_exists($git_config)) {
        throw new Exception('Arquivo .git/config não encontrado. Este sistema não foi clonado de um repositório Git.');
    }
    
    $config_content = file_get_contents($git_config);
    
    // Detectar URL do GitHub
    if (!preg_match('/github\.com[\/:]([^\/]+)\/([^\s\.]+)/i', $config_content, $matches)) {
        throw new Exception('Repositório GitHub não detectado no .git/config. Verifique se o remote origin aponta para o GitHub.');
    }
    
    $owner = $matches[1];
    $repo = str_replace('.git', '', $matches[2]);
    
    // Validar formato
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $owner) || !preg_match('/^[a-zA-Z0-9_-]+$/', $repo)) {
        throw new Exception('Formato de owner/repo inválido');
    }
    
    // Testar se o repositório existe
    $test_url = "https://api.github.com/repos/{$owner}/{$repo}";
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: GAT-Sistema',
                'Accept: application/vnd.github.v3+json'
            ],
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);
    
    $response = @file_get_contents($test_url, false, $context);
    
    if ($response === false) {
        throw new Exception('Não foi possível conectar ao GitHub para validar o repositório.');
    }
    
    // Verificar código HTTP
    $http_code = 200;
    if (isset($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/', $header, $matches)) {
                $http_code = intval($matches[1]);
                break;
            }
        }
    }
    
    if ($http_code == 404) {
        throw new Exception("Repositório não encontrado no GitHub: {$owner}/{$repo}");
    }
    
    if ($http_code != 200) {
        throw new Exception("Erro ao verificar repositório (HTTP {$http_code})");
    }
    
    // Salvar no banco de dados
    $config_data = json_encode([
        'owner' => $owner,
        'repo' => $repo,
        'configured_at' => date('Y-m-d H:i:s'),
        'auto_configured' => true
    ]);
    
    // Verificar se já existe
    $check_sql = "SELECT id FROM system_config WHERE config_key = 'github_repository'";
    $check_result = $mysqli->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        // Atualizar
        $stmt = $mysqli->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'github_repository'");
        $stmt->bind_param('s', $config_data);
    } else {
        // Inserir
        $stmt = $mysqli->prepare("INSERT INTO system_config (config_key, config_value) VALUES ('github_repository', ?)");
        $stmt->bind_param('s', $config_data);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao salvar no banco de dados: ' . $stmt->error);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Repositório configurado automaticamente',
        'repository' => "https://github.com/{$owner}/{$repo}",
        'owner' => $owner,
        'repo' => $repo
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
