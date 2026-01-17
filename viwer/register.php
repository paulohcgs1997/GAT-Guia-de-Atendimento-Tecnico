<?php
// Verifica se o sistema está instalado
$installFlag = __DIR__ . '/../install/.installed';
if (!file_exists($installFlag)) {
    header('Location: ../install/index.php');
    exit;
}

session_start();
if (isset($_SESSION['user_hash_login'])) {
    header('Location: dashboard.php');
    exit;
}

// Buscar configurações do sistema
try {
    require_once(__DIR__ . '/../src/config/conexao.php');
    
    if (!isset($mysqli) || $mysqli === null || $mysqli->connect_errno) {
        throw new Exception('Falha na conexão com o banco de dados');
    }
} catch (Exception $e) {
    error_log('Erro ao conectar no registro: ' . $e->getMessage());
    $systemName = 'Sistema de Gestão';
    $systemDescription = 'Sistema Empresarial';
    $systemLogo = '';
    $systemFavicon = '';
    $mysqli = null;
}

function getConfig($mysqli, $key, $default = '') {
    if ($mysqli === null) return $default;
    
    try {
        $sql = "SELECT config_value FROM system_config WHERE config_key = ?";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) return $default;
        
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return !empty($row['config_value']) ? $row['config_value'] : $default;
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar config: ' . $e->getMessage());
    }
    
    return $default;
}

