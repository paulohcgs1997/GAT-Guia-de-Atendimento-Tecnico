<?php
/**
 * Buscar configuração do repositório GitHub
 */
 
// Suprimir warnings e notices para garantir JSON limpo
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');

// Limpar qualquer output anterior
ob_start();

session_start();
require_once __DIR__ . '/../config/conexao.php';

// Limpar buffer e começar limpo
ob_end_clean();
ob_start();

header('Content-Type: application/json; charset=UTF-8');

// Verificar autenticação e permissão de admin
if (!isset($_SESSION['user_id']) || $_SESSION['perfil'] != '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Verificar se é requisição para buscar branches
$action = $_GET['action'] ?? 'get_config';

if ($action === 'get_branches') {
    getBranches();
    exit;
}

// Função para buscar branches do GitHub
function getBranches() {
    global $mysqli;
    
    try {
        // Buscar configuração do repositório
        $root_dir = realpath(__DIR__ . '/../..');
        $git_config = $root_dir . '/.git/config';
        
        $owner = null;
        $repo = null;
        
        // Detectar do .git/config
        if (file_exists($git_config)) {
            $config_content = file_get_contents($git_config);
            if (preg_match('/github\.com[\/:]([^\/]+)\/([^\s\.]+)/i', $config_content, $matches)) {
                $owner = $matches[1];
                $repo = str_replace('.git', '', $matches[2]);
            }
        }
        
        // Se não detectou, buscar do banco
        if (!$owner || !$repo) {
            $sql = "SELECT config_value FROM system_config WHERE config_key = 'github_repository'";
            $result = $mysqli->query($sql);
            
            if ($result && $row = $result->fetch_assoc()) {
                $repo_data = json_decode($row['config_value'], true);
                if ($repo_data) {
                    $owner = $repo_data['owner'] ?? null;
                    $repo = $repo_data['repo'] ?? null;
                }
            }
        }
        
        if (!$owner || !$repo) {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => 'Repositório não configurado',
                'debug' => ['owner' => $owner, 'repo' => $repo]
            ]);
            exit;
        }
        
        // Buscar token do github_config.php se existir
        $token = null;
        $config_file = __DIR__ . '/../config/github_config.php';
        if (file_exists($config_file)) {
            require_once $config_file;
            if (defined('GITHUB_TOKEN')) {
                $token = GITHUB_TOKEN;
            }
        }
        
        // Se não encontrou no arquivo, buscar do banco
        if (!$token) {
            $sql = "SELECT config_value FROM system_config WHERE config_key = 'github_token'";
            $result = $mysqli->query($sql);
            if ($result && $row = $result->fetch_assoc()) {
                $token = $row['config_value'];
            }
        }
        
        // Buscar branch atual salvo
        $current_branch = 'main';
        $sql = "SELECT config_value FROM system_config WHERE config_key = 'github_branch'";
        $result = $mysqli->query($sql);
        if ($result && $row = $result->fetch_assoc()) {
            $current_branch = $row['config_value'];
        }
        
        // Buscar branches da API do GitHub
        $url = "https://api.github.com/repos/{$owner}/{$repo}/branches";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-Update-Checker');
        
        $headers = ['Accept: application/vnd.github.v3+json'];
        if ($token) {
            $headers[] = "Authorization: token {$token}";
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code === 200) {
            $branches = json_decode($response, true);
            
            if (is_array($branches)) {
                $branch_list = [];
                
                foreach ($branches as $branch) {
                    // Buscar informações do último commit de cada branch
                    $commit_url = "https://api.github.com/repos/{$owner}/{$repo}/commits/{$branch['name']}";
                    
                    $ch_commit = curl_init();
                    curl_setopt($ch_commit, CURLOPT_URL, $commit_url);
                    curl_setopt($ch_commit, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_commit, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch_commit, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch_commit, CURLOPT_USERAGENT, 'PHP-Update-Checker');
                    
                    $commit_headers = ['Accept: application/vnd.github.v3+json'];
                    if ($token) {
                        $commit_headers[] = "Authorization: token {$token}";
                    }
                    curl_setopt($ch_commit, CURLOPT_HTTPHEADER, $commit_headers);
                    
                    $commit_response = curl_exec($ch_commit);
                    $commit_http_code = curl_getinfo($ch_commit, CURLINFO_HTTP_CODE);
                    curl_close($ch_commit);
                    
                    $commit_info = null;
                    if ($commit_http_code === 200) {
                        $commit_data = json_decode($commit_response, true);
                        if ($commit_data && isset($commit_data['commit'])) {
                            $commit_info = [
                                'message' => $commit_data['commit']['message'] ?? 'Sem mensagem',
                                'date' => $commit_data['commit']['author']['date'] ?? null,
                                'author' => $commit_data['commit']['author']['name'] ?? 'Desconhecido',
                                'sha' => substr($commit_data['sha'] ?? '', 0, 7)
                            ];
                        }
                    }
                    
                    // Buscar arquivo .branch-info.json do branch
                    $branch_info_url = "https://api.github.com/repos/{$owner}/{$repo}/contents/.branch-info.json?ref={$branch['name']}";
                    
                    $ch_info = curl_init();
                    curl_setopt($ch_info, CURLOPT_URL, $branch_info_url);
                    curl_setopt($ch_info, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch_info, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch_info, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch_info, CURLOPT_USERAGENT, 'PHP-Update-Checker');
                    curl_setopt($ch_info, CURLOPT_HTTPHEADER, $commit_headers);
                    
                    $info_response = curl_exec($ch_info);
                    $info_http_code = curl_getinfo($ch_info, CURLINFO_HTTP_CODE);
                    curl_close($ch_info);
                    
                    $branch_metadata = null;
                    if ($info_http_code === 200) {
                        $file_data = json_decode($info_response, true);
                        if ($file_data && isset($file_data['content'])) {
                            $decoded_content = base64_decode($file_data['content']);
                            $branch_metadata = json_decode($decoded_content, true);
                        }
                    }
                    
                    $branch_list[] = [
                        'name' => $branch['name'],
                        'commit' => $commit_info,
                        'metadata' => $branch_metadata
                    ];
                }
                
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'branches' => $branch_list,
                    'current_branch' => $current_branch,
                    'debug' => [
                        'owner' => $owner,
                        'repo' => $repo,
                        'total_branches' => count($branch_list),
                        'has_token' => !empty($token)
                    ]
                ]);
                exit;
            } else {
                ob_end_clean();
                echo json_encode([
                    'success' => false,
                    'message' => 'Resposta inválida da API do GitHub',
                    'debug' => ['response' => $response]
                ]);
                exit;
            }
        } else {
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'message' => "Erro ao buscar branches (HTTP {$http_code})",
                'debug' => [
                    'http_code' => $http_code,
                    'curl_error' => $curl_error,
                    'url' => $url,
                    'response' => substr($response, 0, 500)
                ]
            ]);
            exit;
        }
        
    } catch (Exception $e) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Erro: ' . $e->getMessage(),
            'debug' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ]);
        exit;
    }
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
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'config' => $config
        ]);
        exit;
    } else {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Repositório não configurado'
        ]);
        exit;
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar configuração: ' . $e->getMessage()
    ]);
    exit;
}
