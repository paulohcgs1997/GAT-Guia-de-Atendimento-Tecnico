<?php
/**
 * GAT - Sistema de Gestão de Tutoriais
 * Deploy Automático via Git
 * 
 * INSTRUÇÕES:
 * 1. Faça upload deste arquivo para a raiz do seu servidor
 * 2. Acesse: http://seudominio.com/deploy.php
 * 3. O sistema será baixado automaticamente do GitHub
 * 4. Você será redirecionado para o instalador
 * 
 * REQUISITOS:
 * - PHP 7.4 ou superior
 * - Git instalado no servidor
 * - Permissões de escrita na pasta
 */

// Configurações
define('GITHUB_REPO', 'https://github.com/paulohcgs1997/GAT-Guia-de-Atendimento-Tecnico.git');
define('GITHUB_ZIP', 'https://github.com/paulohcgs1997/GAT-Guia-de-Atendimento-Tecnico/archive/refs/heads/main.zip');
define('TARGET_FOLDER', __DIR__);
define('TEMP_FOLDER', __DIR__ . '/gat_temp_deploy');
define('ZIP_FILE', __DIR__ . '/gat_deploy.zip');

// Função para verificar se uma função está disponível
function isFunctionAvailable($func) {
    return function_exists($func) && !in_array($func, explode(',', ini_get('disable_functions')));
}

// Função para executar comandos e capturar output
function executeCommand($command) {
    $output = [];
    $return_var = 0;
    
    if (isFunctionAvailable('exec')) {
        exec($command . ' 2>&1', $output, $return_var);
    } elseif (isFunctionAvailable('shell_exec')) {
        $output = shell_exec($command . ' 2>&1');
        $return_var = ($output === null) ? 1 : 0;
        $output = explode("\n", $output);
    } elseif (isFunctionAvailable('system')) {
        ob_start();
        system($command . ' 2>&1', $return_var);
        $output = explode("\n", ob_get_clean());
    } else {
        return [
            'success' => false,
            'output' => 'Nenhuma função de execução disponível (exec, shell_exec, system)',
            'code' => 1
        ];
    }
    
    return [
        'success' => $return_var === 0,
        'output' => is_array($output) ? implode("\n", $output) : $output,
        'code' => $return_var
    ];
}

// Função para verificar requisitos
function checkRequirements() {
    $errors = [];
    $warnings = [];
    
    // Verificar versão do PHP
    if (version_compare(PHP_VERSION, '7.4.0', '<')) {
        $errors[] = 'PHP 7.4 ou superior é necessário. Versão atual: ' . PHP_VERSION;
    }
    
    // Verificar permissões de escrita
    if (!is_writable(TARGET_FOLDER)) {
        $errors[] = 'Pasta não tem permissão de escrita: ' . TARGET_FOLDER;
    }
    
    // Verificar se ZIP está disponível (necessário para método alternativo)
    if (!class_exists('ZipArchive')) {
        $errors[] = 'Extensão ZipArchive não está disponível. Necessária para instalação.';
    }
    
    // Verificar se funções de execução estão disponíveis (para Git - opcional)
    $has_exec = isFunctionAvailable('exec') || isFunctionAvailable('shell_exec') || isFunctionAvailable('system');
    
    if ($has_exec) {
        // Se temos exec, verificar Git
        $git_check = executeCommand('git --version');
        if (!$git_check['success']) {
            $warnings[] = 'Git não disponível. Será usado método de download ZIP alternativo.';
        }
    } else {
        $warnings[] = 'Funções de execução (exec) desabilitadas. Será usado método de download ZIP alternativo.';
    }
    
    return ['errors' => $errors, 'warnings' => $warnings];
}

// Função para limpar pasta temporária
function cleanupTemp() {
    if (is_dir(TEMP_FOLDER)) {
        deleteDirectory(TEMP_FOLDER);
    }
    if (file_exists(ZIP_FILE)) {
        unlink(ZIP_FILE);
    }
}

// Função para deletar diretório recursivamente
function deleteDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? deleteDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

// Função para mover arquivos
function moveFiles($from, $to) {
    $files = scandir($from);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $source = $from . '/' . $file;
        $dest = $to . '/' . $file;
        
        if (is_dir($source)) {
            if (!is_dir($dest)) {
                mkdir($dest, 0755, true);
            }
            moveFiles($source, $dest);
        } else {
            copy($source, $dest);
        }
    }
}

// ========== PROCESSAR AÇÕES ANTES DE QUALQUER OUTPUT ==========

// Ação: Download do próprio arquivo
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="index.php"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize(__FILE__));
    readfile(__FILE__);
    exit;
}

