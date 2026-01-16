<?php
// Verifica se veio de um erro do uninstall
$errorMessage = '';
if (isset($_GET['error']) && $_GET['error'] === 'not_installed') {
    $errorMessage = 'Sistema não está instalado. Não há nada para desinstalar.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalador - GAT Sistema de Tutoriais</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
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
            max-width: 600px;
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

        .header p {
            opacity: 0.9;
            font-size: 14px;
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
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
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

        .alert-info {
            background: #eef;
            color: #33c;
            border: 1px solid #ccf;
        }

        .progress-bar {
            height: 4px;
            background: #e0e0e0;
            margin-bottom: 30px;
            border-radius: 2px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-bottom: 30px;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e0e0e0;
            transition: all 0.3s ease;
        }

        .step-dot.active {
            background: #667eea;
            width: 30px;
            border-radius: 5px;
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

        .loading {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Instalador GAT</h1>
            <p>Sistema de Gestão de Tutoriais</p>
            <div style="margin-top: 15px;">
                <a href="uninstall.php" style="color: rgba(255,255,255,0.9); text-decoration: none; font-size: 13px; padding: 8px 16px; border: 1px solid rgba(255,255,255,0.3); border-radius: 4px; display: inline-block; transition: all 0.3s;">
                    🗑️ Desinstalar Sistema
                </a>
            </div>
        </div>

        <div class="content">
            <div class="progress-bar">
                <div class="progress-bar-fill" id="progressBar" style="width: 33%"></div>
            </div>

            
            <?php if ($errorMessage): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
            <?php endif; ?>
            <div class="step-indicator">
                <div class="step-dot active" id="dot1"></div>
                <div class="step-dot" id="dot2"></div>
                <div class="step-dot" id="dot3"></div>
            </div>

            <div id="alertContainer"></div>

            <!-- Passo 1: Configuração do Banco de Dados -->
            <div class="step active" id="step1">
                <h2 style="margin-bottom: 20px; color: #333;">Configuração do Banco de Dados</h2>
                
                <form id="dbForm">
                    <div class="form-group">
                        <label for="db_host">Host do Banco de Dados</label>
                        <input type="text" id="db_host" name="db_host" value="localhost" required>
                        <small>Geralmente "localhost" para servidor local</small>
                    </div>

                    <div class="form-group">
                        <label for="db_name">Nome do Banco de Dados</label>
                        <input type="text" id="db_name" name="db_name" value="gat" required>
                        <small>O banco será criado automaticamente se não existir</small>
                    </div>

                    <div class="form-group">
                        <label for="db_user">Usuário do Banco</label>
                        <input type="text" id="db_user" name="db_user" value="root" required>
                    </div>

                    <div class="form-group">
                        <label for="db_pass">Senha do Banco</label>
                        <input type="password" id="db_pass" name="db_pass">
                        <small>Deixe vazio se não houver senha</small>
                    </div>

                    <button type="submit" class="btn">Próximo →</button>
                </form>
            </div>

            <!-- Passo 2: Usuário Administrador -->
            <div class="step" id="step2">
                <h2 style="margin-bottom: 20px; color: #333;">Criar Usuário Administrador</h2>
                
                <form id="adminForm">
                    <div class="form-group">
                        <label for="admin_user">Nome de Usuário</label>
                        <input type="text" id="admin_user" name="admin_user" value="admin" required>
                        <small>Será usado para fazer login no sistema</small>
                    </div>

                    <div class="form-group">
                        <label for="admin_pass">Senha</label>
                        <input type="password" id="admin_pass" name="admin_pass" required minlength="6">
                        <small>Mínimo de 6 caracteres</small>
                    </div>

                    <div class="form-group">
                        <label for="admin_pass_confirm">Confirmar Senha</label>
                        <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" required>
                    </div>

                    <button type="submit" class="btn">Instalar Sistema →</button>
                </form>
            </div>

            <!-- Passo 3: Instalação em Progresso -->
            <div class="step" id="step3">
                <div class="loading">
                    <div class="spinner"></div>
                    <h3 style="color: #333; margin-bottom: 10px;">Instalando o sistema...</h3>
                    <p style="color: #666;" id="installStatus">Criando banco de dados...</p>
                </div>
            </div>

            <!-- Passo 4: Sucesso -->
            <div class="step" id="step4">
                <div class="success-icon">✓</div>
                <h2 style="text-align: center; color: #333; margin-bottom: 20px;">Instalação Concluída!</h2>
                <div class="alert alert-success" style="text-align: center;" id="successMessage">
                    <strong>Sistema instalado com sucesso!</strong><br>
                    Você já pode fazer login no sistema.
                </div>
                <div id="updatesInfo" style="margin-top: 20px; display: none;">
                    <div style="background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <div style="font-weight: 600; color: #1e40af; margin-bottom: 10px;">📦 Atualizações Aplicadas Automaticamente</div>
                        <div id="updatesLog" style="font-size: 14px; color: #1e3a8a;"></div>
                    </div>
                </div>
                <div id="updatesWarnings" style="margin-top: 20px; display: none;">
                    <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                        <div style="font-weight: 600; color: #92400e; margin-bottom: 10px;">⚠️ Avisos</div>
                        <div id="warningsLog" style="font-size: 13px; color: #78350f;"></div>
                        <div style="margin-top: 10px; font-size: 13px; color: #78350f;">
                            💡 Você pode aplicar atualizações manualmente em:<br>
                            <strong>Configurações → Verificador de Banco de Dados</strong>
                        </div>
                    </div>
                </div>
                <button class="btn" onclick="window.location.href='../viwer/login.php'">Ir para Login</button>
            </div>
        </div>
    </div>

    <script>
        let dbConfig = {};
        let currentStep = 1;

        // Form 1: Configuração do Banco
        document.getElementById('dbForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            showLoading('Testando conexão com o banco de dados...');
            
            try {
                const response = await fetch('install_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'test_db', ...data })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    dbConfig = data;
                    showAlert('Conexão bem-sucedida!', 'success');
                    setTimeout(() => goToStep(2), 1000);
                } else {
                    showAlert('Erro: ' + result.message, 'error');
                }
            } catch (error) {
                showAlert('Erro de conexão: ' + error.message, 'error');
            }
        });

        // Form 2: Usuário Admin
        document.getElementById('adminForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const pass = document.getElementById('admin_pass').value;
            const passConfirm = document.getElementById('admin_pass_confirm').value;
            
            if (pass !== passConfirm) {
                showAlert('As senhas não coincidem!', 'error');
                return;
            }
            
            goToStep(3);
            
            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData);
            
            try {
                const response = await fetch('install_process.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        action: 'install',
                        db: dbConfig,
                        admin: data
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Atualizar mensagem de sucesso
                    const successMsg = document.getElementById('successMessage');
                    successMsg.innerHTML = `
                        <strong>Sistema instalado com sucesso!</strong><br>
                        Você já pode fazer login no sistema.
                    `;
                    
                    // Mostrar informações sobre atualizações aplicadas
                    if (result.updates_applied > 0) {
                        const updatesInfo = document.getElementById('updatesInfo');
                        const updatesLog = document.getElementById('updatesLog');
                        updatesInfo.style.display = 'block';
                        
                        // Processar mensagem para extrair atualizações
                        const lines = result.message.split('\n');
                        let updatesHtml = '<ul style="margin: 10px 0; padding-left: 20px;">';
                        
                        let inUpdates = false;
                        lines.forEach(line => {
                            if (line.includes('Atualizações aplicadas:')) {
                                inUpdates = true;
                            } else if (inUpdates && line.trim() && !line.includes('Avisos')) {
                                updatesHtml += `<li>${line.trim()}</li>`;
                            }
                        });
                        
                        updatesHtml += '</ul>';
                        updatesLog.innerHTML = updatesHtml;
                    }
                    
                    // Mostrar avisos se houver
                    if (result.updates_errors > 0) {
                        const warningsDiv = document.getElementById('updatesWarnings');
                        const warningsLog = document.getElementById('warningsLog');
                        warningsDiv.style.display = 'block';
                        
                        // Extrair avisos da mensagem
                        const lines = result.message.split('\n');
                        let warningsHtml = '<ul style="margin: 10px 0; padding-left: 20px;">';
                        
                        let inWarnings = false;
                        lines.forEach(line => {
                            if (line.includes('Avisos durante atualizações:')) {
                                inWarnings = true;
                            } else if (inWarnings && line.trim() && !line.includes('O sistema foi instalado')) {
                                warningsHtml += `<li>${line.trim()}</li>`;
                            }
                        });
                        
                        warningsHtml += '</ul>';
                        warningsLog.innerHTML = warningsHtml;
                    }
                    
                    goToStep(4);
                } else {
                    showAlert('Erro na instalação: ' + result.message, 'error');
                    goToStep(2);
                }
            } catch (error) {
                showAlert('Erro: ' + error.message, 'error');
                goToStep(2);
            }
        });

        function goToStep(step) {
            document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
            document.getElementById('step' + step).classList.add('active');
            
            document.querySelectorAll('.step-dot').forEach(d => d.classList.remove('active'));
            document.getElementById('dot' + Math.min(step, 3)).classList.add('active');
            
            const progress = (step / 3) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            
            currentStep = step;
        }

        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            
            const container = document.getElementById('alertContainer');
            container.innerHTML = '';
            container.appendChild(alertDiv);
            
            setTimeout(() => alertDiv.remove(), 5000);
        }

        function showLoading(message) {
            document.getElementById('installStatus').textContent = message;
        }
    </script>
</body>
</html>
