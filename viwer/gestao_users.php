<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_admin();

include_once(__DIR__ . '/../src/config/conexao.php');

// Buscar perfis
$perfis_query = "SELECT * FROM perfil ORDER BY id";
$perfis_result = $mysqli->query($perfis_query);
$perfis_list = [];
while($perfil = $perfis_result->fetch_assoc()) {
    $perfis_list[] = $perfil;
}

// Buscar usuários com perfil (todos, ativos e inativos)
$users_query = "SELECT u.*, p.type as perfil_type, d.name as dept_name 
                FROM usuarios u 
                LEFT JOIN perfil p ON u.perfil = p.id 
                LEFT JOIN departaments d ON u.departamento = d.id
                ORDER BY u.active DESC, u.last_login DESC";
$users = $mysqli->query($users_query);

// Verificar se existe coluna 'status' na tabela usuarios
$sql_check_status = "SHOW COLUMNS FROM usuarios LIKE 'status'";
$result_check_status = $mysqli->query($sql_check_status);
$has_status_column = ($result_check_status->num_rows > 0);

// Se existe coluna status, buscar usuários pendentes
$pending_users = null;
if ($has_status_column) {
    $pending_query = "SELECT u.*, p.type as perfil_type 
                      FROM usuarios u 
                      LEFT JOIN perfil p ON u.perfil = p.id 
                      WHERE u.status = 'pending'
                      ORDER BY u.id DESC";
    $pending_users = $mysqli->query($pending_query);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once PROJECT_ROOT . '/src/includes/head_config.php'; ?>
    <link rel="stylesheet" href="../src/css/style.css">
</head>
<body>
    <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>

    <?php include_once __DIR__ . '/includes/quick_menu.php'; ?>

    <main>
        <div class="gestao-container">
            <div class="page-header">
                <h1>👥 Gestão de Usuários</h1>
                <button class="btn-primary" onclick="openModal()">+ Novo Usuário</button>
            </div>

            <?php if ($has_status_column && $pending_users && $pending_users->num_rows > 0): ?>
            <!-- Seção de Usuários Pendentes -->
            <div class="alert alert-warning" style="margin-bottom: 20px;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>⏳ <?= $pending_users->num_rows ?> usuário(s) aguardando aprovação</strong>
                    </div>
                    <button class="btn btn-success" onclick="approveAllSelected()">
                        <i class="bi bi-check-circle"></i> Aprovar Selecionados
                    </button>
                </div>
            </div>

            <div class="card mb-4" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
                <div class="card-header" style="background: #fbbf24; color: #78350f; font-weight: 600;">
                    ⏳ Usuários Pendentes de Aprovação
                </div>
                <div class="card-body" style="padding: 0;">
                    <table class="data-table" style="margin: 0;">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="selectAllPending" onchange="toggleAllPending(this)">
                                </th>
                                <th>ID</th>
                                <th>Usuário</th>
                                <th>E-mail</th>
                                <th>Nome Completo</th>
                                <th>Data de Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $pending_users->data_seek(0); // Reset pointer
                            while($user = $pending_users->fetch_assoc()): 
                            ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="pending-checkbox" value="<?= $user['id'] ?>">
                                </td>
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['user']) ?></td>
                                <td><?= isset($user['email']) ? htmlspecialchars($user['email']) : '-' ?></td>
                                <td><?= isset($user['nome_completo']) && !empty($user['nome_completo']) ? htmlspecialchars($user['nome_completo']) : '-' ?></td>
                                <td><?= isset($user['created_at']) ? date('d/m/Y H:i', strtotime($user['created_at'])) : '-' ?></td>
                                <td class="actions-cell">
                                    <button class="btn-icon btn-approve" onclick="approveUser(<?= $user['id'] ?>)" title="Aprovar">✓</button>
                                    <button class="btn-icon btn-delete" onclick="rejectUser(<?= $user['id'] ?>)" title="Rejeitar">✗</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Barra de Ações em Lote -->
            <div id="batchActionsBar" style="display: none; background: #3b82f6; color: white; padding: 15px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <strong id="selectedCount" style="font-size: 1.1em;">0 usuários selecionados</strong>
                        <button onclick="clearSelection()" style="background: rgba(255,255,255,0.2); border: none; color: white; padding: 5px 10px; border-radius: 4px; cursor: pointer;">✕ Limpar seleção</button>
                    </div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button onclick="openBatchEditModal()" class="btn-primary" style="background: white; color: #3b82f6;">✏️ Editar em Lote</button>
                        <button onclick="batchActivate()" class="btn-approve" style="background: #10b981;">✓ Ativar Selecionados</button>
                        <button onclick="batchDeactivate()" class="btn-delete" style="background: #f59e0b;">🚫 Desativar Selecionados</button>
                    </div>
                </div>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAllUsers" onchange="toggleAllUsers(this)">
                            </th>
                            <th>ID</th>
                            <th>Usuário</th>
                            <th>Perfil</th>
                            <th>Departamento</th>
                            <th>Status</th>
                            <th>Último Login</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <input type="checkbox" class="user-checkbox" value="<?= $user['id'] ?>" onchange="updateBatchActions()">
                                <?php else: ?>
                                    <span style="color: #999; font-size: 0.9em;" title="Você não pode selecionar seu próprio usuário">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['user']) ?></td>
                            <td>
                                <span class="perfil-badge perfil-<?= $user['perfil'] ?>">
                                    <?= ucfirst($user['perfil_type']) ?>
                                </span>
                            </td>
                            <td><?= $user['dept_name'] ? htmlspecialchars($user['dept_name']) : '<span style="color: #999;">-</span>' ?></td>
                            <td>
                                <span class="status-badge <?= $user['active'] ? 'approved' : 'inactive' ?>">
                                    <?= $user['active'] ? '✓ Ativo' : '⏸ Inativo' ?>
                                </span>
                            </td>
                            <td><?= $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : 'Nunca' ?></td>
                            <td class="actions-cell">
                                <button class="btn-icon btn-edit" onclick='editUser(<?= json_encode($user) ?>)' title="Editar">✏️</button>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <?php if ($user['active']): ?>
                                        <button class="btn-icon btn-delete" onclick="toggleUserStatus(<?= $user['id'] ?>, 0)" title="Desativar">🚫</button>
                                    <?php else: ?>
                                        <button class="btn-icon btn-approve" onclick="toggleUserStatus(<?= $user['id'] ?>, 1)" title="Reativar" style="background: #10b981; color: white;">✓</button>
                                    <?php endif; ?>
                                    <button class="btn-icon" onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['user'], ENT_QUOTES) ?>')" title="Excluir Permanentemente" style="background: #dc2626; color: white;">🗑️</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Edição em Lote -->
    <div class="modal-overlay" id="batchEditModal">
        <div class="modal-medium">
            <div class="modal-header" style="background: #3b82f6; color: white;">
                <h2>✏️ Edição em Lote</h2>
                <button class="btn-close" onclick="closeBatchEditModal()" style="color: white; opacity: 1;">×</button>
            </div>
            
            <form id="batchEditForm" onsubmit="saveBatchEdit(event)">
                <div class="alert alert-info" style="background: #dbeafe; border-left: 4px solid #3b82f6; margin-bottom: 20px;">
                    <strong>ℹ️ Edição em Lote:</strong> As alterações serão aplicadas a <strong id="batchEditCount">0</strong> usuário(s) selecionado(s).
                    <br><small>Campos deixados em branco não serão alterados.</small>
                </div>
                
                <div class="form-group">
                    <label for="batchPerfil">Alterar Perfil</label>
                    <select id="batchPerfil" name="perfil">
                        <option value="">-- Não alterar --</option>
                        <?php foreach($perfis_list as $perfil): ?>
                        <option value="<?= $perfil['id'] ?>">
                            <?= ucfirst($perfil['type']) ?> - <?= $perfil['permission'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="batchDepartamento">Alterar Departamento</label>
                    <select id="batchDepartamento" name="departamento">
                        <option value="">-- Não alterar --</option>
                        <option value="NULL">🗑️ Remover departamento</option>
                        <?php 
                        $dept_query2 = "SELECT id, name FROM departaments ORDER BY name";
                        $depts2 = $mysqli->query($dept_query2);
                        while($dept = $depts2->fetch_assoc()): 
                        ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="batchStatus">Alterar Status</label>
                    <select id="batchStatus" name="status">
                        <option value="">-- Não alterar --</option>
                        <option value="1">✓ Ativar</option>
                        <option value="0">🚫 Desativar</option>
                    </select>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">💾 Salvar Alterações</button>
                    <button type="button" class="btn-secondary" onclick="closeBatchEditModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Cadastro/Edição -->
    <div class="modal-overlay" id="userModal">
        <div class="modal-medium">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Usuário</h2>
                <button class="btn-close" onclick="closeModal()">×</button>
            </div>
            
            <form id="userForm" onsubmit="saveUser(event)">
                <input type="hidden" id="userId" name="id">
                
                <div class="form-group">
                    <label for="username">Nome de Usuário *</label>
                    <div style="position: relative;">
                        <input type="text" id="username" name="username" required 
                               placeholder="Ex: joao.silva" autocomplete="off" 
                               oninput="checkUsernameAvailability(this.value)">
                        <span id="usernameStatus" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 1.2em;"></span>
                    </div>
                    <small id="usernameHelp">Será usado para fazer login no sistema</small>
                </div>
                
                <div class="form-group" id="passwordGroup" style="display: none;">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Digite a senha" autocomplete="new-password">
                    <small id="passwordHelp">Deixe em branco para manter a senha atual</small>
                </div>
                
                <div class="form-group" id="confirmPasswordGroup" style="display: none;">
                    <label for="confirmPassword">Confirmar Senha</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" 
                           placeholder="Digite a senha novamente">
                </div>

                <div class="alert alert-info" id="defaultPasswordAlert" style="display: block; margin: 15px 0; padding: 12px; background: #dbeafe; border-left: 4px solid #3b82f6; border-radius: 4px;">
                    <strong>ℹ️ Senha Padrão:</strong> O usuário será criado com a senha padrão: <code style="background: #1e40af; color: white; padding: 2px 6px; border-radius: 3px; font-weight: bold;">Mudar@123</code>
                    <br><small style="color: #1e40af;">O usuário será <strong>obrigado a trocar</strong> a senha no primeiro login.</small>
                </div>
                
                <div class="form-group">
                    <label for="perfil">Perfil *</label>
                    <select id="perfil" name="perfil" required onchange="toggleDepartamentoField()">
                        <option value="">Selecione um perfil</option>
                        <?php foreach($perfis_list as $perfil): ?>
                        <option value="<?= $perfil['id'] ?>">
                            <?= ucfirst($perfil['type']) ?> - <?= $perfil['permission'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <small>
                        <strong>Admin:</strong> Acesso total<br>
                        <strong>Criador:</strong> Pode ver e editar<br>
                        <strong>Departamento:</strong> Pode ver e aprovar<br>
                        <strong>Colaborador:</strong> Apenas visualização
                    </small>
                </div>
                
                <div class="form-group" id="departamentoGroup" style="display: none;">
                    <label for="departamento">Departamento *</label>
                    <select id="departamento" name="departamento">
                        <option value="">Selecione um departamento</option>
                        <?php 
                        $dept_query = "SELECT id, name FROM departaments ORDER BY name";
                        $depts = $mysqli->query($dept_query);
                        while($dept = $depts->fetch_assoc()): 
                        ?>
                        <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <small>Obrigatório para usuários com perfil Departamento</small>
                </div>
                
                <div class="modal-footer">
                    <button type="submit" class="btn-primary">Salvar</button>
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <script>
        let isEditMode = false;
        let isUsernameAvailable = true;
        let usernameCheckTimeout = null;
        let originalUsername = '';
        
        // Verificar disponibilidade do nome de usuário
        async function checkUsernameAvailability(username) {
            const statusElement = document.getElementById('usernameStatus');
            const helpElement = document.getElementById('usernameHelp');
            
            // Limpar timeout anterior
            if (usernameCheckTimeout) {
                clearTimeout(usernameCheckTimeout);
            }
            
            // Se está vazio ou é o username original (em edição), não verificar
            if (!username || username.length < 3) {
                statusElement.textContent = '';
                helpElement.textContent = 'Será usado para fazer login no sistema';
                helpElement.style.color = '';
                isUsernameAvailable = false;
                return;
            }
            
            // Se está editando e é o username original, permitir
            if (isEditMode && username === originalUsername) {
                statusElement.textContent = '✓';
                statusElement.style.color = '#10b981';
                helpElement.textContent = 'Username atual (não será alterado)';
                helpElement.style.color = '#6b7280';
                isUsernameAvailable = true;
                return;
            }
            
            // Mostrar loading
            statusElement.textContent = '⏳';
            statusElement.style.color = '#6b7280';
            helpElement.textContent = 'Verificando disponibilidade...';
            helpElement.style.color = '#6b7280';
            
            // Aguardar 500ms antes de fazer a requisição
            usernameCheckTimeout = setTimeout(async () => {
                try {
                    const response = await fetch('../src/php/crud_users.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=check_username&username=${encodeURIComponent(username)}`
                    });
                    
                    const data = await response.json();
                    
                    if (data.available) {
                        statusElement.textContent = '✓';
                        statusElement.style.color = '#10b981';
                        helpElement.textContent = 'Nome de usuário disponível';
                        helpElement.style.color = '#10b981';
                        isUsernameAvailable = true;
                    } else {
                        statusElement.textContent = '✗';
                        statusElement.style.color = '#ef4444';
                        helpElement.textContent = 'Este nome de usuário já está em uso';
                        helpElement.style.color = '#ef4444';
                        isUsernameAvailable = false;
                    }
                } catch (error) {
                    console.error('Erro ao verificar username:', error);
                    statusElement.textContent = '⚠️';
                    statusElement.style.color = '#f59e0b';
                    helpElement.textContent = 'Erro ao verificar disponibilidade';
                    helpElement.style.color = '#f59e0b';
                    isUsernameAvailable = false;
                }
            }, 500);
        }
        
        // Funções de seleção em lote
        function toggleAllUsers(checkbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
            updateBatchActions();
        }
        
        function updateBatchActions() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const count = checkboxes.length;
            const batchBar = document.getElementById('batchActionsBar');
            const selectedCount = document.getElementById('selectedCount');
            
            if (count > 0) {
                batchBar.style.display = 'block';
                selectedCount.textContent = `${count} usuário${count > 1 ? 's' : ''} selecionado${count > 1 ? 's' : ''}`;
            } else {
                batchBar.style.display = 'none';
                document.getElementById('selectAllUsers').checked = false;
            }
        }
        
        function clearSelection() {
            document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllUsers').checked = false;
            updateBatchActions();
        }
        
        function getSelectedUserIds() {
            return Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
        }
        
        function openBatchEditModal() {
            const userIds = getSelectedUserIds();
            if (userIds.length === 0) {
                alert('⚠️ Selecione ao menos um usuário');
                return;
            }
            
            document.getElementById('batchEditCount').textContent = userIds.length;
            document.getElementById('batchEditModal').classList.add('active');
        }
        
        function closeBatchEditModal() {
            document.getElementById('batchEditModal').classList.remove('active');
            document.getElementById('batchEditForm').reset();
        }
        
        async function saveBatchEdit(event) {
            event.preventDefault();
            
            const userIds = getSelectedUserIds();
            if (userIds.length === 0) {
                alert('⚠️ Nenhum usuário selecionado');
                return;
            }
            
            const formData = new FormData(event.target);
            const perfil = formData.get('perfil');
            const departamento = formData.get('departamento');
            const status = formData.get('status');
            
            // Verificar se ao menos um campo foi preenchido
            if (!perfil && !departamento && !status) {
                alert('⚠️ Selecione ao menos uma alteração a ser aplicada');
                return;
            }
            
            if (!confirm(`Confirma a edição em lote de ${userIds.length} usuário(s)?`)) {
                return;
            }
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Processando...';
            
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=batch_edit&user_ids=${JSON.stringify(userIds)}&perfil=${perfil}&departamento=${departamento}&status=${status}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + (data.message || 'Erro ao editar usuários'));
                    submitBtn.disabled = false;
                    submitBtn.textContent = '💾 Salvar Alterações';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao processar edição em lote');
                submitBtn.disabled = false;
                submitBtn.textContent = '💾 Salvar Alterações';
            }
        }
        
        async function batchActivate() {
            const userIds = getSelectedUserIds();
            if (userIds.length === 0) {
                alert('⚠️ Selecione ao menos um usuário');
                return;
            }
            
            if (!confirm(`Deseja ATIVAR ${userIds.length} usuário(s)?`)) return;
            
            await processBatchStatusChange(userIds, 1, 'ativar');
        }
        
        async function batchDeactivate() {
            const userIds = getSelectedUserIds();
            if (userIds.length === 0) {
                alert('⚠️ Selecione ao menos um usuário');
                return;
            }
            
            if (!confirm(`Deseja DESATIVAR ${userIds.length} usuário(s)?`)) return;
            
            await processBatchStatusChange(userIds, 0, 'desativar');
        }
        
        async function processBatchStatusChange(userIds, status, action) {
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=batch_status&user_ids=${JSON.stringify(userIds)}&status=${status}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + (data.message || `Erro ao ${action} usuários`));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert(`❌ Erro ao ${action} usuários em lote`);
            }
        }
        
        // Função para alternar seleção de todos os usuários pendentes
        function toggleAllPending(checkbox) {
            const checkboxes = document.querySelectorAll('.pending-checkbox');
            checkboxes.forEach(cb => cb.checked = checkbox.checked);
        }
        
        // Função para aprovar usuários selecionados em lote
        async function approveAllSelected() {
            const checkboxes = document.querySelectorAll('.pending-checkbox:checked');
            const userIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (userIds.length === 0) {
                alert('⚠️ Selecione ao menos um usuário para aprovar');
                return;
            }
            
            if (!confirm(`Deseja aprovar ${userIds.length} usuário(s)?`)) {
                return;
            }
            
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=approve_batch&user_ids=${JSON.stringify(userIds)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    location.reload();
                } else {
                    alert('❌ ' + (data.erro || 'Erro ao aprovar usuários'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao processar aprovação em lote');
            }
        }
        
        // Função para aprovar um usuário individual
        async function approveUser(userId) {
            if (!confirm('Deseja aprovar este usuário?')) return;
            
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=approve&id=${userId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Usuário aprovado com sucesso!');
                    location.reload();
                } else {
                    alert('❌ ' + (data.erro || 'Erro ao aprovar usuário'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao processar aprovação');
            }
        }
        
        // Função para rejeitar um usuário
        async function rejectUser(userId) {
            const motivo = prompt('Motivo da rejeição (opcional):');
            if (motivo === null) return; // Cancelou
            
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=reject&id=${userId}&motivo=${encodeURIComponent(motivo)}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ Usuário rejeitado');
                    location.reload();
                } else {
                    alert('❌ ' + (data.erro || 'Erro ao rejeitar usuário'));
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao processar rejeição');
            }
        }
        
        function toggleDepartamentoField() {
            const perfil = document.getElementById('perfil').value;
            const deptGroup = document.getElementById('departamentoGroup');
            const deptSelect = document.getElementById('departamento');
            
            // Perfil 3 = Departamento
            if (perfil == '3') {
                deptGroup.style.display = 'block';
                deptSelect.required = true;
            } else {
                deptGroup.style.display = 'none';
                deptSelect.required = false;
                deptSelect.value = '';
            }
        }
        
        function openModal() {
            isEditMode = false;
            originalUsername = '';
            isUsernameAvailable = false;
            document.getElementById('modalTitle').textContent = 'Novo Usuário';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            
            // Limpar verificação de username
            document.getElementById('usernameStatus').textContent = '';
            document.getElementById('usernameHelp').textContent = 'Será usado para fazer login no sistema';
            document.getElementById('usernameHelp').style.color = '';
            
            // Ocultar campos de senha para novo usuário (senha padrão será usada)
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('confirmPasswordGroup').style.display = 'none';
            document.getElementById('defaultPasswordAlert').style.display = 'block';
            document.getElementById('password').required = false;
            document.getElementById('confirmPassword').required = false;
            
            document.getElementById('departamentoGroup').style.display = 'none';
            document.getElementById('departamento').required = false;
            document.getElementById('userModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }
        
        function editUser(user) {
            isEditMode = true;
            originalUsername = user.user;
            isUsernameAvailable = true;
            document.getElementById('modalTitle').textContent = 'Editar Usuário';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.user;
            document.getElementById('perfil').value = user.perfil;
            document.getElementById('departamento').value = user.departamento || '';
            
            // Configurar status do username para edição
            document.getElementById('usernameStatus').textContent = '✓';
            document.getElementById('usernameStatus').style.color = '#10b981';
            document.getElementById('usernameHelp').textContent = 'Username atual';
            document.getElementById('usernameHelp').style.color = '#6b7280';
            
            // Mostrar campos de senha para edição (opcional)
            document.getElementById('passwordGroup').style.display = 'block';
            document.getElementById('confirmPasswordGroup').style.display = 'block';
            document.getElementById('defaultPasswordAlert').style.display = 'none';
            document.getElementById('password').value = '';
            document.getElementById('confirmPassword').value = '';
            document.getElementById('password').required = false;
            document.getElementById('confirmPassword').required = false;
            document.getElementById('passwordHelp').textContent = 'Deixe em branco para manter a senha atual';
            
            toggleDepartamentoField();
            document.getElementById('userModal').classList.add('active');
        }
        
        async function saveUser(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Salvando...';
            
            const formData = new FormData(event.target);
            const username = formData.get('username');
            const password = formData.get('password');
            const confirmPassword = formData.get('confirmPassword');
            const perfil = formData.get('perfil');
            
            // Validar disponibilidade do username (só para novo usuário ou se mudou)
            if (!isEditMode || username !== originalUsername) {
                if (!isUsernameAvailable) {
                    alert('❌ Escolha um nome de usuário diferente. Este já está em uso.');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar';
                    return;
                }
            }
            
            // Validar senha apenas se foi informada
            if (password) {
                if (password !== confirmPassword) {
                    alert('❌ As senhas não coincidem!');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar';
                    return;
                }
                
                if (password.length < 6) {
                    alert('❌ A senha deve ter no mínimo 6 caracteres!');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar';
                    return;
                }
            }
            
            // Validar departamento obrigatório para perfil 3
            if (perfil == '3' && !formData.get('departamento')) {
                alert('❌ Selecione um departamento para o perfil Departamento!');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Salvar';
                return;
            }
            
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✓ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Salvar';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao salvar usuário');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Salvar';
            }
        }
        
        async function toggleUserStatus(id, newStatus) {
            const action = newStatus ? 'reativar' : 'desativar';
            const message = newStatus ? 
                'Tem certeza que deseja reativar este usuário? Ele poderá fazer login novamente.' : 
                'Tem certeza que deseja desativar este usuário? Ele não poderá mais fazer login.';
            
            if (!confirm(message)) return;
            
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=toggle_status&id=' + id + '&status=' + newStatus
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✓ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao ' + action + ' usuário');
            }
        }
        
        async function deleteUser(id, username) {
            const confirmText = `⚠️ ATENÇÃO: Esta ação é IRREVERSÍVEL!\n\nDeseja realmente EXCLUIR PERMANENTEMENTE o usuário "${username}"?\n\nTodos os dados associados serão perdidos.`;
            
            if (!confirm(confirmText)) {
                return;
            }
            
            try {
                const response = await fetch('../src/php/crud_users.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete_permanent&id=' + id
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✓ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao excluir usuário');
            }
        }
    </script>
</body>
</html>
