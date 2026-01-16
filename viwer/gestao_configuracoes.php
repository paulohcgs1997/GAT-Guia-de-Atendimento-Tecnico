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

            <!-- Verificador de Banco de Dados -->
            <div class="config-section" id="databaseChecker" style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 2px solid #3b82f6; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
                <h3>🔍 Verificador de Banco de Dados</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">Verifica se todas as atualizações necessárias foram aplicadas</p>
                
                <div id="dbCheckResult" style="margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #f59e0b;">
                        <span style="font-size: 24px;">⏳</span>
                        <span style="color: #6b7280;">Clique em "Verificar Agora" para checar o banco de dados</span>
                    </div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-primary" onclick="checkDatabase()" id="btnCheckDb">
                        🔍 Verificar Agora
                    </button>
                    <button type="button" class="btn-success" onclick="applyUpdates()" id="btnApplyUpdates" style="display: none; background: #10b981;">
                        ⚡ Aplicar Atualizações
                    </button>
                </div>
                
                <div id="updatesList" style="margin-top: 20px; display: none;"></div>
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
        // ========== VERIFICADOR DE BANCO DE DADOS ==========
        
        let currentUpdates = [];
        
        async function checkDatabase() {
            const btn = document.getElementById('btnCheckDb');
            const resultDiv = document.getElementById('dbCheckResult');
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Verificando...';
            
            resultDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #3b82f6;">
                    <span style="font-size: 24px;">⏳</span>
                    <span style="color: #6b7280;">Verificando estrutura do banco de dados...</span>
                </div>
            `;
            
            try {
                const response = await fetch('../src/php/database_checker.php');
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.message || 'Erro ao verificar banco de dados');
                }
                
                currentUpdates = data.updates_available || [];
                
                if (data.needs_update) {
                    // Banco precisa de atualização
                    resultDiv.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #fef3c7; border-radius: 6px; border-left: 4px solid #f59e0b;">
                            <span style="font-size: 24px;">⚠️</span>
                            <div style="flex: 1;">
                                <strong style="color: #92400e;">Atualizações Disponíveis</strong>
                                <p style="color: #78350f; margin: 5px 0 0 0; font-size: 14px;">
                                    ${data.missing_columns.length} campo(s) faltando em ${new Set(data.missing_columns.map(c => c.table)).size} tabela(s)
                                </p>
                            </div>
                        </div>
                    `;
                    
                    // Mostrar lista de atualizações
                    displayUpdatesList(data.updates_available, data.missing_columns);
                    
                    // Mostrar botão de aplicar
                    document.getElementById('btnApplyUpdates').style.display = 'inline-block';
                } else {
                    // Banco está atualizado
                    resultDiv.innerHTML = `
                        <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #d1fae5; border-radius: 6px; border-left: 4px solid #10b981;">
                            <span style="font-size: 24px;">✅</span>
                            <div style="flex: 1;">
                                <strong style="color: #065f46;">Banco de Dados Atualizado</strong>
                                <p style="color: #047857; margin: 5px 0 0 0; font-size: 14px;">
                                    Todas as estruturas necessárias estão presentes
                                </p>
                            </div>
                        </div>
                    `;
                    
                    document.getElementById('updatesList').style.display = 'none';
                    document.getElementById('btnApplyUpdates').style.display = 'none';
                }
                
            } catch (error) {
                console.error('Erro ao verificar banco:', error);
                resultDiv.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #fee2e2; border-radius: 6px; border-left: 4px solid #dc2626;">
                        <span style="font-size: 24px;">❌</span>
                        <div style="flex: 1;">
                            <strong style="color: #991b1b;">Erro na Verificação</strong>
                            <p style="color: #7f1d1d; margin: 5px 0 0 0; font-size: 14px;">${error.message}</p>
                        </div>
                    </div>
                `;
            } finally {
                btn.disabled = false;
                btn.innerHTML = '🔍 Verificar Agora';
            }
        }
        
        function displayUpdatesList(updates, missingColumns) {
            const listDiv = document.getElementById('updatesList');
            
            if (updates.length === 0) {
                listDiv.style.display = 'none';
                return;
            }
            
            let html = '<h4 style="margin: 0 0 15px 0; color: #374151;">📦 Atualizações Disponíveis:</h4>';
            
            updates.forEach((update, index) => {
                const priority_colors = {
                    'high': '#dc2626',
                    'medium': '#f59e0b',
                    'low': '#3b82f6'
                };
                
                const priority_labels = {
                    'high': 'Alta Prioridade',
                    'medium': 'Média Prioridade',
                    'low': 'Baixa Prioridade'
                };
                
                html += `
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 6px; padding: 15px; margin-bottom: 10px;">
                        <div style="display: flex; align-items: start; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 24px;">📦</span>
                            <div style="flex: 1;">
                                <strong style="color: #1f2937; font-size: 16px;">${update.name}</strong>
                                <span style="background: ${priority_colors[update.priority]}; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 10px;">
                                    ${priority_labels[update.priority]}
                                </span>
                                <p style="color: #6b7280; margin: 8px 0 0 0; font-size: 14px;">${update.description}</p>
                            </div>
                        </div>
                        
                        <div style="background: #f9fafb; padding: 10px; border-radius: 4px; font-size: 13px;">
                            <strong style="color: #4b5563;">Tabelas afetadas:</strong> 
                            <span style="color: #6b7280;">${update.tables_affected.join(', ')}</span>
                        </div>
                    </div>
                `;
            });
            
            // Detalhes técnicos (colapsável)
            if (missingColumns.length > 0) {
                html += `
                    <details style="margin-top: 15px;">
                        <summary style="cursor: pointer; padding: 10px; background: #f3f4f6; border-radius: 6px; font-weight: 600; color: #4b5563;">
                            🔧 Detalhes Técnicos (${missingColumns.length} alterações)
                        </summary>
                        <div style="margin-top: 10px; padding: 10px; background: #f9fafb; border-radius: 6px; font-family: monospace; font-size: 12px;">
                `;
                
                missingColumns.forEach(col => {
                    html += `
                        <div style="padding: 5px 0; border-bottom: 1px solid #e5e7eb;">
                            <strong>${col.table}</strong>.${col.column} - <span style="color: #6b7280;">${col.definition}</span>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </details>
                `;
            }
            
            listDiv.innerHTML = html;
            listDiv.style.display = 'block';
        }
        
        async function applyUpdates() {
            if (currentUpdates.length === 0) {
                alert('Nenhuma atualização para aplicar');
                return;
            }
            
            const confirmed = confirm(
                `⚠️ ATENÇÃO!\n\n` +
                `Esta ação irá modificar a estrutura do banco de dados.\n\n` +
                `${currentUpdates.length} atualização(ões) será(ão) aplicada(s):\n` +
                currentUpdates.map(u => `• ${u.name}`).join('\n') + '\n\n' +
                `Recomenda-se fazer um backup antes de continuar.\n\n` +
                `Deseja continuar?`
            );
            
            if (!confirmed) return;
            
            const btn = document.getElementById('btnApplyUpdates');
            const resultDiv = document.getElementById('dbCheckResult');
            
            btn.disabled = true;
            btn.innerHTML = '⏳ Aplicando...';
            
            resultDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: white; border-radius: 6px; border-left: 4px solid #3b82f6;">
                    <span style="font-size: 24px;">⏳</span>
                    <span style="color: #6b7280;">Aplicando atualizações no banco de dados...</span>
                </div>
            `;
            
            let successCount = 0;
            let errors = [];
            
            for (const update of currentUpdates) {
                try {
                    const formData = new FormData();
                    formData.append('migration_id', update.id);
                    
                    const response = await fetch('../src/php/apply_migration.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        successCount++;
                    } else {
                        errors.push(`${update.name}: ${result.message}`);
                    }
                } catch (error) {
                    errors.push(`${update.name}: ${error.message}`);
                }
            }
            
            if (errors.length === 0) {
                resultDiv.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #d1fae5; border-radius: 6px; border-left: 4px solid #10b981;">
                        <span style="font-size: 24px;">✅</span>
                        <div style="flex: 1;">
                            <strong style="color: #065f46;">Atualizações Aplicadas!</strong>
                            <p style="color: #047857; margin: 5px 0 0 0; font-size: 14px;">
                                ${successCount} atualização(ões) aplicada(s) com sucesso
                            </p>
                        </div>
                    </div>
                `;
                
                document.getElementById('updatesList').style.display = 'none';
                btn.style.display = 'none';
                
                // Mostrar mensagem de sucesso
                alert('✅ Banco de dados atualizado com sucesso!\n\nAs alterações foram aplicadas. Verificando novamente...');
                
                // Verificar novamente após 2 segundos
                setTimeout(() => {
                    checkDatabase();
                }, 2000);
            } else {
                // Mostrar detalhes dos erros
                const errorDetails = errors.map(e => `• ${e}`).join('\n');
                
                resultDiv.innerHTML = `
                    <div style="display: flex; align-items: start; gap: 10px; padding: 15px; background: #fee2e2; border-radius: 6px; border-left: 4px solid #dc2626;">
                        <span style="font-size: 24px;">❌</span>
                        <div style="flex: 1;">
                            <strong style="color: #991b1b;">Erro ao Aplicar Atualizações</strong>
                            <p style="color: #7f1d1d; margin: 5px 0 0 0; font-size: 14px;">
                                ${successCount} sucesso(s), ${errors.length} erro(s)
                            </p>
                            <details style="margin-top: 10px;">
                                <summary style="cursor: pointer; color: #991b1b; font-weight: 600;">Ver detalhes dos erros</summary>
                                <pre style="background: white; padding: 10px; border-radius: 4px; margin-top: 10px; font-size: 12px; overflow-x: auto; color: #7f1d1d;">${errors.join('\n\n')}</pre>
                            </details>
                        </div>
                    </div>
                `;
                
                btn.disabled = false;
                btn.innerHTML = '⚡ Tentar Novamente';
                
                // Alerta com instruções
                alert(
                    '❌ Erro ao aplicar atualizações\n\n' +
                    'Detalhes:\n' + errorDetails + '\n\n' +
                    'Possíveis soluções:\n' +
                    '1. Verifique se tem permissões no banco\n' +
                    '2. Tente executar manualmente via phpMyAdmin\n' +
                    '3. Verifique os logs do PHP para mais detalhes'
                );
            }
        }
        
        // ========== CONFIGURAÇÕES DO SISTEMA ==========
        
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
