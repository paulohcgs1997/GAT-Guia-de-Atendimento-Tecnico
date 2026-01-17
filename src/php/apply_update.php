<?php
// Capturar qualquer output indesejado
ob_start();

// Desabilitar exibição de erros para não quebrar o JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Iniciar sessão antes de qualquer output
session_start();

// Limpar qualquer output anterior
ob_clean();

// Agora sim definir o header JSON
header('Content-Type: application/json');

// Tentar incluir conexão, mas não deixar morrer se falhar
try {
    require_once __DIR__ . '/../config/conexao.php';
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Erro ao conectar ao banco: ' . $e->getMessage()]);
    exit;
}

// Verificar permissões de admin
error_log('=== DEBUG APPLY UPDATE ===');
error_log('Session user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
error_log('Session perfil: ' . (isset($_SESSION['perfil']) ? $_SESSION['perfil'] : 'NOT SET'));

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Sessão não encontrada. Faça login novamente.'
    ]);
    exit;
}

// Verificar perfil - sempre consultar banco de dados
$is_admin = false;

if (isset($mysqli)) {
    try {
        $user_id = intval($_SESSION['user_id']);
        
        $query = "SELECT u.perfil, p.type 
                  FROM usuarios u 
                  LEFT JOIN perfil p ON u.perfil = p.id 
                  WHERE u.id = ?";
        
        $stmt = $mysqli->prepare($query);
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $row = $result->fetch_assoc()) {
                $perfil_id = $row['perfil'];
                $perfil_type = $row['type'];
                
                $is_admin = ($perfil_id == 1 || $perfil_type === 'admin');
                
                error_log('Perfil do banco - ID: ' . $perfil_id . ', Type: ' . $perfil_type . ', É admin: ' . ($is_admin ? 'SIM' : 'NÃO'));
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        error_log('Erro ao verificar perfil: ' . $e->getMessage());
    }
}

if (!$is_admin) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Permissão negada. Apenas administradores podem aplicar atualizações.'
    ]);
    exit;
}

$download_url = $_POST['download_url'] ?? '';

if (empty($download_url)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'URL de download não fornecida']);
    exit;
}

// Limpar buffer antes de começar processamento
ob_clean();

