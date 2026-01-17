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
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="backups-tab" data-bs-toggle="tab" data-bs-target="#tab-backups" type="button" role="tab">
                                <i class="bi bi-archive"></i> Backups
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
                        <h3>📋 Informações Gerais do sistema</h3>
                        
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
                    
                    <!-- Cabeçalho com Status -->
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h4 class="mb-1">
                                <i class="bi bi-cloud-arrow-down" style="color: #10b981;"></i> Central de Atualizações
                            </h4>
                            <p class="text-muted small mb-0">
                                Mantenha seu sistema sempre atualizado
                            </p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="openBranchModal()" title="Configurações Avançadas">
                                <i class="bi bi-gear"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Botão Principal de Atualização -->
                    <div class="text-center my-3">
                        <button type="button" class="btn btn-success px-4" onclick="checkSystemUpdates()" id="btnCheckUpdates">
                            <i class="bi bi-arrow-repeat"></i> Verificar Atualizações
                        </button>
                    </div>
                    
                    <!-- Área de Resultado -->
                    <div id="updateCheckResult">
                        <div class="alert alert-info d-flex align-items-center py-2 mb-3" role="alert">
                            <i class="bi bi-info-circle-fill me-2" style="font-size: 20px;"></i>
                            <small>Clique em <strong>"Verificar Atualizações"</strong> para buscar novas versões.</small>
                        </div>
                    </div>
                    
                    <!-- Informações de Segurança -->
                    <div class="mt-3">
                        <div class="card border-0" style="background: rgba(16, 185, 129, 0.1);">
                            <div class="card-body py-2 px-3">
                                <h6 class="card-title mb-2 small">
                                    <i class="bi bi-shield-check text-success"></i> Recursos de Segurança
                                </h6>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>
                                            <div>
                                                <strong class="small">Backup Automático</strong>
                                                <div class="text-muted" style="font-size: 11px;">Cria backup antes de atualizar</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>
                                            <div>
                                                <strong class="small">Instalação Segura</strong>
                                                <div class="text-muted" style="font-size: 11px;">Preserva suas configurações</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>
                                            <div>
                                                <strong class="small">Verificação Automática</strong>
                                                <div class="text-muted" style="font-size: 11px;">Detecta atualizações disponíveis</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-check-circle-fill text-success" style="font-size: 14px;"></i>
                                            <div>
                                                <strong class="small">Changelog Completo</strong>
                                                <div class="text-muted" style="font-size: 11px;">Veja todas as mudanças</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal de Configuração de Canal -->
            <div class="modal fade" id="branchModal" tabindex="-1" aria-labelledby="branchModalLabel" aria-hidden="true" style="z-index: 1060;">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                    <div class="modal-content" style="z-index: 1061;">
                        <div class="modal-header bg-primary text-white py-2">
                            <h6 class="modal-title mb-0" id="branchModalLabel">
                                <i class="bi bi-gear-fill"></i> Configurações Avançadas
                            </h6>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body py-3">
                            <div class="alert alert-warning d-flex align-items-start py-2" role="alert">
                                <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 18px;"></i>
                                <div>
                                    <strong class="small">Atenção!</strong>
                                    <div style="font-size: 11px;" class="mt-1">Alterar o canal pode instalar versões com bugs ou recursos instáveis.</div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label for="github_branch" class="form-label fw-bold small mb-1">
                                    <i class="bi bi-bezier2"></i> Canal de Atualizações
                                </label>
                                <select class="form-select" id="github_branch" name="github_branch">
                                    <option value="">Carregando canais...</option>
                                </select>
                            </div>

                            <!-- Informações Dinâmicas do Branch Selecionado -->
                            <div id="branchInfoCard" style="display: none;">
                                <div class="card bg-light border-0 mb-2 mt-2">
                                    <div class="card-body py-2 px-3">
                                        <h6 class="card-title mb-2 small">
                                            <i class="bi bi-info-circle"></i> Informações do Canal
                                        </h6>
                                        <div id="branchDetailsContent" style="font-size: 13px;">
                                            <!-- Preenchido dinamicamente -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div id="branchConfigStatus"></div>
                        </div>
                        <div class="modal-footer py-2">
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </button>
                            <button type="button" class="btn btn-sm btn-primary" onclick="saveBranchConfigFromModal()">
                                <i class="bi bi-check-circle"></i> Salvar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Guia: Gerenciamento de Backups -->
            <div class="tab-pane fade" id="tab-backups" role="tabpanel">
                <div class="config-section" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border: 2px solid #f59e0b; border-radius: 8px; padding: 20px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h3 class="mb-2">
                                <i class="bi bi-archive"></i> Gerenciamento de Backups
                            </h3>
                            <p class="text-muted mb-0">
                                Crie e restaure backups do sistema (mantém apenas os 3 mais recentes)
                            </p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-warning" onclick="createManualBackup()">
                                <i class="bi bi-plus-circle"></i> Criar Backup Manual
                            </button>
                        </div>
                    </div>
                    
                    <hr class="my-3">
                    
                    <div id="backupsList">
                        <div class="alert alert-info d-flex align-items-center" role="alert">
                            <div class="spinner-border text-primary me-3" role="status"></div>
                            <div>Carregando backups disponíveis...</div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <div class="alert" style="background: #fef3c7; border-left: 4px solid #f59e0b;">
                            <h6 style="color: #78350f; font-weight: 600;">
                                <i class="bi bi-info-circle"></i> Informações Importantes
                            </h6>
                            <ul class="mb-0" style="color: #92400e;">
                                <li>O sistema mantém automaticamente apenas os <strong>3 backups mais recentes</strong></li>
                                <li>Backups são criados automaticamente antes de cada atualização</li>
                                <li>Você pode criar backups manuais a qualquer momento</li>
                                <li>Ao restaurar um backup, o sistema atual será substituído</li>
                                <li>Um novo backup será criado automaticamente antes da restauração</li>
                            </ul>
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
    <script src="../src/js/backup-manager.js"></script>
    
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
                    
                    // Carregar branches disponíveis
                    loadGithubBranches();
                    
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

        // Função para carregar branches disponíveis do GitHub
        async function loadGithubBranches() {
            const select = document.getElementById('github_branch');
            const badge = document.getElementById('currentBranchBadge');
            const statusDiv = document.getElementById('branchConfigStatus');
            
            console.log('🔍 Carregando branches do GitHub...');
            if (select) select.innerHTML = '<option value="">Carregando...</option>';
            if (badge) badge.textContent = 'Carregando...';
            
            try {
                const url = '../src/php/get_github_config.php?action=get_branches';
                console.log('📡 Fazendo requisição para:', url);
                
                const response = await fetch(url);
                console.log('📥 Resposta recebida:', response.status, response.statusText);
                
                const data = await response.json();
                console.log('📦 Dados recebidos:', data);
                
                if (data.success && data.branches) {
                    console.log('✅ Branches encontrados:', data.branches.length);
                    if (select) select.innerHTML = '';
                    
                    // Armazenar informações dos branches globalmente
                    window.branchesInfo = data.branches;
                    
                    data.branches.forEach(branch => {
                        console.log('➕ Adicionando branch:', branch.name);
                        const option = document.createElement('option');
                        option.value = branch.name;
                        option.textContent = branch.name;
                        
                        // Adicionar informações como data-attributes
                        if (branch.commit) {
                            option.setAttribute('data-commit-message', branch.commit.message || '');
                            option.setAttribute('data-commit-date', branch.commit.date || '');
                            option.setAttribute('data-commit-author', branch.commit.author || '');
                            option.setAttribute('data-commit-sha', branch.commit.sha || '');
                        }
                        
                        // Marcar o branch atual como selecionado
                        if (branch.name === data.current_branch) {
                            option.selected = true;
                            console.log('⭐ Branch atual marcado:', branch.name);
                        }
                        
                        if (select) select.appendChild(option);
                    });
                    
                    // Adicionar evento onChange para mostrar informações do branch
                    if (select) {
                        select.addEventListener('change', function() {
                            updateBranchInfo(this.value);
                        });
                        
                        // Mostrar informações do branch atual
                        if (data.current_branch) {
                            updateBranchInfo(data.current_branch);
                        }
                    }
                    
                    // Atualizar badge com o canal atual
                    if (badge && data.current_branch) {
                        const branchName = data.current_branch.toUpperCase();
                        badge.textContent = branchName === 'MAIN' ? '🟢 Estável (main)' : '🔶 Desenvolvimento (' + data.current_branch + ')';
                        badge.className = branchName === 'MAIN' ? 'badge bg-success' : 'badge bg-warning';
                    }
                    
                    console.log('✅ Branches carregados com sucesso!');
                } else {
                    console.warn('⚠️ Falha ao carregar branches:', data.message || 'Sem mensagem');
                    if (select) select.innerHTML = '<option value="main">main</option>';
                    if (badge) {
                        badge.textContent = '🟢 Estável (main)';
                        badge.className = 'badge bg-success';
                    }
                    if (statusDiv) {
                        statusDiv.innerHTML = `
                            <div class="alert alert-warning alert-sm mb-0">
                                <i class="bi bi-exclamation-triangle"></i> ${data.message || 'Não foi possível carregar os canais.'}
                            </div>
                        `;
                    }
                }
            } catch (error) {
                console.error('❌ Erro ao carregar branches:', error);
                if (select) select.innerHTML = '<option value="main">main</option>';
                if (badge) {
                    badge.textContent = '🟢 Estável (main)';
                    badge.className = 'badge bg-success';
                }
                if (statusDiv) {
                    statusDiv.innerHTML = `
                        <div class="alert alert-danger alert-sm mb-0">
                            <i class="bi bi-x-circle"></i> Erro ao conectar: ${error.message}
                        </div>
                    `;
                }
            }
        }

        // Função para atualizar informações do branch selecionado
        function updateBranchInfo(branchName) {
            const infoCard = document.getElementById('branchInfoCard');
            const detailsContent = document.getElementById('branchDetailsContent');
            
            if (!infoCard || !detailsContent) return;
            
            // Buscar informações do branch
            const branchInfo = window.branchesInfo?.find(b => b.name === branchName);
            
            if (branchInfo && branchInfo.commit) {
                const commit = branchInfo.commit;
                const metadata = branchInfo.metadata;
                
                const commitDate = commit.date ? new Date(commit.date) : null;
                const formattedDate = commitDate ? commitDate.toLocaleString('pt-BR', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : 'Data não disponível';
                
                // Se tiver metadata personalizado, usar ele
                if (metadata) {
                    const badgeClass = metadata.color === 'success' ? 'bg-success' : 
                                      metadata.color === 'warning' ? 'bg-warning' : 
                                      metadata.color === 'danger' ? 'bg-danger' : 'bg-primary';
                    
                    const badgeIcon = metadata.recommended ? '<i class="bi bi-star-fill"></i>' : 
                                     metadata.stability === 'experimental' ? '<i class="bi bi-exclamation-triangle"></i>' : 
                                     '<i class="bi bi-info-circle"></i>';
                    
                    const recommendedBadge = metadata.recommended 
                        ? `<span class="badge ${badgeClass}">${badgeIcon} ${metadata.type || 'Recomendado'}</span>`
                        : `<span class="badge ${badgeClass}">${badgeIcon} ${metadata.stability || metadata.type}</span>`;
                    
                    let html = `
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <strong style="font-size: 1.1em;">${metadata.name || branchName}</strong>
                                ${recommendedBadge}
                            </div>
                            <p class="text-muted small mb-3">${metadata.description || 'Sem descrição disponível'}</p>
                        </div>
                    `;
                    
                    // Recursos
                    if (metadata.features && metadata.features.length > 0) {
                        html += `
                            <div class="border-top pt-3 mb-3">
                                <h6 class="text-muted small mb-2"><i class="bi bi-stars"></i> RECURSOS DESTE CANAL</h6>
                                <ul class="mb-0 small">
                        `;
                        metadata.features.forEach(feature => {
                            html += `<li><i class="bi bi-check-circle text-success"></i> ${feature}</li>`;
                        });
                        html += `</ul></div>`;
                    }
                    
                    // Avisos
                    if (metadata.warnings && metadata.warnings.length > 0) {
                        html += `
                            <div class="border-top pt-3 mb-3">
                                <h6 class="text-muted small mb-2"><i class="bi bi-exclamation-triangle-fill text-warning"></i> AVISOS</h6>
                                <ul class="mb-0 small text-warning">
                        `;
                        metadata.warnings.forEach(warning => {
                            html += `<li><i class="bi bi-exclamation-circle"></i> ${warning}</li>`;
                        });
                        html += `</ul></div>`;
                    }
                    
                    // Informações do commit
                    html += `
                        <div class="border-top pt-3">
                            <h6 class="text-muted small mb-2">ÚLTIMA ATUALIZAÇÃO</h6>
                            <div class="mb-2">
                                <i class="bi bi-calendar-event text-primary"></i>
                                <strong>${formattedDate}</strong>
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-person text-primary"></i>
                                ${commit.author}
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-git text-primary"></i>
                                <code>${commit.sha}</code>
                            </div>
                        </div>
                        
                        <div class="border-top pt-3 mt-3">
                            <h6 class="text-muted small mb-2">ÚLTIMO COMMIT</h6>
                            <div class="small" style="background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 3px solid #0d6efd;">
                                <i class="bi bi-chat-left-text"></i> ${commit.message.split('\n')[0]}
                            </div>
                        </div>
                    `;
                    
                    detailsContent.innerHTML = html;
                } else {
                    // Fallback para branches sem metadata
                    const isMain = branchName.toLowerCase() === 'main';
                    const recommendation = isMain 
                        ? '<span class="badge bg-success"><i class="bi bi-star-fill"></i> Recomendado</span>'
                        : '<span class="badge bg-warning"><i class="bi bi-exclamation-triangle"></i> Experimental</span>';
                    
                    const description = isMain
                        ? 'Canal estável com versões testadas e aprovadas para produção.'
                        : 'Canal de desenvolvimento com recursos experimentais e novas funcionalidades em teste.';
                    
                    detailsContent.innerHTML = `
                        <div class="mb-3">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <strong style="font-size: 1.1em;">${branchName}</strong>
                                ${recommendation}
                            </div>
                            <p class="text-muted small mb-3">${description}</p>
                        </div>
                        
                        <div class="border-top pt-3">
                            <h6 class="text-muted small mb-2">ÚLTIMA ATUALIZAÇÃO</h6>
                            <div class="mb-2">
                                <i class="bi bi-calendar-event text-primary"></i>
                                <strong>${formattedDate}</strong>
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-person text-primary"></i>
                                ${commit.author}
                            </div>
                            <div class="mb-2">
                                <i class="bi bi-git text-primary"></i>
                                <code>${commit.sha}</code>
                            </div>
                        </div>
                        
                        <div class="border-top pt-3 mt-3">
                            <h6 class="text-muted small mb-2">ÚLTIMO COMMIT</h6>
                            <div class="small" style="background: #f8f9fa; padding: 10px; border-radius: 4px; border-left: 3px solid #0d6efd;">
                                <i class="bi bi-chat-left-text"></i> ${commit.message.split('\n')[0]}
                            </div>
                        </div>
                    `;
                }
                
                infoCard.style.display = 'block';
            } else {
                infoCard.style.display = 'none';
            }
        }

        // Função para abrir o modal de configuração
        function openBranchModal() {
            const modal = new bootstrap.Modal(document.getElementById('branchModal'));
            modal.show();
            // Recarregar branches ao abrir o modal
            loadGithubBranches();
        }

        // Função para salvar a configuração do branch a partir do modal
        async function saveBranchConfigFromModal() {
            const select = document.getElementById('github_branch');
            const statusDiv = document.getElementById('branchConfigStatus');
            const badge = document.getElementById('currentBranchBadge');
            const selectedBranch = select.value;
            
            if (!selectedBranch) {
                statusDiv.innerHTML = `
                    <div class="alert alert-warning alert-sm mb-0">
                        <i class="bi bi-exclamation-triangle"></i> Selecione um canal primeiro.
                    </div>
                `;
                return;
            }
            
            statusDiv.innerHTML = `
                <div class="alert alert-info alert-sm mb-0">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    Salvando configuração...
                </div>
            `;
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_branch');
                formData.append('branch', selectedBranch);
                
                const response = await fetch('../src/php/save_github_config.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    statusDiv.innerHTML = `
                        <div class="alert alert-success alert-sm mb-0">
                            <i class="bi bi-check-circle"></i> ${data.message || 'Canal salvo com sucesso!'}
                        </div>
                    `;
                    
                    // Atualizar badge
                    const branchName = selectedBranch.toUpperCase();
                    if (badge) {
                        badge.textContent = branchName === 'MAIN' ? '🟢 Estável (main)' : '🔶 Desenvolvimento (' + selectedBranch + ')';
                        badge.className = branchName === 'MAIN' ? 'badge bg-success' : 'badge bg-warning';
                    }
                    
                    // Fechar modal após 1.5 segundos
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('branchModal'));
                        if (modal) modal.hide();
                    }, 1500);
                } else {
                    statusDiv.innerHTML = `
                        <div class="alert alert-danger alert-sm mb-0">
                            <i class="bi bi-x-circle"></i> ${data.message || 'Erro ao salvar canal.'}
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Erro ao salvar branch:', error);
                statusDiv.innerHTML = `
                    <div class="alert alert-danger alert-sm mb-0">
                        <i class="bi bi-x-circle"></i> Erro ao salvar configuração.
                    </div>
                `;
            }
        }

        // Função mantida para compatibilidade (agora chama a função do modal)
        async function saveBranchConfig() {
            await saveBranchConfigFromModal();
        }
    </script>
    
    <!-- Bootstrap cuida da navegação das tabs automaticamente -->
</body>

</html>
