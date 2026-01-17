<?php
// Capturar qualquer output indesejado
ob_start();

// Desabilitar exibição de erros para não quebrar o JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Limpar qualquer output anterior e definir header JSON
ob_clean();
header('Content-Type: application/json');

// Carregar configuração do GitHub
$github_config_file = __DIR__ . '/../config/github_config.php';

if (!file_exists($github_config_file)) {
    $current_version = ['version' => '1.0.0', 'build' => 'desconhecido'];
    if (file_exists(__DIR__ . '/../../version.json')) {
        $current_version = json_decode(file_get_contents(__DIR__ . '/../../version.json'), true);
    }
    
    ob_clean();
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
    
    ob_clean();
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

// Ler hash do último commit local (se existir .git)
$local_commit_hash = 'desconhecido';
$git_head_file = __DIR__ . '/../../.git/refs/heads/' . $github_branch;

if (file_exists($git_head_file)) {
    $local_commit_hash = trim(file_get_contents($git_head_file));
}

// Se não tiver .git, verificar se existe arquivo .last_update (última atualização aplicada)
$last_update_file = __DIR__ . '/../../.last_update';
if ($local_commit_hash === 'desconhecido' && file_exists($last_update_file)) {
    $last_update_info = json_decode(file_get_contents($last_update_file), true);
    if ($last_update_info && isset($last_update_info['commit_hash'])) {
        $local_commit_hash = $last_update_info['commit_hash'];
        error_log('Hash do último update encontrado: ' . $local_commit_hash);
    }
}

// Usar timestamp de build como versão se não tiver commit hash
$current_version = [
    'version' => '1.0.0', 
    'build' => $local_commit_hash !== 'desconhecido' ? substr($local_commit_hash, 0, 7) : date('Y-m-d')
];

// Se existir version.json, usar para informação adicional (opcional)
$version_file = __DIR__ . '/../../version.json';
if (file_exists($version_file)) {
    $version_data = json_decode(file_get_contents($version_file), true);
    if ($version_data && isset($version_data['version'])) {
        $current_version['version'] = $version_data['version'];
    }
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
    
    // SEMPRE buscar último commit do branch para comparação
    $commits_url = "{$github_api_url}/commits/{$github_branch}";
    $commit_data = @file_get_contents($commits_url, false, $context);
    
    if ($commit_data === false) {
        throw new Exception('Não foi possível conectar ao GitHub para verificar commits');
    }
    
    $latest_commit = json_decode($commit_data, true);
    $remote_commit_hash = substr($latest_commit['sha'], 0, 7);
    
    // Comparar commits: se o hash local é diferente do remoto, há atualização
    $local_hash_short = ($local_commit_hash !== 'desconhecido') ? substr($local_commit_hash, 0, 7) : 'desconhecido';
    $has_update = ($local_hash_short !== 'desconhecido' && $local_hash_short !== $remote_commit_hash);
    
    error_log('Comparação: Local=' . $local_hash_short . ' vs Remoto=' . $remote_commit_hash . ' | Tem update: ' . ($has_update ? 'SIM' : 'NÃO'));
    
    // URL de download direto do branch
    $branch_download_url = "https://github.com/{$github_owner}/{$github_repo}/archive/refs/heads/{$github_branch}.zip";
    
    // Se não há releases, retornar info de commit
    if ($release_data === false) {
        ob_clean();
        echo json_encode([
            'success' => true,
            'has_update' => $has_update,
            'current_version' => $current_version['version'],
            'latest_version' => $current_version['version'],
            'current_build' => $current_version['build'],
            'remote_build' => $remote_commit_hash,
            'message' => $has_update ? 'Nova versão disponível (commits mais recentes)' : 'Sistema atualizado (branch ' . $github_branch . ')',
            'repository' => "{$github_owner}/{$github_repo}",
            'branch' => $github_branch,
            'download_url' => $branch_download_url,
            'last_commit' => [
                'sha' => $remote_commit_hash,
                'message' => $latest_commit['commit']['message'],
                'date' => date('d/m/Y H:i', strtotime($latest_commit['commit']['author']['date'])),
                'author' => $latest_commit['commit']['author']['name']
            ]
        ]);
        exit;
    }
    
    $release = json_decode($release_data, true);
    
    // Se há release, comparar versão também
    $latest_version = ltrim($release['tag_name'], 'v');
    $version_has_update = version_compare($latest_version, $current_version['version'], '>');
    
    // Atualização disponível se: versão maior OU commits mais novos
    $has_update = $version_has_update || $has_update;
    
    // Atualização disponível se: versão maior OU commits mais novos
    $has_update = $version_has_update || $has_update;
    
    // SEMPRE usar o branch configurado para download
    $branch_download_url = "https://github.com/{$github_owner}/{$github_repo}/archive/refs/heads/{$github_branch}.zip";
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'has_update' => $has_update,
        'current_version' => $current_version['version'],
        'latest_version' => $latest_version,
        'current_build' => $current_version['build'],
        'remote_build' => $remote_commit_hash,
        'repository' => "{$github_owner}/{$github_repo}",
        'branch' => $github_branch,
        'release_info' => [
            'name' => $release['name'],
            'tag' => $release['tag_name'],
            'published_at' => date('d/m/Y H:i', strtotime($release['published_at'])),
            'body' => $release['body'],
            'download_url' => $branch_download_url,  // Sempre do branch configurado
            'html_url' => $release['html_url']
        ],
        'download_url' => $branch_download_url,
        'last_commit' => [
            'sha' => $remote_commit_hash,
            'message' => $latest_commit['commit']['message'],
            'date' => date('d/m/Y H:i', strtotime($latest_commit['commit']['author']['date'])),
            'author' => $latest_commit['commit']['author']['name']
        ]
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'current_version' => isset($current_version) ? $current_version['version'] : '1.0.0',
        'repository' => isset($github_owner) && isset($github_repo) ? "{$github_owner}/{$github_repo}" : 'N/A',
        'message' => 'Erro ao conectar com o repositório GitHub'
    ]);
}
