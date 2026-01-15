
    // Criar canal de comunicação entre abas
    const canal = new BroadcastChannel('guia-acoes');
    let aguardandoConfirmacao = false;
    let servicoPendente = null;
    let timeoutConfirmacao = null;
    let elementoEnviando = null;
    
    // Ouvir confirmações do viwer
    canal.onmessage = (evento) => {
        if (evento.data.acao === 'confirmacao_recebimento') {
            console.log('✓ Confirmação recebida do viwer para serviço ID:', evento.data.servicoId);
            aguardandoConfirmacao = false;
            servicoPendente = null;
            
            // Atualizar feedback visual
            if (elementoEnviando) {
                elementoEnviando.classList.remove('enviando');
                elementoEnviando.classList.add('enviado');
                
                // Fechar busca após mostrar sucesso
                setTimeout(() => {
                    document.getElementById('searchResults').style.display = 'none';
                    document.getElementById('searchInput').value = '';
                    elementoEnviando = null;
                }, 1000);
            }
            
            // Limpar timeout
            if (timeoutConfirmacao) {
                clearTimeout(timeoutConfirmacao);
                timeoutConfirmacao = null;
            }
        }
    };
    
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();

            // Limpar timeout anterior
            clearTimeout(searchTimeout);

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            // Aguardar 300ms após parar de digitar
            searchTimeout = setTimeout(() => {
                searchServices(query);
            }, 300);
        });

        // Fechar resultados e limpar pesquisa ao clicar fora
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
                searchInput.value = '';
            }
        });
    });

    function searchServices(query) {
        const searchResults = document.getElementById('searchResults');
        const searchLoading = document.querySelector('.search-loading');

        // Mostrar loading
        searchLoading.classList.add('active');
        searchResults.style.display = 'none';

        fetch('../src/php/search_services.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'query=' + encodeURIComponent(query)
            })
            .then(response => response.json())
            .then(data => {
                searchLoading.classList.remove('active');
                displayResults(data);
            })
            .catch(error => {
                searchLoading.classList.remove('active');
                console.error('Erro na busca:', error);
                searchResults.innerHTML = '<div class="no-results">Erro ao buscar</div>';
                searchResults.style.display = 'block';
            });
    }

    function displayResults(results) {
        const searchResults = document.getElementById('searchResults');

        if (results.length === 0) {
            searchResults.innerHTML = '<div class="no-results">Nenhum serviço encontrado</div>';
            searchResults.style.display = 'block';
            return;
        }

        let html = '';
        results.forEach(service => {
            // Adicionar ../ ao caminho se necessário
            let logoPath = service.dept_logo;
            if (logoPath && !logoPath.startsWith('http') && !logoPath.startsWith('../')) {
                logoPath = '../' + logoPath;
            }
            
            const logoHtml = logoPath 
                ? `<img src="${logoPath}" alt="${service.dept_name || 'Logo'}" class="dept-logo">` 
                : '<div class="dept-logo-placeholder">🏢</div>';
            
            html += `
            <div class="search-result-item" onclick='selectService(${JSON.stringify(service)}, event)'>
                ${logoHtml}
                <div class="search-result-content">
                    <h4>${service.name}</h4>
                    <p>${service.description || ''}</p>
                    ${service.dept_name ? `<span class="dept-tag">${service.dept_name}</span>` : ''}
                </div>
            </div>
        `;
        });

        searchResults.innerHTML = html;
        searchResults.style.display = 'block';
    }

    function selectService(service, event) {
        // Adicionar feedback visual no item clicado
        const itemClicado = event ? event.currentTarget : null;
        if (itemClicado) {
            elementoEnviando = itemClicado;
            itemClicado.classList.add('enviando');
        }
        
        // Enviar serviço (não fecha mais aqui, só fecha ao receber confirmação)
        enviarServicoComConfirmacao(service);
    }
    
    function enviarServicoComConfirmacao(service) {
        // Marcar que está aguardando confirmação
        aguardandoConfirmacao = true;
        servicoPendente = service;
        
        const dadosServico = {
            acao: 'servico_selecionado',
            dados: {
                id: service.id,
                name: service.name,
                description: service.description,
                word_keys: service.word_keys
            }
        };
        
        // Disparar ação via BroadcastChannel
        canal.postMessage(dadosServico);
        
        console.log('Serviço enviado:', service.name);
        
        // Aguardar confirmação por 800ms
        timeoutConfirmacao = setTimeout(() => {
            if (aguardandoConfirmacao && servicoPendente) {
                console.log('⚠ Nenhuma confirmação recebida. Abrindo viwer.php...');
                
                // Abrir viwer.php em nova janela
                const novaJanela = window.open('viwer.php', 'viwer', 'width=477,height=946');
                
                // Reenviar múltiplas vezes para garantir recebimento
                let tentativas = 0;
                const maxTentativas = 5;
                
                const intervaloReenvio = setInterval(() => {
                    if (tentativas < maxTentativas && aguardandoConfirmacao) {
                        console.log(`Reenviando serviço (tentativa ${tentativas + 1})...`);
                        canal.postMessage(dadosServico);
                        tentativas++;
                    } else {
                        clearInterval(intervaloReenvio);
                        
                        // Marcar como enviado após tentativas
                        if (elementoEnviando) {
                            elementoEnviando.classList.remove('enviando');
                            elementoEnviando.classList.add('enviado');
                            
                            setTimeout(() => {
                                document.getElementById('searchResults').style.display = 'none';
                                document.getElementById('searchInput').value = '';
                                elementoEnviando = null;
                            }, 1000);
                        }
                        
                        aguardandoConfirmacao = false;
                        servicoPendente = null;
                    }
                }, 300); // Reenvia a cada 300ms
            }
        }, 800); // Timeout de 800ms
    }
