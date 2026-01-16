<?php
include_once("includes.php");
check_login();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once PROJECT_ROOT . '/src/includes/head_config.php'; ?>
    <link rel="stylesheet" href="../src/css/style.css">
    <title>Meu Perfil</title>
</head>

<body>
    <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <h1>👤 Meu Perfil</h1>
            <p>Gerencie suas informações pessoais</p>
        </div>

        <div id="alertMessage" class="alert"></div>

        <div class="profile-content">
            <!-- Seção de Foto -->
            <div class="profile-photo-section">
                <div class="profile-photo-container">
                    <div class="profile-photo" id="profilePhotoPreview">
                        👤
                    </div>
                </div>
                <div class="photo-buttons">
                    <button class="btn-upload" onclick="document.getElementById('photoInput').click()">
                        📷 Alterar Foto
                    </button>
                    <button class="btn-remove" id="removePhotoBtn" style="display: none;" onclick="removeFoto()">
                        🗑️ Remover
                    </button>
                </div>
                <input type="file" id="photoInput" accept="image/*" onchange="uploadFoto()">
            </div>

            <!-- Formulário de Dados -->
            <div class="profile-form">
                <form id="profileForm">
                <div class="form-group">
                    <label for="nome_completo">Nome Completo *</label>
                    <input type="text" id="nome_completo" name="nome_completo" required>
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                    <small>Será usado para notificações e recuperação de senha</small>
                </div>

                <div class="form-group">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" placeholder="(00) 00000-0000">
                </div>

                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" disabled>
                    <small>O nome de usuário não pode ser alterado</small>
                </div>

                <div class="password-section">
                    <h3>🔒 Alterar Senha</h3>
                    
                    <div class="form-group">
                        <label for="password">Nova Senha</label>
                        <input type="password" id="password" name="password">
                        <small>Deixe em branco para manter a senha atual</small>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirmar Nova Senha</label>
                        <input type="password" id="password_confirm">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="loadProfile()">
                        ↻ Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💾 Salvar Alterações
                    </button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <footer>
        <p>Sistema de Tutoriais - GAT</p>
    </footer>

    <script>
        // Carregar dados do perfil
        function loadProfile() {
            fetch('../src/php/user_profile.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('nome_completo').value = user.nome_completo || '';
                    document.getElementById('email').value = user.email || '';
                    document.getElementById('telefone').value = user.telefone || '';
                    document.getElementById('username').value = user.user || '';
                    
                    // Foto
                    const photoPreview = document.getElementById('profilePhotoPreview');
                    const removeBtn = document.getElementById('removePhotoBtn');
                    
                    if (user.foto) {
                        photoPreview.innerHTML = `<img src="../${user.foto}" alt="Foto de perfil">`;
                        removeBtn.style.display = 'inline-block';
                    } else {
                        photoPreview.innerHTML = '👤';
                        removeBtn.style.display = 'none';
                    }
                    
                    // Limpar senhas
                    document.getElementById('password').value = '';
                    document.getElementById('password_confirm').value = '';
                }
            })
            .catch(error => console.error('Erro ao carregar perfil:', error));
        }

        // Salvar perfil
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = document.getElementById('password').value;
            const password_confirm = document.getElementById('password_confirm').value;
            
            // Validar senhas se foram preenchidas
            if (password || password_confirm) {
                if (password !== password_confirm) {
                    showAlert('As senhas não coincidem', 'error');
                    return;
                }
                
                if (password.length < 4) {
                    showAlert('A senha deve ter no mínimo 4 caracteres', 'error');
                    return;
                }
            }
            
            const formData = new FormData(this);
            formData.append('action', 'update');
            
            fetch('../src/php/user_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadProfile();
                    
                    // Atualizar header se o nome mudou
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao salvar perfil', 'error');
            });
        });

        // Upload de foto
        function uploadFoto() {
            const fileInput = document.getElementById('photoInput');
            const file = fileInput.files[0];
            
            if (!file) return;
            
            // Validar tamanho (2MB)
            if (file.size > 2 * 1024 * 1024) {
                showAlert('Imagem muito grande. Máximo 2MB', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('foto', file);
            formData.append('action', 'upload_foto');
            
            fetch('../src/php/user_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadProfile();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao fazer upload da foto', 'error');
            });
        }

        // Remover foto
        function removeFoto() {
            if (!confirm('Deseja realmente remover sua foto de perfil?')) return;
            
            fetch('../src/php/user_profile.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=remove_foto'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadProfile();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showAlert('Erro ao remover foto', 'error');
            });
        }

        // Mostrar alerta
        function showAlert(message, type) {
            const alert = document.getElementById('alertMessage');
            alert.className = 'alert alert-' + type;
            alert.textContent = message;
            alert.style.display = 'block';
            
            setTimeout(() => {
                alert.style.display = 'none';
            }, 5000);
        }

        // Carregar dados ao carregar página
        loadProfile();
    </script>
</body>
</html>
