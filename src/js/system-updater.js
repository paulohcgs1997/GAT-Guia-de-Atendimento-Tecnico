// Sistema de Atualização Automática via GitHub

async function checkSystemUpdates() {
    console.log('🔍 checkSystemUpdates iniciado');
    const btnCheck = document.getElementById('btnCheckUpdates');
    const resultDiv = document.getElementById('updateCheckResult');
    
    console.log('btnCheck:', btnCheck);
    console.log('resultDiv:', resultDiv);
    
    if (!btnCheck || !resultDiv) {
        console.error('❌ Elementos não encontrados!');
        return;
    }
    
    // Desabilitar botão e mostrar loading
    btnCheck.disabled = true;
    btnCheck.innerHTML = '<i class="bi bi-arrow-repeat spinner-border spinner-border-sm"></i> Verificando...';
    
    try {
        console.log('📡 Fazendo requisição para check_updates.php');
        const response = await fetch('../src/php/check_updates.php');
        console.log('📡 Response status:', response.status);
        const data = await response.json();
        console.log('📦 Dados recebidos:', data);
        
        if (!data.success) {
            // Se o erro é de repositório não configurado, mostrar opção de auto-configurar
            if (data.error && data.error.includes('não configurado')) {
                resultDiv.innerHTML = `
                    <div class="alert alert-warning d-flex align-items-start" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 24px;"></i>
                        <div class="flex-grow-1">
                            <strong>Repositório não configurado</strong>
                            <p class="mb-2 mt-2">${data.message}</p>
                            <small class="text-muted">Versão atual: ${data.current_version}</small>
                        </div>
                    </div>
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary" onclick="tryAutoConfig()">
                            <i class="bi bi-magic"></i> Tentar Configurar Automaticamente
                        </button>
                        <button class="btn btn-outline-secondary" onclick="showGithubConfig()">
                            <i class="bi bi-gear"></i> Configurar Manualmente
                        </button>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div class="alert alert-warning d-flex align-items-center" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 24px;"></i>
                        <div>
                            <strong>Aviso:</strong> ${data.error || data.message}
                            <br><small class="text-muted">Versão atual: ${data.current_version}</small>
                        </div>
                    </div>
                `;
            }
            return;
        }
        
        if (data.has_update) {
            // Há atualização disponível
            resultDiv.innerHTML = `
                <div class="alert alert-success d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-cloud-download-fill me-2" style="font-size: 32px;"></i>
                    <div class="flex-grow-1">
                        <strong>Nova versão disponível!</strong>
                        <div class="mt-2">
                            <span class="badge bg-secondary">
                                <i class="bi bi-laptop"></i> Local: ${data.current_build || data.current_version}
                            </span>
                            <i class="bi bi-arrow-right mx-2"></i>
                            <span class="badge bg-success">
                                <i class="bi bi-cloud"></i> Remoto: ${data.remote_build || data.latest_version}
                            </span>
                            <span class="badge bg-info ms-2">
                                <i class="bi bi-git"></i> Branch: ${data.branch || 'main'}
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">
                                <i class="bi bi-megaphone"></i> ${data.release_info.name}
                            </h5>
                            <small>Publicado em ${data.release_info.published_at}</small>
                        </div>
                        <span class="badge bg-light text-dark">
                            <i class="bi bi-code-slash"></i> ${data.branch || 'main'}
                        </span>
                    </div>
                    <div class="card-body">
                        <h6>📝 Novidades:</h6>
                        <div class="changelog-content" style="white-space: pre-wrap; font-size: 14px; line-height: 1.6;">
${data.release_info.body || 'Sem descrição disponível'}
                        </div>
                    </div>
                    <div class="card-footer d-flex gap-2 justify-content-between">
                        <a href="${data.release_info.html_url}" target="_blank" class="btn btn-outline-secondary">
                            <i class="bi bi-github"></i> Ver no GitHub
                        </a>
                        <button class="btn btn-success" onclick="applySystemUpdate('${data.release_info.download_url}', '${data.latest_version}')">
                            <i class="bi bi-download"></i> Baixar e Instalar
                        </button>
                    </div>
                </div>
            `;
            
            // Mostrar botão de aplicar atualização
            document.getElementById('btnApplyUpdate')?.remove();
            
        } else if (data.last_commit) {
            // Versão de desenvolvimento
            resultDiv.innerHTML = `
                <div class="alert alert-info d-flex align-items-start" role="alert">
                    <i class="bi bi-code-slash me-2" style="font-size: 32px;"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Versão de Desenvolvimento</strong>
                            <span class="badge bg-primary">
                                <i class="bi bi-git"></i> Branch: ${data.branch || 'main'}
                            </span>
                        </div>
                        <p class="mb-2">${data.message}</p>
                        <div class="small">
                            <strong>Último commit:</strong> ${data.last_commit.sha}<br>
                            <strong>Mensagem:</strong> ${data.last_commit.message}<br>
                            <strong>Autor:</strong> ${data.last_commit.author}<br>
                            <strong>Data:</strong> ${data.last_commit.date}
                        </div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-primary" onclick="applySystemUpdate('${data.download_url}', 'main')">
                                <i class="bi bi-download"></i> Baixar Branch Main
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Sistema atualizado
            resultDiv.innerHTML = `
                <div class="alert alert-success d-flex align-items-start" role="alert">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 32px;"></i>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Sistema Atualizado!</strong>
                            <span class="badge bg-success">
                                <i class="bi bi-git"></i> Branch: ${data.branch || 'main'}
                            </span>
                        </div>
                        <p class="mb-0">Você está usando a versão mais recente.</p>
                        <div class="mt-2">
                            <span class="badge bg-info">
                                <i class="bi bi-laptop"></i> Local: ${data.current_build || data.current_version}
                            </span>
                            <span class="badge bg-info ms-2">
                                <i class="bi bi-cloud"></i> Remoto: ${data.remote_build || data.latest_version}
                            </span>
                        </div>
                        <small class="text-muted d-block mt-2">Repositório: ${data.repository || 'N/A'}</small>
                        
                        <!-- Botão para forçar reinstalação (útil para testes ou correções) -->
                        <div class="mt-3">
                            <button class="btn btn-sm btn-outline-warning" onclick="applySystemUpdate('${data.download_url}', '${data.current_version}', true)">
                                <i class="bi bi-arrow-clockwise"></i> Forçar Reinstalação
                            </button>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-info-circle"></i> Útil para restaurar arquivos ou testar o sistema de atualização
                            </small>
                        </div>
                    </div>
                </div>
            `;
        }
        
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2" style="font-size: 24px;"></i>
                <div>
                    <strong>Erro ao verificar atualizações</strong>
                    <p class="mb-0 mt-1">${error.message}</p>
                </div>
            </div>
        `;
    } finally {
        btnCheck.disabled = false;
        btnCheck.innerHTML = '<i class="bi bi-arrow-repeat"></i> Verificar Atualizações';
    }
}

// Tentar configurar repositório automaticamente
async function tryAutoConfig() {
    const resultDiv = document.getElementById('updateCheckResult');
    
    resultDiv.innerHTML = `
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <div class="spinner-border text-primary me-3" role="status">
                <span class="visually-hidden">Configurando...</span>
            </div>
            <div>
                <strong>Detectando repositório...</strong>
                <p class="mb-0 mt-2">Verificando arquivo .git/config</p>
            </div>
        </div>
    `;
    
    try {
        // Tentar detectar repositório via check_updates (que já tem a lógica)
        const response = await fetch('../src/php/auto_config_github.php');
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 32px;"></i>
                    <div>
                        <strong>Repositório configurado automaticamente!</strong>
                        <p class="mb-0 mt-2">📦 ${data.repository}</p>
                    </div>
                </div>
            `;
            
            // Tentar verificar atualizações novamente
            setTimeout(() => checkSystemUpdates(), 1500);
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 24px;"></i>
                    <div>
                        <strong>Não foi possível detectar automaticamente</strong>
                        <p class="mb-2 mt-2">${data.message}</p>
                        <button class="btn btn-sm btn-primary" onclick="showGithubConfig()">
                            <i class="bi bi-gear"></i> Configurar Manualmente
                        </button>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-circle-fill me-2" style="font-size: 24px;"></i>
                <div>
                    <strong>Erro na configuração automática</strong>
                    <p class="mb-0 mt-1">${error.message}</p>
                </div>
            </div>
        `;
    }
}

async function applySystemUpdate(downloadUrl, version, forceReinstall = false) {
    const message = forceReinstall 
        ? `Deseja realmente forçar a reinstalação da versão ${version}?\n\nUm backup será criado automaticamente.`
        : `Deseja realmente atualizar para a versão ${version}?\n\nUm backup será criado automaticamente antes da atualização.`;
    
    if (!confirm(message)) {
        return;
    }
    
    const resultDiv = document.getElementById('updateCheckResult');
    
    // Mostrar loading
    resultDiv.innerHTML = `
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <div class="spinner-border text-primary me-3" role="status">
                <span class="visually-hidden">Atualizando...</span>
            </div>
            <div>
                <strong>Aplicando atualização...</strong>
                <p class="mb-0 mt-2">Isso pode levar alguns minutos. Não feche esta página.</p>
                <div class="progress mt-2" style="width: 300px; height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                </div>
            </div>
        </div>
    `;
    
    try {
        const formData = new FormData();
        formData.append('download_url', downloadUrl);
        
        console.log('🚀 Enviando requisição de atualização...');
        const response = await fetch('../src/php/apply_update.php', {
            method: 'POST',
            body: formData
        });
        
        console.log('📡 Response status:', response.status);
        const data = await response.json();
        console.log('📦 Resposta do servidor:', data);
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success d-flex align-items-center" role="alert">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 32px;"></i>
                    <div>
                        <strong>Atualização concluída com sucesso! 🎉</strong>
                        <p class="mb-2 mt-2">${data.message}</p>
                        <small class="text-muted">
                            <i class="bi bi-save"></i> Backup salvo em: ${data.backup_path}
                        </small>
                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="window.location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Recarregar Página
                            </button>
                        </div>
                    </div>
                </div>
            `;
        } else {
            const error = new Error(data.error);
            error.debug = data.debug;
            throw error;
        }
        
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-x-circle-fill me-2" style="font-size: 24px;"></i>
                <div>
                    <strong>Erro ao aplicar atualização</strong>
                    <p class="mb-0 mt-1">${error.message}</p>
                    ${error.debug ? '<pre class="mt-2 mb-0 small">' + JSON.stringify(error.debug, null, 2) + '</pre>' : ''}
                    <small class="text-muted">O sistema permanece na versão anterior.</small>
                </div>
            </div>
        `;
    }
}

// Função para tentar configurar automaticamente o repositório GitHub
async function tryAutoConfig() {
    console.log('🔧 tryAutoConfig iniciado');
    const resultDiv = document.getElementById('updateCheckResult');
    
    resultDiv.innerHTML = `
        <div class="alert alert-info d-flex align-items-center">
            <div class="spinner-border text-primary me-3" role="status"></div>
            <div>
                <strong>Detectando repositório...</strong>
                <p class="mb-0 mt-2">Tentando detectar automaticamente do .git/config</p>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch('../src/php/auto_config_github.php');
        const data = await response.json();
        
        if (data.success) {
            resultDiv.innerHTML = `
                <div class="alert alert-success d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2" style="font-size: 32px;"></i>
                    <div>
                        <strong>Repositório configurado automaticamente! 🎉</strong>
                        <p class="mb-2 mt-2">${data.message}</p>
                        <div class="small">
                            <i class="bi bi-github"></i> <strong>Repositório:</strong> ${data.repository}<br>
                            <i class="bi bi-git"></i> <strong>Branch:</strong> main
                        </div>
                    </div>
                </div>
            `;
            
            // Verificar atualizações automaticamente após configurar
            setTimeout(() => checkSystemUpdates(), 1500);
        } else {
            resultDiv.innerHTML = `
                <div class="alert alert-warning d-flex align-items-start">
                    <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 24px;"></i>
                    <div class="flex-grow-1">
                        <strong>Não foi possível detectar automaticamente</strong>
                        <p class="mb-2 mt-2">${data.message}</p>
                        <button class="btn btn-sm btn-primary mt-2" onclick="showGithubConfig()">
                            <i class="bi bi-gear"></i> Configurar Manualmente
                        </button>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        resultDiv.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center">
                <i class="bi bi-x-circle-fill me-2" style="font-size: 24px;"></i>
                <div>
                    <strong>Erro ao configurar</strong>
                    <p class="mb-0 mt-1">${error.message}</p>
                </div>
            </div>
        `;
    }
}
