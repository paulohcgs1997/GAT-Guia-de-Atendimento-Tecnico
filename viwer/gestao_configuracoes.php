<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_admin(); // Apenas admin pode alterar configurações
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
            <h1>⚙️ Configurações do Sistema</h1>
            <p style="color: #666; margin-bottom: 30px;">Personalize as informações do sistema</p>

            <div class="loading-overlay" id="loadingOverlay">
                <div class="loading-spinner"></div>
                <p>Salvando configurações...</p>
            </div>

            <form id="configForm" class="config-form">
                <div class="config-section">
                    <h3>📋 Informações Gerais</h3>
                    
                    <div class="form-group">
                        <label for="system_name">Nome do Sistema *</label>
                        <input type="text" id="system_name" name="system_name" required 
                               placeholder="Ex: Sistema de Gestão Empresarial">
                    </div>

                    <div class="form-group">
                        <label for="system_description">Descrição</label>
                        <textarea id="system_description" name="system_description" rows="3" 
                                  placeholder="Breve descrição do sistema"></textarea>
                    </div>
                </div>

                <div class="config-section">
                    <h3>🎨 Identidade Visual</h3>
                    
                    <div class="form-group">
                        <label for="system_logo">Logotipo do Sistema</label>
                        <div class="image-upload-container">
                            <div class="image-preview" id="logoPreview">
                                <img id="logoImg" src="" alt="Logo" style="display: none; max-width: 200px; max-height: 100px;">
                                <span class="preview-placeholder">Nenhuma imagem selecionada</span>
                            </div>
                            <input type="file" id="system_logo" name="system_logo" accept="image/*">
                            <small>Formato recomendado: PNG com fundo transparente, 300x100px</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="system_favicon">Favicon</label>
                        <div class="image-upload-container">
                            <div class="image-preview" id="faviconPreview">
                                <img id="faviconImg" src="" alt="Favicon" style="display: none; max-width: 64px; max-height: 64px;">
                                <span class="preview-placeholder">Nenhuma imagem selecionada</span>
                            </div>
                            <input type="file" id="system_favicon" name="system_favicon" accept="image/*">
                            <small>Formato recomendado: ICO ou PNG, 32x32px ou 64x64px</small>
                        </div>
                    </div>
                </div>

                <div class="config-section">
                    <h3>📞 Contato</h3>
                    
                    <div class="form-group">
                        <label for="system_email">E-mail</label>
                        <input type="email" id="system_email" name="system_email" 
                               placeholder="contato@empresa.com">
                    </div>

                    <div class="form-group">
                        <label for="system_phone">Telefone</label>
                        <input type="text" id="system_phone" name="system_phone" 
                               placeholder="(00) 0000-0000">
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="window.location.href='gestao.php'">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary">
                        💾 Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <script>
        // Carregar configurações atuais
        async function loadConfigurations() {
            try {
                const response = await fetch('../src/php/get_configuracoes.php');
                const data = await response.json();
                
                if (data.success) {
                    const configs = data.configs;
                    
                    // Preencher campos de texto
                    document.getElementById('system_name').value = configs.system_name;
                    document.getElementById('system_description').value = configs.system_description;
                    document.getElementById('system_email').value = configs.system_email;
                    document.getElementById('system_phone').value = configs.system_phone;
                    
                    // Mostrar preview do logo
                    if (configs.system_logo) {
                        const logoImg = document.getElementById('logoImg');
                        logoImg.src = configs.system_logo;
                        logoImg.style.display = 'block';
                        document.querySelector('#logoPreview .preview-placeholder').style.display = 'none';
                    }
                    
                    // Mostrar preview do favicon
                    if (configs.system_favicon) {
                        const faviconImg = document.getElementById('faviconImg');
                        faviconImg.src = configs.system_favicon;
                        faviconImg.style.display = 'block';
                        document.querySelector('#faviconPreview .preview-placeholder').style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Erro ao carregar configurações:', error);
            }
        }
        
        // Preview de imagem ao selecionar arquivo
        document.getElementById('system_logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const logoImg = document.getElementById('logoImg');
                    logoImg.src = event.target.result;
                    logoImg.style.display = 'block';
                    document.querySelector('#logoPreview .preview-placeholder').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
        
        document.getElementById('system_favicon').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const faviconImg = document.getElementById('faviconImg');
                    faviconImg.src = event.target.result;
                    faviconImg.style.display = 'block';
                    document.querySelector('#faviconPreview .preview-placeholder').style.display = 'none';
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Salvar configurações
        document.getElementById('configForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'save_config');
            
            const loadingOverlay = document.getElementById('loadingOverlay');
            loadingOverlay.style.display = 'flex';
            
            try {
                const response = await fetch('../src/php/crud_configuracoes.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('✅ ' + data.message);
                    // Recarregar a página para mostrar as mudanças
                    window.location.reload();
                } else {
                    alert('❌ ' + data.message);
                }
            } catch (error) {
                console.error('Erro ao salvar configurações:', error);
                alert('❌ Erro ao salvar configurações');
            } finally {
                loadingOverlay.style.display = 'none';
            }
        });
        
        // Carregar configurações ao iniciar
        document.addEventListener('DOMContentLoaded', loadConfigurations);
    </script>
</body>

</html>
