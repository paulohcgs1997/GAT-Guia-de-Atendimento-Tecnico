<?php
/**
 * Script de Diagnóstico do Sistema
 * Verifica configurações, permissões e integridade após atualizações
 * 
 * Acesse via: http://seu-dominio/diagnostico.php
 */

// Desabilitar limite de tempo
set_time_limit(0);

// Cores para output
$colors = [
    'success' => '#10b981',
    'warning' => '#f59e0b',
    'error' => '#ef4444',
    'info' => '#3b82f6'
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Diagnóstico do Sistema - GAT</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 30px;
        }
        
        .section {
            margin-bottom: 30px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .section-header {
            background: #f9fafb;
            padding: 15px 20px;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            font-size: 18px;
        }
        
        .section-body {
            padding: 20px;
        }
        
        .check-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            background: #f9fafb;
        }
        
        .check-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-weight: bold;
            color: white;
        }
        
        .check-success { background: <?php echo $colors['success']; ?>; }
        .check-warning { background: <?php echo $colors['warning']; ?>; }
        .check-error { background: <?php echo $colors['error']; ?>; }
        .check-info { background: <?php echo $colors['info']; ?>; }
        
        .check-label {
            flex: 1;
            font-weight: 500;
        }
        
        .check-value {
            color: #6b7280;
            font-family: 'Courier New', monospace;
        }
        
        pre {
            background: #1f2937;
            color: #10b981;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.5;
        }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            padding: 20px;
            border-radius: 8px;
            color: white;
            text-align: center;
        }
        
        .summary-card h3 {
            font-size: 36px;
            margin-bottom: 5px;
        }
        
        .summary-card p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .btn-reload {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: block;
            margin: 0 auto;
        }
        
        .btn-reload:hover {
            background: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Diagnóstico do Sistema</h1>
            <p>Verificação completa de configurações e integridade</p>
        </div>
        
        <div class="content">
            <?php
            $checks_passed = 0;
            $checks_failed = 0;
            $checks_warning = 0;
            $checks_total = 0;
            
            function checkItem($label, $passed, $value = '', $type = 'check') {
                global $checks_passed, $checks_failed, $checks_warning, $checks_total;
                $checks_total++;
                
                if ($type === 'warning') {
                    $checks_warning++;
                    $class = 'check-warning';
                    $icon = '⚠';
                } elseif ($passed) {
                    $checks_passed++;
                    $class = 'check-success';
                    $icon = '✓';
                } else {
                    $checks_failed++;
                    $class = 'check-error';
                    $icon = '✗';
                }
                
                echo '<div class="check-item">';
                echo '<div class="check-icon ' . $class . '">' . $icon . '</div>';
                echo '<div class="check-label">' . $label . '</div>';
                if ($value) {
                    echo '<div class="check-value">' . htmlspecialchars($value) . '</div>';
                }
                echo '</div>';
            }
            ?>
            
            <!-- VERIFICAÇÕES DE ARQUIVOS -->
            <div class="section">
                <div class="section-header">📁 Arquivos de Configuração</div>
                <div class="section-body">
                    <?php
                    $config_files = [
                        'src/config/conexao.php' => 'Configuração do Banco de Dados',
                        'src/config/github_config.php' => 'Configuração do GitHub',
                        'version.json' => 'Arquivo de Versão',
                        '.last_update' => 'Informações de Atualização'
                    ];
                    
                    foreach ($config_files as $file => $description) {
                        $exists = file_exists(__DIR__ . '/' . $file);
                        $value = $exists ? '✓ Existe' : '✗ Não encontrado';
                        checkItem($description, $exists, $value);
                    }
                    ?>
                </div>
            </div>
            
            <!-- VERIFICAÇÕES DE DIRETÓRIOS -->
            <div class="section">
                <div class="section-header">📂 Diretórios do Sistema</div>
                <div class="section-body">
                    <?php
                    $directories = [
                        'backups' => 'Backups do Sistema',
                        'uploads' => 'Uploads - Avatares de Usuários',
                        'uploads/avatars' => 'Diretório de Avatares',
                        'src/uploads' => 'Uploads - Mídia do Sistema',
                        'src/uploads/departamentos' => 'Mídia de Departamentos',
                        'src/php' => 'Scripts PHP',
                        'src/js' => 'Scripts JavaScript',
                        'viwer' => 'Views do Sistema'
                    ];
                    
                    foreach ($directories as $dir => $description) {
                        $path = __DIR__ . '/' . $dir;
                        $exists = is_dir($path);
                        $writable = $exists && is_writable($path);
                        
                        if ($exists && $writable) {
                            checkItem($description, true, '✓ Acessível e Gravável');
                        } elseif ($exists) {
                            checkItem($description, false, '⚠ Existe mas não é gravável', 'warning');
                        } else {
                            checkItem($description, false, '✗ Não encontrado');
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- VERIFICAÇÕES DO GITHUB CONFIG -->
            <div class="section">
                <div class="section-header">🔗 Configuração do GitHub</div>
                <div class="section-body">
                    <?php
                    $github_config = __DIR__ . '/src/config/github_config.php';
                    if (file_exists($github_config)) {
                        require_once $github_config;
                        
                        checkItem('Arquivo github_config.php', true, '✓ Encontrado');
                        checkItem('GITHUB_TOKEN definido', defined('GITHUB_TOKEN') && !empty(GITHUB_TOKEN), defined('GITHUB_TOKEN') ? 'ghp_***' . substr(GITHUB_TOKEN, -4) : '✗');
                        checkItem('GITHUB_OWNER definido', defined('GITHUB_OWNER') && !empty(GITHUB_OWNER), defined('GITHUB_OWNER') ? GITHUB_OWNER : '✗');
                        checkItem('GITHUB_REPO definido', defined('GITHUB_REPO') && !empty(GITHUB_REPO), defined('GITHUB_REPO') ? GITHUB_REPO : '✗');
                        checkItem('GITHUB_BRANCH definido', defined('GITHUB_BRANCH') && !empty(GITHUB_BRANCH), defined('GITHUB_BRANCH') ? GITHUB_BRANCH : '✗');
                    } else {
                        checkItem('Arquivo github_config.php', false, '✗ Não encontrado');
                    }
                    ?>
                </div>
            </div>
            
            <!-- VERIFICAÇÕES DO BANCO DE DADOS -->
            <div class="section">
                <div class="section-header">🗄️ Banco de Dados</div>
                <div class="section-body">
                    <?php
                    $conexao_file = __DIR__ . '/src/config/conexao.php';
                    if (file_exists($conexao_file)) {
                        try {
                            require_once $conexao_file;
                            
                            if (isset($mysqli) && $mysqli->connect_error === null) {
                                checkItem('Conexão com o Banco', true, '✓ Conectado');
                                
                                // Verificar tabelas
                                $tables = ['usuarios', 'perfil', 'system_config', 'departamentos', 'blocos'];
                                foreach ($tables as $table) {
                                    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
                                    checkItem("Tabela: $table", $result && $result->num_rows > 0, $result && $result->num_rows > 0 ? '✓ Existe' : '✗ Não encontrada');
                                }
                                
                                // Verificar configuração do branch no banco
                                $result = $mysqli->query("SELECT config_value FROM system_config WHERE config_key = 'github_branch'");
                                if ($result && $row = $result->fetch_assoc()) {
                                    checkItem('Branch configurado no DB', true, $row['config_value']);
                                } else {
                                    checkItem('Branch configurado no DB', false, '✗ Não encontrado', 'warning');
                                    
                                    // Tentar inserir
                                    $mysqli->query("INSERT INTO system_config (config_key, config_value) VALUES ('github_branch', 'main')");
                                    echo '<div class="check-item">';
                                    echo '<div class="check-icon check-info">ℹ</div>';
                                    echo '<div class="check-label">Tentativa de correção: Branch "main" inserido no banco</div>';
                                    echo '</div>';
                                }
                                
                            } else {
                                checkItem('Conexão com o Banco', false, '✗ Erro: ' . ($mysqli->connect_error ?? 'Desconhecido'));
                            }
                        } catch (Exception $e) {
                            checkItem('Conexão com o Banco', false, '✗ Erro: ' . $e->getMessage());
                        }
                    } else {
                        checkItem('Arquivo conexao.php', false, '✗ Não encontrado');
                    }
                    ?>
                </div>
            </div>
            
            <!-- VERIFICAÇÕES DE VERSÃO -->
            <div class="section">
                <div class="section-header">📌 Informações de Versão</div>
                <div class="section-body">
                    <?php
                    $version_file = __DIR__ . '/version.json';
                    if (file_exists($version_file)) {
                        $version_data = json_decode(file_get_contents($version_file), true);
                        checkItem('Arquivo version.json', true, '✓ Encontrado');
                        checkItem('Versão', true, $version_data['version'] ?? 'N/A');
                        checkItem('Build', true, $version_data['build'] ?? 'N/A');
                    } else {
                        checkItem('Arquivo version.json', false, '✗ Não encontrado', 'warning');
                    }
                    
                    $last_update = __DIR__ . '/.last_update';
                    if (file_exists($last_update)) {
                        $update_data = json_decode(file_get_contents($last_update), true);
                        checkItem('Última Atualização', true, $update_data['installed_at'] ?? 'N/A');
                        checkItem('Commit Hash', true, substr($update_data['commit_hash'] ?? 'N/A', 0, 7));
                    } else {
                        checkItem('Arquivo .last_update', false, '✗ Não encontrado', 'warning');
                    }
                    ?>
                </div>
            </div>
            
            <!-- VERIFICAÇÕES DE BACKUPS -->
            <div class="section">
                <div class="section-header">💾 Backups Disponíveis</div>
                <div class="section-body">
                    <?php
                    $backups_dir = __DIR__ . '/backups';
                    if (is_dir($backups_dir)) {
                        $backups = glob($backups_dir . '/backup_*.zip');
                        checkItem('Diretório de Backups', true, '✓ Acessível');
                        checkItem('Total de Backups', count($backups) > 0, count($backups) . ' arquivo(s)');
                        
                        if (count($backups) > 0) {
                            usort($backups, function($a, $b) {
                                return filemtime($b) - filemtime($a);
                            });
                            
                            $latest = $backups[0];
                            $size = filesize($latest);
                            $date = date('d/m/Y H:i:s', filemtime($latest));
                            
                            checkItem('Backup mais recente', true, basename($latest));
                            checkItem('Data do último backup', true, $date);
                            checkItem('Tamanho do último backup', true, round($size / 1024 / 1024, 2) . ' MB');
                        }
                    } else {
                        checkItem('Diretório de Backups', false, '✗ Não encontrado');
                    }
                    ?>
                </div>
            </div>
            
            <!-- RESUMO -->
            <div class="summary">
                <div class="summary-card" style="background: <?php echo $colors['success']; ?>">
                    <h3><?php echo $checks_passed; ?></h3>
                    <p>Verificações OK</p>
                </div>
                <div class="summary-card" style="background: <?php echo $colors['warning']; ?>">
                    <h3><?php echo $checks_warning; ?></h3>
                    <p>Avisos</p>
                </div>
                <div class="summary-card" style="background: <?php echo $colors['error']; ?>">
                    <h3><?php echo $checks_failed; ?></h3>
                    <p>Erros</p>
                </div>
                <div class="summary-card" style="background: <?php echo $colors['info']; ?>">
                    <h3><?php echo $checks_total; ?></h3>
                    <p>Total de Verificações</p>
                </div>
            </div>
            
            <button class="btn-reload" onclick="window.location.reload()">🔄 Executar Novamente</button>
        </div>
    </div>
</body>
</html>
