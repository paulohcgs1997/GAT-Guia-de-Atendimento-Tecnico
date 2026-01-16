<?php
session_start();

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include_once(__DIR__ . '/../src/config/conexao.php');

// Verificar se realmente precisa trocar a senha
$user_id = $_SESSION['user_id'];
$checkForce = $mysqli->query("SELECT force_password_change FROM usuarios WHERE id = $user_id");
$userData = $checkForce->fetch_assoc();

// Se não precisa trocar, redirecionar para dashboard
if (!$userData || $userData['force_password_change'] != 1) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once __DIR__ . '/../src/includes/head_config.php'; ?>
    <link rel="stylesheet" href="../src/css/style.css">
    <title>Alterar Senha - GAT</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        
        .change-password-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
        
        .change-password-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .change-password-header h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 10px;
        }
        
        .alert-warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }
        
        .alert-warning strong {
            color: #92400e;
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group small {
            display: block;
            color: #6b7280;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .password-requirements {
            background: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .password-requirements h4 {
            font-size: 13px;
            color: #374151;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .password-requirements li {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }
        
        .password-requirements li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #10b981;
        }
        
        .btn-change-password {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-change-password:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-change-password:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
    </style>
</head>
<body>
    <div class="change-password-container">
        <div class="change-password-header">
            <h1>🔐 Alterar Senha</h1>
            <p style="color: #6b7280; font-size: 14px;">Primeiro acesso ao sistema</p>
        </div>
        
        <div class="alert-warning">
            <strong>⚠️ Alteração Obrigatória</strong>
            <p style="margin: 0; font-size: 13px; color: #92400e;">
                Por segurança, você precisa criar uma nova senha antes de acessar o sistema.
            </p>
        </div>
        
        <form id="changePasswordForm">
            <div class="form-group">
                <label for="currentPassword">Senha Atual</label>
                <input type="password" id="currentPassword" name="currentPassword" required
                       placeholder="Digite a senha padrão fornecida">
                <small>Digite a senha: <strong>Mudar@123</strong></small>
            </div>
            
            <div class="form-group">
                <label for="newPassword">Nova Senha</label>
                <input type="password" id="newPassword" name="newPassword" required
                       placeholder="Digite sua nova senha">
            </div>
            
            <div class="form-group">
                <label for="confirmPassword">Confirmar Nova Senha</label>
                <input type="password" id="confirmPassword" name="confirmPassword" required
                       placeholder="Digite a nova senha novamente">
            </div>
            
            <div class="password-requirements">
                <h4>📋 Requisitos da Senha:</h4>
                <ul>
                    <li>Mínimo de 6 caracteres</li>
                    <li>Diferente da senha padrão</li>
                    <li>Ambas as senhas devem coincidir</li>
                </ul>
            </div>
            
            <button type="submit" class="btn-change-password">
                🔒 Alterar Senha e Continuar
            </button>
        </form>
    </div>
    
    <script>
        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const currentPassword = document.getElementById('currentPassword').value;
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            
            // Validações
            if (newPassword.length < 6) {
                alert('❌ A nova senha deve ter no mínimo 6 caracteres!');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                alert('❌ As senhas não coincidem!');
                return;
            }
            
            if (newPassword === 'Mudar@123') {
                alert('❌ Você não pode usar a senha padrão como sua nova senha!');
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Alterando senha...';
            
            try {
                const formData = new FormData();
                formData.append('current_password', currentPassword);
                formData.append('new_password', newPassword);
                
                const response = await fetch('../src/php/change_password_first_login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    window.location.href = 'dashboard.php';
                } else {
                    alert('❌ ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = '🔒 Alterar Senha e Continuar';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao alterar senha. Tente novamente.');
                submitBtn.disabled = false;
                submitBtn.textContent = '🔒 Alterar Senha e Continuar';
            }
        });
    </script>
</body>
</html>
