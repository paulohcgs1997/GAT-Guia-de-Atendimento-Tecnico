<?php
/**
 * Script de Incremento Automático de Versão
 * Incrementa a versão PATCH automaticamente
 * Uso: php increment_version.php [major|minor|patch]
 */

$versionFile = __DIR__ . '/version.json';

// Carregar versão atual
if (!file_exists($versionFile)) {
    $version = [
        'major' => 1,
        'minor' => 0,
        'patch' => 0,
        'version' => '1.0.0',
        'last_update' => date('Y-m-d H:i:s'),
        'commit' => ''
    ];
} else {
    $version = json_decode(file_get_contents($versionFile), true);
}

// Determinar tipo de incremento
$type = $argv[1] ?? 'patch';

switch ($type) {
    case 'major':
        $version['major']++;
        $version['minor'] = 0;
        $version['patch'] = 0;
        break;
    case 'minor':
        $version['minor']++;
        $version['patch'] = 0;
        break;
    case 'patch':
    default:
        $version['patch']++;
        break;
}

// Atualizar versão string
$version['version'] = sprintf('%d.%d.%d', $version['major'], $version['minor'], $version['patch']);
$version['last_update'] = date('Y-m-d H:i:s');

// Obter hash do último commit
exec('git rev-parse --short HEAD', $output);
$version['commit'] = $output[0] ?? '';

// Salvar
file_put_contents($versionFile, json_encode($version, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "✅ Versão incrementada para: {$version['version']}\n";
echo "📅 Data: {$version['last_update']}\n";
echo "🔗 Commit: {$version['commit']}\n";

// Adicionar ao Git
exec('git add version.json');
echo "📝 version.json adicionado ao commit\n";
