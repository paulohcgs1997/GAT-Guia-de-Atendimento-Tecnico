<?php
include_once("includes.php");
check_login();
check_permission_viewer();



// Departamentos serão carregados via AJAX
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once PROJECT_ROOT . '/src/includes/head_config.php'; ?>
    <link rel="stylesheet" href="../src/css/style.css">
    <title>Dashboard - GAT</title>
</head>

<body>
    <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>

    <main>
        <!-- Loading Overlay -->
        <div id="loadingOverlay" class="loading-overlay hidden">
            <div class="loading-spinner"></div>
            <div class="loading-text">Carregando...</div>
        </div>

        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1>📚 Biblioteca de Conteúdo</h1>
                <p>Selecione um departamento para visualizar serviços e tutoriais</p>
            </div>

            <!-- GRID DE DEPARTAMENTOS -->
            <div id="departamentosView">
                <div class="search-container">
                    <span class="search-icon"></span>
                    <input type="text" 
                           id="searchDeptInput" 
                           class="search-input" 
                           placeholder="Buscar departamentos...">
                </div>
                <div id="searchDeptResults" class="search-results" style="display: none;"></div>
                <div class="dept-grid" id="deptGrid">
                    <!-- Departamentos serão carregados aqui via JavaScript -->
                </div>
            </div>

            <!-- CONTEÚDO DO DEPARTAMENTO -->
            <div id="departamentoContent" style="display: none;">
                <button class="back-to-depts" onclick="backToDepartamentos()">← Voltar aos Departamentos</button>
                
                <div class="content-tabs">
                    <button class="content-tab active" onclick="switchTab('servicos')">🎯 Serviços</button>
                    <button class="content-tab" onclick="switchTab('tutoriais')">📖 Tutoriais</button>
                </div>

                <!-- ABA DE SERVIÇOS -->
                <div id="servicosTab" class="tab-content active">
                    <div id="servicosContainer"></div>
                </div>

                <!-- ABA DE TUTORIAIS -->
                <div id="tutoriaisTab" class="tab-content">
                    <div id="tutoriaisContainer" class="tutoriais-grid"></div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>Sistema de Tutoriais - GAT</p>
    </footer>

    <script>
        let currentDeptId = null;
        let currentTab = 'servicos';
        let allDepartamentos = []; // Armazenar todos os departamentos

        // As variáveis canal, aguardandoConfirmacao, servicoPendente, timeoutConfirmacao 
        // já estão declaradas no serach.js
        
        let botaoEnviando = null;

        // Funções de loading
        function showLoading() {
            document.getElementById('loadingOverlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.add('hidden');
        }

        // Adicionar listener para confirmações do viwer (sem sobrescrever o existente)
        const dashboardChannelHandler = (evento) => {
            if (evento.data.acao === 'confirmacao_recebimento') {
                console.log('✓ Confirmação recebida do viwer para serviço ID:', evento.data.servicoId);
                aguardandoConfirmacao = false;
                servicoPendente = null;
                
                if (botaoEnviando) {
                    botaoEnviando.innerHTML = '✓ Iniciado';
                    botaoEnviando.style.background = '#10b981';
                    
                    setTimeout(() => {
                        botaoEnviando.innerHTML = '▶ Iniciar';
                        botaoEnviando.style.background = '';
                        botaoEnviando = null;
                    }, 2000);
                }
                
                if (timeoutConfirmacao) {
                    clearTimeout(timeoutConfirmacao);
                    timeoutConfirmacao = null;
                }
            }
        };
        
        // Adicionar o listener ao canal
        canal.addEventListener('message', dashboardChannelHandler);

        // Mostrar aviso para departamento vazio
        function showEmptyWarning(event) {
            event.stopPropagation();
            const card = event.currentTarget;
            const deptName = card.dataset.deptName;
            alert(`⚠️ Departamento sem conteúdo\n\nO departamento "${deptName}" ainda não possui serviços ou tutoriais aprovados.`);
        }
        
        // Abrir departamento
        function openDepartamento(cardElement) {
            showLoading();
            
            const deptId = cardElement.dataset.deptId;
            const deptName = cardElement.dataset.deptName;
            const deptLogo = cardElement.dataset.deptLogo;
            const deptSite = cardElement.dataset.deptSite;
            
            console.log('openDepartamento chamado:', deptId, deptName);
            currentDeptId = deptId;
            
            // Esconder departamentos e mostrar conteúdo
            document.getElementById('departamentosView').style.display = 'none';
            document.getElementById('departamentoContent').style.display = 'block';
            
            // Atualizar header com logo, nome e site
            const headerH1 = document.querySelector('.dashboard-header h1');
            const headerP = document.querySelector('.dashboard-header p');
            
            // Construir título com logo
            let titleHTML = '';
            if (deptLogo) {
                titleHTML += `<img src="${escapeHtml(deptLogo)}" alt="${escapeHtml(deptName)}" class="dept-header-logo">`;
            }
            titleHTML += `<span>${escapeHtml(deptName)}</span>`;
            headerH1.innerHTML = titleHTML;
            
            // Atualizar descrição com link do site
            let descHTML = 'Serviços e tutoriais disponíveis neste departamento';
            if (deptSite && deptSite.trim() !== '') {
                descHTML += `<br><a href="${escapeHtml(deptSite)}" target="_blank" class="dept-site-link">🌐 Acessar site do departamento</a>`;
            }
            headerP.innerHTML = descHTML;
            
            // Carregar conteúdo e esconder loading quando ambos terminarem
            let loadedCount = 0;
            const checkLoaded = () => {
                loadedCount++;
                if (loadedCount >= 2) {
                    hideLoading();
                }
            };
            
            loadServicos(deptId, checkLoaded);
            loadTutoriais(deptId, checkLoaded);
        }

        // Voltar aos departamentos
        function backToDepartamentos() {
            document.getElementById('departamentosView').style.display = 'block';
            document.getElementById('departamentoContent').style.display = 'none';
            document.querySelector('.dashboard-header h1').innerHTML = '📚 Biblioteca de Conteúdo';
            document.querySelector('.dashboard-header p').innerHTML = 'Selecione um departamento para visualizar serviços e tutoriais';
            currentDeptId = null;
        }

        // Trocar abas
        function switchTab(tab) {
            currentTab = tab;
            
            document.querySelectorAll('.content-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            if (tab === 'servicos') {
                document.getElementById('servicosTab').classList.add('active');
            } else {
                document.getElementById('tutoriaisTab').classList.add('active');
            }
        }

        // Carregar serviços do departamento
        function loadServicos(deptId, callback) {
            console.log('Carregando serviços do departamento:', deptId);
            fetch('../src/php/get_dept_content.php?dept_id=' + deptId + '&type=servicos')
                .then(response => {
                    console.log('Response serviços:', response.status);
                    return response.json();
                })
                .then(servicos => {
                    console.log('Serviços recebidos:', servicos);
                    const container = document.getElementById('servicosContainer');
                    if (servicos.length === 0) {
                        container.innerHTML = '<div class="empty-state"><p>Nenhum serviço disponível neste departamento.</p></div>';
                        return;
                    }
                    
                    let html = '';
                    servicos.forEach(servico => {
                        html += `
                            <div class="service-card">
                                <div class="service-header">
                                    <div class="service-info">
                                        <h3>🎯 ${escapeHtml(servico.name)}</h3>
                                    </div>
                                    <button class="start-service-btn" 
                                            data-service-id="${servico.id}" 
                                            data-service-name="${escapeHtml(servico.name)}" 
                                            data-blocos="${servico.blocos}"
                                            onclick="startService(this.dataset.serviceId, this.dataset.serviceName, this.dataset.blocos)">
                                        ▶ Iniciar
                                    </button>
                                </div>
                                ${servico.description ? `<div class="service-description">${escapeHtml(servico.description)}</div>` : ''}
                                <div class="service-meta">
                                    <span>📅 ${formatDate(servico.last_modification)}</span>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    if (callback) callback();
                })
                .catch(error => {
                    console.error('Erro ao carregar serviços:', error);
                    document.getElementById('servicosContainer').innerHTML = '<div class="empty-state"><p>Erro ao carregar serviços.</p></div>';
                    if (callback) callback();
                });
        }

        // Carregar tutoriais do departamento
        function loadTutoriais(deptId, callback) {
            console.log('Carregando tutoriais do departamento:', deptId);
            fetch('../src/php/get_dept_content.php?dept_id=' + deptId + '&type=tutoriais')
                .then(response => {
                    console.log('Response tutoriais:', response.status);
                    return response.json();
                })
                .then(tutoriais => {
                    console.log('Tutoriais recebidos:', tutoriais);
                    const container = document.getElementById('tutoriaisContainer');
                    if (tutoriais.length === 0) {
                        container.innerHTML = '<div class="empty-state"><p>Nenhum tutorial disponível neste departamento.</p></div>';
                        return;
                    }
                    
                    let html = '';
                    tutoriais.forEach(tutorial => {
                        html += `
                            <div class="tutorial-card" onclick="window.location.href='viwer_tutorial.php?id=${tutorial.id}'">
                                <div class="tutorial-card-header">
                                    <div class="tutorial-icon">📖</div>
                                    <div class="tutorial-info">
                                        <h3 class="tutorial-name">${escapeHtml(tutorial.name)}</h3>
                                    </div>
                                </div>
                                <div class="tutorial-date">
                                    📅 ${formatDate(tutorial.last_modification)}
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    if (callback) callback();
                })
                .catch(error => {
                    console.error('Erro ao carregar tutoriais:', error);
                    document.getElementById('tutoriaisContainer').innerHTML = '<div class="empty-state"><p>Erro ao carregar tutoriais.</p></div>';
                    if (callback) callback();
                });
        }

        // Função para iniciar serviço
        function startService(serviceId, serviceName, blocosIds) {
            if (!blocosIds || blocosIds.trim() === '') {
                alert('Este serviço não possui tutoriais vinculados.');
                return;
            }

            botaoEnviando = event.target;
            botaoEnviando.innerHTML = '⏳ Iniciando...';
            botaoEnviando.disabled = true;
            
            fetch('../src/php/search_services.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'service_id=' + encodeURIComponent(serviceId)
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.length > 0) {
                    enviarServicoComConfirmacao(data[0]);
                } else {
                    enviarServicoComConfirmacao({
                        id: serviceId,
                        name: serviceName,
                        blocos: blocosIds,
                        description: '',
                        word_keys: ''
                    });
                }
            })
            .catch(error => {
                console.error('Erro ao buscar serviço:', error);
                enviarServicoComConfirmacao({
                    id: serviceId,
                    name: serviceName,
                    blocos: blocosIds,
                    description: '',
                    word_keys: ''
                });
            });
        }
        
        function enviarServicoComConfirmacao(service) {
            aguardandoConfirmacao = true;
            servicoPendente = service;
            
            const dadosServico = {
                acao: 'servico_selecionado',
                dados: {
                    id: service.id,
                    name: service.name,
                    description: service.description || '',
                    word_keys: service.word_keys || ''
                }
            };
            
            canal.postMessage(dadosServico);
            console.log('Serviço enviado:', service.name);
            
            timeoutConfirmacao = setTimeout(() => {
                if (aguardandoConfirmacao && servicoPendente) {
                    console.log('⚠ Nenhuma confirmação recebida. Abrindo viwer.php...');
                    
                    window.open('viwer.php', 'viwer', 'width=477,height=946');
                    
                    let tentativas = 0;
                    const maxTentativas = 5;
                    
                    const intervaloReenvio = setInterval(() => {
                        if (tentativas < maxTentativas && aguardandoConfirmacao) {
                            console.log(`Reenviando serviço (tentativa ${tentativas + 1})...`);
                            canal.postMessage(dadosServico);
                            tentativas++;
                        } else {
                            clearInterval(intervaloReenvio);
                            
                            if (botaoEnviando) {
                                botaoEnviando.innerHTML = '✓ Iniciado';
                                botaoEnviando.style.background = '#10b981';
                                botaoEnviando.disabled = false;
                                
                                setTimeout(() => {
                                    botaoEnviando.innerHTML = '▶ Iniciar';
                                    botaoEnviando.style.background = '';
                                    botaoEnviando = null;
                                }, 2000);
                            }
                            
                            aguardandoConfirmacao = false;
                            servicoPendente = null;
                        }
                    }, 300);
                }
            }, 800);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR');
        }

        function fixLogoPath(logo) {
            if (!logo) return '';
            let path = logo;
            if (path.startsWith('/')) {
                path = path.substring(1);
            }
            if (!path.startsWith('../') && !path.startsWith('http')) {
                path = '../' + path;
            }
            return path;
        }
        
        // Filtrar departamentos
        function filterDepartamentos() {
            const searchInput = document.getElementById('searchDeptInput');
            if (!searchInput) {
                console.error('Input de busca não encontrado');
                return;
            }
            
            const searchTerm = searchInput.value.toLowerCase().trim();
            console.log('Buscando por: "' + searchTerm + '"');
            console.log('Total de departamentos:', allDepartamentos.length);
            
            const resultsDiv = document.getElementById('searchDeptResults');
            
            if (searchTerm === '') {
                // Mostrar todos
                renderDepartamentos(allDepartamentos);
                if (resultsDiv) resultsDiv.style.display = 'none';
                return;
            }
            
            // Filtrar departamentos
            const filtered = allDepartamentos.filter(dept => {
                const match = dept.name.toLowerCase().indexOf(searchTerm) !== -1;
                console.log('Departamento:', dept.name, '- Match:', match);
                return match;
            });
            
            console.log('Departamentos filtrados:', filtered.length);
            
            // Renderizar filtrados
            renderDepartamentos(filtered);
            
            // Mostrar contador
            if (resultsDiv) {
                resultsDiv.style.display = 'block';
                if (filtered.length === 0) {
                    resultsDiv.innerHTML = '🔍 Nenhum departamento encontrado para "' + escapeHtml(searchTerm) + '"';
                } else {
                    resultsDiv.innerHTML = '🔍 <strong>' + filtered.length + '</strong> departamento(s) encontrado(s)';
                }
            }
        }
        
        // Renderizar departamentos no grid
        function renderDepartamentos(departamentos) {
            const grid = document.getElementById('deptGrid');
            if (!grid) return;
            
            if (departamentos.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">🔍</div>
                        <h3 style="color: #374151; margin-bottom: 10px;">Nenhum resultado encontrado</h3>
                        <p>Tente buscar por outro termo.</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            departamentos.forEach(dept => {
                const logoPath = fixLogoPath(dept.logo);
                const isEmpty = (dept.servicos_count == 0 && dept.tutoriais_count == 0);
                const emptyClass = isEmpty ? ' empty' : '';
                const clickHandler = isEmpty ? 'onclick="showEmptyWarning(event)"' : 'onclick="openDepartamento(this)"';
                
                html += `
                    <div class="dept-card${emptyClass}" 
                         data-dept-id="${dept.id}" 
                         data-dept-name="${escapeHtml(dept.name)}"
                         data-dept-logo="${escapeHtml(logoPath)}"
                         data-dept-site="${escapeHtml(dept.logo)}"
                         ${clickHandler}>
                `;
                
                if (isEmpty) {
                    html += '<div class="dept-empty-badge">⚠ Vazio</div>';
                }
                
                if (logoPath) {
                    html += `
                        <img src="${escapeHtml(logoPath)}" 
                             alt="${escapeHtml(dept.name)}" 
                             class="dept-logo" 
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="dept-logo-placeholder" style="display:none;">🏢</div>
                    `;
                } else {
                    html += '<div class="dept-logo-placeholder">🏢</div>';
                }
                
                html += `
                        <div class="dept-name">${escapeHtml(dept.name)}</div>
                        <div class="dept-stats">
                            <div class="dept-stat">
                                <span>🎯</span>
                                <span>${dept.servicos_count} serviço(s)</span>
                            </div>
                            <div class="dept-stat">
                                <span>📖</span>
                                <span>${dept.tutoriais_count} tutorial(is)</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            grid.innerHTML = html;
        }

        // Carregar departamentos via AJAX
        function loadDepartamentos() {
            showLoading();
            
            fetch('../src/php/get_dept_content.php?type=departamentos&show_all=1')
                .then(response => response.json())
                .then(departamentos => {
                    console.log('Departamentos recebidos:', departamentos);
                    
                    // Ordenar: departamentos com conteúdo primeiro, depois vazios
                    departamentos.sort((a, b) => {
                        const aHasContent = (a.servicos_count > 0 || a.tutoriais_count > 0);
                        const bHasContent = (b.servicos_count > 0 || b.tutoriais_count > 0);
                        
                        // Se ambos têm conteúdo ou ambos estão vazios, ordenar por nome
                        if (aHasContent === bHasContent) {
                            return a.name.localeCompare(b.name);
                        }
                        
                        // Departamentos com conteúdo vêm primeiro
                        return bHasContent ? 1 : -1;
                    });
                    
                    // Armazenar globalmente
                    allDepartamentos = departamentos;
                    
                    const grid = document.getElementById('deptGrid');
                    
                    if (departamentos.length === 0) {
                        grid.innerHTML = `
                            <div class="empty-state">
                                <div class="empty-state-icon">📭</div>
                                <h3 style="color: #374151; margin-bottom: 10px;">Nenhum departamento cadastrado</h3>
                                <p>Ainda não há departamentos no sistema.</p>
                            </div>
                        `;
                        hideLoading();
                        return;
                    }
                    
                    // Renderizar todos os departamentos
                    renderDepartamentos(departamentos);
                    hideLoading();
                })
                .catch(error => {
                    console.error('Erro ao carregar departamentos:', error);
                    document.getElementById('deptGrid').innerHTML = '<div class="empty-state"><p>Erro ao carregar departamentos.</p></div>';
                    hideLoading();
                });
        }

        // Carregar departamentos quando a página estiver pronta
        document.addEventListener('DOMContentLoaded', function() {
            loadDepartamentos();
            
            // Configurar busca com debounce
            const searchInput = document.getElementById('searchDeptInput');
            let debounceTimer;
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        filterDepartamentos();
                    }, 300);
                });
            }
            
            // Adicionar scroll horizontal com mouse wheel
            const deptGrid = document.getElementById('deptGrid');
            if (deptGrid) {
                deptGrid.addEventListener('wheel', function(e) {
                    // Prevenir scroll vertical da página
                    if (e.deltaY !== 0) {
                        e.preventDefault();
                        
                        // Scroll horizontal
                        deptGrid.scrollLeft += e.deltaY;
                    }
                }, { passive: false });
            }
        });
    </script>
</body>

</html>