if (!isset($systemName)) {
    $systemName = getConfig($mysqli, 'system_name', 'Sistema de Gestão');
    $systemDescription = getConfig($mysqli, 'system_description', 'Sistema Empresarial');
    $systemLogo = getConfig($mysqli, 'system_logo', '');
    $systemFavicon = getConfig($mysqli, 'system_favicon', '');
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - <?php echo htmlspecialchars($systemName); ?></title>
    <?php if (!empty($systemFavicon)): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($systemFavicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="../src/css/style.css">
</head>

<body class="login-page">
    <div class="login-container">
        <!-- Logo e Cabeçalho -->
        <div class="logo-section">
            <?php if (!empty($systemLogo)): ?>
                <img src="<?php echo htmlspecialchars($systemLogo); ?>" alt="<?php echo htmlspecialchars($systemName); ?>" style="max-width: 200px; max-height: 100px; margin-bottom: 20px;">
            <?php else: ?>
                <h1><?php echo htmlspecialchars($systemName); ?></h1>
            <?php endif; ?>
            <p><?php echo htmlspecialchars($systemDescription); ?></p>
        </div>
        <h2>Criar Conta</h2>

        <!-- Tela de Loading -->
        <div class="loading-screen" id="loadingScreen">
            <div class="spinner"></div>
            <p>Processando cadastro...</p>
        </div>

        <!-- Conteúdo do Cadastro -->
        <div class="login_content">
            <form id="registerForm">
                <div class="form-group">
                    <label for="user">Usuário *</label>
                    <input type="text" id="user" name="user" required placeholder="Escolha um nome de usuário" minlength="3" maxlength="50">
                    <small class="text-muted" id="usernameHelp">Mínimo 3 caracteres</small>
                    <div id="usernameStatus" style="margin-top: 5px; font-size: 14px; font-weight: 500;"></div>
                </div>
                
                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" required placeholder="seu@email.com">
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha *</label>
                    <input type="password" id="senha" name="senha" required placeholder="Crie uma senha forte" minlength="6">
                    <small class="text-muted">Mínimo 6 caracteres</small>
                </div>
                
                <div class="form-group">
                    <label for="senha_confirm">Confirmar Senha *</label>
                    <input type="password" id="senha_confirm" name="senha_confirm" required placeholder="Digite a senha novamente">
                </div>
                
                <div class="form-group">
                    <label for="nome_completo">Nome Completo</label>
                    <input type="text" id="nome_completo" name="nome_completo" placeholder="Seu nome completo (opcional)">
                </div>
                
                <button type="submit">Criar Conta</button>
            </form>
            
            <!-- Mensagens -->
            <div id="mensagem" style="margin-top: 20px;"></div>
            
            <!-- Link para Login -->
            <div id="loginLink" style="text-align: center; margin-top: 20px;">
                <p class="text-muted">Já tem uma conta? <a href="login.php" style="color: var(--primary-color); font-weight: 600;">Entrar</a></p>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($systemName); ?>. Todos os direitos reservados.</p>
    </footer>

    <!-- Scripts -->
    <script>
        let usernameCheckTimeout = null;
        let isUsernameAvailable = false;
        
        // Verificar disponibilidade do nome de usuário em tempo real
        document.getElementById('user').addEventListener('input', function() {
            const username = this.value.trim();
            const statusDiv = document.getElementById('usernameStatus');
            const submitBtn = document.querySelector('button[type="submit"]');
            
            // Limpar timeout anterior
            clearTimeout(usernameCheckTimeout);
            
            // Resetar status
            statusDiv.innerHTML = '';
            statusDiv.className = '';
            
            if (username.length < 3) {
                isUsernameAvailable = false;
                submitBtn.disabled = false;
                return;
            }
            
            // Mostrar loading
            statusDiv.innerHTML = '<span style="color: #666;">⏳ Verificando...</span>';
            
            // Aguardar 500ms após parar de digitar
            usernameCheckTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`../src/php/check_username.php?username=${encodeURIComponent(username)}`);
                    const data = await response.json();
                    
                    if (data.available) {
                        statusDiv.innerHTML = '<span style="color: #10b981;">✓ Usuário disponível</span>';
                        isUsernameAvailable = true;
                        submitBtn.disabled = false;
                    } else {
                        statusDiv.innerHTML = '<span style="color: #ef4444;">✗ ' + data.message + '</span>';
                        isUsernameAvailable = false;
                        submitBtn.disabled = true;
                    }
                } catch (error) {
                    console.error('Erro ao verificar usuário:', error);
                    statusDiv.innerHTML = '';
                    isUsernameAvailable = false;
                    submitBtn.disabled = false;
                }
            }, 500);
        });
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const senha = document.getElementById('senha').value;
            const senhaConfirm = document.getElementById('senha_confirm').value;
            const mensagemDiv = document.getElementById('mensagem');
            
            // Verificar se o nome de usuário está disponível
            if (!isUsernameAvailable && document.getElementById('user').value.trim().length >= 3) {
                mensagemDiv.innerHTML = '<div class="alert alert-danger">Este nome de usuário já está em uso!</div>';
                return;
            }
            
            // Validar senhas
            if (senha !== senhaConfirm) {
                mensagemDiv.innerHTML = '<div class="alert alert-danger">As senhas não coincidem!</div>';
                return;
            }
            
            if (senha.length < 6) {
                mensagemDiv.innerHTML = '<div class="alert alert-danger">A senha deve ter no mínimo 6 caracteres!</div>';
                return;
            }

            const formData = new FormData(this);
            const loginContainer = document.querySelector('.login_content');
            const loadingScreen = document.getElementById('loadingScreen');
            
            // Debug - mostrar dados que serão enviados
            console.log('Dados do formulário:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ': ' + pair[1]);
            }

            // Esconder o formulário e mostrar loading
            loginContainer.style.display = 'none';
            loadingScreen.classList.add('active');

            // Enviar requisição de cadastro
            fetch('../src/php/register_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Response text:', text);
                
                loginContainer.style.display = 'block';
                loadingScreen.classList.remove('active');
                
                // Tentar fazer parse do JSON
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    console.error('Texto recebido:', text);
                    mensagemDiv.innerHTML = `
                        <div style="padding: 20px; margin: 20px 0; border-radius: 8px; background: #fee2e2; border: 2px solid #ef4444; color: #991b1b;">
                            <strong>❌ Erro:</strong> Resposta inválida do servidor.<br>
                            <small>Verifique o console para mais detalhes.</small>
                        </div>
                    `;
                    return;
                }
                
                console.log('Data recebida:', data);
                
                if (data.success) {
                    // Esconder o formulário e o link de login
                    document.getElementById('registerForm').style.display = 'none';
                    document.getElementById('loginLink').style.display = 'none';
                    
                    // Mostrar mensagem de sucesso grande e visível (substituindo o conteúdo)
                    mensagemDiv.innerHTML = `
                        <div style="padding: 30px; margin: 20px 0; border-radius: 12px; background: #d1fae5; border: 3px solid #10b981; text-align: center; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);">
                            <div style="font-size: 64px; margin-bottom: 15px; animation: bounce 1s;">✓</div>
                            <h3 style="color: #065f46; margin: 10px 0; font-size: 24px; font-weight: bold;">Cadastro Realizado com Sucesso!</h3>
                            <p style="color: #047857; font-size: 16px; margin: 15px 0; line-height: 1.6;">
                                ${data.message || 'Sua conta será ativada após aprovação do administrador.'}
                            </p>
                            <a href="login.php" style="margin-top: 20px; padding: 14px 35px; font-size: 16px; text-decoration: none; display: inline-block; background: #1a365d; color: white; border-radius: 8px; font-weight: 600; transition: all 0.3s; box-shadow: 0 2px 8px rgba(26, 54, 93, 0.3);">
                                Ir para Login →
                            </a>
                        </div>
                    `;
                    mensagemDiv.style.display = 'block';
                    loginContainer.style.display = 'block';
                    
                    // Scroll suave para a mensagem
                    setTimeout(() => {
                        mensagemDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                } else {
                    mensagemDiv.innerHTML = `
                        <div style="padding: 20px; margin: 20px 0; border-radius: 8px; background: #fee2e2; border: 2px solid #ef4444; color: #991b1b;">
                            <strong style="font-size: 18px;">❌ Erro no Cadastro</strong><br>
                            <p style="margin: 10px 0;">${data.erro || data.error || 'Erro desconhecido'}</p>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erro no cadastro:', error);
                loginContainer.style.display = 'block';
                loadingScreen.classList.remove('active');
                mensagemDiv.innerHTML = `
                    <div class="alert alert-danger" style="padding: 15px; margin: 15px 0; border-radius: 6px; background: #fee2e2; border: 2px solid #ef4444; color: #991b1b;">
                        <strong>❌ Erro ao processar cadastro:</strong><br>${error.message}
                    </div>
                `;
            });
        });
    </script>
</body>

</html>
