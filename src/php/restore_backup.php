<?php
/**
 * Sistema de Restauração de Backup
 * Restaura o sistema a partir de um arquivo de backup
 */

// Capturar qualquer output indesejado
ob_start();

// Desabilitar exibição de erros para não quebrar o JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Iniciar sessão antes de qualquer output
session_start();

// Limpar qualquer output anterior
ob_clean();

// Definir o header JSON
header('Content-Type: application/json');

// Tentar incluir conexão
try {
    require_once __DIR__ . '/../config/conexao.php';
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Erro ao conectar ao banco: ' . $e->getMessage()]);
    exit;
}

// Verificar permissões de admin
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
                $is_admin = ($row['perfil'] == 1 || $row['type'] === 'admin');
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
        'error' => 'Permissão negada. Apenas administradores podem restaurar backups.'
    ]);
    exit;
}

$backup_file = $_POST['backup_file'] ?? '';

if (empty($backup_file)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Arquivo de backup não fornecido']);
    exit;
}

// Limpar buffer antes de começar processamento
ob_clean();

try {
    $root_dir = realpath(__DIR__ . '/../..');
    $backup_dir = $root_dir . DIRECTORY_SEPARATOR . 'backups';
    $backup_path = $backup_dir . DIRECTORY_SEPARATOR . basename($backup_file);
    
    error_log('=== INICIANDO RESTAURAÇÃO DE BACKUP ===');
    error_log('Root dir: ' . $root_dir);
    error_log('Backup file: ' . $backup_file);
    
    // Validar se o backup existe
    if (!file_exists($backup_path)) {
        throw new Exception('Arquivo de backup não encontrado: ' . basename($backup_file));
    }
    
    // Validar extensão .zip
    if (pathinfo($backup_path, PATHINFO_EXTENSION) !== 'zip') {
        throw new Exception('Arquivo de backup inválido. Apenas arquivos .zip são aceitos.');
    }
    
    // Validar tamanho (máximo 500MB)
    $backup_size = filesize($backup_path);
    if ($backup_size > 500 * 1024 * 1024) {
        throw new Exception('Arquivo de backup muito grande. Máximo: 500MB');
    }
    
    // Criar diretório temporário para restauração
    $restore_dir = $root_dir . DIRECTORY_SEPARATOR . 'temp_restore_' . time();
    if (!mkdir($restore_dir, 0755, true)) {
        throw new Exception('Não foi possível criar diretório temporário');
    }
    
    error_log('Criado diretório temporário: ' . $restore_dir);
    
    // PASSO 1: CRIAR BACKUP DO ESTADO ATUAL (segurança)
    error_log('Criando backup de segurança do estado atual...');
    
    $safety_backup_name = 'safety_before_restore_' . date('Y-m-d_H-i-s') . '.zip';
    $safety_backup_path = $backup_dir . DIRECTORY_SEPARATOR . $safety_backup_name;
    
    $zip = new ZipArchive();
    if ($zip->open($safety_backup_path, ZipArchive::CREATE) === true) {
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
        error_log('Backup de segurança criado: ' . $safety_backup_name);
    } else {
        error_log('AVISO: Não foi possível criar backup de segurança');
    }
    
    // PASSO 2: EXTRAIR BACKUP
    error_log('Extraindo backup...');
    
    $zip = new ZipArchive();
    if ($zip->open($backup_path) !== true) {
        throw new Exception('Não foi possível abrir o arquivo de backup');
    }
    
    $zip->extractTo($restore_dir);
    $num_files = $zip->numFiles;
    $zip->close();
    
    error_log("Backup extraído com sucesso: {$num_files} arquivos");
    
    // PASSO 3: VALIDAR ESTRUTURA DO BACKUP
    error_log('Validando estrutura do backup...');
    
    $required_files = [
        'index.php',
        'src' . DIRECTORY_SEPARATOR . 'config',
        'viwer'
    ];
    
    $is_valid = true;
    foreach ($required_files as $required) {
        $check_path = $restore_dir . DIRECTORY_SEPARATOR . $required;
        if (!file_exists($check_path)) {
            $is_valid = false;
            error_log("AVISO: Arquivo/pasta não encontrada no backup: {$required}");
        }
    }
    
    if (!$is_valid) {
        throw new Exception('Estrutura do backup parece incompleta. Restauração abortada por segurança.');
    }
    
    error_log('Estrutura validada com sucesso');
    
    // PASSO 4: REMOVER ARQUIVOS ATUAIS (EXCETO PROTEGIDOS)
    error_log('Removendo arquivos atuais...');
    
    // Lista de arquivos e diretórios protegidos (NÃO serão removidos)
    $protected_paths = [
        'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'conexao.php',
        'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'github_config.php',
        'uploads',
        'src' . DIRECTORY_SEPARATOR . 'uploads',
        'backups',
        'temp_restore_',
        'temp_update',
        '.git',
        '.last_update'
    ];
    
    function isProtectedPath($path, $protected_paths) {
        foreach ($protected_paths as $protected) {
            if (strpos($path, $protected) === 0) {
                return true;
            }
        }
        return false;
    }
    
    $current_files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    $files_removed = 0;
    foreach ($current_files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($root_dir) + 1);
        
        if (!isProtectedPath($relativePath, $protected_paths)) {
            if (is_file($filePath)) {
                unlink($filePath);
                $files_removed++;
            } elseif (is_dir($filePath)) {
                @rmdir($filePath);
            }
        }
    }
    
    error_log("Arquivos removidos: {$files_removed} (protegidos preservados)");
    
    // PASSO 5: RESTAURAR ARQUIVOS DO BACKUP
    error_log('Restaurando arquivos do backup...');
    
    $restored_files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($restore_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    $files_restored = 0;
    foreach ($restored_files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($restore_dir) + 1);
        
        // Não restaurar arquivos de configuração se já existem (preservar configurações atuais)
        $targetPath = $root_dir . DIRECTORY_SEPARATOR . $relativePath;
        
        if (file_exists($targetPath)) {
            // Se for arquivo de configuração ou upload, pular (preservar)
            if (strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'conexao.php') !== false ||
                strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'github_config.php') !== false ||
                strpos($relativePath, 'uploads' . DIRECTORY_SEPARATOR) === 0 ||
                strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR) === 0) {
                error_log("Preservando configuração: {$relativePath}");
                continue;
            }
        }
        
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
            $files_restored++;
        }
    }
    
    error_log("Arquivos restaurados: {$files_restored}");
    
    // PASSO 6: LIMPAR DIRETÓRIO TEMPORÁRIO
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
    
    deleteDirectory($restore_dir);
    error_log('Temporários removidos');
    
    // PASSO 7: REGISTRAR RESTAURAÇÃO
    $restore_log_file = $root_dir . DIRECTORY_SEPARATOR . '.last_restore';
    $restore_info = [
        'backup_file' => basename($backup_file),
        'restored_at' => date('Y-m-d H:i:s'),
        'files_restored' => $files_restored,
        'safety_backup' => $safety_backup_name
    ];
    
    file_put_contents($restore_log_file, json_encode($restore_info, JSON_PRETTY_PRINT));
    error_log('Restauração registrada em .last_restore');
    
    // Limpar buffer final e enviar JSON
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'message' => 'Backup restaurado com sucesso!',
        'files_restored' => $files_restored,
        'backup_file' => basename($backup_file),
        'safety_backup' => $safety_backup_name
    ]);
    
    error_log('=== RESTAURAÇÃO CONCLUÍDA COM SUCESSO ===');
    
} catch (Exception $e) {
    ob_clean();
    
    error_log('ERRO na restauração: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());
    
    // Tentar limpar diretório temporário em caso de erro
    if (isset($restore_dir) && is_dir($restore_dir)) {
        function deleteDirectory($dir) {
            if (!is_dir($dir)) return;
            
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                is_dir($path) ? deleteDirectory($path) : unlink($path);
            }
            rmdir($dir);
        }
        
        deleteDirectory($restore_dir);
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