try {
    $root_dir = realpath(__DIR__ . '/../..');
    $backup_dir = $root_dir . DIRECTORY_SEPARATOR . 'backups';
    $temp_dir = $root_dir . DIRECTORY_SEPARATOR . 'temp_update';
    
    error_log('Iniciando atualização...');
    error_log('Root dir: ' . $root_dir);
    
    // Verificar permissão de escrita
    if (!is_writable($root_dir)) {
        throw new Exception('Sem permissão de escrita no diretório raiz');
    }
    
    // Criar diretório de backups
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Nome do backup
    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
    $backup_path = $backup_dir . DIRECTORY_SEPARATOR . $backup_name;
    
    error_log('Criando backup: ' . $backup_name);
    
    // PASSO 1: CRIAR BACKUP
    $zip = new ZipArchive();
    if ($zip->open($backup_path, ZipArchive::CREATE) !== true) {
        throw new Exception('Não foi possível criar arquivo de backup');
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($root_dir) + 1);
            
            // Ignorar backups, uploads e temp
            if (strpos($relativePath, 'backups' . DIRECTORY_SEPARATOR) !== 0 && 
                strpos($relativePath, 'uploads' . DIRECTORY_SEPARATOR) !== 0 && 
                strpos($relativePath, 'temp_') !== 0) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    $zip->close();
    error_log('Backup criado com sucesso');
    
    // PASSO 2: BAIXAR ATUALIZAÇÃO
    error_log('Baixando atualização...');
    
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    $update_zip = $temp_dir . DIRECTORY_SEPARATOR . 'update.zip';
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: GAT-Sistema',
            'follow_location' => 1,
            'timeout' => 60
        ]
    ]);
    
    $update_data = file_get_contents($download_url, false, $context);
    
    if ($update_data === false) {
        throw new Exception('Falha ao baixar atualização do GitHub');
    }
    
    file_put_contents($update_zip, $update_data);
    error_log('Download concluído: ' . strlen($update_data) . ' bytes');
    
    // PASSO 3: EXTRAIR ATUALIZAÇÃO
    error_log('Extraindo atualização...');
    
    $zip = new ZipArchive();
    if ($zip->open($update_zip) !== true) {
        throw new Exception('Arquivo de atualização corrompido');
    }
    
    $zip->extractTo($temp_dir);
    $zip->close();
    
    // Encontrar diretório extraído (GitHub adiciona um diretório com nome do repo)
    $extracted_dirs = glob($temp_dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    $update_files_dir = $extracted_dirs[0] ?? $temp_dir;
    error_log('Diretório extraído: ' . $update_files_dir);
    
    // PASSO 4: APLICAR ATUALIZAÇÃO
    error_log('Aplicando arquivos...');
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($update_files_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($update_files_dir) + 1);
        
        // Ignorar arquivos que não devem ser sobrescritos
        if (strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'conexao.php') !== false ||
            strpos($relativePath, 'uploads' . DIRECTORY_SEPARATOR) === 0 ||
            strpos($relativePath, 'backups' . DIRECTORY_SEPARATOR) === 0 ||
            strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'github_config.php') !== false) {
            continue;
        }
        
        $targetPath = $root_dir . DIRECTORY_SEPARATOR . $relativePath;
        
        if ($file->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }
            copy($filePath, $targetPath);
        }
    }
    
    error_log('Arquivos aplicados com sucesso');
    
    // PASSO 5: LIMPAR TEMPORÁRIOS
    error_log('Limpando arquivos temporários...');
    
    function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    deleteDirectory($temp_dir);
    error_log('Atualização concluída com sucesso!');
    
    // Salvar informação da versão instalada
    $version_info = [
        'installed_at' => date('Y-m-d H:i:s'),
        'download_url' => $download_url,
        'backup_file' => $backup_name
    ];
    
    // Tentar obter o hash do último commit do GitHub para salvar
    try {
        // Se a URL for do formato archive/refs/heads/branch.zip, buscar o último commit dessa branch
        if (preg_match('/github\.com\/([^\/]+)\/([^\/]+)\/archive\/refs\/heads\/([^\/]+)\.zip/i', $download_url, $matches)) {
            $owner = $matches[1];
            $repo = $matches[2];
            $branch = $matches[3];
            
            $commit_url = "https://api.github.com/repos/{$owner}/{$repo}/commits/{$branch}";
            
            // Tentar buscar o hash (com ou sem token)
            $github_config = __DIR__ . '/../config/github_config.php';
            $token = '';
            if (file_exists($github_config)) {
                require_once $github_config;
                if (defined('GITHUB_TOKEN')) {
                    $token = GITHUB_TOKEN;
                }
            }
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: GAT-Sistema',
                        'Accept: application/vnd.github.v3+json'
                    ] . ($token ? ['Authorization: token ' . $token] : []),
                    'timeout' => 10
                ]
            ]);
            
            $commit_data = @file_get_contents($commit_url, false, $context);
            if ($commit_data !== false) {
                $commit_info = json_decode($commit_data, true);
                if ($commit_info && isset($commit_info['sha'])) {
                    $version_info['commit_hash'] = $commit_info['sha'];
                    error_log('Hash do commit obtido: ' . $commit_info['sha']);
                }
            }
        }
    } catch (Exception $e) {
        error_log('Não foi possível obter hash do commit: ' . $e->getMessage());
    }
    
    $version_file = $root_dir . DIRECTORY_SEPARATOR . '.last_update';
    file_put_contents($version_file, json_encode($version_info, JSON_PRETTY_PRINT));
    error_log('Informações da versão salvas em .last_update');
    
    // Limpar buffer final e enviar JSON
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Atualização aplicada com sucesso!',
        'backup_file' => $backup_name,
        'backup_path' => 'backups/' . $backup_name
    ]);
    
} catch (Exception $e) {
    ob_clean();
    
    error_log('ERRO na atualização: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
