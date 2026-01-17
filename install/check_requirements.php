<?php
/**
 * Verificador de Requisitos do Sistema
 * Verifica todas as dependências necessárias para o funcionamento do GAT
 */

header('Content-Type: application/json');

$requirements = [
    'php_version' => [
        'name' => 'Versão do PHP',
        'required' => '7.4.0',
        'current' => PHP_VERSION,
        'status' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'critical' => true,
        'message' => 'PHP 7.4 ou superior é necessário'
    ],
    'pdo' => [
        'name' => 'PDO (MySQL)',
        'required' => 'Habilitado',
        'current' => extension_loaded('pdo') && extension_loaded('pdo_mysql') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
        'critical' => true,
        'message' => 'Extensão PDO MySQL necessária para banco de dados'
    ],
    'mysqli' => [
        'name' => 'MySQLi',
        'required' => 'Habilitado',
        'current' => extension_loaded('mysqli') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('mysqli'),
        'critical' => true,
        'message' => 'Extensão MySQLi necessária para banco de dados'
    ],
    'zip' => [
        'name' => 'ZIP Archive',
        'required' => 'Habilitado',
        'current' => class_exists('ZipArchive') ? 'Habilitado' : 'Desabilitado',
        'status' => class_exists('ZipArchive'),
        'critical' => true,
        'message' => 'Extensão ZIP necessária para backups e atualizações. Habilite no php.ini: extension=zip'
    ],
    'json' => [
        'name' => 'JSON',
        'required' => 'Habilitado',
        'current' => extension_loaded('json') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('json'),
        'critical' => true,
        'message' => 'Extensão JSON necessária'
    ],
    'mbstring' => [
        'name' => 'Multibyte String',
        'required' => 'Habilitado',
        'current' => extension_loaded('mbstring') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('mbstring'),
        'critical' => false,
        'message' => 'Extensão mbstring recomendada para manipulação de strings UTF-8'
    ],
    'gd' => [
        'name' => 'GD (Imagens)',
        'required' => 'Habilitado',
        'current' => extension_loaded('gd') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('gd'),
        'critical' => false,
        'message' => 'Extensão GD recomendada para manipulação de imagens'
    ],
    'curl' => [
        'name' => 'cURL',
        'required' => 'Habilitado',
        'current' => extension_loaded('curl') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('curl'),
        'critical' => true,
        'message' => 'Extensão cURL necessária para atualizações do GitHub'
    ],
    'openssl' => [
        'name' => 'OpenSSL',
        'required' => 'Habilitado',
        'current' => extension_loaded('openssl') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('openssl'),
        'critical' => true,
        'message' => 'OpenSSL necessário para conexões HTTPS (GitHub API)'
    ],
    'fileinfo' => [
        'name' => 'File Info',
        'required' => 'Habilitado',
        'current' => extension_loaded('fileinfo') ? 'Habilitado' : 'Desabilitado',
        'status' => extension_loaded('fileinfo'),
        'critical' => false,
        'message' => 'Extensão fileinfo recomendada para detecção de tipo de arquivo'
    ]
];

