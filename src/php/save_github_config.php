<?php
/**
 * Salvar configuração do repositório GitHub
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

// Verificar se é requisição para salvar branch
$action = $_POST['action'] ?? 'save_repo';

if ($action === 'save_branch') {
    saveBranch();
    exit;
}

// Função para salvar o branch selecionado
function saveBranch() {
    global $mysqli;
    
    try {
        $branch = trim($_POST['branch'] ?? '');
        
        if (empty($branch)) {
            throw new Exception('Branch é obrigatório');
        }
        
        // Validar formato do branch
        if (!preg_match('/^[a-zA-Z0-9_\/-]+$/', $branch)) {
            throw new Exception('Nome do branch inválido');
        }
        
        // Verificar se já existe
        $check_sql = "SELECT id FROM system_config WHERE config_key = 'github_branch'";
        $check_result = $mysqli->query($check_sql);
        
        if ($check_result->num_rows > 0) {
            // Atualizar
            $stmt = $mysqli->prepare("UPDATE system_config SET config_value = ? WHERE config_key = 'github_branch'");
            $stmt->bind_param('s', $branch);
        } else {
            // Inserir
            $stmt = $mysqli->prepare("INSERT INTO system_config (config_key, config_value) VALUES ('github_branch', ?)");
            $stmt->bind_param('s', $branch);
        }
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => "Branch '{$branch}' configurado com sucesso!"
            ]);
        } else {
            throw new Exception('Erro ao salvar no banco de dados');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

try {
    $github_url = trim($_POST['github_url'] ?? '');
    
    // Validar se URL foi fornecida
    if (empty($github_url)) {
        throw new Exception('URL do GitHub é obrigatória');
    }
    
    // Extrair owner e repo da URL
    $pattern = '/github\.com[\/:]([^\/]+)\/([^\/\s\.]+)/i';
    if (!preg_match($pattern, $github_url, $matches)) {
        throw new Exception('URL do GitHub inválida. Use o formato: https://github.com/usuario/repositorio');
    }
    
    $owner = $matches[1];
    $repo = str_replace('.git', '', $matches[2]);
    
    // Validar formato (alfanumérico, hífen e underscore)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $owner)) {
        throw new Exception('Nome do proprietário inválido');
    }
    
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $repo)) {
        throw new Exception('Nome do repositório inválido');
    }
    
    // Testar se o repositório existe fazendo uma requisição à API do GitHub
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
        throw new Exception('Não foi possível conectar ao GitHub. Verifique sua conexão com a internet.');
    }
    
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
        throw new Exception("Repositório não encontrado: https://github.com/{$owner}/{$repo}");
    }
    
    if ($http_code != 200) {
        throw new Exception("Erro ao verificar repositório (HTTP {$http_code})");
    }
    
    // Salvar no banco de dados
    $config_data = json_encode([
        'owner' => $owner,
        'repo' => $repo,
        'configured_at' => date('Y-m-d H:i:s')
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
        'message' => 'Repositório configurado com sucesso',
        'repository' => "https://github.com/{$owner}/{$repo}"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
