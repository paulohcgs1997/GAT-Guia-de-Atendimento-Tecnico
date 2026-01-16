<?php
// Verifica se o sistema está instalado
$installFlag = __DIR__ . '/.installed';
$configFile = __DIR__ . '/../src/config/conexao.php';

$isInstalled = file_exists($installFlag) || file_exists($configFile);

if (!$isInstalled) {
    header('Location: index.php?error=not_installed');
    exit;
}

// Tenta carregar credenciais do banco se o arquivo existir
$dbHost = 'localhost';
$dbName = 'gat';
$dbUser = 'root';
$dbPass = '';

if (file_exists($configFile)) {
    $configContent = file_get_contents($configFile);
    
    // Extrai as credenciais do arquivo
    if (preg_match("/define\('DB_HOST',\s*'([^']*)'\)/", $configContent, $matches)) {
        $dbHost = $matches[1];
    }
    if (preg_match("/define\('DB_NAME',\s*'([^']*)'\)/", $configContent, $matches)) {
        $dbName = $matches[1];
    }
    if (preg_match("/define\('DB_USER',\s*'([^']*)'\)/", $configContent, $matches)) {
        $dbUser = $matches[1];
    }
    if (preg_match("/define\('DB_PASS',\s*'([^']*)'\)/", $configContent, $matches)) {
        $dbPass = $matches[1];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Desinstalar - GAT Sistema</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
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
            max-width: 600px;
            width: 100%;
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 40px;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .warning-box h3 {
            color: #856404;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .warning-box ul {
            color: #856404;
            margin-left: 20px;
            margin-top: 10px;
        }

        .warning-box li {
            margin-bottom: 8px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #f44336;
            box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .checkbox-group label {
            color: #333;
            font-weight: 500;
            cursor: pointer;
        }

        .btn {
            width: 100%;
            padding: 14px;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-danger {
            background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
        }

        .btn-danger:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(244, 67, 54, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
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
            border: 1px solid #fcc;
        }

        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }

        .loading {
            text-align: center;
            padding: 40px;
            display: none;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #f44336;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .success-section {
            display: none;
            text-align: center;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ Desinstalar GAT</h1>
            <p>Remover Sistema e Banco de Dados</p>
        </div>

        <div class="content">
            <div id="alertContainer"></div>

            <!-- Formulário de Desinstalação -->
            <div id="uninstallForm">
                <?php if (file_exists($configFile)): ?>
                <div style="background: #e3f2fd; border: 2px solid #2196f3; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                    <p style="color: #1565c0; font-weight: 500;">
                        ℹ️ Instalação detectada! As credenciais do banco foram carregadas automaticamente.
                    </p>
                </div>
                <?php endif; ?>
                
                <div class="warning-box">
                    <h3>
                        <span style="font-size: 24px;">⚠️</span>
                        ATENÇÃO: Esta ação é irreversível!
                    </h3>
                    <p style="color: #856404; margin-top: 10px;">
                        A desinstalação irá remover:
                    </p>
                    <ul>
                        <li><strong>Todo o banco de dados</strong> e seus dados</li>
                        <li>Todas as tabelas e registros</li>
                        <li>Arquivo de configuração</li>
                        <li>Flag de instalação</li>
                        <li>Todas as informações cadastradas</li>
                    </ul>
                </div>

                <form id="confirmForm">
                    <div class="form-group">
                        <label for="db_host">Host do Banco de Dados</label>
                        <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($dbHost); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="db_name">Nome do Banco de Dados</label>
                        <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($dbName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="db_user">Usuário do Banco</label>
                        <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($dbUser); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="db_pass">Senha do Banco</label>
                        <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($dbPass); ?>">
                    </div>

                    <div class="checkbox-group">
                        <input type="checkbox" id="confirm_delete" required>
                        <label for="confirm_delete">
                            Confirmo que quero REMOVER PERMANENTEMENTE todos os dados
                        </label>
                    </div>

                    <button type="submit" class="btn btn-danger" id="uninstallBtn" disabled>
                        🗑️ Desinstalar Sistema
                    </button>
                    
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">
                        ← Voltar ao Instalador
                    </button>
                </form>
            </div>

            <!-- Loading -->
            <div class="loading" id="loadingSection">
                <div class="spinner"></div>
                <h3 style="color: #333; margin-bottom: 10px;">Desinstalando...</h3>
                <p style="color: #666;">Removendo banco de dados e arquivos...</p>
            </div>

            <!-- Sucesso -->
            <div class="success-section" id="successSection">
                <div class="success-icon">✓</div>
                <h2 style="color: #333; margin-bottom: 20px;">Desinstalação Concluída!</h2>
                <div class="alert alert-success">
                    <strong>Sistema desinstalado com sucesso!</strong><br>
                    Você pode instalar novamente quando quiser.
                </div>
                <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                    Ir para Instalador
                </button>
            </div>
        </div>
    </div>

    <script>
        const confirmCheckbox = document.getElementById('confirm_delete');
        const uninstallBtn = document.getElementById('uninstallBtn');

        // Habilita botão apenas se checkbox marcado
        confirmCheckbox.addEventListener('change', () => {
            uninstallBtn.disabled = !confirmCheckbox.checked;
        });

        document.getElementById('confirmForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!confirm('⚠️ ÚLTIMA CONFIRMAÇÃO!\n\nTem certeza absoluta que deseja APAGAR TUDO?\n\nEsta ação NÃO pode ser desfeita!')) {
                return;
            }
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            // Mostra loading
            document.getElementById('uninstallForm').style.display = 'none';
            document.getElementById('loadingSection').style.display = 'block';
            
            try {
                const response = await fetch('uninstall_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                console.log('Resposta completa:', result);
                console.log('Debug log:', result.debug);
                
                if (result.success) {
                    document.getElementById('loadingSection').style.display = 'none';
                    document.getElementById('successSection').style.display = 'block';
                    
                    // Mostrar debug em caso de sucesso (opcional, para verificação)
                    if (result.debug && result.debug.length > 0) {
                        console.log('=== LOG DE DESINSTALAÇÃO ===');
                        result.debug.forEach(log => console.log(log));
                    }
                } else {
                    let errorMsg = result.message || 'Erro desconhecido';
                    
                    // Adicionar debug ao erro se disponível
                    if (result.debug && result.debug.length > 0) {
                        console.error('=== DEBUG LOG ===');
                        result.debug.forEach(log => console.error(log));
                        errorMsg += '\n\n📋 Veja o console (F12) para mais detalhes.';
                    }
                    
                    showAlert(errorMsg, 'error');
                    document.getElementById('loadingSection').style.display = 'none';
                    document.getElementById('uninstallForm').style.display = 'block';
                }
            } catch (error) {
                showAlert('Erro: ' + error.message, 'error');
                document.getElementById('loadingSection').style.display = 'none';
                document.getElementById('uninstallForm').style.display = 'block';
            }
        });

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.style.whiteSpace = 'pre-wrap';
            alertDiv.style.maxHeight = '300px';
            alertDiv.style.overflowY = 'auto';
            alertDiv.textContent = message;
            
            const container = document.getElementById('alertContainer');
            container.innerHTML = '';
            container.appendChild(alertDiv);
            
            // Não remover automaticamente se for erro
            if (type !== 'error') {
                setTimeout(() => alertDiv.remove(), 5000);
            }
        }
    </script>
</body>
</html>
