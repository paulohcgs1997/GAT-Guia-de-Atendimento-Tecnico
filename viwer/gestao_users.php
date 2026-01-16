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

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
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
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

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
                    <input type="text" id="username" name="username" required 
                           placeholder="Ex: joao.silva" autocomplete="off">
                    <small>Será usado para fazer login no sistema</small>
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
            document.getElementById('modalTitle').textContent = 'Novo Usuário';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            
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
            document.getElementById('modalTitle').textContent = 'Editar Usuário';
            document.getElementById('userId').value = user.id;
            document.getElementById('username').value = user.user;
            document.getElementById('perfil').value = user.perfil;
            document.getElementById('departamento').value = user.departamento || '';
            
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
            const password = formData.get('password');
            const confirmPassword = formData.get('confirmPassword');
            const perfil = formData.get('perfil');
            
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
    </script>
</body>
</html>
