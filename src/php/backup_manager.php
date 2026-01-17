<?php
// Desabilitar exibição de erros HTML
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Log customizado para debug
$debug_log = __DIR__ . '/../../backups/backup_debug.log';

function debug_log($message) {
    global $debug_log;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debug_log, "[$timestamp] $message\n", FILE_APPEND);
}

debug_log('===== Backup Manager Iniciado =====');
debug_log('Action: ' . ($_GET['action'] ?? $_POST['action'] ?? 'none'));

session_start();

// Verificar se está logado e se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['perfil'] != '1') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado']);
    exit;
}

header('Content-Type: application/json');

$backups_dir = __DIR__ . '/../../backups';
$root_dir = __DIR__ . '/../..';
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// Garantir que o diretório de backups existe
if (!is_dir($backups_dir)) {
    mkdir($backups_dir, 0755, true);
}

// ========== LISTAR BACKUPS ==========
if ($action === 'list') {
    try {
        $backups = [];
        $files = glob($backups_dir . '/backup_*.zip');
        
        // Ordenar por data de modificação (mais recente primeiro)
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        foreach ($files as $file) {
            $filename = basename($file);
            $filesize = filesize($file);
            $filetime = filemtime($file);
            
            // Determinar tipo (auto ou manual)
            $type = 'auto'; // Padrão
            if (strpos($filename, 'manual') !== false) {
                $type = 'manual';
            }
            
            $backups[] = [
                'filename' => $filename,
                'path' => $file,
                'size' => formatBytes($filesize),
                'date' => date('d/m/Y H:i:s', $filetime),
                'timestamp' => $filetime,
                'type' => $type
            ];
        }
        
        echo json_encode([
            'success' => true,
            'backups' => $backups,
            'total' => count($backups)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ========== CRIAR BACKUP MANUAL ==========
if ($action === 'create') {
    try {
        $backup_name = 'backup_manual_' . date('Y-m-d_H-i-s') . '.zip';
        $backup_path = $backups_dir . '/' . $backup_name;
        
        // Criar o backup
        $zip = new ZipArchive();
        if ($zip->open($backup_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            throw new Exception('Não foi possível criar o arquivo ZIP');
        }
        
        // Adicionar arquivos ao ZIP
        addFilesToZip($zip, $root_dir, '', [
            'backups',
            '.git',
            'node_modules',
            '.env',
            'vendor'
        ]);
        
        $zip->close();
        
        // Manter apenas os 3 mais recentes
        cleanOldBackups($backups_dir, 3);
        
        echo json_encode([
            'success' => true,
            'filename' => $backup_name,
            'size' => formatBytes(filesize($backup_path))
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ========== RESTAURAR BACKUP ==========
if ($action === 'restore') {
    debug_log('Iniciando restauração de backup');
    
    try {
        // Verificar extensão ZipArchive
        if (!class_exists('ZipArchive')) {
            debug_log('ERRO: ZipArchive não disponível');
            throw new Exception('Extensão ZIP não está habilitada no PHP. Execute: habilitar extensão zip no php.ini');
        }
        debug_log('ZipArchive disponível');
        
        $filename = $_POST['filename'] ?? '';
        debug_log('Filename recebido: ' . $filename);
        
        if (empty($filename)) {
            throw new Exception('Nome do arquivo não fornecido');
        }
        
        // Sanitizar nome do arquivo
        $filename = basename($filename);
        $backup_path = $backups_dir . '/' . $filename;
        debug_log('Caminho do backup: ' . $backup_path);
        
        if (!file_exists($backup_path)) {
            debug_log('ERRO: Arquivo não encontrado');
            throw new Exception('Arquivo de backup não encontrado: ' . $filename);
        }
        debug_log('Arquivo existe');
        
        if (!is_readable($backup_path)) {
            debug_log('ERRO: Sem permissão de leitura');
            throw new Exception('Sem permissão para ler o arquivo de backup');
        }
        debug_log('Arquivo legível');
        
        // Criar um backup do estado atual antes de restaurar
        debug_log('Criando backup de segurança...');
        $safety_backup = 'backup_before_restore_' . date('Y-m-d_H-i-s') . '.zip';
        $safety_path = $backups_dir . '/' . $safety_backup;
        
        $zip = new ZipArchive();
        if ($zip->open($safety_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            debug_log('Adicionando arquivos ao backup de segurança...');
            addFilesToZip($zip, $root_dir, '', ['backups', '.git', 'node_modules']);
            $zip->close();
            debug_log('Backup de segurança criado: ' . $safety_backup);
        } else {
            debug_log('AVISO: Não foi possível criar backup de segurança');
        }
        
        // Extrair o backup
        debug_log('Abrindo arquivo de backup para restauração...');
        $zip = new ZipArchive();
        $open_result = $zip->open($backup_path);
        
        if ($open_result !== TRUE) {
            $error_messages = [
                ZipArchive::ER_EXISTS => 'Arquivo já existe',
                ZipArchive::ER_INCONS => 'ZIP inconsistente',
                ZipArchive::ER_INVAL => 'Argumento inválido',
                ZipArchive::ER_MEMORY => 'Erro de memória',
                ZipArchive::ER_NOENT => 'Arquivo não existe',
                ZipArchive::ER_NOZIP => 'Não é um arquivo ZIP',
                ZipArchive::ER_OPEN => 'Não foi possível abrir o arquivo',
                ZipArchive::ER_READ => 'Erro de leitura',
                ZipArchive::ER_SEEK => 'Erro de posicionamento'
            ];
            
            $error_msg = $error_messages[$open_result] ?? 'Erro desconhecido: ' . $open_result;
            debug_log('ERRO ao abrir ZIP: ' . $error_msg);
            throw new Exception('Não foi possível abrir o backup: ' . $error_msg);
        }
        debug_log('Arquivo ZIP aberto com sucesso');
        
        // Extrair para um diretório temporário primeiro
        $temp_dir = $root_dir . '/temp_restore_' . time();
        debug_log('Criando diretório temporário: ' . $temp_dir);
        
        if (!mkdir($temp_dir, 0755, true)) {
            debug_log('ERRO: Falha ao criar diretório temporário');
            throw new Exception('Não foi possível criar diretório temporário');
        }
        debug_log('Diretório temporário criado');
        
        debug_log('Extraindo arquivos...');
        if (!$zip->extractTo($temp_dir)) {
            debug_log('ERRO: Falha na extração');
            throw new Exception('Erro ao extrair arquivos do backup');
        }
        debug_log('Arquivos extraídos com sucesso');
        
        $zip->close();
        
        // Encontrar o diretório raiz extraído
        debug_log('Procurando diretório raiz extraído...');
        $extracted_root = $temp_dir;
        $contents = scandir($temp_dir);
        debug_log('Conteúdo do temp_dir: ' . implode(', ', $contents));
        
        if ($contents && count($contents) == 3) {
            $possible_root = $temp_dir . '/' . $contents[2];
            if (is_dir($possible_root)) {
                $extracted_root = $possible_root;
                debug_log('Diretório raiz ajustado para: ' . $extracted_root);
            }
        }
        
        // Copiar arquivos
        debug_log('Iniciando cópia de arquivos...');
        $copy_result = copyDirectory($extracted_root, $root_dir, [
            'src/config/conexao.php',
            'src/config/github_config.php',
            'backups'
        ]);
        
        if (!$copy_result) {
            debug_log('ERRO: Falha ao copiar arquivos');
            throw new Exception('Erro ao copiar arquivos do backup');
        }
        debug_log('Arquivos copiados com sucesso');
        
        // Limpar diretório temporário
        debug_log('Limpando diretório temporário...');
        deleteDirectory($temp_dir);
        debug_log('Diretório temporário removido');
        
        // Manter apenas os 3 mais recentes
        debug_log('Limpando backups antigos...');
        cleanOldBackups($backups_dir, 3);
        
        debug_log('===== Restauração concluída com SUCESSO =====');
        
        echo json_encode([
            'success' => true,
            'message' => 'Backup restaurado com sucesso! A página será recarregada.',
            'safety_backup' => $safety_backup
        ]);
        
    } catch (Exception $e) {
        debug_log('ERRO Exception: ' . $e->getMessage());
        debug_log('Arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine());
        debug_log('Stack: ' . $e->getTraceAsString());
        
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'debug_log' => 'Veja backups/backup_debug.log para detalhes'
        ]);
    } catch (Throwable $e) {
        debug_log('ERRO Throwable: ' . $e->getMessage());
        debug_log('Arquivo: ' . $e->getFile() . ' Linha: ' . $e->getLine());
        debug_log('Stack: ' . $e->getTraceAsString());
        
        echo json_encode([
            'success' => false,
            'error' => 'Erro fatal: ' . $e->getMessage(),
            'debug_log' => 'Veja backups/backup_debug.log para detalhes'
        ]);
    }
    exit;
}

// ========== EXCLUIR BACKUP ==========
if ($action === 'delete') {
    try {
        $filename = $_POST['filename'] ?? '';
        if (empty($filename)) {
            throw new Exception('Nome do arquivo não fornecido');
        }
        
        $backup_path = $backups_dir . '/' . basename($filename);
        if (!file_exists($backup_path)) {
            throw new Exception('Arquivo de backup não encontrado');
        }
        
        if (!unlink($backup_path)) {
            throw new Exception('Não foi possível excluir o arquivo');
        }
        
        echo json_encode(['success' => true, 'message' => 'Backup excluído com sucesso']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ========== FUNÇÕES AUXILIARES ==========

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

function addFilesToZip($zip, $source, $prefix = '', $exclude = []) {
    $source = realpath($source);
    
    if (is_dir($source)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($files as $file) {
            $file = realpath($file);
            
            // Verificar se deve excluir
            $should_exclude = false;
            foreach ($exclude as $exc) {
                if (strpos($file, DIRECTORY_SEPARATOR . $exc . DIRECTORY_SEPARATOR) !== false || 
                    strpos($file, DIRECTORY_SEPARATOR . $exc) === strlen($file) - strlen(DIRECTORY_SEPARATOR . $exc)) {
                    $should_exclude = true;
                    break;
                }
            }
            
            if ($should_exclude) continue;
            
            if (is_dir($file)) {
                $zip->addEmptyDir($prefix . str_replace($source . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
            } else if (is_file($file)) {
                $zip->addFile($file, $prefix . str_replace($source . DIRECTORY_SEPARATOR, '', $file));
            }
        }
    }
}

function copyDirectory($source, $destination, $preserve = []) {
    if (!is_dir($source)) return false;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($files as $file) {
        $target = $destination . DIRECTORY_SEPARATOR . substr($file, strlen($source) + 1);
        
        // Verificar se deve preservar
        $should_preserve = false;
        foreach ($preserve as $pres) {
            $pres_path = str_replace('/', DIRECTORY_SEPARATOR, $pres);
            if (strpos($target, $destination . DIRECTORY_SEPARATOR . $pres_path) === 0) {
                $should_preserve = true;
                break;
            }
        }
        
        if ($should_preserve) continue;
        
        if (is_dir($file)) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            copy($file, $target);
        }
    }
    
    return true;
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) return false;
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    
    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getRealPath());
        } else {
            unlink($file->getRealPath());
        }
    }
    
    return rmdir($dir);
}

function cleanOldBackups($backups_dir, $keep_count = 3) {
    $files = glob($backups_dir . '/backup_*.zip');
    
    // Ordenar por data de modificação (mais recente primeiro)
    usort($files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Manter apenas os N mais recentes
    $to_delete = array_slice($files, $keep_count);
    
    foreach ($to_delete as $file) {
        unlink($file);
        error_log('Backup antigo excluído: ' . basename($file));
    }
    
    return count($to_delete);
}