// Ação: Cleanup (remover arquivo e redirecionar)
if (isset($_GET['action']) && $_GET['action'] === 'cleanup') {
    // Remover arquivo de deploy por segurança
    if (file_exists(__FILE__)) {
        unlink(__FILE__);
    }
    
    // Redirecionar para instalador
    header('Location: install/index.php');
    exit;
}

// HTML Header
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy GAT - Sistema de Gestão de Tutoriais</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 700px;
            width: 100%;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 40px;
        }
        
        .step {
            display: none;
        }
        
        .step.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
            border: 2px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 2px solid #cfc;
        }
        
        .alert-info {
            background: #eef;
            color: #33c;
            border: 2px solid #ccf;
        }
        
        .alert-warning {
            background: #ffeaa7;
            color: #d63031;
            border: 2px solid #fdcb6e;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .console {
            background: #1e1e1e;
            color: #00ff00;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
        }
        
        .console-line {
            margin-bottom: 5px;
        }
        
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #4caf50;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        
        ul {
            padding-left: 20px;
        }
        
        ul li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Deploy GAT</h1>
            <p>Sistema de Gestão de Tutoriais</p>
        </div>
        
        <div class="content">
<?php

// Processar deploy (já com HTML iniciado)
if (isset($_GET['action']) && $_GET['action'] === 'deploy') {
    echo '<div class="step active">';
    echo '<h2 style="margin-bottom: 20px;">📦 Instalando Sistema...</h2>';
    echo '<div class="console" id="console">';
    
    flush();
    ob_flush();
    
    function logConsole($message) {
        echo '<div class="console-line">' . htmlspecialchars($message) . '</div>';
        flush();
        ob_flush();
    }
    
    // Verificar requisitos
    logConsole('[1/5] Verificando requisitos...');
    $check = checkRequirements();
    
    if (!empty($check['errors'])) {
        logConsole('❌ ERRO: Requisitos não atendidos:');
        foreach ($check['errors'] as $error) {
            logConsole('  - ' . $error);
        }
        echo '</div>';
        echo '<div class="alert alert-error" style="margin-top: 20px;">';
        echo '<strong>❌ Falha na instalação</strong><br>';
        echo 'Corrija os erros acima e tente novamente.';
        echo '</div>';
        echo '</div>';
        exit;
    }
    
    if (!empty($check['warnings'])) {
        foreach ($check['warnings'] as $warning) {
            logConsole('⚠️ ' . $warning);
        }
    }
    
    logConsole('✓ Requisitos OK');
    sleep(1);
    
    // Limpar instalação anterior se existir
    logConsole('[2/5] Preparando ambiente...');
    cleanupTemp();
    logConsole('✓ Ambiente preparado');
    sleep(1);
    
    // Tentar usar Git primeiro, se falhar usar download ZIP
    logConsole('[3/5] Baixando sistema do GitHub...');
    
    $use_git = false;
    $has_exec = isFunctionAvailable('exec') || isFunctionAvailable('shell_exec') || isFunctionAvailable('system');
    
    if ($has_exec) {
        $git_check = executeCommand('git --version');
        $use_git = $git_check['success'];
    }
    
    $download_success = false;
    
    if ($use_git) {
        // Método 1: Git Clone
        logConsole('Usando Git para clonar repositório...');
        logConsole('URL: ' . GITHUB_REPO);
        
        $clone_result = executeCommand('git clone "' . GITHUB_REPO . '" "' . TEMP_FOLDER . '"');
        
        if ($clone_result['success']) {
            logConsole('✓ Repositório clonado com sucesso');
            $download_success = true;
        } else {
            logConsole('⚠️ Git clone falhou, tentando método alternativo...');
        }
    }
    
    if (!$download_success) {
        // Método 2: Download ZIP
        logConsole('Baixando via ZIP...');
        logConsole('URL: ' . GITHUB_ZIP);
        
        $zip_content = @file_get_contents(GITHUB_ZIP);
        
        if ($zip_content === false) {
            logConsole('❌ ERRO ao baixar ZIP do repositório');
            echo '</div>';
            echo '<div class="alert alert-error" style="margin-top: 20px;">';
            echo '<strong>❌ Falha ao baixar sistema</strong><br>';
            echo 'Não foi possível baixar o arquivo do GitHub. Verifique sua conexão com a internet.';
            echo '</div>';
            echo '</div>';
            exit;
        }
        
        file_put_contents(ZIP_FILE, $zip_content);
        logConsole('✓ Arquivo ZIP baixado (' . round(strlen($zip_content) / 1024 / 1024, 2) . ' MB)');
        
        // Extrair ZIP
        logConsole('Extraindo arquivos...');
        $zip = new ZipArchive();
        
        if ($zip->open(ZIP_FILE) === true) {
            $zip->extractTo(TEMP_FOLDER);
            $zip->close();
            logConsole('✓ Arquivos extraídos com sucesso');
            
            // O GitHub cria uma subpasta com o nome do repo-branch
            $extracted_folders = array_diff(scandir(TEMP_FOLDER), ['.', '..']);
            if (count($extracted_folders) == 1) {
                $subfolder = TEMP_FOLDER . '/' . reset($extracted_folders);
                // Mover conteúdo da subpasta para TEMP_FOLDER
                $files = array_diff(scandir($subfolder), ['.', '..']);
                foreach ($files as $file) {
                    rename($subfolder . '/' . $file, TEMP_FOLDER . '/' . $file);
                }
                rmdir($subfolder);
            }
            
            $download_success = true;
        } else {
            logConsole('❌ ERRO ao extrair ZIP');
            echo '</div>';
            echo '<div class="alert alert-error" style="margin-top: 20px;">';
            echo '<strong>❌ Falha ao extrair arquivos</strong><br>';
            echo 'Não foi possível extrair o arquivo ZIP baixado.';
            echo '</div>';
            echo '</div>';
            exit;
        }
    }
    
    if (!$download_success) {
        logConsole('❌ ERRO: Todos os métodos de download falharam');
        echo '</div>';
        echo '<div class="alert alert-error" style="margin-top: 20px;">';
        echo '<strong>❌ Falha no download</strong><br>';
        echo 'Não foi possível baixar o sistema usando nenhum método disponível.';
        echo '</div>';
        echo '</div>';
        exit;
    }
    
    logConsole('✓ Sistema baixado com sucesso');
    sleep(1);
    
    // Mover arquivos
    logConsole('[4/5] Instalando arquivos...');
    
    if (is_dir(TEMP_FOLDER)) {
        moveFiles(TEMP_FOLDER, TARGET_FOLDER);
        logConsole('✓ Arquivos instalados');
    } else {
        logConsole('❌ ERRO: Pasta temporária não encontrada');
        echo '</div></div>';
        exit;
    }
    
    sleep(1);
    
    // Limpar
    logConsole('[5/5] Finalizando...');
    cleanupTemp();
    
    // Criar diretórios necessários
    if (!is_dir(TARGET_FOLDER . '/uploads/avatars')) {
        mkdir(TARGET_FOLDER . '/uploads/avatars', 0755, true);
    }
    
    if (!is_dir(TARGET_FOLDER . '/backups')) {
        mkdir(TARGET_FOLDER . '/backups', 0755, true);
    }
    
    logConsole('✓ Instalação concluída!');
    
    echo '</div>'; // Fecha console
    
    echo '<div class="success-icon">✓</div>';
    echo '<div class="alert alert-success">';
    echo '<strong>✅ Sistema instalado com sucesso!</strong><br>';
    echo 'O sistema GAT foi baixado e instalado na pasta atual.';
    echo '</div>';
    
    echo '<form method="POST" action="?action=cleanup">';
    echo '<button type="submit" class="btn">Continuar para Instalação →</button>';
    echo '</form>';
    
    echo '</div>'; // Fecha step
    
} else {
    // Tela inicial
    ?>
    <div class="step active">
        <h2 style="margin-bottom: 20px;">Bem-vindo ao Deploy Automático</h2>
        
        <div class="alert alert-info">
            <strong>📋 O que este script faz:</strong>
            <ul style="margin-top: 10px;">
                <li>Verifica requisitos do servidor (PHP 7.4+, ZipArchive)</li>
                <li>Baixa a versão mais recente do sistema do GitHub (ZIP ou Git)</li>
                <li>Instala todos os arquivos na pasta atual</li>
                <li>Cria diretórios necessários</li>
                <li>Redireciona para o instalador</li>
            </ul>
        </div>
        
        <div class="alert alert-warning">
            <strong>⚠️ Atenção:</strong>
            <ul style="margin-top: 10px;">
                <li>Certifique-se de que esta pasta está vazia ou faça backup</li>
                <li>Se Git não estiver disponível, será usado download ZIP</li>
                <li>Permissões de escrita são necessárias</li>
                <li>Este arquivo será automaticamente deletado após a instalação</li>
            </ul>
        </div>
        
        <h3 style="margin: 20px 0 10px;">📦 Repositório:</h3>
        <p style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; word-break: break-all;">
            <?php echo GITHUB_REPO; ?>
        </p>
        
        <form method="GET" action="" style="margin-top: 30px;">
            <input type="hidden" name="action" value="deploy">
            <button type="submit" class="btn">🚀 Iniciar Deploy</button>
        </form>
        
        <div style="text-align: center; margin-top: 15px;">
            <a href="?action=download" style="color: #667eea; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 5px;">
                <span style="font-size: 18px;">📥</span>
                Baixar este arquivo (install.php)
            </a>
        </div>
    </div>
    <?php
}
?>
        </div>
    </div>
</body>
</html>
