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
    
    // Verificar se a conexão foi estabelecida
    if (!isset($mysqli) || $mysqli === null || $mysqli->connect_errno) {
        throw new Exception('Falha na conexão com o banco de dados');
    }
} catch (Exception $e) {
    // Se falhar, usar valores padrão e não mostrar erro fatal na tela de login
    error_log('Erro ao conectar no login: ' . $e->getMessage());
    $systemName = 'Sistema de Gestão';
    $systemDescription = 'Sistema Empresarial';
    $systemLogo = '';
    $systemFavicon = '';
    $mysqli = null; // Marca como null para não tentar usar depois
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
    <title>Login - <?php echo htmlspecialchars($systemName); ?></title>
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
        <h2>Acesso ao Sistema</h2>

        <!-- Tela de Loading -->
        <div class="loading-screen" id="loadingScreen">
            <div class="spinner"></div>
            <p>Carregando...</p>
        </div>

        <!-- Conteúdo do Login -->
        <div class="login_content">
            <form id="loginForm">
                <div class="form-group">
                    <label for="user">Usuário</label>
                    <input type="text" id="user" name="user" required placeholder="Digite seu usuário">
                </div>
                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input type="password" id="senha" name="senha" required placeholder="Digite sua senha">
                </div>
                <button type="submit">Entrar no Sistema</button>
            </form>
            
            <!-- Mensagens de Erro -->
            <div id="mensagem"></div>
            
            <!-- Link para Cadastro -->
            <div style="text-align: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                <p class="text-muted">Não tem uma conta? <a href="register.php" style="color: var(--primary-color); font-weight: 600; text-decoration: none;">Cadastre-se aqui</a></p>
            </div>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($systemName); ?>. Todos os direitos reservados.</p>
    </footer>

    <!-- Scripts -->
    <script>
        // Manipulação do Formulário de Login
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const loginContainer = document.querySelector('.login_content');
            const loadingScreen = document.getElementById('loadingScreen');
            const mensagemDiv = document.getElementById('mensagem');

            // Esconder o formulário e mostrar loading
            loginContainer.style.display = 'none';
            loadingScreen.classList.add('active');

            // Enviar requisição de login
            fetch('../src/php/login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Verificar se precisa trocar senha
                    if (data.force_password_change) {
                        window.location.href = 'change_password.php';
                    } else {
                        // Redirecionar para o dashboard em caso de sucesso
                        window.location.href = 'dashboard.php';
                    }
                } else {
                    // Mostrar formulário novamente em caso de erro
                    loginContainer.style.display = 'block';
                    loadingScreen.classList.remove('active');
                    mensagemDiv.innerHTML = data.erro;
                }
            })
            .catch(error => {
                // Tratar erros de conexão ou outros erros
                console.error('Erro no login:', error);
                loginContainer.style.display = 'block';
                loadingScreen.classList.remove('active');
                mensagemDiv.innerHTML = 'Erro ao processar login. Tente novamente.';
            });
        });
    </script>
</body>

</html>