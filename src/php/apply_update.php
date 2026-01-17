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

error_log('========== INICIANDO ATUALIZAÇÃO DO SISTEMA ==========');
error_log('URL de download: ' . $download_url);

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
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($root_dir) + 1);
        
        // Ignorar backups, uploads (AMBAS as pastas) e temp
        if (strpos($relativePath, 'backups' . DIRECTORY_SEPARATOR) !== 0 && 
            strpos($relativePath, 'uploads' . DIRECTORY_SEPARATOR) !== 0 && 
            strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR) !== 0 && 
            strpos($relativePath, 'temp_') !== 0) {
            $zip->addFile($filePath, $relativePath);
        }
    }
    
    $zip->close();
    error_log('Backup criado com sucesso');
    
    // Limpar backups antigos (manter apenas os 3 mais recentes)
    error_log('Limpando backups antigos...');
    $backup_files = glob($backup_dir . DIRECTORY_SEPARATOR . 'backup_*.zip');
    
    // Ordenar por data de modificação (mais recente primeiro)
    usort($backup_files, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });
    
    // Manter apenas os 3 mais recentes
    $backups_to_delete = array_slice($backup_files, 3);
    foreach ($backups_to_delete as $old_backup) {
        if (unlink($old_backup)) {
            error_log('Backup antigo excluído: ' . basename($old_backup));
        }
    }
    
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
    // O nome geralmente é: REPO-BRANCH (ex: GAT-Guia-de-Atendimento-Tecnico-dev)
    $extracted_dirs = glob($temp_dir . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
    
    if (empty($extracted_dirs)) {
        throw new Exception('Nenhum diretório encontrado após extração');
    }
    
    $update_files_dir = $extracted_dirs[0];
    error_log('Diretório extraído: ' . $update_files_dir);
    
    // Verificar se o diretório realmente existe e é acessível
    if (!is_dir($update_files_dir)) {
        error_log('ERRO: Diretório não é válido: ' . $update_files_dir);
        error_log('Diretórios encontrados: ' . print_r($extracted_dirs, true));
        throw new Exception('Diretório extraído não é válido');
    }
    
    // Verificar se tem permissão de leitura
    if (!is_readable($update_files_dir)) {
        error_log('ERRO: Sem permissão de leitura no diretório: ' . $update_files_dir);
        throw new Exception('Sem permissão de leitura no diretório extraído');
    }
    
    error_log('Diretório validado e acessível');
    
    // ⚠️ VALIDAÇÃO CRÍTICA: GARANTIR QUE O DIRETÓRIO TEM ARQUIVOS DO SISTEMA
    error_log('Validando conteúdo do diretório extraído...');
    
    $critical_files = ['index.php', 'src', 'viwer', 'install'];
    $found_critical = 0;
    
    foreach ($critical_files as $critical_file) {
        $check_path = $update_files_dir . DIRECTORY_SEPARATOR . $critical_file;
        if (file_exists($check_path)) {
            $found_critical++;
            error_log("✓ Arquivo crítico encontrado: $critical_file");
        } else {
            error_log("✗ Arquivo crítico NÃO encontrado: $critical_file");
        }
    }
    
    // Se não encontrou pelo menos 3 arquivos críticos, algo está errado
    if ($found_critical < 3) {
        error_log("ERRO CRÍTICO: Apenas $found_critical de 4 arquivos críticos encontrados!");
        error_log('Listando conteúdo do diretório:');
        $dir_contents = scandir($update_files_dir);
        error_log(print_r($dir_contents, true));
        
        throw new Exception(
            "Validação falhou: O diretório extraído não parece conter os arquivos do sistema. " .
            "Apenas $found_critical de 4 arquivos críticos foram encontrados. " .
            "A atualização foi cancelada para evitar perda de dados."
        );
    }
    
    error_log("✓ Validação passou: $found_critical arquivos críticos encontrados");
    
    // PASSO 4: REMOVER ARQUIVOS ANTIGOS (EXCETO PROTEGIDOS)
    error_log('Removendo arquivos antigos...');
    
    // Lista EXPANDIDA de diretórios e arquivos que NUNCA devem ser removidos
    $protected_paths = [
        // Configurações críticas
        'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'conexao.php',
        'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'github_config.php',
        
        // Uploads (TODAS as variações)
        'uploads',
        'uploads' . DIRECTORY_SEPARATOR,
        'src' . DIRECTORY_SEPARATOR . 'uploads',
        'src' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR,
        
        // Backups
        'backups',
        'backups' . DIRECTORY_SEPARATOR,
        
        // Arquivos temporários
        'temp_restore_',
        'temp_update',
        
        // Git
        '.git',
        '.gitignore',
        
        // Versionamento
        '.last_update',
        'version.json',
        
        // Instalação
        'install' . DIRECTORY_SEPARATOR . '.installed',
        
        // Logs
        'error_log',
        'php_errors.log'
    ];
    
    // Função para verificar se um caminho está protegido
    function isProtectedPath($path, $protected_paths) {
        // Normalizar barras
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        foreach ($protected_paths as $protected) {
            $protected = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $protected);
            
            // Verificar se começa com o caminho protegido
            if (strpos($path, $protected) === 0) {
                return true;
            }
            
            // Verificar se contém o caminho protegido
            if (strpos($path, DIRECTORY_SEPARATOR . $protected) !== false) {
                return true;
            }
        }
        return false;
    }
    
    // 🛡️ PROTEÇÃO ADICIONAL: Contar quantos arquivos serão deletados
    error_log('Analisando arquivos para remoção...');
    
    $files_count = 0;
    $protected_count = 0;
    // 🛡️ PROTEÇÃO ADICIONAL: Contar quantos arquivos serão deletados
    error_log('Analisando arquivos para remoção...');
    
    $files_count = 0;
    $protected_count = 0;
    
    // Coletar todos os arquivos atuais (exceto protegidos)
    $current_files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST // CHILD_FIRST para deletar arquivos antes de pastas
    );
    
    $files_to_delete = [];
    foreach ($current_files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($root_dir) + 1);
        
        $files_count++;
        
        // Verificar se o caminho está protegido
        if (isProtectedPath($relativePath, $protected_paths)) {
            $protected_count++;
            error_log("🛡️ PROTEGIDO: $relativePath");
        } else {
            $files_to_delete[] = $filePath;
        }
    }
    
    error_log("📊 Total de arquivos: $files_count");
    error_log("🛡️ Arquivos protegidos: $protected_count");
    error_log("🗑️ Arquivos a deletar: " . count($files_to_delete));
    
    // 🚨 VALIDAÇÃO CRÍTICA: Se vai deletar mais de 90% dos arquivos, algo está errado!
    $delete_percentage = ($files_count > 0) ? (count($files_to_delete) / $files_count) * 100 : 0;
    
    if ($delete_percentage > 95) {
        error_log("⚠️ ALERTA CRÍTICO: Tentando deletar {$delete_percentage}% dos arquivos!");
        throw new Exception(
            "Operação cancelada por segurança: O sistema tentaria deletar {$delete_percentage}% dos arquivos. " .
            "Isso pode indicar um problema com a atualização. Total: $files_count, A deletar: " . count($files_to_delete)
        );
    }
    
    // Deletar arquivos coletados
    $deleted_count = 0;
    $failed_count = 0;
    
    foreach ($files_to_delete as $file_path) {
        try {
            if (is_file($file_path)) {
                if (@unlink($file_path)) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                    error_log("Falha ao deletar arquivo: $file_path");
                }
            } elseif (is_dir($file_path)) {
                if (@rmdir($file_path)) {
                    $deleted_count++;
                }
                // Se falhar, não é erro crítico (diretório pode não estar vazio)
            }
        } catch (Exception $e) {
            $failed_count++;
            error_log("Erro ao deletar: $file_path - " . $e->getMessage());
        }
    }
    
    error_log("✅ Arquivos deletados: $deleted_count");
    if ($failed_count > 0) {
        error_log("⚠️ Falhas ao deletar: $failed_count");
    }
    
    error_log('Arquivos antigos removidos (exceto configurações e uploads)');
    
    // PASSO 5: APLICAR NOVOS ARQUIVOS
    error_log('Aplicando arquivos novos...');
    
    // Verificar novamente antes de iterar
    if (!is_dir($update_files_dir) || !is_readable($update_files_dir)) {
        throw new Exception('Diretório de atualização não está acessível: ' . $update_files_dir);
    }
    
    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($update_files_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
    } catch (Exception $e) {
        error_log('ERRO ao criar iterador: ' . $e->getMessage());
        error_log('Diretório tentado: ' . $update_files_dir);
        error_log('Existe? ' . (file_exists($update_files_dir) ? 'SIM' : 'NÃO'));
        error_log('É diretório? ' . (is_dir($update_files_dir) ? 'SIM' : 'NÃO'));
        error_log('Legível? ' . (is_readable($update_files_dir) ? 'SIM' : 'NÃO'));
        
        // Listar conteúdo do temp_dir para debug
        $temp_contents = scandir($temp_dir);
        error_log('Conteúdo de temp_dir: ' . print_r($temp_contents, true));
        
        throw new Exception('Erro ao acessar diretório de atualização: ' . $e->getMessage());
    }
    
    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, strlen($update_files_dir) + 1);
        
        // Ignorar arquivos que não devem ser sobrescritos (já existem e estão protegidos)
        if (strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'conexao.php') !== false ||
            strpos($relativePath, 'uploads' . DIRECTORY_SEPARATOR) === 0 ||
            strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR) === 0 ||
            strpos($relativePath, 'backups' . DIRECTORY_SEPARATOR) === 0 ||
            strpos($relativePath, 'src' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'github_config.php') !== false) {
            
            // Se for arquivo de configuração exemplo (.example.php), copiar
            if (strpos($relativePath, '.example.php') !== false) {
                // Copiar arquivo exemplo (não sobrescrever configurações reais)
                $targetPath = $root_dir . DIRECTORY_SEPARATOR . $relativePath;
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($filePath, $targetPath);
            }
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
    
    // PASSO 6: LIMPAR TEMPORÁRIOS
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
            error_log('Buscando hash do commit em: ' . $commit_url);
            
            // Tentar buscar o hash (com ou sem token)
            $github_config = __DIR__ . '/../config/github_config.php';
            $token = '';
            if (file_exists($github_config)) {
                require_once $github_config;
                if (defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN)) {
                    $token = GITHUB_TOKEN;
                    error_log('Token GitHub encontrado');
                }
            }
            
            $headers = [
                'User-Agent: GAT-Sistema',
                'Accept: application/vnd.github.v3+json'
            ];
            
            if ($token) {
                $headers[] = "Authorization: token {$token}";
            }
            
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => $headers,
                    'timeout' => 10
                ]
            ]);
            
            $commit_data = @file_get_contents($commit_url, false, $context);
            if ($commit_data !== false) {
                $commit_info = json_decode($commit_data, true);
                if ($commit_info && isset($commit_info['sha'])) {
                    $version_info['commit_hash'] = $commit_info['sha'];
                    error_log('✅ Hash do commit obtido e salvo: ' . $commit_info['sha']);
                } else {
                    error_log('❌ Resposta do GitHub não contém hash (sha)');
                    error_log('Resposta: ' . substr($commit_data, 0, 200));
                }
            } else {
                error_log('❌ Falha ao buscar commit do GitHub API');
            }
        } else {
            error_log('❌ URL não corresponde ao padrão esperado do GitHub');
        }
    } catch (Exception $e) {
        error_log('❌ Erro ao obter hash do commit: ' . $e->getMessage());
    }
    
    $version_file = $root_dir . DIRECTORY_SEPARATOR . '.last_update';
    file_put_contents($version_file, json_encode($version_info, JSON_PRETTY_PRINT));
    error_log('📝 Informações da versão salvas em .last_update: ' . json_encode($version_info));
    
    // PASSO 7: APLICAR ATUALIZAÇÕES DE BANCO DE DADOS
    error_log('Verificando atualizações de banco de dados...');
    
    $db_updates_applied = 0;
    $db_updates_failed = [];
    
    try {
        // Buscar arquivos SQL de migração na pasta install
        $install_dir = $root_dir . DIRECTORY_SEPARATOR . 'install';
        $sql_files = glob($install_dir . DIRECTORY_SEPARATOR . '*.sql');
        
        // Filtrar apenas arquivos de update (ignorar database.sql)
        $sql_files = array_filter($sql_files, function($file) {
            $basename = basename($file);
            return $basename !== 'database.sql' && 
                   (strpos($basename, 'update_') === 0 || strpos($basename, 'add_') === 0);
        });
        
        if (count($sql_files) > 0) {
            error_log('Encontrados ' . count($sql_files) . ' arquivo(s) de migração');
            
            // Verificar quais tabelas e colunas existem
            $existing_tables = [];
            $tables_query = $mysqli->query("SHOW TABLES");
            while ($row = $tables_query->fetch_array()) {
                $table_name = $row[0];
                $existing_tables[$table_name] = [];
                
                $columns_query = $mysqli->query("SHOW COLUMNS FROM `$table_name`");
                while ($col = $columns_query->fetch_assoc()) {
                    $existing_tables[$table_name][] = $col['Field'];
                }
            }
            
            // Processar cada arquivo SQL
            foreach ($sql_files as $sql_file) {
                $filename = basename($sql_file);
                $sql_content = file_get_contents($sql_file);
                
                // Verificar se há algo para aplicar
                $needs_apply = false;
                
                // Verificar ALTER TABLE ADD COLUMN
                if (preg_match_all('/ALTER\s+TABLE\s+`?(\w+)`?\s+ADD\s+(?:COLUMN\s+)?`?(\w+)`?/i', $sql_content, $matches)) {
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $table = $matches[1][$i];
                        $column = $matches[2][$i];
                        
                        if (isset($existing_tables[$table]) && !in_array($column, $existing_tables[$table])) {
                            $needs_apply = true;
                            break;
                        }
                    }
                }
                
                // Verificar CREATE TABLE
                if (preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sql_content, $matches)) {
                    foreach ($matches[1] as $table) {
                        if (!isset($existing_tables[$table])) {
                            $needs_apply = true;
                            break;
                        }
                    }
                }
                
                // Se precisa aplicar, executar SQL
                if ($needs_apply) {
                    error_log("Aplicando migração: $filename");
                    
                    // Remover comentários
                    $sql_content = preg_replace('/--[^\n]*\n/', "\n", $sql_content);
                    
                    // Dividir por comandos
                    $queries = array_filter(array_map('trim', preg_split('/;[\s]*(\n|$)/', $sql_content)));
                    
                    $mysqli->begin_transaction();
                    
                    try {
                        foreach ($queries as $query) {
                            if (empty($query) || strlen($query) < 5) continue;
                            
                            if (!$mysqli->query($query)) {
                                $error = $mysqli->error;
                                
                                // Ignorar erros de duplicação
                                if (stripos($error, 'Duplicate') === false && 
                                    stripos($error, 'already exists') === false) {
                                    throw new Exception("Erro SQL: $error");
                                }
                            }
                        }
                        
                        $mysqli->commit();
                        $db_updates_applied++;
                        error_log("✅ Migração aplicada: $filename");
                        
                    } catch (Exception $e) {
                        $mysqli->rollback();
                        $db_updates_failed[] = $filename . ': ' . $e->getMessage();
                        error_log("❌ Erro ao aplicar $filename: " . $e->getMessage());
                    }
                } else {
                    error_log("⏭️ Migração já aplicada: $filename");
                }
            }
        }
        
    } catch (Exception $e) {
        error_log('Erro ao verificar/aplicar migrações de BD: ' . $e->getMessage());
        $db_updates_failed[] = 'Erro geral: ' . $e->getMessage();
    }
    
    // Limpar buffer final e enviar JSON
    ob_clean();
    
    $response = [
        'success' => true,
        'message' => 'Atualização aplicada com sucesso!',
        'backup_file' => $backup_name,
        'backup_path' => 'backups/' . $backup_name
    ];
    
    if ($db_updates_applied > 0) {
        $response['db_updates_applied'] = $db_updates_applied;
        $response['message'] .= " ($db_updates_applied migração(ões) de BD aplicada(s))";
    }
    
    if (count($db_updates_failed) > 0) {
        $response['db_updates_failed'] = $db_updates_failed;
        $response['message'] .= ' Alguns updates de BD falharam - verifique manualmente.';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    ob_clean();
    
    error_log('❌ ERRO CRÍTICO na atualização: ' . $e->getMessage());
    error_log('Trace: ' . $e->getTraceAsString());
    
    // Tentar informar qual backup pode ser usado para restaurar
    $latest_backup = null;
    if (isset($backup_name) && !empty($backup_name)) {
        $latest_backup = $backup_name;
        error_log('💾 Backup criado antes do erro: ' . $backup_name);
    } else {
        // Buscar o backup mais recente
        if (isset($backup_dir) && is_dir($backup_dir)) {
            $backup_files = glob($backup_dir . DIRECTORY_SEPARATOR . 'backup_*.zip');
            if (!empty($backup_files)) {
                usort($backup_files, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $latest_backup = basename($backup_files[0]);
                error_log('💾 Último backup disponível: ' . $latest_backup);
            }
        }
    }
    
    $error_response = [
        'success' => false,
        'error' => $e->getMessage(),
        'restore_available' => !is_null($latest_backup)
    ];
    
    if ($latest_backup) {
        $error_response['backup_file'] = $latest_backup;
        $error_response['restore_message'] = 'Um backup está disponível. Acesse a aba "Backups" nas configurações para restaurar o sistema.';
    }
    
    echo json_encode($error_response);
}
