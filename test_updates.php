<?php
/**
 * Teste de sistema de atualizações
 * Acesse: http://localhost/test_updates.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Teste do Sistema de Atualizações</h1>";
echo "<hr>";

// 1. Verificar arquivo version.json
echo "<h2>1. Verificando version.json</h2>";
$version_file = __DIR__ . '/version.json';
if (file_exists($version_file)) {
    $version_data = json_decode(file_get_contents($version_file), true);
    echo "✅ Arquivo existe<br>";
    echo "<pre>" . json_encode($version_data, JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "❌ Arquivo não existe<br>";
    echo "📍 Caminho esperado: {$version_file}<br>";
}

echo "<hr>";

// 2. Verificar .git/config
echo "<h2>2. Verificando .git/config</h2>";
$git_config = __DIR__ . '/.git/config';
if (file_exists($git_config)) {
    $config_content = file_get_contents($git_config);
    echo "✅ Arquivo .git/config existe<br>";
    
    // Detectar URL do GitHub
    if (preg_match('/github\.com[\/:]([^\/]+)\/([^\s\.]+)/i', $config_content, $matches)) {
        echo "✅ Repositório detectado automaticamente:<br>";
        echo "📦 Owner: <strong>{$matches[1]}</strong><br>";
        echo "📦 Repo: <strong>" . str_replace('.git', '', $matches[2]) . "</strong><br>";
    } else {
        echo "⚠️ Repositório não detectado no .git/config<br>";
    }
    echo "<pre>" . htmlspecialchars(substr($config_content, 0, 500)) . "</pre>";
} else {
    echo "⚠️ Arquivo .git/config não existe (normal em produção)<br>";
}

echo "<hr>";

// 3. Verificar tabela system_config
echo "<h2>3. Verificando tabela system_config</h2>";
require_once __DIR__ . '/src/config/conexao.php';

$check_table = "SHOW TABLES LIKE 'system_config'";
$result = $mysqli->query($check_table);
if ($result->num_rows > 0) {
    echo "✅ Tabela system_config existe<br><br>";
    
    // Buscar configuração do GitHub
    $sql = "SELECT * FROM system_config WHERE config_key = 'github_repository'";
    $result = $mysqli->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo "✅ Configuração GitHub encontrada no banco:<br>";
        echo "<pre>" . htmlspecialchars(print_r($row, true)) . "</pre>";
        
        $repo_data = json_decode($row['config_value'], true);
        if ($repo_data) {
            echo "📦 Owner: <strong>{$repo_data['owner']}</strong><br>";
            echo "📦 Repo: <strong>{$repo_data['repo']}</strong><br>";
        }
    } else {
        echo "⚠️ Configuração github_repository não encontrada no banco<br>";
        echo "💡 Isso é normal se você ainda não configurou o repositório<br>";
    }
    
    echo "<br><strong>Todos os registros em system_config:</strong><br>";
    $all_configs = $mysqli->query("SELECT config_key, config_value FROM system_config");
    echo "<pre>";
    while ($config = $all_configs->fetch_assoc()) {
        echo "- {$config['config_key']}: " . substr($config['config_value'], 0, 50) . "\n";
    }
    echo "</pre>";
} else {
    echo "❌ Tabela system_config não existe<br>";
    echo "💡 Execute a instalação do sistema primeiro<br>";
}

echo "<hr>";

// 4. Testar conexão com GitHub API
echo "<h2>4. Testando GitHub API</h2>";
$test_repo = "paulohcgs1997/GAT-Guia-de-Atendimento-Tecnico"; // Repo padrão para teste
$test_url = "https://api.github.com/repos/{$test_repo}";

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

$response = @file_get_contents($test_url, false, $context);
if ($response !== false) {
    $repo_info = json_decode($response, true);
    echo "✅ Conexão com GitHub API funcionando<br>";
    echo "📦 Repositório de teste: {$test_repo}<br>";
    echo "⭐ Stars: {$repo_info['stargazers_count']}<br>";
    echo "🍴 Forks: {$repo_info['forks_count']}<br>";
} else {
    echo "❌ Falha na conexão com GitHub API<br>";
    echo "💡 Verifique sua conexão com a internet<br>";
}

echo "<hr>";
echo "<h2>5. Arquivos JavaScript e PHP</h2>";
$files_to_check = [
    'src/js/system-updater.js',
    'src/js/github-config.js',
    'src/php/check_updates.php',
    'src/php/get_github_config.php',
    'src/php/save_github_config.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "✅ {$file} (" . filesize($full_path) . " bytes)<br>";
    } else {
        echo "❌ {$file} não encontrado<br>";
    }
}

echo "<hr>";
echo "<h2>✅ Teste Concluído</h2>";
echo "<p><a href='viwer/gestao_configuracoes.php'>← Voltar para Configurações</a></p>";
?>
