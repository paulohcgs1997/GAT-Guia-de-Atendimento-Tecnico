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
        <div class="gestao-container container-fluid">
            <div class="row">
                <div class="col-12">
                    <h1 class="mb-3">⚙️ Configurações do Sistema</h1>
                    <p class="text-muted mb-4">Personalize as informações do sistema</p>
                </div>
            </div>

            <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                <div class="loading-spinner"></div>
                <p>Salvando configurações...</p>
            </div>

            <!-- Sistema de Guias/Tabs com Bootstrap -->
            <div class="row">
                <div class="col-12">
                    <ul class="nav nav-tabs mb-4" id="configTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#tab-info" type="button" role="tab">
                                <i class="bi bi-clipboard-data"></i> Informações do Sistema
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="visual-tab" data-bs-toggle="tab" data-bs-target="#tab-visual" type="button" role="tab">
                                <i class="bi bi-palette"></i> Identidade Visual
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="database-tab" data-bs-toggle="tab" data-bs-target="#tab-database" type="button" role="tab">
                                <i class="bi bi-database-check"></i> Verificador de BD
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="updates-tab" data-bs-toggle="tab" data-bs-target="#tab-updates" type="button" role="tab">
                                <i class="bi bi-cloud-download"></i> Atualizações
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Conteúdo das Guias com Bootstrap -->
            <div class="tab-content" id="configTabContent">
            
            <!-- Guia: Informações do Sistema -->
            <div class="tab-pane fade show active" id="tab-info" role="tabpanel">
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

                    <div class="config-section">
                        <h3>🔗 Repositório GitHub (Atualizações)</h3>
                        <p class="text-muted small mb-3">Configure o repositório GitHub para habilitar atualizações automáticas. Deixe em branco para detecção automática.</p>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="github_owner">Usuário/Organização</label>
                                    <input type="text" id="github_owner" name="github_owner" 
                                           placeholder="ex: microsoft">
                                    <small class="text-muted">Nome do usuário ou organização no GitHub</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="github_repo">Nome do Repositório</label>
                                    <input type="text" id="github_repo" name="github_repo" 
                                           placeholder="ex: vscode">
                                    <small class="text-muted">Nome do repositório (sem .git)</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info d-flex align-items-start mt-2">
                            <i class="bi bi-info-circle me-2 mt-1"></i>
                            <div class="small">
                                <strong>Detecção Automática:</strong> Se você clonou este projeto de um repositório Git, 
                                o sistema tentará detectar automaticamente o repositório de origem. Você só precisa 
                                preencher estes campos se quiser apontar para um repositório diferente.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='gestao.php'">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Configurações
                        </button>
                    </div>
                </form>
            </div>

            <!-- Guia: Identidade Visual -->
            <div class="tab-pane fade" id="tab-visual" role="tabpanel">
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

                    <div class="d-flex gap-2 justify-content-end mt-4">
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='gestao.php'">
                            <i class="bi bi-arrow-left"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Salvar Imagens
                        </button>
                    </div>
                </form>
            </div>

            <!-- Guia: Verificador de Banco de Dados -->
            <div class="tab-pane fade" id="tab-database" role="tabpanel">
                <div class="config-section" id="databaseChecker" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #3b82f6; border-radius: 8px; padding: 20px;">
                    <h3>🔍 Verificador de Banco de Dados</h3>
                    <p style="color: #6b7280; margin-bottom: 20px;">Verifica se todas as atualizações necessárias foram aplicadas</p>
                    
                    <div id="dbCheckResult" style="margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #f59e0b;">
                            <span style="font-size: 24px;">⏳</span>
                            <span style="color: #6b7280;">Clique em "Verificar Agora" para checar o banco de dados</span>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" onclick="checkDatabase()" id="btnCheckDb">
                            <i class="bi bi-search"></i> Verificar Agora
                        </button>
                        <button type="button" class="btn btn-success" onclick="applyUpdates()" id="btnApplyUpdates" style="display: none;">
                            <i class="bi bi-lightning-charge"></i> Aplicar Atualizações
                        </button>
                    </div>
                    
                    <div id="updatesList" style="margin-top: 20px; display: none;"></div>
                </div>
            </div>

            <!-- Guia: Atualizações do Sistema -->
            <div class="tab-pane fade" id="tab-updates" role="tabpanel">
                <div class="config-section" style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 2px solid #10b981; border-radius: 8px; padding: 20px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h3 class="mb-2">
                                <i class="bi bi-cloud-download"></i> Atualizador de Sistema
                            </h3>
                            <p class="text-muted mb-0">
                                Mantenha seu sistema atualizado com as últimas melhorias do GitHub
                            </p>
                            <small class="text-info">
                                <i class="bi bi-git"></i> <strong>Branch:</strong> main
                                <span class="mx-2">|</span>
                                <i class="bi bi-shield-lock"></i> <strong>Token:</strong> Configurado no servidor
                            </small>
                        </div>
                        <div>
                            <button type="button" class="btn btn-success" onclick="checkSystemUpdates()" id="btnCheckUpdates">
                                <i class="bi bi-arrow-repeat"></i> Verificar Atualizações
                            </button>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div id="updateCheckResult">
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <i class="bi bi-info-circle-fill me-2" style="font-size: 24px;"></i>
                            <div>
                                Clique em <strong>"Verificar Atualizações"</strong> para checar se há novas versões disponíveis no GitHub.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="bi bi-shield-check"></i> Funcionalidades do Atualizador
                                </h6>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <strong>Verificação Automática:</strong> Conecta-se ao GitHub para buscar novas versões
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <strong>Backup Automático:</strong> Cria backup completo antes de qualquer atualização
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <strong>Changelog Integrado:</strong> Visualize todas as mudanças antes de atualizar
                                    </li>
                                    <li class="mb-0">
                                        <i class="bi bi-check-circle-fill text-success me-2"></i>
                                        <strong>Instalação Segura:</strong> Preserva configurações e uploads existentes
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            </div><!-- Fecha tab-content Bootstrap -->
        </div>
    </main>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <!-- Scripts customizados -->
    <script src="../src/js/config-database.js"></script>
    <script src="../src/js/config-system.js"></script>
    <script src="../src/js/system-updater.js"></script>
    
    <!-- Verificação Automática de Atualizações -->
    <script>
        console.log('✅ Scripts carregados - Sistema de Atualizações');
        
        // Auto-verificar atualizações quando a aba é ativada
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🔍 DOM carregado');
            console.log('- checkSystemUpdates:', typeof checkSystemUpdates);
            console.log('- btnCheckUpdates:', document.getElementById('btnCheckUpdates'));
            console.log('- updateCheckResult:', document.getElementById('updateCheckResult'));
            
            const updatesTab = document.getElementById('updates-tab');
            if (updatesTab) {
                updatesTab.addEventListener('shown.bs.tab', function() {
                    console.log('📑 Aba de Atualizações ativada');
                    
                    setTimeout(() => {
                        if (typeof checkSystemUpdates === 'function') {
                            console.log('🚀 Verificando atualizações automaticamente...');
                            checkSystemUpdates();
                        }
                    }, 500);
                });
            }
            
            // Se a URL contém #updates-tab, ativar a aba e verificar
            if (window.location.hash === '#updates-tab') {
                console.log('🔗 Hash #updates-tab detectado na URL');
                setTimeout(() => {
                    const tabTrigger = new bootstrap.Tab(updatesTab);
                    tabTrigger.show();
                }, 100);
            }
        });
    </script>
    
    <!-- Bootstrap cuida da navegação das tabs automaticamente -->
</body>

</html>
