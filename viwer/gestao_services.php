<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_gestor();

// Conectar ao banco de dados
require_once(__DIR__ . '/../src/config/conexao.php');

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

// Buscar serviços
$services_query = "SELECT s.*, d.name as dept_name 
                   FROM services s 
                   LEFT JOIN departaments d ON s.departamento = d.id 
                   WHERE s.active = 1 
                   ORDER BY s.last_modification DESC";
$services = $mysqli->query($services_query);

// Verificar se campo status existe, se não, usar fallback
$status_field_exists = true;
$test_query = $mysqli->query("SHOW COLUMNS FROM services LIKE 'status'");
if ($test_query->num_rows == 0) {
    $status_field_exists = false;
}
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

    <!-- Loading Overlay -->
    <div class="page-loading-overlay" id="pageLoadingOverlay">
        <div class="page-loading-spinner"></div>
        <div class="page-loading-text">Carregando serviço...</div>
        <div class="page-loading-subtext">Aguarde enquanto preparamos a edição</div>
    </div>

    <main>
        <div class="gestao-container">
            <div class="page-header">
                <h1>🛠️ Gestão de Serviços</h1>
                <button class="btn-primary" onclick="openModal()">+ Novo Serviço</button>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Descrição</th>
                            <th>Departamento</th>
                            <th>Blocos</th>
                            <th>Status</th>
                            <th>Última Modificação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="servicesTableBody">
                        <?php while($service = $services->fetch_assoc()): 
                            $hasRejection = !empty($service['rejection_reason']);
                            
                            // Determinar status
                            $status = 'draft';
                            if ($status_field_exists && isset($service['status'])) {
                                $status = $service['status'];
                            } else {
                                // Fallback para sistemas sem campo status
                                if ($hasRejection) $status = 'rejected';
                                elseif ($service['accept']) $status = 'approved';
                                else $status = 'pending';
                            }
                        ?>
                        <tr>
                            <td><?= $service['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($service['name']) ?>
                                <?php if ($hasRejection): ?>
                                    <br>
                                    <div style="margin-top: 8px; padding: 8px; background: #fee2e2; border-left: 3px solid #dc2626; border-radius: 4px;">
                                        <div style="font-weight: 600; color: #dc2626; font-size: 12px; margin-bottom: 4px;">❌ REJEITADO</div>
                                        <div style="font-size: 11px; color: #991b1b; margin-bottom: 4px;"><?= htmlspecialchars(substr($service['rejection_reason'], 0, 80)) ?><?= strlen($service['rejection_reason']) > 80 ? '...' : '' ?></div>
                                        <span onclick="showRejectionReason('<?= addslashes(htmlspecialchars($service['rejection_reason'])) ?>', '<?= date('d/m/Y H:i', strtotime($service['reject_date'])) ?>')" style="color: #dc2626; cursor: pointer; font-size: 11px; text-decoration: underline; font-weight: 600;">
                                            📋 Ver motivo completo
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="description-cell"><?= htmlspecialchars($service['description']) ?></td>
                            <td><?= htmlspecialchars($service['dept_name']) ?></td>
                            <td><?= htmlspecialchars($service['blocos'] ?? '-') ?></td>
                            <td>
                                <?php 
                                // Badges de status
                                $status_badges = [
                                    'draft' => ['icon' => '📝', 'text' => 'Rascunho', 'bg' => '#f3f4f6', 'color' => '#6b7280', 'border' => '#9ca3af'],
                                    'pending' => ['icon' => '⏳', 'text' => 'Em Análise', 'bg' => '#fef3c7', 'color' => '#d97706', 'border' => '#f59e0b'],
                                    'approved' => ['icon' => '✓', 'text' => 'Aprovado', 'bg' => '#d1fae5', 'color' => '#059669', 'border' => '#10b981'],
                                    'rejected' => ['icon' => '❌', 'text' => 'Rejeitado', 'bg' => '#fee2e2', 'color' => '#dc2626', 'border' => '#dc2626']
                                ];
                                $badge = $status_badges[$status];
                                ?>
                                <span class="status-badge" style="background: <?= $badge['bg'] ?>; color: <?= $badge['color'] ?>; border: 2px solid <?= $badge['border'] ?>; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block;">
                                    <?= $badge['icon'] ?> <?= $badge['text'] ?>
                                </span>
                                <br>
                                <?php if ($status === 'pending' && $_SESSION['perfil'] == '1'): ?>
                                    <button class="btn-icon btn-approve" onclick="approveService(<?= $service['id'] ?>, event)" title="Aprovar" style="background: #10b981; color: white; margin-top: 5px; padding: 4px 8px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">✓ Aprovar</button>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($service['last_modification'])) ?></td>
                            <td class="actions-cell">
                                <button class="btn-icon btn-edit" onclick='editService(<?= json_encode($service) ?>)' title="Editar">✏️</button>
                                <?php if (in_array($status, ['draft', 'rejected'])): ?>
                                    <button class="btn-icon btn-send" onclick="sendToReview('service', <?= $service['id'] ?>, event)" title="Enviar para Análise" style="background: #3b82f6; color: white; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin-left: 3px;">📤 Enviar</button>
                                <?php endif; ?>
                                <button class="btn-icon btn-delete" onclick="deleteService(<?= $service['id'] ?>)" title="Excluir">🗑️</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Cadastro/Edição -->
    <div class="modal-overlay" id="serviceModal">
        <div class="modal-large">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Serviço</h2>
                <button class="btn-close" onclick="closeModal()">×</button>
            </div>
            
            <!-- Área de notificação dentro do modal -->
            <div id="modalNotification" class="modal-notification"></div>
            
            <form id="serviceForm" onsubmit="saveService(event)">
                <input type="hidden" id="serviceId" name="id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="serviceName">Nome do Serviço *</label>
                        <input type="text" id="serviceName" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="serviceDept">Departamento *</label>
                        <select id="serviceDept" name="departamento" required>
                            <option value="">Selecione...</option>
                            <?php 
                            $departamentos->data_seek(0);
                            while($dept = $departamentos->fetch_assoc()): 
                            ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="serviceDescription">Descrição</label>
                    <textarea id="serviceDescription" name="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label>Blocos Associados (na ordem de execução)</label>
                    <input type="text" id="searchBlocos" placeholder="🔍 Buscar bloco..." onkeyup="filterBlocos()">
                    <div class="blocos-container">
                        <div class="blocos-available">
                            <h4>Blocos Disponíveis</h4>
                            <div class="checkbox-group" id="blocosCheckboxes">
                                <?php foreach($blocos_list as $bloco): ?>
                                <div class="bloco-item" data-id="<?= $bloco['id'] ?>" data-name="<?= strtolower(htmlspecialchars($bloco['name'])) ?>">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="blocos[]" value="<?= $bloco['id'] ?>" onchange="updateBlocoOrder()">
                                        <?= htmlspecialchars($bloco['name']) ?>
                                    </label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="blocos-selected">
                            <h4>Ordem de Execução</h4>
                            <div id="blocosOrdered" class="blocos-ordered-list">
                                <p class="empty-message">Selecione blocos à esquerda</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="serviceKeywords">Palavras-chave</label>
                    <input type="text" id="serviceKeywordsInput" placeholder="Digite e pressione Enter, Espaço ou Vírgula..." onkeydown="handleKeywordInput(event)">
                    <input type="hidden" id="serviceKeywords" name="word_keys">
                    <div id="keywordsList" class="keywords-list"></div>
                    <small>As palavras-chave ajudam na busca dos serviços</small>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <!-- Modal de Alerta Customizado -->
    <div class="custom-alert-overlay" id="customAlertOverlay">
        <div class="custom-alert-modal">
            <div class="custom-alert-icon" id="customAlertIcon"></div>
            <div class="custom-alert-title" id="customAlertTitle"></div>
            <div class="custom-alert-message" id="customAlertMessage"></div>
            <div class="custom-alert-buttons">
                <button class="custom-alert-btn" onclick="closeCustomAlert()">OK</button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação Customizado -->
    <div class="custom-alert-overlay" id="customConfirmOverlay">
        <div class="custom-alert-modal">
            <div class="custom-alert-icon">⚠️</div>
            <div class="custom-alert-title" id="customConfirmTitle">Confirmar</div>
            <div class="custom-alert-message" id="customConfirmMessage"></div>
            <div class="custom-alert-buttons">
                <button class="custom-alert-btn-secondary" id="customConfirmCancel" onclick="closeCustomConfirm(false)">Cancelar</button>
                <button class="custom-alert-btn" id="customConfirmOk" onclick="closeCustomConfirm(true)">Confirmar</button>
            </div>
        </div>
    </div>

    <!-- Modal de Motivo de Rejeição -->
    <div class="custom-alert-overlay" id="rejectionReasonModal" style="display: none;">
        <div class="custom-alert-modal" style="max-width: 600px;">
            <div class="custom-alert-icon">❌</div>
            <div class="custom-alert-title">Motivo da Rejeição</div>
            <div class="custom-alert-message" id="rejectionReasonContent" style="text-align: left; white-space: pre-wrap; background: #fef2f2; padding: 16px; border-radius: 8px; border-left: 4px solid #dc2626; margin: 16px 0;"></div>
            <div style="font-size: 12px; color: #6b7280; margin-bottom: 16px;" id="rejectionDate"></div>
            <div class="custom-alert-buttons">
                <button class="custom-alert-btn" onclick="closeRejectionModal()">Fechar</button>
            </div>
        </div>
    </div>

    <script>
        let blocoOrderMap = new Map(); // Armazena a ordem dos blocos
        let keywords = []; // Armazena as palavras-chave
        let confirmResolve = null;

        // ========== MODAL DE ALERTA CUSTOMIZADO ==========
        function showAlert(message, type = 'info', title = '') {
            const overlay = document.getElementById('customAlertOverlay');
            const icon = document.getElementById('customAlertIcon');
            const titleEl = document.getElementById('customAlertTitle');
            const messageEl = document.getElementById('customAlertMessage');
            
            const types = {
                success: { icon: '✅', title: 'Sucesso!', color: '#10b981' },
                error: { icon: '❌', title: 'Erro', color: '#ef4444' },
                warning: { icon: '⚠️', title: 'Atenção', color: '#f59e0b' },
                info: { icon: 'ℹ️', title: 'Informação', color: '#3b82f6' }
            };
            
            const config = types[type] || types.info;
            icon.textContent = config.icon;
            icon.style.color = config.color;
            titleEl.textContent = title || config.title;
            messageEl.textContent = message;
            
            overlay.classList.add('active');
        }
        
        function closeCustomAlert() {
            document.getElementById('customAlertOverlay').classList.remove('active');
        }
        
        function showConfirm(message, title = 'Confirmar') {
            return new Promise((resolve) => {
                confirmResolve = resolve;
                document.getElementById('customConfirmTitle').textContent = title;
                document.getElementById('customConfirmMessage').textContent = message;
                document.getElementById('customConfirmOverlay').classList.add('active');
            });
        }
        
        function closeCustomConfirm(result) {
            document.getElementById('customConfirmOverlay').classList.remove('active');
            if (confirmResolve) {
                confirmResolve(result);
                confirmResolve = null;
            }
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('customAlertOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'customAlertOverlay') closeCustomAlert();
        });
        
        document.getElementById('customConfirmOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'customConfirmOverlay') closeCustomConfirm(false);
        });

        // ========== RECARREGAR LISTA DE SERVIÇOS SEM RELOAD ==========
        async function reloadServicesList() {
            try {
                const response = await fetch('gestao_services.php', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const html = await response.text();
                
                // Criar elemento temporário para parsear o HTML
                const temp = document.createElement('div');
                temp.innerHTML = html;
                
                // Extrair apenas o tbody
                const newTbody = temp.querySelector('#servicesTableBody');
                if (newTbody) {
                    document.getElementById('servicesTableBody').innerHTML = newTbody.innerHTML;
                }
            } catch (error) {
                console.error('Erro ao recarregar lista:', error);
            }
        }

        // Modal de motivo de rejeição
        function showRejectionReason(reason, date) {
            document.getElementById('rejectionReasonContent').textContent = reason;
            document.getElementById('rejectionDate').textContent = 'Rejeitado em: ' + date;
            document.getElementById('rejectionReasonModal').style.display = 'flex';
        }

        function closeRejectionModal() {
            document.getElementById('rejectionReasonModal').style.display = 'none';
        }

        function showModalNotification(message, type = 'success') {
            const notification = document.getElementById('modalNotification');
            notification.textContent = message;
            notification.className = `modal-notification ${type} active`;
            
            // Ocultar automaticamente após 3 segundos
            setTimeout(() => {
                notification.classList.remove('active');
            }, 3000);
        }

        function handleKeywordInput(event) {
            const input = event.target;
            const key = event.key;
            
            // Verificar se pressionou Enter, Espaço ou Vírgula
            if (key === 'Enter' || key === ' ' || key === ',') {
                event.preventDefault();
                
                const value = input.value.trim().replace(/,/g, '');
                
                if (value && !keywords.includes(value.toLowerCase())) {
                    keywords.push(value.toLowerCase());
                    updateKeywordsList();
                }
                
                input.value = '';
            }
        }

        function updateKeywordsList() {
            const keywordsList = document.getElementById('keywordsList');
            const hiddenInput = document.getElementById('serviceKeywords');
            
            if (keywords.length === 0) {
                keywordsList.innerHTML = '<p class="empty-keywords">Nenhuma palavra-chave adicionada</p>';
                hiddenInput.value = '';
                return;
            }
            
            let html = '';
            keywords.forEach((keyword, index) => {
                html += `
                    <span class="keyword-tag">
                        ${keyword}
                        <button type="button" class="remove-keyword" onclick="removeKeyword(${index})" title="Remover">×</button>
                    </span>
                `;
            });
            
            keywordsList.innerHTML = html;
            
            // Salvar com aspas duplas em cada palavra
            const formattedKeywords = keywords.map(k => `"${k}"`).join(',');
            hiddenInput.value = formattedKeywords;
        }

        function removeKeyword(index) {
            keywords.splice(index, 1);
            updateKeywordsList();
        }

        function filterBlocos() {
            const searchTerm = document.getElementById('searchBlocos').value.toLowerCase();
            const blocoItems = document.querySelectorAll('.bloco-item');
            
            blocoItems.forEach(item => {
                const blocoName = item.getAttribute('data-name');
                if (blocoName.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function updateBlocoOrder() {
            const blocosOrdered = document.getElementById('blocosOrdered');
            const checkedBoxes = document.querySelectorAll('input[name="blocos[]"]:checked');
            
            if (checkedBoxes.length === 0) {
                blocosOrdered.innerHTML = '<p class="empty-message">Selecione blocos à esquerda</p>';
                blocoOrderMap.clear();
                return;
            }
            
            // Criar array dos IDs atualmente selecionados
            const currentIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            // Remover blocos desmarcados do map
            for (let [id] of blocoOrderMap) {
                if (!currentIds.includes(id)) {
                    blocoOrderMap.delete(id);
                }
            }
            
            // Adicionar novos blocos ao final
            currentIds.forEach(id => {
                if (!blocoOrderMap.has(id)) {
                    blocoOrderMap.set(id, blocoOrderMap.size + 1);
                }
            });
            
            // Renumerar para garantir sequência 1, 2, 3...
            const sortedEntries = Array.from(blocoOrderMap.entries()).sort((a, b) => a[1] - b[1]);
            blocoOrderMap.clear();
            sortedEntries.forEach(([id], index) => {
                blocoOrderMap.set(id, index + 1);
            });
            
            // Renderizar lista ordenada
            let html = '';
            sortedEntries.forEach(([blocoId], index) => {
                const checkbox = document.querySelector(`input[name="blocos[]"][value="${blocoId}"]`);
                const blocoName = checkbox ? checkbox.parentElement.textContent.trim() : 'Desconhecido';
                const orderNum = index + 1;
                
                html += `
                    <div class="ordered-bloco-item" data-id="${blocoId}">
                        <span class="order-number">${orderNum}</span>
                        <span class="bloco-name">${blocoName}</span>
                        <div class="order-controls">
                            <button type="button" class="btn-order" onclick="moveBlocoUp('${blocoId}')" title="Mover para cima">▲</button>
                            <button type="button" class="btn-order" onclick="moveBlocoDown('${blocoId}')" title="Mover para baixo">▼</button>
                        </div>
                    </div>
                `;
            });
            
            blocosOrdered.innerHTML = html;
        }

        function moveBlocoUp(blocoId) {
            const items = Array.from(blocoOrderMap.entries());
            const index = items.findIndex(([id]) => id === blocoId);
            
            if (index > 0) {
                const currentElement = document.querySelector(`.ordered-bloco-item[data-id="${blocoId}"]`);
                const previousElement = currentElement.previousElementSibling;
                
                if (!previousElement) return;
                
                // Calcular altura para animação
                const currentRect = currentElement.getBoundingClientRect();
                const previousRect = previousElement.getBoundingClientRect();
                const distance = previousRect.top - currentRect.top;
                
                // Aplicar animação
                currentElement.style.transform = `translateY(${distance}px)`;
                previousElement.style.transform = `translateY(${-distance}px)`;
                
                currentElement.classList.add('swapping');
                previousElement.classList.add('swapping');
                
                // Trocar posições no map
                [items[index], items[index - 1]] = [items[index - 1], items[index]];
                
                blocoOrderMap.clear();
                items.forEach(([id], idx) => {
                    blocoOrderMap.set(id, idx + 1);
                });
                
                // Atualizar após animação
                setTimeout(() => {
                    currentElement.style.transform = '';
                    previousElement.style.transform = '';
                    currentElement.classList.remove('swapping');
                    previousElement.classList.remove('swapping');
                    updateBlocoOrder();
                }, 400);
            }
        }

        function moveBlocoDown(blocoId) {
            const items = Array.from(blocoOrderMap.entries());
            const index = items.findIndex(([id]) => id === blocoId);
            
            if (index < items.length - 1) {
                const currentElement = document.querySelector(`.ordered-bloco-item[data-id="${blocoId}"]`);
                const nextElement = currentElement.nextElementSibling;
                
                if (!nextElement) return;
                
                // Calcular altura para animação
                const currentRect = currentElement.getBoundingClientRect();
                const nextRect = nextElement.getBoundingClientRect();
                const distance = nextRect.bottom - currentRect.bottom;
                
                // Aplicar animação
                currentElement.style.transform = `translateY(${distance}px)`;
                nextElement.style.transform = `translateY(${-distance}px)`;
                
                currentElement.classList.add('swapping');
                nextElement.classList.add('swapping');
                
                // Trocar posições no map
                [items[index], items[index + 1]] = [items[index + 1], items[index]];
                
                blocoOrderMap.clear();
                items.forEach(([id], idx) => {
                    blocoOrderMap.set(id, idx + 1);
                });
                
                // Atualizar após animação
                setTimeout(() => {
                    currentElement.style.transform = '';
                    nextElement.style.transform = '';
                    currentElement.classList.remove('swapping');
                    nextElement.classList.remove('swapping');
                    updateBlocoOrder();
                }, 400);
            }
        }

        function openModal() {
            // Limpar flag de serviço rejeitado ao abrir modal para novo serviço
            window.isEditingRejectedService = false;
            
            document.getElementById('modalTitle').textContent = 'Novo Serviço';
            document.getElementById('serviceForm').reset();
            document.getElementById('serviceId').value = '';
            blocoOrderMap.clear();
            
            // Limpar palavras-chave
            keywords = [];
            document.getElementById('serviceKeywords').value = '';
            document.getElementById('serviceKeywordsInput').value = '';
            
            updateBlocoOrder();
            updateKeywordsList();
            document.getElementById('serviceModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('serviceModal').classList.remove('active');
        }

        async function editService(service) {
            // Armazenar se o serviço estava rejeitado (para enviar clear_rejection ao salvar)
            window.isEditingRejectedService = (service.rejection_reason && service.rejection_reason.trim() !== '');
            
            // Verificar se o serviço está aprovado
            if (service.accept == 1) {
                const userConfirmed = await showConfirm(
                    'Este serviço está aprovado.\n\nSuas alterações criarão uma versão pendente que precisará ser aprovada novamente.\n\nDeseja continuar?',
                    '⚠️ Serviço Aprovado'
                );
                
                if (!userConfirmed) {
                    return;
                }
                
                // Criar clone do serviço
                try {
                    const response = await fetch('../src/php/crud_services.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'clone_service',
                            service_id: service.id
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (!result.success) {
                        showAlert(result.message || 'Erro ao criar clone do serviço', 'error');
                        return;
                    }
                    
                    // Usar o ID do clone
                    service.id = result.clone_id;
                    
                    // Recarregar lista para mostrar o clone
                    await reloadServicesList();
                    
                    showAlert('Modo de edição: Suas alterações ficarão pendentes até aprovação.', 'warning');
                    
                } catch (error) {
                    console.error('Erro ao criar clone:', error);
                    showAlert('Erro ao preparar edição do serviço', 'error');
                    return;
                }
            }
            
            document.getElementById('modalTitle').textContent = 'Editar Serviço';
            document.getElementById('serviceId').value = service.id;
            document.getElementById('serviceName').value = service.name;
            document.getElementById('serviceDescription').value = service.description || '';
            document.getElementById('serviceDept').value = service.departamento;
            
            // Carregar palavras-chave
            keywords = [];
            if (service.word_keys) {
                // Remover aspas e parsear corretamente
                const keywordsStr = service.word_keys.replace(/"/g, '').trim();
                keywords = keywordsStr.split(',').map(k => k.trim()).filter(k => k);
            }
            updateKeywordsList();
            
            // Marcar blocos selecionados e restaurar ordem
            blocoOrderMap.clear();
            const blocosArray = service.blocos ? service.blocos.split(',') : [];
            
            document.querySelectorAll('input[name="blocos[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            blocosArray.forEach((blocoId, index) => {
                const checkbox = document.querySelector(`input[name="blocos[]"][value="${blocoId}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    blocoOrderMap.set(blocoId, index + 1);
                }
            });
            
            updateBlocoOrder();
            document.getElementById('serviceModal').classList.add('active');
        }

        async function saveService(event) {
            event.preventDefault();
            
            // IMPORTANTE: Garantir que as palavras-chave sejam atualizadas no campo hidden
            const hiddenInput = document.getElementById('serviceKeywords');
            const formattedKeywords = keywords.map(k => `"${k}"`).join(',');
            hiddenInput.value = formattedKeywords;
            
            console.log('Keywords array:', keywords);
            console.log('Keywords formatadas:', formattedKeywords);
            console.log('Campo hidden value:', hiddenInput.value);
            
            const formData = new FormData(event.target);
            const submitBtn = event.target.querySelector('button[type="submit"]');
            
            // Verificar se word_keys está no FormData
            console.log('FormData word_keys:', formData.get('word_keys'));
            
            // Desabilitar botão e mostrar loading
            submitBtn.disabled = true;
            submitBtn.textContent = '⏳ Salvando...';
            
            // Remover blocos não ordenados
            formData.delete('blocos[]');
            
            // Adicionar blocos na ordem correta
            const orderedBlocos = Array.from(blocoOrderMap.entries())
                .sort((a, b) => a[1] - b[1])
                .map(([id]) => id);
            
            orderedBlocos.forEach(blocoId => {
                formData.append('blocos[]', blocoId);
            });
            
            // Se tiver ID (edição) E estava rejeitado, enviar clear_rejection
            if (formData.get('id') && window.isEditingRejectedService) {
                formData.append('clear_rejection', 'true');
            }
            
            try {
                const response = await fetch('../src/php/crud_services.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                submitBtn.disabled = false;
                submitBtn.textContent = 'Salvar';
                
                if (result.success) {
                    showModalNotification('✓ ' + result.message, 'success');
                    
                    // Aguardar e recarregar SEM parâmetros na URL
                    setTimeout(() => {
                        closeModal();
                        // Limpar parâmetros da URL e recarregar
                        window.history.replaceState({}, document.title, 'gestao_services.php');
                        location.reload();
                    }, 1500);
                } else {
                    showModalNotification('⚠ ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Salvar';
                showModalNotification('⚠ Erro ao salvar serviço. Tente novamente.', 'error');
            }
        }

        // ========== ENVIAR PARA ANÁLISE ==========
        async function sendToReview(type, id, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const itemName = type === 'tutorial' ? 'tutorial' : 'serviço';
            const confirmed = await showConfirm(`Deseja enviar este ${itemName} para análise? Os administradores serão notificados.`);
            if (!confirmed) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '⏳ Enviando...';
            
            try {
                const formData = new FormData();
                formData.append('type', type);
                formData.append('id', id);
                
                const response = await fetch('../src/php/send_to_review.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(result.message || 'Erro ao enviar para análise', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '📤 Enviar';
                }
            } catch (error) {
                console.error('Erro ao enviar para análise:', error);
                showAlert('Erro ao enviar para análise', 'error');
                btn.disabled = false;
                btn.innerHTML = '📤 Enviar';
            }
        }

        async function approveService(id, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const confirmed = await showConfirm('Deseja aprovar este serviço? Ele ficará disponível para uso.');
            if (!confirmed) return;
            
            const btn = event.target;
            btn.disabled = true;
            btn.innerHTML = '⏳ Aprovando...';
            
            try {
                const response = await fetch('../src/php/crud_services.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=approve&id=' + id
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Serviço aprovado com sucesso!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(result.message || 'Erro ao aprovar serviço', 'error');
                    btn.disabled = false;
                    btn.innerHTML = '✓ Aprovar';
                }
            } catch (error) {
                console.error('Erro ao aprovar serviço:', error);
                showAlert('Erro ao aprovar serviço', 'error');
                btn.disabled = false;
                btn.innerHTML = '✓ Aprovar';
            }
        }

        async function deleteService(id) {
            const confirmed = await showConfirm('Tem certeza que deseja excluir este serviço?', 'Excluir Serviço');
            if (!confirmed) return;
            
            try {
                const response = await fetch('../src/php/crud_services.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=delete&id=' + id
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert(result.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert(result.message || 'Erro ao excluir serviço', 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao excluir serviço', 'error');
            }
        }
        
        // ========== VERIFICAR SE VEIO DO REDIRECT COM PARÂMETRO ?edit=ID ==========
        document.addEventListener('DOMContentLoaded', async function() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            if (editId) {
                console.log('Abrindo modal para editar serviço ID:', editId);
                
                // Mostrar loading overlay
                const loadingOverlay = document.getElementById('pageLoadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.add('active');
                    document.body.classList.add('loading-service');
                }
                
                // Buscar dados do serviço
                setTimeout(async () => {
                    try {
                        const response = await fetch(`../src/php/crud_services.php?action=get&id=${editId}`);
                        const data = await response.json();
                        
                        if (data.success) {
                            // Chamar função editService com o objeto do serviço
                            await editService(data.service);
                        } else {
                            showAlert('Erro ao carregar serviço', 'error');
                        }
                    } catch (error) {
                        console.error('Erro ao carregar serviço:', error);
                        showAlert('Erro ao carregar serviço', 'error');
                    }
                    
                    // Remover loading após carregar
                    setTimeout(() => {
                        if (loadingOverlay) {
                            loadingOverlay.classList.remove('active');
                            document.body.classList.remove('loading-service');
                        }
                    }, 800);
                }, 500);
            }
        });
    </script>
</body>
</html>
