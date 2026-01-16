<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexao.php';

// Função para detectar repositório automaticamente do git remoto
function detectGitHubRepo() {
    $root_dir = realpath(__DIR__ . '/../..');
    $git_config = $root_dir . '/.git/config';
    
    if (file_exists($git_config)) {
        $config_content = file_get_contents($git_config);
        
        // Detectar URL do GitHub no formato: github.com:usuario/repositorio.git
        if (preg_match('/github\.com[\/:]([^\/]+)\/([^\s\.]+)/i', $config_content, $matches)) {
            return [
                'owner' => $matches[1],
                'repo' => str_replace('.git', '', $matches[2])
            ];
        }
    }
    
    return null;
}

// Tentar detectar automaticamente
$repo_info = detectGitHubRepo();

// Se não detectou, buscar do banco de dados
if (!$repo_info) {
    $sql = "SELECT config_value FROM system_config WHERE config_key = 'github_repository'";
    $result = $mysqli->query($sql);
    
    if ($result && $row = $result->fetch_assoc()) {
        $repo_data = json_decode($row['config_value'], true);
        if ($repo_data && isset($repo_data['owner']) && isset($repo_data['repo'])) {
            $repo_info = $repo_data;
        }
    }
}

// Se ainda não tem informações, retornar erro
if (!$repo_info || empty($repo_info['owner']) || empty($repo_info['repo'])) {
    $current_version = ['version' => '1.0.0', 'build' => 'desconhecido'];
    if (file_exists(__DIR__ . '/../../version.json')) {
        $current_version = json_decode(file_get_contents(__DIR__ . '/../../version.json'), true);
    }
    
    echo json_encode([
        'success' => false,
        'error' => 'Repositório GitHub não configurado',
        'current_version' => $current_version['version'],
        'message' => 'Configure o repositório GitHub nas Configurações do Sistema'
    ]);
    exit;
}

$github_owner = $repo_info['owner'];
$github_repo = $repo_info['repo'];
$github_api_url = "https://api.github.com/repos/{$github_owner}/{$github_repo}";

// IMPORTANTE: Sempre usar o branch 'main' para atualizações
$github_branch = 'main';

// Ler versão atual do sistema
$version_file = __DIR__ . '/../../version.json';
$current_version = ['version' => '1.0.0', 'build' => 'desconhecido'];

if (file_exists($version_file)) {
    $current_version = json_decode(file_get_contents($version_file), true);
}

try {
    // Configurar contexto para requisição HTTP (incluir User-Agent obrigatório para GitHub API)
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: GAT-Sistema',
                'Accept: application/vnd.github.v3+json'
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
