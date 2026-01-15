<?php
// Buscar configurações do sistema
try {
    require_once(__DIR__ . '/../config/conexao.php');
} catch (Exception $e) {
    error_log('Erro ao carregar conexão no header: ' . $e->getMessage());
    $mysqli = null;
}

function getConfig($mysqli, $key, $default = '') {
    if ($mysqli === null || !($mysqli instanceof mysqli)) {
        return $default;
    }
    
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
        error_log('Erro ao buscar config no header: ' . $e->getMessage());
    }
    
    return $default;
}

$systemName = getConfig($mysqli ?? null, 'system_name', 'SISTEMA');
$systemLogo = getConfig($mysqli ?? null, 'system_logo', '');
$systemFavicon = getConfig($mysqli ?? null, 'system_favicon', '');
?>

<header>
    <div class="logo">
        <?php if (!empty($systemLogo)): ?>
            <img src="<?php echo htmlspecialchars($systemLogo); ?>" alt="<?php echo htmlspecialchars($systemName); ?>" style="max-height: 50px;">
        <?php else: ?>
            <?php echo htmlspecialchars($systemName); ?>
        <?php endif; ?>
    </div>
    <div class="search-container">
        <input type="text" id="searchInput" placeholder="Buscar serviços..." autocomplete="off">
        <div class="search-loading">
            <div class="spinner"></div>
        </div>
        <div id="searchResults"></div>
        <script src="../src/js/serach.js"></script>
    </div>
    <nav>
        <ul>
            <li><a href="dashboard.php">Início</a></li>
            <?php if (isset($_SESSION['perfil'])): ?>
                <?php if ($_SESSION['perfil'] == '1' || $_SESSION['perfil'] == '2'): ?>
                    <li><a href="gestao.php">Gestão</a></li>
                <?php endif; ?>
                
                <?php if ($_SESSION['perfil'] == '1' || $_SESSION['perfil'] == '3'): ?>
                    <li><a href="aprovacoes.php">Aprovações</a></li>
                <?php endif; ?>
            <?php endif; ?>
            <li class="user-menu-container">
                <button class="user-menu-btn" onclick="toggleUserMenu(event)">
                    <span class="user-icon">👤</span>
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></span>
                    <span class="dropdown-arrow">▼</span>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <strong><?php echo htmlspecialchars($_SESSION['nome'] ?? 'Usuário'); ?></strong>
                        <small><?php echo htmlspecialchars($_SESSION['user'] ?? ''); ?></small>
                    </div>
                    <div class="user-dropdown-divider"></div>
                    <a href="#" class="user-dropdown-item" onclick="openChangePasswordModal(); return false;">
                        <span>🔑</span> Alterar Senha
                    </a>
                    <a href="logout.php" class="user-dropdown-item logout">
                        <span>🚪</span> Sair
                    </a>
                </div>
            </li>
        </ul>
    </nav>
</header>

<!-- Modal de Alteração de Senha -->
<div class="modal-overlay" id="passwordModal">
    <div class="modal-content-small">
        <div class="modal-header">
            <h3>🔑 Alterar Senha</h3>
            <button class="modal-close" onclick="closePasswordModal()">&times;</button>
        </div>
        <form id="changePasswordForm">
            <div class="form-group">
                <label for="current_password">Senha Atual *</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            <div class="form-group">
                <label for="new_password">Nova Senha *</label>
                <input type="password" id="new_password" name="new_password" required minlength="6">
                <small>Mínimo de 6 caracteres</small>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirmar Nova Senha *</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closePasswordModal()">Cancelar</button>
                <button type="submit" class="btn-primary">💾 Salvar Senha</button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle do menu de usuário
function toggleUserMenu(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('userDropdown');
    dropdown.classList.toggle('show');
}

// Fechar dropdown ao clicar fora
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const userMenu = document.querySelector('.user-menu-container');
    
    if (!userMenu.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

// Abrir modal de senha
function openChangePasswordModal() {
    document.getElementById('passwordModal').style.display = 'flex';
    document.getElementById('userDropdown').classList.remove('show');
    document.getElementById('changePasswordForm').reset();
}

// Fechar modal de senha
function closePasswordModal() {
    document.getElementById('passwordModal').style.display = 'none';
}

// Processar mudança de senha
document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    // Validar senhas
    if (newPassword !== confirmPassword) {
        alert('❌ As senhas não coincidem!');
        return;
    }
    
    if (newPassword.length < 6) {
        alert('❌ A senha deve ter no mínimo 6 caracteres!');
        return;
    }
    
    // Desabilitar botão durante o envio
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Salvando...';
    
    try {
        const formData = new FormData();
        formData.append('action', 'change_password');
        formData.append('current_password', currentPassword);
        formData.append('new_password', newPassword);
        
        const response = await fetch('../src/php/user_actions.php', {
            method: 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('Erro na resposta do servidor');
        }
        
        const data = await response.json();
        
        if (data.success) {
            alert('✅ ' + data.message);
            closePasswordModal();
        } else {
            alert('❌ ' + data.message);
        }
    } catch (error) {
        console.error('Erro ao alterar senha:', error);
        alert('❌ Erro ao alterar senha. Verifique sua conexão e tente novamente.');
    } finally {
        // Reabilitar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});
</script>