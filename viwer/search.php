<?php
session_start();

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

include '../src/includes/head_config.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Busca: <?php echo htmlspecialchars($searchQuery); ?> - <?php echo htmlspecialchars($systemName ?? 'Sistema'); ?></title>
    <link rel="stylesheet" href="../src/css/style.css">
</head>
<body>
    <?php include '../src/includes/header.php'; ?>

    <main class="search-page-main">
        <div class="container">
            <div id="resultsContainer">
                <?php if (empty($searchQuery)): ?>
                    <div class="search-empty-state">
                        <div class="search-empty-icon">🔍</div>
                        <h2>Digite algo para buscar</h2>
                        <p class="text-muted">Encontre serviços e tutoriais rapidamente</p>
                    </div>
                <?php else: ?>
                    <div class="search-loading">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        <p class="mt-3">Buscando por "<?php echo htmlspecialchars($searchQuery); ?>"...</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        const searchQuery = <?php echo json_encode($searchQuery); ?>;

        if (searchQuery && searchQuery.trim() !== '') {
            performSearch(searchQuery);
        }

        function performSearch(query) {
            fetch('../src/php/search_services.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'query=' + encodeURIComponent(query)
            })
            .then(response => response.json())
            .then(data => {
                displaySearchResults(data, query);
            })
            .catch(error => {
                console.error('Erro na busca:', error);
                document.getElementById('resultsContainer').innerHTML = `
                    <div class="search-empty-state">
                        <div class="search-empty-icon text-danger">❌</div>
                        <h2>Erro ao buscar</h2>
                        <p class="text-muted">Tente novamente mais tarde</p>
                    </div>
                `;
            });
        }

        function displaySearchResults(results, query) {
            const container = document.getElementById('resultsContainer');
            
            if (results.length === 0) {
                container.innerHTML = `
                    <div class="search-empty-state">
                        <div class="search-empty-icon">🔍</div>
                        <h2>Nenhum resultado encontrado</h2>
                        <p class="text-muted mb-4">Sua busca por "<strong>${escapeHtml(query)}</strong>" não retornou resultados.</p>
                        <div class="card border-0 shadow-sm mx-auto" style="max-width: 450px;">
                            <div class="card-body">
                                <h5 class="card-title text-primary mb-3"><i class="bi bi-lightbulb"></i> Sugestões:</h5>
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Verifique a ortografia das palavras</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Tente palavras-chave diferentes</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Use termos mais gerais</li>
                                    <li><i class="bi bi-check-circle-fill text-success me-2"></i>Tente usar sinônimos</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                `;
                return;
            }

            let html = `<div class="text-muted small mb-3">Aproximadamente ${results.length} resultado${results.length !== 1 ? 's' : ''} encontrado${results.length !== 1 ? 's' : ''}</div>`;
            html += '<div class="search-results-list">';

            results.forEach(service => {
                // Ajustar caminho do logo
                let logoPath = service.dept_logo;
                if (logoPath && !logoPath.startsWith('http') && !logoPath.startsWith('../')) {
                    logoPath = '../' + logoPath;
                }

                const logoHtml = logoPath 
                    ? `<img src="${logoPath}" alt="${escapeHtml(service.dept_name || 'Logo')}" class="img-fluid" onerror="this.parentElement.innerHTML='<div class=\\'search-result-logo-placeholder\\'>🏢</div>'">`
                    : '<div class="search-result-logo-placeholder">🏢</div>';

                // Extrair tags das word_keys
                const tags = service.word_keys ? service.word_keys.split(',').slice(0, 5) : [];
                const tagsHtml = tags.map(tag => 
                    `<span class="badge bg-light text-secondary me-1 mb-1">${escapeHtml(tag.trim())}</span>`
                ).join('');

                html += `
                    <div class="card border-0 shadow-sm mb-3 search-result-card" onclick="openService(${service.id}, '${escapeHtml(service.name).replace(/'/g, "\\'")}')">
                        <div class="card-body p-3">
                            <div class="d-flex gap-3">
                                <div class="search-result-logo flex-shrink-0">
                                    ${logoHtml}
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="search-result-title mb-1">${escapeHtml(service.name)}</h5>
                                    ${service.dept_name ? `
                                        <div class="text-success small mb-2">${escapeHtml(service.dept_name)}</div>
                                    ` : ''}
                                    ${service.description ? `
                                        <p class="text-secondary small mb-2">${escapeHtml(service.description)}</p>
                                    ` : ''}
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        ${service.dept_name ? `
                                            <span class="badge text-bg-primary">
                                                <i class="bi bi-building"></i> ${escapeHtml(service.dept_name)}
                                            </span>
                                        ` : ''}
                                        ${tagsHtml}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function openService(serviceId, serviceName) {
            // Criar canal de comunicação
            const canal = new BroadcastChannel('guia-acoes');
            
            const dadosServico = {
                acao: 'servico_selecionado',
                dados: {
                    id: serviceId,
                    name: serviceName
                }
            };
            
            // Tentar enviar para aba aberta
            canal.postMessage(dadosServico);
            
            // Aguardar confirmação por 800ms
            let confirmado = false;
            
            canal.onmessage = (evento) => {
                if (evento.data.acao === 'confirmacao_recebimento') {
                    confirmado = true;
                }
            };
            
            setTimeout(() => {
                if (!confirmado) {
                    // Abrir nova janela
                    window.open('viwer.php', 'viwer', 'width=477,height=946');
                    
                    // Reenviar múltiplas vezes
                    let tentativas = 0;
                    const intervalo = setInterval(() => {
                        if (tentativas < 5) {
                            canal.postMessage(dadosServico);
                            tentativas++;
                        } else {
                            clearInterval(intervalo);
                        }
                    }, 300);
                }
            }, 800);
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
    </script>
</body>
</html>
