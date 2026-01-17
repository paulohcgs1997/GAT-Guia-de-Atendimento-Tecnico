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
// Log para debug
error_log('=== DEBUG APPLY UPDATE ===');
error_log('Session user_id: ' . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET'));
error_log('Session perfil: ' . (isset($_SESSION['perfil']) ? $_SESSION['perfil'] : 'NOT SET'));
error_log('All session data: ' . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Sessão não encontrada. Faça login novamente.', 
        'debug' => 'user_id not set',
        'session_keys' => array_keys($_SESSION)
    ]);
    exit;
}

// Verificar perfil na sessão OU consultar banco de dados como fallback
$is_admin = false;

if (isset($_SESSION['perfil'])) {
    // Aceitar tanto 'admin' (string) quanto 1 (inteiro - ID do perfil admin)
    $perfil = $_SESSION['perfil'];
    $is_admin = ($perfil === 'admin' || $perfil === 1 || $perfil === '1');
    error_log('Perfil da sessão: ' . $perfil . ' (tipo: ' . gettype($perfil) . ')');
} 

// Sempre consultar banco como verificação de segurança adicional
if (isset($mysqli)) {
    $user_id = intval($_SESSION['user_id']);
    
    // JOIN com tabela perfil para pegar o tipo correto
    $query = "SELECT u.perfil, p.type 
              FROM usuarios u 
              LEFT JOIN perfil p ON u.perfil = p.id 
              WHERE u.id = ?";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $row = $result->fetch_assoc()) {
        $perfil_id = $row['perfil'];
        $perfil_type = $row['type'];
        
        // Admin é o ID 1 ou type 'admin'
        $is_admin_db = ($perfil_id === 1 || $perfil_id === '1' || $perfil_type === 'admin');
        
        error_log('Perfil do banco - ID: ' . $perfil_id . ', Type: ' . $perfil_type);
        error_log('É admin (banco): ' . ($is_admin_db ? 'SIM' : 'NÃO'));
        
        // Usar verificação do banco como definitiva
        $is_admin = $is_admin_db;
    } else {
        error_log('Usuário não encontrado no banco!');
    }
}

if (!$is_admin) {
    // TEMPORÁRIO: Permitir com senha de administrador como fallback
    $admin_override = $_POST['admin_password'] ?? '';
    
    if (!empty($admin_override)) {
        // Verificar se a senha é de um admin
        $user_id = intval($_SESSION['user_id']);
        $stmt = $mysqli->prepare("SELECT password, perfil FROM usuarios WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result && $row = $result->fetch_assoc()) {
            if (password_verify($admin_override, $row['password']) && $row['perfil'] === 'admin') {
                $is_admin = true;
                error_log('Admin override com senha aceito');
            }
        }
    }
    
    if (!$is_admin) {
        ob_clean();
        echo json_encode([
            'success' => false, 
            'error' => 'Permissão negada. Apenas administradores podem aplicar atualizações.',
            'debug' => [
                'session_perfil' => isset($_SESSION['perfil']) ? $_SESSION['perfil'] : 'NOT SET',
                'user_id' => $_SESSION['user_id'],
                'session_keys' => array_keys($_SESSION),
                'help' => 'Acesse /src/php/test_session.php para ver sua sessão completa'
            ]
        ]);
        exit;
    }
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
    // Diretórios
    $root_dir = realpath(__DIR__ . '/../..');
    $backup_dir = $root_dir . '/backups';
    $temp_dir = $root_dir . '/temp_update';
    
    error_log('Root dir: ' . $root_dir);
    error_log('Backup dir: ' . $backup_dir);
    error_log('Temp dir: ' . $temp_dir);
    
    // Verificar se temos permissão de escrita
    if (!is_writable($root_dir)) {
        throw new Exception('Sem permissão de escrita no diretório raiz: ' . $root_dir);
    }
    
    // Criar diretório de backups se não existir
    if (!is_dir($backup_dir)) {
        if (!mkdir($backup_dir, 0755, true)) {
            throw new Exception('Não foi possível criar diretório de backups. Verifique as permissões.');
        }
    }
    
    if (!is_writable($backup_dir)) {
        throw new Exception('Sem permissão de escrita no diretório de backups: ' . $backup_dir);
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
    // Limpar buffer e garantir JSON limpo
    ob_clean();
    
    }
    
    deleteDirectory($temp_dir);
    
    echo json_encode([
        'success' => true,
        'message' => 'Atualização aplicada com sucesso!',
        'backup_file' => $backup_name,
        'backup_path' => 'backups/' . $backup_name
    ]);
    
} catch (Exception $e) {
    // Limpar qualquer output anterior
    ob_clean();
    
    // Em caso de erro, tentar restaurar backup
    if (isset($backup_path) && file_exists($backup_path)) {
        // Código de restauração aqui se necessário
    }
    
    // Log do erro completo para debug
    error_log('Erro na atualização: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
