<?php
header('Content-Type: application/json');

// Carregar configuração do GitHub
$github_config_file = __DIR__ . '/../config/github_config.php';

if (!file_exists($github_config_file)) {
    $current_version = ['version' => '1.0.0', 'build' => 'desconhecido'];
    if (file_exists(__DIR__ . '/../../version.json')) {
        $current_version = json_decode(file_get_contents(__DIR__ . '/../../version.json'), true);
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Sistema de atualizações não configurado',
        'current_version' => $current_version['version'],
        'message' => 'O token do GitHub precisa ser configurado durante a instalação. Reinstale o sistema ou configure manualmente o arquivo src/config/github_config.php'
    ]);
    exit;
}

require_once $github_config_file;

// Validar se as constantes foram definidas
if (!defined('GITHUB_TOKEN') || !defined('GITHUB_OWNER') || !defined('GITHUB_REPO')) {
    $current_version = ['version' => '1.0.0', 'build' => 'desconhecido'];
    if (file_exists(__DIR__ . '/../../version.json')) {
        $current_version = json_decode(file_get_contents(__DIR__ . '/../../version.json'), true);
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Configuração incompleta',
        'current_version' => $current_version['version'],
        'message' => 'O arquivo github_config.php existe mas está incompleto. Configure o token no arquivo src/config/github_config.php'
    ]);
    exit;
}

$github_token = GITHUB_TOKEN;
$github_owner = GITHUB_OWNER;
$github_repo = GITHUB_REPO;
$github_branch = GITHUB_BRANCH;
$github_api_url = "https://api.github.com/repos/{$github_owner}/{$github_repo}";

// Ler versão atual do sistema
$version_file = __DIR__ . '/../../version.json';
$current_version = ['version' => '1.0.0', 'build' => 'desconhecido'];

if (file_exists($version_file)) {
    $current_version = json_decode(file_get_contents($version_file), true);
}

try {
    // Configurar contexto para requisição HTTP com autenticação via token
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: GAT-Sistema',
                'Accept: application/vnd.github.v3+json',
                "Authorization: token {$github_token}"
            ],
            'timeout' => 10
        ]
    ]);

    // Verificar última release no GitHub
    $releases_url = "{$github_api_url}/releases/latest";
    $release_data = @file_get_contents($releases_url, false, $context);
    
    if ($release_data === false) {
        // Se não há releases, tentar obter info do último commit do branch main
        $commits_url = "{$github_api_url}/commits/{$github_branch}";
        $commit_data = @file_get_contents($commits_url, false, $context);
        
        if ($commit_data === false) {
            throw new Exception('Não foi possível conectar ao GitHub');
        }
        
        $commit = json_decode($commit_data, true);
        
        // URL de download direto do branch main
        $main_branch_download = "https://github.com/{$github_owner}/{$github_repo}/archive/refs/heads/main.zip";
        
        echo json_encode([
            'success' => true,
            'has_update' => false,
            'current_version' => $current_version['version'],
            'latest_version' => 'desenvolvimento',
            'current_build' => $current_version['build'],
            'message' => 'Sistema está em versão de desenvolvimento',
            'repository' => "{$github_owner}/{$github_repo}",
            'branch' => 'main',
            'download_url' => $main_branch_download,  // Sempre do branch main
            'last_commit' => [
                'sha' => substr($commit['sha'], 0, 7),
                'message' => $commit['commit']['message'],
                'date' => date('d/m/Y H:i', strtotime($commit['commit']['author']['date'])),
                'author' => $commit['commit']['author']['name']
            ]
        ]);
        exit;
    }
    
    $release = json_decode($release_data, true);
    
    // Comparar versões
    $latest_version = ltrim($release['tag_name'], 'v');
    $has_update = version_compare($latest_version, $current_version['version'], '>');
    
    // SEMPRE usar o branch main para download, não a release específica
    $main_branch_download = "https://github.com/{$github_owner}/{$github_repo}/archive/refs/heads/main.zip";
    
    echo json_encode([
        'success' => true,
        'has_update' => $has_update,
        'current_version' => $current_version['version'],
        'latest_version' => $latest_version,
        'current_build' => $current_version['build'],
        'repository' => "{$github_owner}/{$github_repo}",
        'branch' => 'main',
        'release_info' => [
            'name' => $release['name'],
            'tag' => $release['tag_name'],
            'published_at' => date('d/m/Y H:i', strtotime($release['published_at'])),
            'body' => $release['body'],
            'download_url' => $main_branch_download,  // Sempre do branch main
            'release_download_url' => $release['zipball_url'],  // URL da release (backup)
            'html_url' => $release['html_url']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'current_version' => $current_version['version'],
        'repository' => "{$github_owner}/{$github_repo}",
        'message' => 'Erro ao conectar com o repositório GitHub'
    ]);
}
