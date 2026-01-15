<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_viewer();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guia de Atendimento</title>
    <link rel="stylesheet" href="../src/css/style.css">
</head>

<body>
    <div id="container-viwer">
        <div class="loading">Aguardando serviço...</div>
    </div>

    <script>
        const canal = new BroadcastChannel('guia-acoes');
        const container = document.getElementById('container-viwer');
        let servicoAtual = null;
        let todosSteps = []; // Armazenar todos os steps do serviço
        let stepAtualIndex = 0; // Índice do step atual
        let navegacaoPorPerguntas = false; // Flag para indicar se está navegando por perguntas

        // Verificar se foi passado service_id na URL
        const urlParams = new URLSearchParams(window.location.search);
        const serviceIdFromUrl = urlParams.get('service_id');
        
        if (serviceIdFromUrl) {
            // Carregar serviço diretamente da URL
            carregarServicoPorId(serviceIdFromUrl);
        }

        canal.onmessage = (evento) => {
            if (evento.data.acao === 'servico_selecionado') {
                const servico = evento.data.dados;
                servicoAtual = servico;

                // ENVIAR CONFIRMAÇÃO DE RECEBIMENTO IMEDIATAMENTE
                canal.postMessage({
                    acao: 'confirmacao_recebimento',
                    servicoId: servico.id
                });

                console.log('Serviço recebido:', servico);

                // Carregar informações do serviço
                carregarServico(servico);
            }
        };
        
        function carregarServicoPorId(servicoId) {
            container.innerHTML = '<div class="loading">Carregando serviço...</div>';
            
            fetch('../src/php/get_servico_steps.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'servico_id=' + servicoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    container.innerHTML = `<div class="erro-mensagem">${data.error}</div>`;
                    return;
                }

                // Criar objeto de serviço simulado
                servicoAtual = {
                    id: servicoId,
                    name: data.servico_name || 'Serviço',
                    description: data.servico_description || ''
                };

                // Armazenar todos os steps do serviço
                todosSteps = data.steps || [];
                stepAtualIndex = 0;
                navegacaoPorPerguntas = false; // Resetar flag

                // Exibir primeiro step
                if (todosSteps.length > 0) {
                    exibirStepPorIndice(0);
                } else {
                    container.innerHTML = '<div class="erro-mensagem">Nenhum passo encontrado para este serviço.</div>';
                }
            })
            .catch(error => {
                console.error('Erro ao carregar serviço:', error);
                container.innerHTML = '<div class="erro-mensagem">Erro ao carregar o guia de atendimento.</div>';
            });
        }

        function carregarServico(servico) {
            // Mostrar loading
            container.innerHTML = '<div class="loading">Carregando guia...</div>';

            // Buscar dados do servidor
            fetch('../src/php/get_servico_steps.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'servico_id=' + servico.id
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        container.innerHTML = `<div class="erro-mensagem">${data.error}</div>`;
                        return;
                    }

                    // Armazenar todos os steps do serviço
                    todosSteps = data.steps || [];
                    stepAtualIndex = 0;
                    navegacaoPorPerguntas = false; // Resetar flag

                    // Exibir primeiro step
                    if (todosSteps.length > 0) {
                        exibirStepPorIndice(0);
                    } else {
                        container.innerHTML = '<div class="erro-mensagem">Nenhum passo encontrado para este serviço.</div>';
                    }
                })
                .catch(error => {
                    console.error('Erro ao carregar serviço:', error);
                    container.innerHTML = '<div class="erro-mensagem">Erro ao carregar o guia de atendimento.</div>';
                });
        }

        function exibirStepPorIndice(indice) {
            if (indice < 0 || indice >= todosSteps.length) {
                mostrarFinalTutorial();
                return;
            }

            stepAtualIndex = indice;
            navegacaoPorPerguntas = false; // Voltou para navegação sequencial
            const step = todosSteps[indice];
            exibirStep(step, servicoAtual);
        }

        function exibirStep(step, servico) {
            let html = `
                <div class="servico-info">
                    <h3>${servico.name}</h3>
                    <p>${servico.description || ''}</p>
                    <div style="font-size: 13px; color: #6b7280; margin-top: 8px;">
                        📚 Passo ${stepAtualIndex + 1} de ${todosSteps.length}
                    </div>
                </div>
            `;

            // Verificar tipo de mídia
            if (step.src) {
                // Corrigir caminho relativo
                const mediaSrc = step.src.startsWith('http') ? step.src : (step.src.startsWith('../') ? step.src : '../' + step.src);
                
                const src = step.src.toLowerCase();
                const isVideo = src.endsWith('.mp4') || src.endsWith('.webm') || src.endsWith('.ogg');
                const isUrl = src.startsWith('http://') || src.startsWith('https://');
                
                if (isVideo) {
                    // Renderizar vídeo
                    html += `
                        <video controls class="step-image" style="max-width: 100%;">
                            <source src="${mediaSrc}" type="video/${src.split('.').pop()}">
                            Seu navegador não suporta vídeos.
                        </video>
                    `;
                } else if (isUrl) {
                    // Renderizar iframe para URL
                    html += `
                        <iframe src="${mediaSrc}" class="step-image" style="width: 100%; min-height: 500px; border: none;"></iframe>
                    `;
                } else {
                    // Renderizar imagem
                    html += `<img src="${mediaSrc}" alt="${step.name}" class="step-image">`;
                }
            }

            // Conteúdo HTML
            if (step.html) {
                // Corrigir links sem protocolo
                let processedHtml = step.html;
                
                // Encontrar todos os links e adicionar https:// se necessário
                processedHtml = processedHtml.replace(/href=["']([^"']+)["']/gi, function(match, url) {
                    // Se não começa com http://, https://, mailto:, tel:, #, ou /
                    if (!url.match(/^(https?:\/\/|mailto:|tel:|#|\/)/i)) {
                        return `href="https://${url}"`;
                    }
                    return match;
                });
                
                html += `<div class="step-content">${processedHtml}</div>`;
            }

            // Perguntas
            if (step.questions && step.questions.length > 0) {
                html += `
                    <div class="questions-container">
                        <div class="questions-title">O que aconteceu?</div>
                `;

                step.questions.forEach(question => {
                    html += `
                        <button class="question-btn" onclick="proximoStep(${question.proximo})">
                            ${question.text}
                        </button>
                    `;
                });

                html += `</div>`;
            } else if (!navegacaoPorPerguntas) {
                // Se não há perguntas E não está navegando por perguntas, adicionar botão "Próximo"
                html += `
                    <div class="questions-container">
                        <button class="question-btn" onclick="avancarParaProximoStep()" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);">
                            ➡️ Próximo Passo
                        </button>
                    </div>
                `;
            } else {
                // Se está navegando por perguntas mas não há perguntas, considerar como fim
                html += `
                    <div class="questions-container">
                        <div style="padding: 20px; text-align: center; color: #6b7280;">
                            <p style="margin-bottom: 16px;">✅ Passo concluído!</p>
                            <button class="question-btn" onclick="finalizarFluxoPerguntas()" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                🏁 Finalizar Tutorial
                            </button>
                        </div>
                    </div>
                `;
            }

            container.innerHTML = html;
        }

        function avancarParaProximoStep() {
            const proximoIndice = stepAtualIndex + 1;
            
            if (proximoIndice >= todosSteps.length) {
                mostrarFinalTutorial();
            } else {
                exibirStepPorIndice(proximoIndice);
            }
        }

        function proximoStep(stepId) {
            if (!servicoAtual) return;

            // Verificar se deve avançar para o próximo bloco/tutorial
            if (stepId === 'next_block' || stepId === 505 || stepId == '505') {
                // "next_block" significa fim de um fluxo de perguntas, voltar para sequência linear
                navegacaoPorPerguntas = false;
                avancarParaProximoStep();
                return;
            }

            // Marcar que está navegando por perguntas
            navegacaoPorPerguntas = true;

            // Tentar encontrar o step pelo ID nos steps carregados
            const indiceEncontrado = todosSteps.findIndex(s => s.id == stepId);
            
            if (indiceEncontrado !== -1) {
                // Se encontrou o step na lista, exibir por índice (mantém navegação por perguntas)
                stepAtualIndex = indiceEncontrado;
                const step = todosSteps[indiceEncontrado];
                exibirStep(step, servicoAtual);
            } else {
                // Se não encontrou, buscar no servidor (fluxo de perguntas para steps externos)
                container.innerHTML = '<div class="loading">Carregando próximo passo...</div>';

                fetch('../src/php/get_step.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'step_id=' + stepId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            // Se não encontrou o step, voltar para navegação sequencial
                            navegacaoPorPerguntas = false;
                            avancarParaProximoStep();
                            return;
                        }

                        // Continua no modo de navegação por perguntas
                        exibirStep(data, servicoAtual);
                    })
                    .catch(error => {
                        console.error('Erro ao carregar step:', error);
                        // Em caso de erro, voltar para navegação sequencial
                        navegacaoPorPerguntas = false;
                        avancarParaProximoStep();
                    });
            }
        }

        function finalizarFluxoPerguntas() {
            // Finaliza o fluxo de perguntas e mostra tela de conclusão
            navegacaoPorPerguntas = false;
            mostrarFinalTutorial();
        }

        function mostrarFinalTutorial() {
            container.innerHTML = `
                <div style="text-align: center; padding: 60px 20px;">
                    <div style="font-size: 72px; margin-bottom: 20px;">✅</div>
                    <h2 style="color: #10b981; margin-bottom: 10px; font-size: 28px;">Tutorial Concluído!</h2>
                    <p style="color: #6b7280; font-size: 16px; margin-bottom: 30px;">
                        Você chegou ao final deste guia de atendimento.
                    </p>
                    ${servicoAtual ? `
                        <div class="servico-info" style="max-width: 500px; margin: 0 auto 20px auto;">
                            <h3 style="margin: 0 0 5px 0;">Tutorial: ${servicoAtual.name}</h3>
                            <p style="margin: 0; color: #6b7280; font-size: 14px;">Concluído com sucesso</p>
                        </div>
                    ` : ''}
                    <button onclick="window.close()" style="background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; border: none; padding: 12px 32px; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);">
                        ✖️ Fechar
                    </button>
                </div>
            `;
        }
    </script>
</body>

</html>