// Verificar configurações do PHP.ini
$php_config = [
    'upload_max_filesize' => [
        'name' => 'Tamanho Máximo de Upload',
        'required' => '50M',
        'current' => ini_get('upload_max_filesize'),
        'status' => parseSize(ini_get('upload_max_filesize')) >= parseSize('50M'),
        'critical' => false,
        'message' => 'Recomendado 50M ou mais para upload de imagens e vídeos'
    ],
    'post_max_size' => [
        'name' => 'Tamanho Máximo POST',
        'required' => '50M',
        'current' => ini_get('post_max_size'),
        'status' => parseSize(ini_get('post_max_size')) >= parseSize('50M'),
        'critical' => false,
        'message' => 'Recomendado 50M ou mais para envio de formulários com arquivos'
    ],
    'memory_limit' => [
        'name' => 'Limite de Memória',
        'required' => '256M',
        'current' => ini_get('memory_limit'),
        'status' => ini_get('memory_limit') === '-1' || parseSize(ini_get('memory_limit')) >= parseSize('256M'),
        'critical' => false,
        'message' => 'Recomendado 256M ou mais para processamento de backups'
    ],
    'max_execution_time' => [
        'name' => 'Tempo Máximo de Execução',
        'required' => '300',
        'current' => ini_get('max_execution_time') . 's',
        'status' => ini_get('max_execution_time') == 0 || ini_get('max_execution_time') >= 300,
        'critical' => false,
        'message' => 'Recomendado 300 segundos ou mais para backups e atualizações'
    ],
    'file_uploads' => [
        'name' => 'Upload de Arquivos',
        'required' => 'On',
        'current' => ini_get('file_uploads') ? 'On' : 'Off',
        'status' => (bool)ini_get('file_uploads'),
        'critical' => true,
        'message' => 'Upload de arquivos deve estar habilitado'
    ]
];

// Verificar permissões de diretórios
$root = dirname(__DIR__);
$directories = [
    'uploads' => [
        'path' => $root . '/uploads',
        'writable' => false,
        'exists' => false,
        'critical' => true
    ],
    'uploads/avatars' => [
        'path' => $root . '/uploads/avatars',
        'writable' => false,
        'exists' => false,
        'critical' => true
    ],
    'backups' => [
        'path' => $root . '/backups',
        'writable' => false,
        'exists' => false,
        'critical' => true
    ],
    'src/config' => [
        'path' => $root . '/src/config',
        'writable' => false,
        'exists' => false,
        'critical' => true
    ]
];

foreach ($directories as $key => $dir) {
    $directories[$key]['exists'] = file_exists($dir['path']);
    
    if ($directories[$key]['exists']) {
        $directories[$key]['writable'] = is_writable($dir['path']);
    } else {
        // Tentar criar o diretório
        if (@mkdir($dir['path'], 0755, true)) {
            $directories[$key]['exists'] = true;
            $directories[$key]['writable'] = is_writable($dir['path']);
        }
    }
}

// Função auxiliar para converter tamanhos
function parseSize($size) {
    $unit = strtoupper(substr($size, -1));
    $value = (int)$size;
    
    switch ($unit) {
        case 'G':
            $value *= 1024;
        case 'M':
            $value *= 1024;
        case 'K':
            $value *= 1024;
    }
    
    return $value;
}

// Verificar se há requisitos críticos faltando
$critical_failed = [];
$warnings = [];

foreach ($requirements as $key => $req) {
    if (!$req['status']) {
        if ($req['critical']) {
            $critical_failed[] = $req;
        } else {
            $warnings[] = $req;
        }
    }
}

foreach ($php_config as $key => $config) {
    if (!$config['status']) {
        if ($config['critical']) {
            $critical_failed[] = $config;
        } else {
            $warnings[] = $config;
        }
    }
}

foreach ($directories as $key => $dir) {
    if (!$dir['writable'] || !$dir['exists']) {
        if ($dir['critical']) {
            $critical_failed[] = [
                'name' => 'Diretório ' . $key,
                'message' => !$dir['exists'] 
                    ? "Diretório {$dir['path']} não existe e não pode ser criado" 
                    : "Diretório {$dir['path']} não tem permissão de escrita"
            ];
        }
    }
}

$can_install = count($critical_failed) === 0;

// Informações do servidor
$server_info = [
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Desconhecido',
    'php_sapi' => php_sapi_name(),
    'php_ini' => php_ini_loaded_file(),
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Desconhecido'
];

echo json_encode([
    'success' => true,
    'can_install' => $can_install,
    'requirements' => $requirements,
    'php_config' => $php_config,
    'directories' => $directories,
    'critical_failed' => $critical_failed,
    'warnings' => $warnings,
    'server_info' => $server_info
], JSON_PRETTY_PRINT);
