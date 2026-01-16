<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/conexao.php';

// Verificar permissões de admin
session_start();
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Permissão negada']);
    exit;
}

$download_url = $_POST['download_url'] ?? '';

if (empty($download_url)) {
    echo json_encode(['success' => false, 'error' => 'URL de download não fornecida']);
    exit;
}

try {
    // Diretórios
    $root_dir = realpath(__DIR__ . '/../..');
    $backup_dir = $root_dir . '/backups';
    $temp_dir = $root_dir . '/temp_update';
    
    // Criar diretório de backups se não existir
    if (!is_dir($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Nome do backup com timestamp
    $backup_name = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
    $backup_path = $backup_dir . '/' . $backup_name;
    
    // ===== PASSO 1: CRIAR BACKUP =====
    $zip = new ZipArchive();
    if ($zip->open($backup_path, ZipArchive::CREATE) !== true) {
        throw new Exception('Não foi possível criar arquivo de backup');
    }
    
    // Adicionar arquivos ao backup (exceto backups, uploads e temp)
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($root_dir) + 1);
            
            // Ignorar certos diretórios
            if (strpos($relativePath, 'backups/') !== 0 && 
                strpos($relativePath, 'uploads/') !== 0 && 
                strpos($relativePath, 'temp_') !== 0) {
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    $zip->close();
    
    // ===== PASSO 2: BAIXAR ATUALIZAÇÃO =====
    if (!is_dir($temp_dir)) {
        mkdir($temp_dir, 0755, true);
    }
    
    $update_zip = $temp_dir . '/update.zip';
    
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
    
    // ===== PASSO 3: EXTRAIR ATUALIZAÇÃO =====
    $zip = new ZipArchive();
    if ($zip->open($update_zip) !== true) {
        throw new Exception('Arquivo de atualização corrompido');
    }
    
    $zip->extractTo($temp_dir);
    $zip->close();
    
    // GitHub adiciona um diretório com o nome do repositório, encontrá-lo
    $extracted_dirs = glob($temp_dir . '/*', GLOB_ONLYDIR);
    $update_files_dir = $extracted_dirs[0] ?? $temp_dir;
    
    // ===== PASSO 4: APLICAR ATUALIZAÇÃO =====
    // Copiar arquivos atualizados (preservando uploads e config)
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($update_files_dir),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        $relativePath = substr($file->getRealPath(), strlen($update_files_dir) + 1);
        
        // Ignorar alguns arquivos/diretórios
        if (strpos($relativePath, 'src/config/conexao.php') !== false ||
            strpos($relativePath, 'uploads/') === 0 ||
            strpos($relativePath, 'backups/') === 0) {
            continue;
        }
        
        $targetPath = $root_dir . '/' . $relativePath;
        
        if ($file->isDir()) {
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
        } else {
            copy($file->getRealPath(), $targetPath);
        }
    }
    
    // ===== PASSO 5: LIMPAR ARQUIVOS TEMPORÁRIOS =====
    function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    deleteDirectory($temp_dir);
    
    echo json_encode([
        'success' => true,
        'message' => 'Atualização aplicada com sucesso!',
        'backup_file' => $backup_name,
        'backup_path' => 'backups/' . $backup_name
    ]);
    
} catch (Exception $e) {
    // Em caso de erro, tentar restaurar backup
    if (isset($backup_path) && file_exists($backup_path)) {
        // Código de restauração aqui se necessário
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
