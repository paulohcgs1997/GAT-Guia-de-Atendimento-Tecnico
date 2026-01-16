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

            <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Salvando configurações...</p>
            </div>

            <!-- Sistema de Guias/Tabs -->
            <div class="tabs-container">
                <div class="tabs-header">
                    <button class="tab-button active" data-tab="info">
                        📋 Informações do Sistema
                    </button>
                    <button class="tab-button" data-tab="visual">
                        🎨 Identidade Visual
                    </button>
                    <button class="tab-button" data-tab="database">
                        🔍 Verificador de BD
                    </button>
                </div>
            </div>

            <!-- Conteúdo das Guias -->
            
            <!-- Guia: Informações do Sistema -->
            <div class="tab-content active" id="tab-info">
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
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='gestao.php'">
                            ↩️ Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            💾 Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Guia: Identidade Visual -->
            <div class="tab-content" id="tab-visual">
                <form id="visualForm" class="config-form">
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

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='gestao.php'">
                            ↩️ Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            💾 Salvar Imagens
                        </button>
                    </div>
                </form>
            </div>

            <!-- Guia: Verificador de Banco de Dados -->
            <div class="tab-content" id="tab-database">
                <div class="config-section" id="databaseChecker" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #3b82f6; border-radius: 8px; padding: 20px;">
                    <h3>🔍 Verificador de Banco de Dados</h3>
                    <p style="color: #6b7280; margin-bottom: 20px;">Verifica se todas as atualizações necessárias foram aplicadas</p>
                    
                    <div id="dbCheckResult" style="margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #f59e0b;">
                            <span style="font-size: 24px;">⏳</span>
                            <span style="color: #6b7280;">Clique em "Verificar Agora" para checar o banco de dados</span>
                        </div>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-primary" onclick="checkDatabase()" id="btnCheckDb">
                            🔍 Verificar Agora
                        </button>
                        <button type="button" class="btn btn-success" onclick="applyUpdates()" id="btnApplyUpdates" style="display: none;">
                            ⚡ Aplicar Atualizações
                        </button>
                    </div>
                    
                    <div id="updatesList" style="margin-top: 20px; display: none;"></div>
                </div>
            </div>

        </div>
    </main>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <script src="../src/js/config-tabs.js"></script>
    <script src="../src/js/config-database.js"></script>
    <script src="../src/js/config-system.js"></script>
</body>

</html>
