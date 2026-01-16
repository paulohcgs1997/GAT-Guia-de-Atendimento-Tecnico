<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_creator();

include_once(__DIR__ . '/../src/config/conexao.php');

// Buscar steps para o select
$steps_query = "SELECT id, name FROM steps WHERE active = 1 ORDER BY name";
$steps_result = $mysqli->query($steps_query);
$steps_list = [];
while($step = $steps_result->fetch_assoc()) {
    $steps_list[] = $step;
}

// Buscar departamentos para o select
$dept_query = "SELECT id, name FROM departaments ORDER BY name";
$departamentos = $mysqli->query($dept_query);

// Buscar blocos para o select
$blocos_query = "SELECT id, name FROM blocos WHERE active = 1 ORDER BY name";
$blocos_result = $mysqli->query($blocos_query);
$blocos_list = [];
while($bloco = $blocos_result->fetch_assoc()) {
    $blocos_list[] = $bloco;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Itens Reprovados - Gestão</title>
    <link rel="stylesheet" href="../src/css/style.css">
</head>
<body>
    <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>

    <?php include_once __DIR__ . '/includes/quick_menu.php'; ?>

    <main>
        <div class="reprovados-container">
            <h1>📛 Itens Reprovados</h1>
            <p style="color: #6b7280; margin-bottom: 20px;">Gerencie serviços e tutoriais que foram rejeitados e precisam de correção</p>

            <div class="tabs-container">
                <button class="tab active" data-tab="tutoriais">
                    📚 Tutoriais
                    <span class="tab-badge" id="tutoriaisBadge">0</span>
                </button>
                <button class="tab" data-tab="servicos">
                    🛠️ Serviços
                    <span class="tab-badge" id="servicosBadge">0</span>
                </button>
            </div>

            <div id="tutoriais-tab" class="tab-content active">
                <div id="tutoriaisGrid" class="items-grid">
                    <!-- Tutoriais reprovados serão carregados aqui -->
                </div>
            </div>

            <div id="servicos-tab" class="tab-content">
                <div id="servicosGrid" class="items-grid">
                    <!-- Serviços reprovados serão carregados aqui -->
                </div>
            </div>
        </div>
    </main>

    <!-- Modal para editar Tutorial -->
    <div id="editTutorialModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">✏️ Editar Tutorial</h2>
                <button class="modal-close" onclick="closeEditTutorialModal()">&times;</button>
            </div>
            <form id="editTutorialForm">
                <input type="hidden" id="editTutorialId">
                
                <div class="form-group">
                    <label class="form-label">Nome do Tutorial</label>
                    <input type="text" id="editTutorialName" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Step Vinculado</label>
                    <select id="editTutorialStep" class="form-input" required>
                        <option value="">Selecione um step...</option>
                        <?php foreach($steps_list as $step): ?>
                            <option value="<?php echo $step['id']; ?>"><?php echo htmlspecialchars($step['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo da Rejeição (referência)</label>
                    <textarea id="editTutorialRejection" class="form-input form-textarea" readonly style="background: #fef2f2; color: #7f1d1d;"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditTutorialModal()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-save">💾 Salvar e Reenviar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para editar Serviço -->
    <div id="editServicoModal" class="modal">
        <div class="modal-content" style="max-width: 1000px;">
            <div class="modal-header">
                <h2 class="modal-title">✏️ Editar Serviço</h2>
                <button class="modal-close" onclick="closeEditServicoModal()">&times;</button>
            </div>
            <form id="editServicoForm">
                <input type="hidden" id="editServicoId">
                
                <div class="form-group">
                    <label class="form-label">Nome do Serviço *</label>
                    <input type="text" id="editServicoName" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Departamento *</label>
                    <select id="editServicoDept" class="form-input" required>
                        <option value="">Selecione um departamento</option>
                        <?php foreach ($departamentos as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Descrição</label>
                    <textarea id="editServicoDescription" class="form-input form-textarea" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Blocos Associados (na ordem de execução)</label>
                    <input type="text" id="searchEditBlocos" class="search-input" placeholder="🔍 Buscar bloco..." onkeyup="filterEditBlocos()">
                    <div class="blocos-selection">
                        <div class="blocos-available">
                            <div class="blocos-header">📋 Disponíveis</div>
                            <div id="editBlocosAvailable">
                                <?php foreach ($blocos_list as $bloco): ?>
                                    <div class="bloco-item" data-name="<?= strtolower($bloco['name']) ?>">
                                        <label>
                                            <input type="checkbox" name="blocos[]" value="<?= $bloco['id'] ?>" onchange="updateEditBlocoOrder()">
                                            <?= htmlspecialchars($bloco['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="blocos-ordered">
                            <div class="blocos-header">✓ Selecionados (ordem de execução)</div>
                            <div id="editBlocosOrdered">
                                <p class="empty-message">Selecione blocos à esquerda</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Palavras-chave</label>
                    <input type="text" id="editServicoKeywordsInput" class="form-input" placeholder="Digite e pressione Enter, Espaço ou Vírgula..." onkeydown="handleEditKeywordInput(event)">
                    <input type="hidden" id="editServicoKeywords" name="word_keys">
                    <div id="editKeywordsList" class="keywords-list"></div>
                    <small style="color: #6b7280; font-size: 12px;">As palavras-chave ajudam na busca dos serviços</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Motivo da Rejeição (referência)</label>
                    <textarea id="editServicoRejection" class="form-input form-textarea" readonly style="background: #fef2f2; color: #7f1d1d;"></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditServicoModal()">Cancelar</button>
                    <button type="submit" class="btn-modal btn-save">💾 Salvar e Reenviar</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <script src="../src/js/serach.js"></script>
    <script>
        let editBlocoOrderMap = new Map(); // Armazena a ordem dos blocos
        let editKeywords = []; // Armazena as palavras-chave

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            loadRejectedItems();
        });

        // Tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                const tabName = this.dataset.tab;
                
                // Atualizar tabs
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // Atualizar conteúdo
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.classList.remove('active');
                });
                document.getElementById(tabName + '-tab').classList.add('active');
            });
        });

        // Carregar itens reprovados
        async function loadRejectedItems() {
            try {
                const response = await fetch('../src/php/get_rejected_items.php');
                const data = await response.json();

                if (data.success) {
                    displayTutoriais(data.tutoriais);
                    displayServicos(data.servicos);
                    
                    document.getElementById('tutoriaisBadge').textContent = data.tutoriais.length;
                    document.getElementById('servicosBadge').textContent = data.servicos.length;
                }
            } catch (error) {
                console.error('Erro ao carregar itens:', error);
            }
        }

        function displayTutoriais(tutoriais) {
            const grid = document.getElementById('tutoriaisGrid');
            
            if (tutoriais.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-title">Nenhum tutorial reprovado</div>
                        <div class="empty-state-text">Todos os tutoriais estão aprovados ou pendentes!</div>
                    </div>
                `;
                return;
            }

            grid.innerHTML = tutoriais.map(tutorial => `
                <div class="item-card">
                    <div class="item-header">
                        <div class="item-title">${escapeHtml(tutorial.name)}</div>
                        <div class="item-badge">Reprovado</div>
                    </div>
                    
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 8px;">
                        📍 Step: <strong>${escapeHtml(tutorial.step_name)}</strong>
                    </div>

                    <div class="rejection-info">
                        <div class="rejection-label">Motivo da Rejeição:</div>
                        <div class="rejection-text">${escapeHtml(tutorial.rejection_reason)}</div>
                        <div class="rejection-meta">
                            Rejeitado por: ${escapeHtml(tutorial.rejected_by_name)} • 
                            ${formatDate(tutorial.reject_date)}
                        </div>
                    </div>

                    <div class="item-actions">
                        <button class="btn-action btn-edit" onclick="editTutorial(${tutorial.id})">
                            ✏️ Editar
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteTutorial(${tutorial.id})">
                            🗑️ Excluir
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function displayServicos(servicos) {
            const grid = document.getElementById('servicosGrid');
            
            if (servicos.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state">
                        <div class="empty-state-icon">✅</div>
                        <div class="empty-state-title">Nenhum serviço reprovado</div>
                        <div class="empty-state-text">Todos os serviços estão aprovados ou pendentes!</div>
                    </div>
                `;
                return;
            }

            grid.innerHTML = servicos.map(servico => `
                <div class="item-card">
                    <div class="item-header">
                        <div class="item-title">${escapeHtml(servico.name)}</div>
                        <div class="item-badge">Reprovado</div>
                    </div>
                    
                    <div style="font-size: 13px; color: #6b7280; margin-bottom: 8px;">
                        🏢 Departamento: <strong>${escapeHtml(servico.departamento)}</strong>
                    </div>

                    <div class="rejection-info">
                        <div class="rejection-label">Motivo da Rejeição:</div>
                        <div class="rejection-text">${escapeHtml(servico.rejection_reason)}</div>
                        <div class="rejection-meta">
                            Rejeitado por: ${escapeHtml(servico.rejected_by_name)} • 
                            ${formatDate(servico.reject_date)}
                        </div>
                    </div>

                    <div class="item-actions">
                        <button class="btn-action btn-edit" onclick="editServico(${servico.id})">
                            ✏️ Editar
                        </button>
                        <button class="btn-action btn-delete" onclick="deleteServico(${servico.id})">
                            🗑️ Excluir
                        </button>
                    </div>
                </div>
            `).join('');
        }

        // Editar Tutorial - Redirecionar para gestao_blocos.php
        function editTutorial(id) {
            // Redirecionar para a página de gestão de tutoriais com o ID
            window.location.href = `gestao_blocos.php?edit=${id}`;
        }

        function closeEditTutorialModal() {
            document.getElementById('editTutorialModal').classList.remove('active');
        }

        document.getElementById('editTutorialForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const id = document.getElementById('editTutorialId').value;
            const name = document.getElementById('editTutorialName').value;
            const step = document.getElementById('editTutorialStep').value;

            try {
                const response = await fetch('../src/php/crud_blocos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'update',
                        id: id,
                        name: name,
                        id_step: step,
                        clear_rejection: true
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Tutorial atualizado! Enviado para nova aprovação.');
                    closeEditTutorialModal();
                    loadRejectedItems();
                } else {
                    alert('❌ Erro: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar tutorial');
            }
        });

        // ===== FUNÇÕES PARA GERENCIAR BLOCOS E PALAVRAS-CHAVE =====
        
        // Filtrar blocos na busca
        function filterEditBlocos() {
            const searchTerm = document.getElementById('searchEditBlocos').value.toLowerCase();
            const blocoItems = document.querySelectorAll('#editBlocosAvailable .bloco-item');
            
            blocoItems.forEach(item => {
                const blocoName = item.getAttribute('data-name');
                if (blocoName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Atualizar ordem dos blocos
        function updateEditBlocoOrder() {
            const blocosOrdered = document.getElementById('editBlocosOrdered');
            const checkedBoxes = document.querySelectorAll('#editBlocosAvailable input[name="blocos[]"]:checked');
            
            if (checkedBoxes.length === 0) {
                blocosOrdered.innerHTML = '<p class="empty-message">Selecione blocos à esquerda</p>';
                editBlocoOrderMap.clear();
                return;
            }
            
            // Criar array dos IDs atualmente selecionados
            const currentIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            // Remover blocos desmarcados do map
            for (let [id] of editBlocoOrderMap) {
                if (!currentIds.includes(id)) {
                    editBlocoOrderMap.delete(id);
                }
            }
            
            // Adicionar novos blocos ao final
            currentIds.forEach(id => {
                if (!editBlocoOrderMap.has(id)) {
                    editBlocoOrderMap.set(id, editBlocoOrderMap.size + 1);
                }
            });
            
            // Renumerar para garantir sequência 1, 2, 3...
            const sortedEntries = Array.from(editBlocoOrderMap.entries()).sort((a, b) => a[1] - b[1]);
            editBlocoOrderMap.clear();
            sortedEntries.forEach(([id], index) => {
                editBlocoOrderMap.set(id, index + 1);
            });
            
            // Renderizar lista ordenada
            let html = '';
            sortedEntries.forEach(([blocoId], index) => {
                const checkbox = document.querySelector(`#editBlocosAvailable input[name="blocos[]"][value="${blocoId}"]`);
                const blocoName = checkbox ? checkbox.parentElement.textContent.trim() : 'Desconhecido';
                const orderNum = index + 1;
                
                html += `
                    <div class="ordered-bloco-item" data-id="${blocoId}">
                        <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                            <div class="bloco-order-number">${orderNum}</div>
                            <div>${blocoName}</div>
                        </div>
                        <div class="bloco-order-controls">
                            <button type="button" class="btn-order" onclick="moveEditBlocoUp('${blocoId}')" ${index === 0 ? 'disabled' : ''}>▲</button>
                            <button type="button" class="btn-order" onclick="moveEditBlocoDown('${blocoId}')" ${index === sortedEntries.length - 1 ? 'disabled' : ''}>▼</button>
                        </div>
                    </div>
                `;
            });
            
            blocosOrdered.innerHTML = html;
        }

        function moveEditBlocoUp(blocoId) {
            const items = Array.from(editBlocoOrderMap.entries());
            const index = items.findIndex(([id]) => id === blocoId);
            
            if (index > 0) {
                [items[index], items[index - 1]] = [items[index - 1], items[index]];
                
                editBlocoOrderMap.clear();
                items.forEach(([id], idx) => {
                    editBlocoOrderMap.set(id, idx + 1);
                });
                
                updateEditBlocoOrder();
            }
        }

        function moveEditBlocoDown(blocoId) {
            const items = Array.from(editBlocoOrderMap.entries());
            const index = items.findIndex(([id]) => id === blocoId);
            
            if (index < items.length - 1) {
                [items[index], items[index + 1]] = [items[index + 1], items[index]];
                
                editBlocoOrderMap.clear();
                items.forEach(([id], idx) => {
                    editBlocoOrderMap.set(id, idx + 1);
                });
                
                updateEditBlocoOrder();
            }
        }

        // Gerenciar palavras-chave
        function handleEditKeywordInput(event) {
            const input = event.target;
            const key = event.key;
            
            if (key === 'Enter' || key === ' ' || key === ',') {
                event.preventDefault();
                
                const value = input.value.trim().replace(/,/g, '');
                
                if (value && !editKeywords.includes(value.toLowerCase())) {
                    editKeywords.push(value.toLowerCase());
                    updateEditKeywordsList();
                }
                
                input.value = '';
            }
        }

        function updateEditKeywordsList() {
            const keywordsList = document.getElementById('editKeywordsList');
            const hiddenInput = document.getElementById('editServicoKeywords');
            
            if (editKeywords.length === 0) {
                keywordsList.innerHTML = '<p class="empty-keywords">Nenhuma palavra-chave adicionada</p>';
                hiddenInput.value = '';
                return;
            }
            
            let html = '';
            editKeywords.forEach((keyword, index) => {
                html += `
                    <span class="keyword-tag">
                        ${keyword}
                        <button type="button" class="keyword-remove" onclick="removeEditKeyword(${index})">×</button>
                    </span>
                `;
            });
            
            keywordsList.innerHTML = html;
            
            const formattedKeywords = editKeywords.map(k => `"${k}"`).join(',');
            hiddenInput.value = formattedKeywords;
        }

        function removeEditKeyword(index) {
            editKeywords.splice(index, 1);
            updateEditKeywordsList();
        }

        // ===== EDITAR SERVIÇO =====
        
        // Editar Serviço - Redirecionar para gestao_services.php
        async function editServico(id) {
            // Redirecionar para a página de gestão de serviços com o ID
            window.location.href = `gestao_services.php?edit=${id}`;
        }

        function closeEditServicoModal() {
            document.getElementById('editServicoModal').classList.remove('active');
        }

        document.getElementById('editServicoForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', document.getElementById('editServicoId').value);
            formData.append('name', document.getElementById('editServicoName').value);
            formData.append('description', document.getElementById('editServicoDescription').value);
            formData.append('departamento', document.getElementById('editServicoDept').value);
            formData.append('word_keys', document.getElementById('editServicoKeywords').value);
            formData.append('clear_rejection', 'true');
            
            // Adicionar blocos na ordem correta
            const orderedBlocos = Array.from(editBlocoOrderMap.entries())
                .sort((a, b) => a[1] - b[1])
                .map(([id]) => id);
            
            orderedBlocos.forEach(blocoId => {
                formData.append('blocos[]', blocoId);
            });

            try {
                const response = await fetch('../src/php/crud_services.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Serviço atualizado! Enviado para nova aprovação.');
                    closeEditServicoModal();
                    loadRejectedItems();
                } else {
                    alert('❌ Erro: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar serviço');
            }
        });

        // Deletar Tutorial
        async function deleteTutorial(id) {
            if (!confirm('⚠️ Tem certeza que deseja excluir este tutorial? Esta ação não pode ser desfeita.')) {
                return;
            }

            try {
                const response = await fetch('../src/php/crud_blocos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Tutorial excluído com sucesso!');
                    loadRejectedItems();
                } else {
                    alert('❌ Erro: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir tutorial');
            }
        }

        // Deletar Serviço
        async function deleteServico(id) {
            if (!confirm('⚠️ Tem certeza que deseja excluir este serviço? Esta ação não pode ser desfeita.')) {
                return;
            }

            try {
                const response = await fetch('../src/php/crud_services.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Serviço excluído com sucesso!');
                    loadRejectedItems();
                } else {
                    alert('❌ Erro: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir serviço');
            }
        }

        // Helpers
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('pt-BR') + ' às ' + date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
        }
    </script>
</body>
</html>
