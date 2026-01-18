<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_gestor();

include_once(__DIR__ . '/../src/config/conexao.php');

// Buscar steps para o select
$steps_query = "SELECT id, name FROM steps WHERE active = 1 ORDER BY name";
$steps_result = $mysqli->query($steps_query);
$steps_list = [];
while($step = $steps_result->fetch_assoc()) {
    $steps_list[] = $step;
}

// Buscar departamentos
$depts_query = "SELECT id, name FROM departaments ORDER BY name";
$depts_result = $mysqli->query($depts_query);
$depts_list = [];
while($dept = $depts_result->fetch_assoc()) {
    $depts_list[] = $dept;
}

// Buscar blocos
$blocos_query = "SELECT b.*, d.name as dept_name FROM blocos b 
                 LEFT JOIN departaments d ON b.departamento = d.id
                 WHERE b.active = 1 ORDER BY b.last_modification DESC";
$blocos = $mysqli->query($blocos_query);

// Verificar se campo status existe, se não, usar fallback
$status_field_exists = true;
$test_query = $mysqli->query("SHOW COLUMNS FROM blocos LIKE 'status'");
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
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
</head>
<body>
    
    <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>

    <?php include_once __DIR__ . '/includes/quick_menu.php'; ?>

    <!-- Loading Overlay -->
    <div class="page-loading-overlay" id="pageLoadingOverlay">
        <div class="page-loading-spinner"></div>
        <div class="page-loading-text">Carregando tutorial...</div>
        <div class="page-loading-subtext">Aguarde enquanto preparamos a edição</div>
    </div>

    <main>
        <div class="gestao-container">
            <div class="page-header">
                <h1>� Gestão de Tutoriais</h1>
                <button class="btn-primary" onclick="openModal()">+ Novo Tutorial</button>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome do Tutorial</th>
                            <th>Nº de Passos</th>
                            <th>Status</th>
                            <th>Última Modificação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody id="blocosTableBody">
                        <?php while($bloco = $blocos->fetch_assoc()): 
                            $numPassos = !empty($bloco['id_step']) ? count(explode(',', $bloco['id_step'])) : 0;
                            $hasRejection = !empty($bloco['rejection_reason']);
                            
                            // Determinar status
                            $status = 'draft';
                            if ($status_field_exists && isset($bloco['status'])) {
                                $status = $bloco['status'];
                            } else {
                                // Fallback para sistemas sem campo status
                                if ($hasRejection) $status = 'rejected';
                                elseif ($bloco['accept']) $status = 'approved';
                                else $status = 'pending';
                            }
                        ?>
                        <tr>
                            <td><?= $bloco['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($bloco['name']) ?>
                                <?php if ($hasRejection): ?>
                                    <br>
                                    <div style="margin-top: 8px; padding: 8px; background: #fee2e2; border-left: 3px solid #dc2626; border-radius: 4px;">
                                        <div style="font-weight: 600; color: #dc2626; font-size: 12px; margin-bottom: 4px;">❌ REJEITADO</div>
                                        <div style="font-size: 11px; color: #991b1b; margin-bottom: 4px;"><?= htmlspecialchars(substr($bloco['rejection_reason'], 0, 80)) ?><?= strlen($bloco['rejection_reason']) > 80 ? '...' : '' ?></div>
                                        <span class="rejection-warning" onclick="showRejectionReason(<?= $bloco['id'] ?>, '<?= addslashes(htmlspecialchars($bloco['rejection_reason'])) ?>', '<?= date('d/m/Y H:i', strtotime($bloco['reject_date'])) ?>')" style="color: #dc2626; cursor: pointer; font-size: 11px; text-decoration: underline; font-weight: 600;">
                                            📋 Ver motivo completo
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?= $numPassos ?> passo(s)</td>
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
                                    <button class="btn-icon btn-approve" onclick="approveTutorial(<?= $bloco['id'] ?>, event)" title="Aprovar" style="background: #10b981; color: white; margin-top: 5px; padding: 4px 8px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">✓ Aprovar</button>
                                <?php endif; ?>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($bloco['last_modification'])) ?></td>
                            <td class="actions-cell">
                                <button class="btn-icon btn-edit" onclick='editTutorial(<?= $bloco['id'] ?>, event)' title="Editar">✏️</button>
                                <?php if (in_array($status, ['draft', 'rejected'])): ?>
                                    <button class="btn-icon btn-send" onclick="sendToReview('tutorial', <?= $bloco['id'] ?>, event)" title="Enviar para Análise" style="background: #3b82f6; color: white; padding: 6px 10px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; margin-left: 3px;">📤 Enviar</button>
                                <?php endif; ?>
                                <button class="btn-icon btn-delete" onclick="deleteTutorial(<?= $bloco['id'] ?>, event)" title="Excluir">🗑️</button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de Criação de Tutorial -->
    <div class="modal-overlay" id="tutorialModal">
        <div class="modal-large">
            <div class="modal-header">
                <h2 id="modalTitle">Novo Tutorial</h2>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button type="button" class="btn-primary" onclick="finishTutorial()" id="btnSaveTutorial" style="display: none;">
                        💾 Salvar Tutorial
                    </button>
                    <button class="btn-close" onclick="closeModal()">×</button>
                </div>
            </div>

            <!-- Etapa 1: Nome do Tutorial -->
            <div class="modal-step active" id="step1">
                <form id="tutorialNameForm" onsubmit="createTutorialAndStart(event)">
                    <div class="form-group">
                        <label for="tutorialName">Nome do Tutorial *</label>
                        <input type="text" id="tutorialName" name="name" required placeholder="Ex: Como renovar CNH">
                    </div>
                    
                    <div class="form-group">
                        <label for="tutorialDept">Departamento *</label>
                        <select id="tutorialDept" name="departamento" required>
                            <option value="">Selecione um departamento</option>
                            <?php foreach($depts_list as $dept): ?>
                            <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small>Indica qual departamento é responsável por este tutorial</small>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Cancelar</button>
                        <button type="submit" class="btn-primary">Próximo →</button>
                    </div>
                </form>
            </div>

            <!-- Etapa 2: Editor de Passos -->
            <div class="modal-step" id="step2">
                <input type="hidden" id="tutorialId">
                
                <!-- Diagrama de Fluxo -->
                <div class="flow-diagram" id="flowDiagram">
                    <p style="text-align: center; color: #6b7280;">Crie o primeiro passo do tutorial</p>
                </div>

                <!-- Preview do Passo Selecionado -->
                <div class="step-viewer" id="stepViewer">
                    <div class="step-viewer-empty">
                        <div class="step-viewer-empty-icon">👁️</div>
                        <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Preview do Passo</div>
                        <div style="font-size: 14px;">Selecione um passo no diagrama para visualizar</div>
                    </div>
                </div>

                <!-- Formulário de Edição de Passo -->
                <div id="stepEditor" style="border: 2px solid #e5e7eb; padding: 20px; border-radius: 12px; background: linear-gradient(135deg, #f9fafb 0%, #ffffff 100%); flex: 1; overflow-y: auto; display: flex; flex-direction: column; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);">
                    <h3 style="margin: 0 0 15px 0; color: #374151; font-size: 16px; font-weight: 600; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb;">
                        ✏️ <span id="editorTitle">Novo Passo</span>
                    </h3>
                    <form id="stepForm" style="flex: 1; display: flex; flex-direction: column;">
                        <input type="hidden" id="currentStepId">
                        <input type="hidden" id="currentQuestionId">
                        
                        <div class="form-group">
                            <label for="stepName">Nome do Passo *</label>
                            <input type="text" id="stepName" name="name" required placeholder="Ex: Acesse o portal">
                        </div>

                        <div class="form-group">
                            <label>Conteúdo HTML *</label>
                            <div id="stepHtmlEditor" style="height: 200px; background: white;"></div>
                            <textarea id="stepHtml" name="html" style="display: none;" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Mídia (Imagem, Vídeo, GIF)</label>
                            <input type="file" id="mediaFile" name="mediaFile" accept="image/*,video/*,.gif" onchange="previewMedia(event)" style="width: 100%;">
                            <button type="button" class="btn-secondary" onclick="uploadMedia()" id="uploadMediaBtn" style="display: none; margin-top: 10px; width: 100%;">
                                📤 Fazer Upload
                            </button>
                            <button type="button" class="btn-danger" onclick="removeMedia()" id="removeMediaBtn" style="display: none; margin-top: 10px; width: 100%; background: #ef4444; color: white;">
                                🗑️ Remover Mídia
                            </button>
                            <small>Formatos aceitos: JPG, PNG, GIF, MP4, WebM</small>
                            <div id="mediaPreview" class="media-preview-box"></div>
                        </div>

                        <div class="form-actions" style="border-top: 1px solid #e5e7eb; padding-top: 15px; margin-top: auto;">
                            <button type="button" class="btn-primary" onclick="openQuestionModal()">
                                + Adicionar Pergunta
                            </button>
                        </div>
                    </form>
                    
                    <!-- Botões de Ação -->
                    <div style="display: flex; gap: 10px; margin-top: 15px; padding-top: 15px; border-top: 2px solid #e5e7eb;">
                        <button type="button" class="btn-secondary" onclick="limparEditor()" style="flex: 1;">✖️ Limpar</button>
                        <button type="button" class="btn-success" onclick="criarNovoPasso()" id="btnCriarNovoPasso" style="flex: 1; background: #10b981; display: none;">➕ Criar Novo Passo</button>
                        <button type="button" class="btn-primary" onclick="saveCurrentStep()" id="btnSalvarPasso" style="display: none;">💾 Salvar Alterações</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Adicionar Pergunta -->
    <div class="modal-overlay" id="questionModal">
        <div class="modal-medium" style="max-width: 600px;">
            <div class="modal-header">
                <h2>Adicionar Pergunta</h2>
                <button class="btn-close" onclick="closeQuestionModal()">×</button>
            </div>
            
            <form id="questionForm" onsubmit="saveQuestion(event)">
                <div class="form-group">
                    <label for="questionText">Texto da Pergunta *</label>
                    <input type="text" id="questionText" required placeholder="Ex: Conseguiu fazer login?">
                </div>

                <div class="form-group">
                    <label for="questionLabel">Rótulo da Pergunta *</label>
                    <input type="text" id="questionLabel" required placeholder="Ex: Login realizado com sucesso">
                </div>

                <div class="form-group">
                    <label>Esta pergunta leva para: *</label>
                    <select id="questionDestination" class="form-control" onchange="updateDestinationOptions()" required>
                        <option value="">Selecione...</option>
                        <option value="new_step">➕ Criar Novo Passo</option>
                        <option value="existing_step">🔄 Passo Existente</option>
                        <option value="next_block">⏭️ Próximo Tutorial (Bloco)</option>
                    </select>
                </div>

                <div class="question-destination" id="existingStepSelector" style="display: none;">
                    <label>Selecione o passo:</label>
                    <select id="existingStepId" class="form-control"></select>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeQuestionModal()">Cancelar</button>
                    <button type="submit" class="btn-primary">Salvar Pergunta</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Alerta Customizado -->
    <div class="custom-alert-overlay" id="customAlertOverlay">
        <div class="custom-alert-modal">
            <div class="custom-alert-icon" id="customAlertIcon"></div>
            <div class="custom-alert-title" id="customAlertTitle"></div>
            <div class="custom-alert-message" id="customAlertMessage"></div>
            <div class="custom-alert-buttons">
                <button class="custom-alert-btn" id="customAlertBtn" onclick="closeCustomAlert()">OK</button>
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
                <button class="custom-alert-btn-secondary" id="customConfirmCancel">Cancelar</button>
                <button class="custom-alert-btn" id="customConfirmOk">Confirmar</button>
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

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <script>
        let quillEditor = null;
        let currentTutorialId = null;
        let tutorialSteps = {}; // Objeto com steps indexados por ID
        let currentStepId = null;
        let uploadedMediaFile = null;
        let isRejectedTutorial = false; // Rastreia se o tutorial estava rejeitado
        let isSaving = false; // Flag para prevenir salvamentos múltiplos

        // ========== MODAL DE MOTIVO DE REJEIÇÃO ==========
        function showRejectionReason(tutorialId, reason, date) {
            document.getElementById('rejectionReasonContent').textContent = reason;
            document.getElementById('rejectionDate').textContent = 'Rejeitado em: ' + date;
            document.getElementById('rejectionReasonModal').style.display = 'flex';
        }

        function closeRejectionModal() {
            document.getElementById('rejectionReasonModal').style.display = 'none';
        }

        // ========== MODAL DE ALERTA CUSTOMIZADO ==========
        function showAlert(message, type = 'info', title = '') {
            const overlay = document.getElementById('customAlertOverlay');
            const icon = document.getElementById('customAlertIcon');
            const titleEl = document.getElementById('customAlertTitle');
            const messageEl = document.getElementById('customAlertMessage');
            
            // Definir ícone e título baseado no tipo
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
        
        function closeCustomConfirm() {
            document.getElementById('customConfirmOverlay').classList.remove('active');
            const btnOk = document.getElementById('customConfirmOk');
            const btnCancel = document.getElementById('customConfirmCancel');
            btnOk.disabled = false;
            btnCancel.disabled = false;
            btnOk.innerHTML = 'Confirmar';
        }
        
        // ========== RECARREGAR LISTA DE BLOCOS SEM RELOAD ==========
        async function reloadBlocosList() {
            try {
                const response = await fetch('gestao_blocos.php', {
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
                const newTbody = temp.querySelector('#blocosTableBody');
                if (newTbody) {
                    document.getElementById('blocosTableBody').innerHTML = newTbody.innerHTML;
                }
            } catch (error) {
                console.error('Erro ao recarregar lista:', error);
            }
        }
        
        function showConfirmLoading(message = 'Processando...') {
            const messageEl = document.getElementById('customConfirmMessage');
            const btnOk = document.getElementById('customConfirmOk');
            const btnCancel = document.getElementById('customConfirmCancel');
            
            messageEl.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0;">
                <span style="display: inline-block; animation: spin 1s linear infinite; font-size: 24px;">⏳</span>
                <span>${message}</span>
            </div>`;
            btnOk.disabled = true;
            btnCancel.disabled = true;
            btnOk.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Aguarde...';
        }
        
        function showConfirmSuccess(message, title = 'Sucesso!') {
            const titleEl = document.getElementById('customConfirmTitle');
            const messageEl = document.getElementById('customConfirmMessage');
            const btnOk = document.getElementById('customConfirmOk');
            const btnCancel = document.getElementById('customConfirmCancel');
            
            titleEl.textContent = title;
            messageEl.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0;">
                <span style="font-size: 48px;">✅</span>
            </div>
            <div style="text-align: center;">${message}</div>`;
            
            btnOk.disabled = false;
            btnOk.innerHTML = 'OK';
            btnCancel.style.display = 'none';
        }
        
        function showConfirmError(message, title = 'Erro') {
            const titleEl = document.getElementById('customConfirmTitle');
            const messageEl = document.getElementById('customConfirmMessage');
            const btnOk = document.getElementById('customConfirmOk');
            const btnCancel = document.getElementById('customConfirmCancel');
            
            titleEl.textContent = title;
            messageEl.innerHTML = `<div style="display: flex; align-items: center; justify-content: center; gap: 10px; margin: 20px 0;">
                <span style="font-size: 48px;">❌</span>
            </div>
            <div style="text-align: center;">${message}</div>`;
            
            btnOk.disabled = false;
            btnOk.innerHTML = 'OK';
            btnCancel.style.display = 'none';
        }
        
        function showConfirm(message, callback, title = 'Confirmar') {
            return new Promise((resolve) => {
                const overlay = document.getElementById('customConfirmOverlay');
                const titleEl = document.getElementById('customConfirmTitle');
                const messageEl = document.getElementById('customConfirmMessage');
                const btnOk = document.getElementById('customConfirmOk');
                const btnCancel = document.getElementById('customConfirmCancel');
                
                titleEl.textContent = title;
                messageEl.textContent = message;
                overlay.classList.add('active');
                
                const cleanup = () => {
                    overlay.classList.remove('active');
                    btnOk.onclick = null;
                    btnCancel.onclick = null;
                };
                
                btnOk.onclick = () => {
                    cleanup();
                    resolve(true);
                    if (callback) callback();
                };
                
                btnCancel.onclick = () => {
                    cleanup();
                    resolve(false);
                };
            });
        }
        
        // Fechar modal ao clicar fora
        document.getElementById('customAlertOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'customAlertOverlay') closeCustomAlert();
        });
        
        document.getElementById('customConfirmOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'customConfirmOverlay') {
                e.target.classList.remove('active');
            }
        });

        // ========== MODAL PRINCIPAL ==========
        function openModal() {
            document.getElementById('modalTitle').textContent = 'Novo Tutorial';
            document.getElementById('tutorialName').value = '';
            document.getElementById('tutorialDept').value = '';
            document.getElementById('step1').classList.add('active');
            document.getElementById('step2').classList.remove('active');
            document.getElementById('tutorialModal').classList.add('active');
            
            // Limpar TODOS os dados do tutorial anterior
            currentTutorialId = null;
            tutorialSteps = {};
            currentStepId = null;
            uploadedMediaFile = null;
            isRejectedTutorial = false;
            isSaving = false;
            
            // Limpar diagrama de fluxo
            const flowDiagram = document.getElementById('flowDiagram');
            if (flowDiagram) {
                flowDiagram.innerHTML = '<p style="text-align: center; color: #6b7280;">Crie o primeiro passo do tutorial</p>';
            }
            
            // Limpar preview do step
            const stepViewer = document.getElementById('stepViewer');
            if (stepViewer) {
                stepViewer.innerHTML = `
                    <div class="step-viewer-empty">
                        <div class="step-viewer-empty-icon">📋</div>
                        <div>Selecione um passo no diagrama para visualizar</div>
                    </div>
                `;
            }
            
            // Limpar editor
            document.getElementById('currentStepId').value = '';
            document.getElementById('stepName').value = '';
            document.getElementById('mediaPreview').classList.remove('active');
            document.getElementById('mediaPreview').innerHTML = '';
            document.getElementById('mediaFile').value = '';
            document.getElementById('uploadMediaBtn').style.display = 'none';
            
            if (quillEditor) {
                quillEditor.setText('');
            }
        }

        function closeModal() {
            document.getElementById('tutorialModal').classList.remove('active');
            document.getElementById('btnSaveTutorial').style.display = 'none';
            if (quillEditor) {
                quillEditor.setText('');
            }
            currentTutorialId = null;
            tutorialSteps = {};
            currentStepId = null;
        }

        // ========== CRIAR TUTORIAL E INICIAR EDIÇÃO ==========
        async function createTutorialAndStart(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Criando...';
            }
            
            const tutorialName = document.getElementById('tutorialName').value;
            const tutorialDept = document.getElementById('tutorialDept').value;
            
            try {
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create_tutorial',
                        name: tutorialName,
                        departamento: tutorialDept
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (submitBtn) {
                        submitBtn.innerHTML = '✓ Tutorial Criado!';
                    }
                    
                    currentTutorialId = result.tutorial_id;
                    document.getElementById('tutorialId').value = currentTutorialId;
                    
                    setTimeout(() => {
                        // Ir para etapa 2 (editor)
                        document.getElementById('step1').classList.remove('active');
                        document.getElementById('step2').classList.add('active');
                        
                        // Mostrar botão Salvar Tutorial no header
                        document.getElementById('btnSaveTutorial').style.display = 'block';
                        
                        // Inicializar editor para o primeiro passo
                        initializeStepEditor();
                        
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'Próximo →';
                        }
                    }, 1000);
                } else {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = 'Próximo →';
                    }
                    showAlert(result.message, 'error');
                }
            } catch (error) {
                console.error('Erro:', error);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Próximo →';
                }
                showAlert('Erro ao criar tutorial', 'error');
            }
        }

        // ========== INICIALIZAR EDITOR DE PASSO ==========
        function initializeStepEditor() {
            document.getElementById('editorTitle').textContent = 'Novo Passo';
            document.getElementById('currentStepId').value = '';
            currentStepId = null;
            document.getElementById('stepName').value = '';
            document.getElementById('mediaPreview').classList.remove('active');
            document.getElementById('mediaPreview').innerHTML = '';
            document.getElementById('mediaFile').value = '';
            document.getElementById('uploadMediaBtn').style.display = 'none';
            document.getElementById('removeMediaBtn').style.display = 'none';
            
            // Limpar editor Quill se já existir
            if (quillEditor) {
                quillEditor.setText('');
            }
            
            // Mostrar botão de criar, esconder botão de salvar
            document.getElementById('btnCriarNovoPasso').style.display = 'block';
            document.getElementById('btnSalvarPasso').style.display = 'none';
            uploadedMediaFile = null;
            
            // Inicializar Quill Editor
            if (!quillEditor) {
                quillEditor = new Quill('#stepHtmlEditor', {
                    theme: 'snow',
                    placeholder: 'Digite o conteúdo do passo...',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            [{ 'color': [] }, { 'background': [] }],
                            ['link'],
                            ['clean']
                        ]
                    }
                });
                
                // Atualizar preview ao digitar
                quillEditor.on('text-change', function() {
                    const html = quillEditor.root.innerHTML;
                    document.getElementById('stepHtml').value = html;
                });
            } else {
                quillEditor.setText('');
            }
        }

        // Função para limpar o editor e voltar ao estado inicial
        function limparEditor() {
            // Confirmar se está editando um passo existente
            if (currentStepId) {
                if (!confirm('Tem certeza que deseja descartar as alterações e criar um novo passo?')) {
                    return;
                }
            }
            
            // Reinicializar o editor para modo de criação
            initializeStepEditor();
            
            // Limpar preview do step
            document.getElementById('stepHtmlPreview').innerHTML = '';
            
            // Limpar mídia preview se existir
            const mediaPreview = document.getElementById('mediaPreview');
            if (mediaPreview) {
                mediaPreview.innerHTML = '';
            }
            
            // Resetar variáveis globais
            uploadedMediaFile = null;
            uploadedMediaFilePath = null;
            
            console.log('Editor limpo e pronto para criar novo passo');
        }

        // ========== PREVIEW DE MÍDIA ==========
        function previewMedia(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            uploadedMediaFile = file;
            const preview = document.getElementById('mediaPreview');
            const uploadBtn = document.getElementById('uploadMediaBtn');
            
            // Verificar tamanho
            const fileSizeMB = (file.size / 1024 / 1024).toFixed(2);
            const maxSizeMB = 50;
            const sizeWarning = fileSizeMB > maxSizeMB ? `<p style="color: #ef4444; font-weight: bold; margin-top: 10px;">⚠️ Arquivo muito grande! (${fileSizeMB}MB / ${maxSizeMB}MB)</p>` : `<p style="color: #6b7280; font-size: 12px; margin-top: 5px;">Tamanho: ${fileSizeMB}MB</p>`;
            
            preview.classList.add('active');
            preview.innerHTML = '';
            uploadBtn.style.display = 'block';
            
            const reader = new FileReader();
            reader.onload = function(e) {
                if (file.type.startsWith('image/')) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">${sizeWarning}<p style="margin-top: 10px; color: #6b7280; font-size: 12px;">⚠️ Clique em "Fazer Upload" para salvar a mídia</p>`;
                } else if (file.type.startsWith('video/')) {
                    preview.innerHTML = `<video controls src="${e.target.result}"></video>${sizeWarning}<p style="margin-top: 10px; color: #6b7280; font-size: 12px;">⚠️ Clique em "Fazer Upload" para salvar a mídia</p>`;
                }
            };
            reader.readAsDataURL(file);
        }
        
        // ========== REMOVER MÍDIA ==========
        async function removeMedia() {
            if (!currentStepId) {
                showAlert('Nenhum passo selecionado', 'warning');
                return;
            }
            
            console.log('Remove Media - Step ID:', currentStepId);
            
            const confirmed = await showConfirm('Deseja realmente remover a mídia deste passo? O arquivo será excluído do servidor.');
            if (!confirmed) {
                console.log('Remove Media - Cancelado pelo usuário');
                return;
            }
            
            // Mostrar loading no modal de confirmação
            showConfirmLoading('Removendo mídia do servidor...');
            
            const removeBtn = document.getElementById('removeMediaBtn');
            removeBtn.disabled = true;
            removeBtn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Removendo...';
            
            try {
                console.log('Remove Media - Enviando requisição...');
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'remove_media',
                        step_id: currentStepId
                    })
                });
                
                console.log('Remove Media - Resposta recebida:', response.status);
                const result = await response.json();
                console.log('Remove Media - Resultado:', result);
                
                if (result.success) {
                    removeBtn.innerHTML = '✓ Removido!';
                    
                    // Transformar modal em sucesso
                    showConfirmSuccess('Mídia removida com sucesso!');
                    
                    console.log('Remove Media - Recarregando steps...');
                    // Atualizar diagrama e preview
                    await loadTutorialSteps();
                    
                    console.log('Remove Media - Atualizando viewer...');
                    if (tutorialSteps[currentStepId]) {
                        updateStepViewer(tutorialSteps[currentStepId]);
                    }
                    
                    // Fechar modal após 2 segundos
                    setTimeout(() => {
                        closeCustomConfirm();
                        
                        console.log('Remove Media - Limpando interface...');
                        // Limpar preview e input
                        document.getElementById('mediaFile').value = '';
                        document.getElementById('mediaPreview').innerHTML = '';
                        document.getElementById('mediaPreview').classList.remove('active');
                        document.getElementById('uploadMediaBtn').style.display = 'none';
                        removeBtn.style.display = 'none';
                        removeBtn.disabled = false;
                        removeBtn.innerHTML = '🗑️ Remover Mídia';
                        uploadedMediaFile = null;
                        
                        console.log('Remove Media - Concluído!');
                    }, 2000);
                } else {
                    console.error('Remove Media - Erro no servidor:', result.message);
                    // Transformar modal em erro
                    showConfirmError(result.message || 'Erro ao remover mídia');
                    removeBtn.disabled = false;
                    removeBtn.innerHTML = '🗑️ Remover Mídia';
                    
                    // Fechar modal após 3 segundos
                    setTimeout(() => {
                        closeCustomConfirm();
                    }, 3000);
                }
            } catch (error) {
                console.error('Remove Media - Exceção:', error);
                showConfirmError('Erro ao remover mídia: ' + error.message);
                removeBtn.disabled = false;
                removeBtn.innerHTML = '🗑️ Remover Mídia';
                
                // Fechar modal após 3 segundos
                setTimeout(() => {
                    closeCustomConfirm();
                }, 3000);
            }
        }
        
        // ========== FAZER UPLOAD DA MÍDIA ==========
        async function uploadMedia() {
            if (!uploadedMediaFile) {
                alert('Selecione um arquivo primeiro');
                return;
            }
            
            // Validar se o passo tem nome e conteúdo
            const stepName = document.getElementById('stepName').value;
            const stepHtml = document.getElementById('stepHtml').value;
            
            if (!stepName || !stepHtml) {
                showAlert('Preencha o nome e o conteúdo do passo antes de fazer upload da mídia', 'warning');
                return;
            }
            
            // Validar tamanho do arquivo (limite: 50MB)
            const maxSize = 50 * 1024 * 1024; // 50MB em bytes
            if (uploadedMediaFile.size > maxSize) {
                alert(`Arquivo muito grande! Tamanho: ${(uploadedMediaFile.size / 1024 / 1024).toFixed(2)}MB\nLimite máximo: 50MB\n\n`);
                return;
            }
            
            const uploadBtn = document.getElementById('uploadMediaBtn');
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Uploading...';
            
            // Criar barra de progresso
            const preview = document.getElementById('mediaPreview');
            const progressBar = document.createElement('div');
            progressBar.id = 'uploadProgress';
            progressBar.style.cssText = 'margin-top: 10px; background: #e5e7eb; border-radius: 8px; height: 24px; overflow: hidden; position: relative;';
            progressBar.innerHTML = `
                <div id="progressFill" style="background: linear-gradient(90deg, #10b981, #059669); height: 100%; width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 12px;">0%</div>
            `;
            preview.appendChild(progressBar);
            
            // Verificar se já tem um stepId (se não tiver, será criado pelo saveCurrentStepWithProgress)
            const currentStepIdValue = document.getElementById('currentStepId').value;
            console.log('Upload - Current Step ID:', currentStepIdValue);
            
            const stepId = await saveCurrentStepWithProgress();
            
            // Remover barra de progresso
            const uploadProgressBar = document.getElementById('uploadProgress');
            if (uploadProgressBar) uploadProgressBar.remove();
            
            if (stepId) {
                uploadBtn.innerHTML = '✓ Upload Concluído';
                document.getElementById('currentStepId').value = stepId;
                currentStepId = stepId;
                uploadedMediaFile = null; // Limpar arquivo para evitar duplicação
                
                setTimeout(() => {
                    uploadBtn.style.display = 'none';
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '📤 Fazer Upload';
                }, 2000);
                
                // Salvar o stepId antes de recarregar
                const savedStepId = stepId;
                
                // Recarregar os passos para atualizar com a mídia
                await loadTutorialSteps();
                
                // Restaurar o stepId atual após o reload (preservar contexto)
                currentStepId = savedStepId;
                document.getElementById('currentStepId').value = savedStepId;
                
                // Atualizar o step atual no cache com a mídia
                if (tutorialSteps[savedStepId]) {
                    updateStepViewer(tutorialSteps[savedStepId]);
                }
                
                // Atualizar o diagrama para marcar o step como ativo
                updateFlowDiagram();
                
                showAlert('Mídia enviada com sucesso!', 'success');
            } else {
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '📤 Fazer Upload';
                showAlert('Erro ao fazer upload. Verifique se o arquivo não é muito grande (limite: 50MB).', 'error');
            }
        }

        // ========== MODAL DE PERGUNTA ==========
        let currentEditingQuestionId = null;
        
        function openQuestionModal() {
            // Validar se o passo tem nome e conteúdo
            const stepName = document.getElementById('stepName').value;
            const stepHtml = document.getElementById('stepHtml').value;
            
            if (!stepName || !stepHtml) {
                showAlert('Preencha o nome e o conteúdo do passo antes de adicionar uma pergunta', 'warning');
                return;
            }
            
            // Resetar modo de edição
            currentEditingQuestionId = null;
            document.querySelector('#questionModal .modal-header h2').textContent = 'Adicionar Pergunta';
            
            // Preencher select de passos existentes
            updateExistingStepsSelect();
            
            document.getElementById('questionModal').classList.add('active');
        }

        function closeQuestionModal() {
            document.getElementById('questionModal').classList.remove('active');
            document.getElementById('questionForm').reset();
            document.getElementById('existingStepSelector').style.display = 'none';
            currentEditingQuestionId = null;
            document.querySelector('#questionModal .modal-header h2').textContent = 'Adicionar Pergunta';
        }

        function updateDestinationOptions() {
            const destination = document.getElementById('questionDestination').value;
            const selector = document.getElementById('existingStepSelector');
            
            if (destination === 'existing_step') {
                selector.style.display = 'block';
            } else {
                selector.style.display = 'none';
            }
        }

        function updateExistingStepsSelect() {
            const select = document.getElementById('existingStepId');
            select.innerHTML = '<option value="">Selecione um passo...</option>';
            
            Object.values(tutorialSteps).forEach(step => {
                const option = document.createElement('option');
                option.value = step.id;
                option.textContent = step.name;
                select.appendChild(option);
            });
        }

        // ========== SALVAR PERGUNTA ==========
        async function saveQuestion(event) {
            event.preventDefault();
            
            const submitBtn = event.target.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Salvando...';
            }
            
            const questionText = document.getElementById('questionText').value;
            const questionLabel = document.getElementById('questionLabel').value;
            const destination = document.getElementById('questionDestination').value;
            const existingStepId = document.getElementById('existingStepId').value;
            
            // Não salvar o passo novamente se já tiver stepId (evitar duplicação de mídia)
            const currentStepIdVal = document.getElementById('currentStepId').value;
            let stepId = currentStepIdVal;
            
            // Se não tem stepId, salvar sem mídia (para não duplicar)
            if (!stepId) {
                stepId = await saveCurrentStep();
                if (!stepId) {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '💾 Salvar Pergunta';
                    }
                    return;
                }
            }
            
            try {
                const actionType = currentEditingQuestionId ? 'edit_question' : 'add_question';
                const requestBody = {
                    action: actionType,
                    tutorial_id: currentTutorialId,
                    step_id: stepId,
                    question_text: questionText,
                    question_label: questionLabel,
                    destination: destination,
                    existing_step_id: existingStepId
                };
                
                if (currentEditingQuestionId) {
                    requestBody.question_id = currentEditingQuestionId;
                }
                
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(requestBody)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (submitBtn) {
                        submitBtn.innerHTML = '✓ Salvo!';
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '💾 Salvar Pergunta';
                        }, 1500);
                    }
                    
                    closeQuestionModal();
                    
                    // Recarregar o diagrama
                    await loadTutorialSteps();
                    
                    // Atualizar preview
                    if (currentStepId) {
                        updateStepViewer(tutorialSteps[currentStepId]);
                    }
                    
                    // Se for "novo passo", abrir editor para o novo passo
                    if (destination === 'new_step' && result.new_step_id) {
                        loadStepInEditor(result.new_step_id);
                    } else if (destination === 'next_block') {
                        showAlert(currentEditingQuestionId ? 'Pergunta atualizada com sucesso!' : 'Pergunta adicionada! Este passo levará ao próximo tutorial.', 'success');
                    } else {
                        showAlert(currentEditingQuestionId ? 'Pergunta atualizada com sucesso!' : 'Pergunta adicionada com sucesso!', 'success');
                    }
                } else {
                    showAlert(result.message, 'error', 'Erro ao salvar');
                }
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao salvar pergunta', 'error');
            }
        }
        
        // ========== EDITAR PERGUNTA ==========
        async function editQuestion(stepId, questionId) {
            try {
                // Buscar dados da pergunta
                const step = tutorialSteps[stepId];
                if (!step || !step.questions) return;
                
                const question = step.questions.find(q => q.id == questionId);
                if (!question) {
                    showAlert('Pergunta não encontrada', 'error');
                    return;
                }
                
                // Carregar passo no editor primeiro
                loadStepInEditor(stepId);
                
                // Preencher modal com dados da pergunta
                currentEditingQuestionId = questionId;
                document.querySelector('#questionModal .modal-header h2').textContent = 'Editar Pergunta';
                document.getElementById('questionText').value = question.text;
                document.getElementById('questionLabel').value = question.name;
                
                // Preencher select de passos existentes
                updateExistingStepsSelect();
                
                // Determinar destino
                if (question.proximo == 'next_block' || question.proximo == 505) {
                    document.getElementById('questionDestination').value = 'next_tutorial';
                    document.getElementById('existingStepSelector').style.display = 'none';
                } else if (question.proximo == 0) {
                    document.getElementById('questionDestination').value = 'new_step';
                    document.getElementById('existingStepSelector').style.display = 'none';
                } else {
                    document.getElementById('questionDestination').value = 'existing_step';
                    document.getElementById('existingStepSelector').style.display = 'block';
                    document.getElementById('existingStepId').value = question.proximo;
                }
                
                // Abrir modal
                document.getElementById('questionModal').classList.add('active');
                
            } catch (error) {
                console.error('Erro ao editar pergunta:', error);
                showAlert('Erro ao carregar pergunta para edição', 'error');
            }
        }
        
        // ========== EXCLUIR PASSO ==========
        async function deleteStep(stepId) {
            const confirmed = await showConfirm('Deseja realmente excluir este passo? Esta ação não pode ser desfeita.', null, 'Excluir Passo');
            if (!confirmed) return;
            
            try {
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'delete_step',
                        step_id: stepId,
                        tutorial_id: currentTutorialId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('Passo excluído com sucesso!', 'success');
                    
                    // Limpar editor se o step excluído era o atual
                    if (currentStepId == stepId) {
                        initializeStepEditor();
                        currentStepId = null;
                    }
                    
                    // Recarregar steps e atualizar diagrama
                    await loadTutorialSteps();
                } else {
                    showConfirmError(result.message || 'Erro ao excluir passo');
                }
            } catch (error) {
                console.error('Erro ao excluir passo:', error);
                showConfirmError('Erro ao excluir passo');
            }
        }
        
        // ========== EXCLUIR PERGUNTA ==========
        async function deleteQuestion(stepId, questionId) {
            const confirmed = await showConfirm('Deseja realmente excluir esta pergunta?', null, 'Excluir Pergunta');
            if (!confirmed) return;
            
            showConfirmLoading('Excluindo pergunta...');
            
            try {
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_question',
                        step_id: stepId,
                        question_id: questionId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showConfirmSuccess('Pergunta excluída com sucesso!');
                    
                    await loadTutorialSteps();
                    if (tutorialSteps[stepId]) {
                        loadStepInEditor(stepId);
                    }
                    
                    setTimeout(() => {
                        closeCustomConfirm();
                    }, 2000);
                } else {
                    showConfirmError(result.message || 'Erro ao excluir pergunta');
                    setTimeout(() => {
                        closeCustomConfirm();
                    }, 3000);
                }
            } catch (error) {
                console.error('Erro:', error);
                showConfirmError('Erro ao excluir pergunta');
                setTimeout(() => {
                    closeCustomConfirm();
                }, 3000);
            }
        }

        // ========== MOVER PERGUNTA ==========
        async function moveQuestion(stepId, questionIndex, direction) {
            try {
                const step = tutorialSteps[stepId];
                if (!step || !step.questions || step.questions.length < 2) {
                    showAlert('Não há perguntas suficientes para reordenar', 'error');
                    return;
                }
                
                const questions = [...step.questions];
                const newIndex = direction === 'up' ? questionIndex - 1 : questionIndex + 1;
                
                if (newIndex < 0 || newIndex >= questions.length) {
                    showAlert('Movimento inválido', 'error');
                    return;
                }
                
                // Trocar posições
                [questions[questionIndex], questions[newIndex]] = [questions[newIndex], questions[questionIndex]];
                
                // Extrair IDs na nova ordem
                const questionIds = questions.map(q => q.id);
                
                console.log('Reordenando perguntas:', {
                    stepId,
                    questionIndex,
                    direction,
                    newIndex,
                    questionIds
                });
                
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reorder_questions',
                        step_id: stepId,
                        question_ids: questionIds
                    })
                });
                
                const result = await response.json();
                console.log('Resultado da reordenação:', result);
                
                if (result.success) {
                    // Atualizar ordem localmente
                    step.questions = questions;
                    
                    // Re-renderizar o diagrama
                    updateFlowDiagram();
                    
                    // Atualizar preview se estiver visualizando este step
                    if (currentStepId == stepId) {
                        updateStepViewer(step);
                    }
                    
                    showAlert('Ordem atualizada com sucesso!', 'success');
                } else {
                    console.error('Erro ao reordenar:', result);
                    showAlert(result.message || 'Erro ao reordenar perguntas', 'error');
                }
            } catch (error) {
                console.error('Erro ao reordenar:', error);
                showAlert('Erro ao reordenar perguntas: ' + error.message, 'error');
            }
        }

        // ========== SALVAR PASSO ATUAL COM PROGRESSO ==========
        async function saveCurrentStepWithProgress() {
            const stepId = document.getElementById('currentStepId').value;
            const stepName = document.getElementById('stepName').value || 'Passo sem título';
            
            // Garantir que o conteúdo do Quill está no textarea hidden
            if (quillEditor) {
                document.getElementById('stepHtml').value = quillEditor.root.innerHTML;
            }
            
            const stepHtml = document.getElementById('stepHtml').value || '<p>Sem conteúdo</p>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_step');
                formData.append('tutorial_id', currentTutorialId);
                // Apenas adicionar step_id se não estiver vazio (prevenir duplicação)
                if (stepId && stepId.trim() !== '') {
                    formData.append('step_id', stepId);
                }
                formData.append('name', stepName);
                formData.append('html', stepHtml);
                
                if (uploadedMediaFile) {
                    formData.append('mediaFile', uploadedMediaFile);
                }
                
                const xhr = new XMLHttpRequest();
                
                return new Promise((resolve, reject) => {
                    xhr.upload.addEventListener('progress', (e) => {
                        if (e.lengthComputable) {
                            const percentComplete = Math.round((e.loaded / e.total) * 100);
                            const progressFill = document.getElementById('progressFill');
                            if (progressFill) {
                                progressFill.style.width = percentComplete + '%';
                                progressFill.textContent = percentComplete + '%';
                            }
                        }
                    });
                    
                    xhr.addEventListener('load', () => {
                        // Atualizar barra para "Processando..."
                        const progressFill = document.getElementById('progressFill');
                        if (progressFill) {
                            progressFill.textContent = 'Processando...';
                            progressFill.style.background = 'linear-gradient(90deg, #f59e0b, #d97706)';
                        }
                        
                        if (xhr.status === 200) {
                            try {
                                const result = JSON.parse(xhr.responseText);
                                if (result.success) {
                                    resolve(result.step_id);
                                } else {
                                    reject(new Error(result.message || 'Erro ao processar arquivo'));
                                }
                            } catch (e) {
                                console.error('Resposta do servidor:', xhr.responseText);
                                
                                // Verificar se é erro de limite PHP
                                if (xhr.responseText.includes('POST Content-Length') && xhr.responseText.includes('exceeds the limit')) {
                                    const fileSizeMB = (uploadedMediaFile.size / 1024 / 1024).toFixed(2);
                                    reject(new Error(`Arquivo muito grande (${fileSizeMB}MB).\n\nO servidor PHP está limitado. Configure o php.ini:\npost_max_size = 100M\nupload_max_filesize = 100M\n\nApós editar, reinicie o servidor.`));
                                } else {
                                    reject(new Error('Erro ao processar resposta do servidor.'));
                                }
                            }
                        } else {
                            reject(new Error('Erro no upload (código: ' + xhr.status + ')'));
                        }
                    });
                    
                    xhr.addEventListener('error', () => reject(new Error('Erro de rede')));
                    xhr.addEventListener('timeout', () => reject(new Error('Timeout - O arquivo é muito grande')));
                    
                    xhr.timeout = 300000; // 5 minutos de timeout
                    xhr.open('POST', '../src/php/crud_tutoriais_flow.php');
                    xhr.send(formData);
                });
            } catch (error) {
                console.error('Erro:', error);
                return null;
            }
        }
        
        // ========== CRIAR NOVO PASSO ==========
        async function criarNovoPasso() {
            // Garantir que o conteúdo do Quill está no textarea hidden
            if (quillEditor) {
                document.getElementById('stepHtml').value = quillEditor.root.innerHTML;
            }
            
            const stepName = document.getElementById('stepName').value;
            const stepHtml = document.getElementById('stepHtml').value;
            
            if (!stepName || !stepHtml) {
                showAlert('Preencha o nome e o conteúdo do passo', 'warning');
                return;
            }
            
            const btn = document.getElementById('btnCriarNovoPasso');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Criando...';
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_step');
                formData.append('tutorial_id', currentTutorialId);
                // NÃO enviar step_id - forçar criação de novo passo
                formData.append('name', stepName);
                formData.append('html', stepHtml);
                
                if (uploadedMediaFile) {
                    formData.append('mediaFile', uploadedMediaFile);
                }
                
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    const newStepId = result.step_id;
                    
                    // Atualizar para modo de edição
                    document.getElementById('currentStepId').value = newStepId;
                    currentStepId = newStepId;
                    document.getElementById('editorTitle').textContent = 'Editando: ' + stepName;
                    uploadedMediaFile = null;
                    
                    // Trocar botões: esconder criar, mostrar salvar
                    document.getElementById('btnCriarNovoPasso').style.display = 'none';
                    document.getElementById('btnSalvarPasso').style.display = 'block';
                    
                    showAlert('Passo criado com sucesso!', 'success');
                    
                    // Recarregar dados
                    await loadTutorialSteps();
                    updateFlowDiagram();
                    if (tutorialSteps[newStepId]) {
                        updateStepViewer(tutorialSteps[newStepId]);
                    }
                } else {
                    showAlert(result.message || 'Erro ao criar passo', 'error');
                }
                
                btn.disabled = false;
                btn.innerHTML = originalText;
            } catch (error) {
                console.error('Erro:', error);
                showAlert('Erro ao criar passo', 'error');
                btn.disabled = false;
                btn.innerHTML = originalText;
            }
        }
        
        // ========== SALVAR PASSO ATUAL ==========
        async function saveCurrentStep() {
            // Prevenir múltiplos salvamentos simultâneos
            if (isSaving) {
                console.log('Já está salvando, aguarde...');
                return null;
            }
            
            const stepId = document.getElementById('currentStepId').value;
            const finalStepId = stepId || currentStepId;
            
            // VERIFICAR SE É UM PASSO EXISTENTE
            if (!finalStepId || finalStepId === '' || finalStepId === '0') {
                showAlert('Use o botão "Criar Novo Passo" para criar um novo passo', 'warning');
                return null;
            }
            
            isSaving = true;
            
            const stepName = document.getElementById('stepName').value;
            
            console.log('SAVE STEP - Step ID atual:', finalStepId);
            
            // Garantir que o conteúdo do Quill está no textarea hidden
            if (quillEditor) {
                document.getElementById('stepHtml').value = quillEditor.root.innerHTML;
            }
            
            const stepHtml = document.getElementById('stepHtml').value;
            
            if (!stepName || !stepHtml) {
                showAlert('Preencha o nome e o conteúdo do passo', 'warning');
                isSaving = false;
                return null;
            }
            
            // Feedback visual no botão
            const saveBtn = document.getElementById('btnSalvarPasso');
            let originalText = '';
            if (saveBtn) {
                originalText = saveBtn.innerHTML;
                saveBtn.disabled = true;
                saveBtn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span> Salvando...';
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'save_step');
                formData.append('tutorial_id', currentTutorialId);
                formData.append('step_id', finalStepId); // SEMPRE enviar step_id
                formData.append('name', stepName);
                formData.append('html', stepHtml);
                
                if (uploadedMediaFile) {
                    formData.append('mediaFile', uploadedMediaFile);
                }
                
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Atualizar stepId se for novo
                    const savedStepId = result.step_id;
                    document.getElementById('currentStepId').value = savedStepId;
                    currentStepId = savedStepId;
                    
                    // Atualizar título do editor para "Editando: Nome do Passo"
                    const stepNameValue = document.getElementById('stepName').value || 'Passo sem título';
                    document.getElementById('editorTitle').textContent = 'Editando: ' + stepNameValue;
                    
                    // Feedback de sucesso IMEDIATO
                    if (saveBtn) {
                        saveBtn.innerHTML = '✓ Salvo!';
                    }
                    showAlert('Passo salvo com sucesso!', 'success');
                    
                    // Recarregar dados em background (sem await para não bloquear)
                    loadTutorialSteps().then(() => {
                        // Restaurar o stepId após o reload (preservar contexto)
                        currentStepId = savedStepId;
                        document.getElementById('currentStepId').value = savedStepId;
                        
                        // Atualizar diagrama e preview após reload
                        updateFlowDiagram();
                        if (tutorialSteps[savedStepId]) {
                            updateStepViewer(tutorialSteps[savedStepId]);
                        }
                    });
                    
                    // Restaurar botão após 1 segundo
                    if (saveBtn) {
                        setTimeout(() => {
                            saveBtn.disabled = false;
                            saveBtn.innerHTML = originalText;
                            isSaving = false;
                        }, 1000);
                    } else {
                        isSaving = false;
                    }
                    
                    return savedStepId;
                } else {
                    if (saveBtn) {
                        saveBtn.disabled = false;
                        saveBtn.innerHTML = originalText;
                    }
                    isSaving = false;
                    showAlert(result.message, 'error', 'Erro ao salvar');
                    return null;
                }
            } catch (error) {
                console.error('Erro:', error);
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
                isSaving = false;
                showAlert('Erro ao salvar passo', 'error');
                return null;
            }
        }

        // ========== CARREGAR PASSOS DO TUTORIAL ==========
        async function loadTutorialSteps() {
            try {
                const response = await fetch(`../src/php/crud_tutoriais_flow.php?action=get_steps&tutorial_id=${currentTutorialId}`);
                const result = await response.json();
                
                if (result.success) {
                    tutorialSteps = {};
                    result.steps.forEach(step => {
                        tutorialSteps[step.id] = step;
                    });
                    
                    // Limpar step atual ao trocar de tutorial
                    currentStepId = null;
                    document.getElementById('currentStepId').value = '';
                    
                    // Resetar preview para estado vazio
                    const viewer = document.getElementById('stepViewer');
                    if (viewer) {
                        viewer.innerHTML = `
                            <div class="step-viewer-empty">
                                <div class="step-viewer-empty-icon">📋</div>
                                <div>Selecione um passo no diagrama para visualizar</div>
                            </div>
                        `;
                    }
                    
                    updateFlowDiagram();
                }
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // ========== ATUALIZAR DIAGRAMA DE FLUXO ==========
        function updateFlowDiagram() {
            const diagram = document.getElementById('flowDiagram');
            
            if (Object.keys(tutorialSteps).length === 0) {
                diagram.innerHTML = '<p style="text-align: center; color: #6b7280; padding: 40px;">🌳 Crie o primeiro passo do tutorial<br><small>O diagrama aparecerá aqui em formato de árvore</small></p>';
                return;
            }
            
            let html = '<div class="flow-tree">';
            let stepNumber = 1;
            const stepsArray = Object.values(tutorialSteps);
            
            stepsArray.forEach(step => {
                const isActive = step.id == currentStepId;
                
                html += `
                    <div class="flow-step-container">
                        <div class="flow-step-node ${isActive ? 'active' : ''}" onclick="loadStepInEditor(${step.id})">
                            <div class="flow-step-header">
                                <div class="flow-step-number">${stepNumber++}</div>
                                <div class="flow-step-name">${step.name}</div>
                                <button onclick="event.stopPropagation(); deleteStep(${step.id})" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600; margin-left: auto;" title="Excluir passo">🗑️</button>
                            </div>
                `;
                
                if (step.questions && step.questions.length > 0) {
                    html += '<div class="flow-questions">';
                    step.questions.forEach((q, qIndex) => {
                        const isError = q.text.toLowerCase().includes('erro') || 
                                      q.text.toLowerCase().includes('não') ||
                                      q.text.toLowerCase().includes('falha');
                        // Se a pergunta leva a outro passo, tornar clicável
                        const destinationStepId = q.proximo;
                        
                        const isNextBlock = destinationStepId === 'next_block' || destinationStepId == 505;
                        const isFirst = qIndex === 0;
                        const isLast = qIndex === step.questions.length - 1;
                        
                        html += `
                            <div class="flow-question ${isError ? 'error' : ''}" style="position: relative; padding-right: 120px;">
                                <div style="cursor: pointer;" onclick="${destinationStepId && !isNextBlock ? `event.stopPropagation(); loadStepInEditor(${destinationStepId})` : ''}" title="${destinationStepId && !isNextBlock ? 'Clique para abrir o passo destino' : ''}">
                                    <div class="flow-question-header">
                                        <div class="flow-question-icon">${isError ? '✗' : '✓'}</div>
                                        <div class="flow-question-text">${q.name}</div>
                                    </div>
                                    <div class="flow-question-destination">${q.destination_name || 'Próximo bloco'}</div>
                                </div>
                                <div style="position: absolute; top: 8px; right: 8px; display: flex; gap: 4px;">
                                    ${!isFirst ? `<button onclick="event.stopPropagation(); moveQuestion(${step.id}, ${qIndex}, 'up')" style="background: #6b7280; color: white; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;" title="Mover para cima">↑</button>` : ''}
                                    ${!isLast ? `<button onclick="event.stopPropagation(); moveQuestion(${step.id}, ${qIndex}, 'down')" style="background: #6b7280; color: white; border: none; padding: 4px 6px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;" title="Mover para baixo">↓</button>` : ''}
                                    <button onclick="event.stopPropagation(); editQuestion(${step.id}, ${q.id})" style="background: #3b82f6; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;" title="Editar pergunta">✏️</button>
                                    <button onclick="event.stopPropagation(); deleteQuestion(${step.id}, ${q.id})" style="background: #ef4444; color: white; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: 600;" title="Excluir pergunta">🗑️</button>
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                } else {
                    html += '<div style="margin-top: 10px; padding: 8px; background: #fef3c7; border: 1px dashed #f59e0b; border-radius: 6px; color: #92400e; font-size: 12px; text-align: center;">⚠️ Adicione perguntas a este passo</div>';
                }
                
                html += `
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            diagram.innerHTML = html;
        }

        // ========== CARREGAR PASSO NO EDITOR ==========
        function loadStepInEditor(stepId) {
            const step = tutorialSteps[stepId];
            if (!step) return;
            
            // Adicionar animação de transição
            const editorPanel = document.querySelector('.editor-panel');
            if (editorPanel) {
                editorPanel.classList.remove('step-transition');
                void editorPanel.offsetWidth; // Forçar reflow
                editorPanel.classList.add('step-transition');
            }
            
            currentStepId = stepId;
            document.getElementById('currentStepId').value = stepId;
            document.getElementById('editorTitle').textContent = 'Editando: ' + step.name;
            document.getElementById('stepName').value = step.name;
            
            // Mostrar botão de salvar, esconder botão de criar
            document.getElementById('btnCriarNovoPasso').style.display = 'none';
            document.getElementById('btnSalvarPasso').style.display = 'block';
            
            // Limpar upload anterior
            document.getElementById('mediaFile').value = '';
            document.getElementById('uploadMediaBtn').style.display = 'none';
            uploadedMediaFile = null;
            
            // Mostrar ou ocultar botão de remover mídia
            const removeMediaBtn = document.getElementById('removeMediaBtn');
            
            // Mostrar mídia existente se houver
            const mediaPreview = document.getElementById('mediaPreview');
            if (step.src && step.src.trim() !== '') {
                mediaPreview.classList.add('active');
                removeMediaBtn.style.display = 'block'; // Mostrar botão de remover
                
                // Adicionar ../ no início se não tiver
                const mediaSrc = step.src.startsWith('http') ? step.src : (step.src.startsWith('../') ? step.src : '../' + step.src);
                
                const src = mediaSrc.toLowerCase();
                const isVideo = src.match(/\.(mp4|webm|ogg)$/i);
                const isImage = src.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                
                if (isVideo) {
                    const extension = mediaSrc.split('.').pop().toLowerCase();
                    // Criar elemento de vídeo com key única para forçar recarga
                    mediaPreview.innerHTML = `<video controls style="max-width: 100%; border-radius: 8px;" key="${Date.now()}">
                                                <source src="${mediaSrc}" type="video/${extension}">
                                                Seu navegador não suporta vídeos.
                                              </video>`;
                    // Forçar carregamento do vídeo
                    const videoElement = mediaPreview.querySelector('video');
                    if (videoElement) {
                        videoElement.load();
                    }
                } else if (isImage) {
                    mediaPreview.innerHTML = `<img src="${mediaSrc}" alt="Mídia do passo" style="max-width: 100%; border-radius: 8px;">`;
                } else {
                    mediaPreview.innerHTML = `<p>📎 ${mediaSrc}</p>`;
                }
            } else {
                mediaPreview.classList.remove('active');
                mediaPreview.innerHTML = '';
                removeMediaBtn.style.display = 'none'; // Ocultar botão de remover
            }
            
            if (quillEditor) {
                quillEditor.root.innerHTML = step.html;
                document.getElementById('stepHtml').value = step.html;
            }
            
            updateFlowDiagram();
            updateStepViewer(step);
            
            // Adicionar animação no preview também
            const stepViewer = document.getElementById('stepViewer');
            if (stepViewer) {
                stepViewer.classList.remove('step-transition');
                void stepViewer.offsetWidth; // Forçar reflow
                stepViewer.classList.add('step-transition');
            }
        }
        
        // ========== ATUALIZAR PREVIEW DO PASSO ==========
        function updateStepViewer(step) {
            const viewer = document.getElementById('stepViewer');
            
            if (!step) {
                viewer.innerHTML = `
                    <div class="step-viewer-empty">
                        <div class="step-viewer-empty-icon">👁️</div>
                        <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Preview do Passo</div>
                        <div style="font-size: 14px;">Selecione um passo no diagrama para visualizar</div>
                    </div>
                `;
                return;
            }
            
            let html = `
                <div class="step-viewer-header">
                    <div class="step-viewer-subtitle">📝 Preview do Tutorial</div>
                    <div class="step-viewer-title">${step.name}</div>
                </div>
            `;
            
            // Mídia (primeiro, como no viwer.php)
            if (step.src && step.src.trim() !== '') {
                // Adicionar ../ no início se não tiver
                const mediaSrc = step.src.startsWith('http') ? step.src : (step.src.startsWith('../') ? step.src : '../' + step.src);
                
                html += `
                    <div>
                        <div style="font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 8px; text-transform: uppercase;">🖼️ Mídia</div>
                        <div class="step-viewer-media">
                `;
                
                const src = mediaSrc.toLowerCase();
                const isVideo = src.match(/\.(mp4|webm|ogg)$/i);
                const isImage = src.match(/\.(jpg|jpeg|png|gif|webp)$/i);
                
                if (isVideo) {
                    const extension = mediaSrc.split('.').pop().toLowerCase();
                    html += `<video controls style="max-width: 100%; border-radius: 8px;" key="${Date.now()}">
                                <source src="${mediaSrc}" type="video/${extension}">
                                Seu navegador não suporta vídeos.
                             </video>`;
                } else if (isImage) {
                    html += `<img src="${mediaSrc}" alt="Mídia do passo" style="max-width: 100%; border-radius: 8px;">`;
                } else {
                    html += `<p style="color: #6b7280;">📎 ${mediaSrc}</p>`;
                }
                
                html += `
                        </div>
                    </div>
                `;
            }
            
            // Conteúdo HTML (segundo, como no viwer.php)
            if (step.html && step.html.trim() !== '') {
                html += `
                    <div>
                        <div style="font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 8px; text-transform: uppercase;">✨ Conteúdo</div>
                        <div class="step-viewer-content">${step.html}</div>
                    </div>
                `;
            }
            
            // Perguntas (terceiro, como no viwer.php)
            if (step.questions && step.questions.length > 0) {
                html += `
                    <div>
                        <div style="font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 8px; text-transform: uppercase;">❓ Perguntas</div>
                        <div class="step-viewer-questions">
                `;
                
                step.questions.forEach(q => {
                    const isError = q.text.toLowerCase().includes('erro') || 
                                  q.text.toLowerCase().includes('não') ||
                                  q.text.toLowerCase().includes('falha');
                    html += `
                        <div class="step-viewer-question ${isError ? 'error' : 'success'}">
                            <div style="font-weight: 600; margin-bottom: 4px;">${isError ? '❌' : '✅'} ${q.text}</div>
                            <div style="font-size: 12px; color: #6b7280;">→ ${q.destination_name || 'Próximo bloco'}</div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            } else {
                html += `
                    <div style="background: #fef3c7; border: 2px dashed #f59e0b; border-radius: 8px; padding: 16px; text-align: center;">
                        <div style="color: #92400e; font-weight: 600; margin-bottom: 4px;">⚠️ Nenhuma pergunta adicionada</div>
                        <div style="color: #92400e; font-size: 13px;">Adicione perguntas para continuar o fluxo</div>
                    </div>
                `;
            }
            
            viewer.innerHTML = html;
            
            // Forçar load dos vídeos após inserção no DOM
            const videos = viewer.querySelectorAll('video');
            videos.forEach(video => video.load());
        }

        // ========== FINALIZAR TUTORIAL ==========
        async function finishTutorial() {
            if (Object.keys(tutorialSteps).length === 0) {
                alert('Crie pelo menos um passo antes de concluir o tutorial');
                return;
            }
            
            if (!confirm('Deseja concluir e salvar este tutorial?')) {
                return;
            }

            try {
                // Se for edição de tutorial rejeitado, enviar clear_rejection
                const formData = new FormData();
                formData.append('action', 'save');
                formData.append('id', currentTutorialId);
                formData.append('name', document.getElementById('tutorialName').value);
                
                // Obter IDs dos steps
                const stepIds = Object.keys(tutorialSteps).join(',');
                formData.append('id_step', stepIds);
                
                // Enviar clear_rejection APENAS se o tutorial estava rejeitado
                if (isRejectedTutorial) {
                    formData.append('clear_rejection', 'true');
                }

                const response = await fetch('../src/php/crud_blocos.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                
                if (result.success) {
                    alert(result.message || 'Tutorial salvo com sucesso!');
                    closeModal();
                    // Limpar parâmetros da URL antes de recarregar
                    window.history.replaceState({}, document.title, 'gestao_blocos.php');
                    location.reload();
                } else {
                    alert('Erro: ' + (result.message || 'Erro ao salvar tutorial'));
                }
            } catch (error) {
                console.error('Erro ao salvar tutorial:', error);
                alert('Erro ao salvar tutorial');
            }
        }

        // ========== FUNÇÕES DE EDIÇÃO E EXCLUSÃO ==========
        async function editTutorial(id, event) {
            // Desabilitar botão enquanto carrega
            const btn = event ? event.target : null;
            let originalContent = '';
            if (btn) {
                originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span>';
            }
            
            try {
                // Verificar se o tutorial está aprovado
                const checkResponse = await fetch(`../src/php/crud_tutoriais_flow.php?action=check_status&tutorial_id=${id}`);
                const checkData = await checkResponse.json();
                
                // Se está aprovado, criar um clone
                if (checkData.is_approved) {
                    const userConfirmed = await showConfirm(
                        '⚠️ Este tutorial está aprovado.\n\nSuas alterações criarão uma versão pendente que precisará ser aprovada novamente.\n\nDeseja continuar?',
                        null,
                        'Tutorial Aprovado'
                    );
                    
                    if (!userConfirmed) {
                        if (btn) {
                            btn.disabled = false;
                            btn.innerHTML = originalContent;
                        }
                        return;
                    }
                    
                    // Criar clone
                    const cloneResponse = await fetch('../src/php/crud_tutoriais_flow.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'clone_tutorial',
                            tutorial_id: id
                        })
                    });
                    
                    const cloneResult = await cloneResponse.json();
                    
                    if (!cloneResult.success) {
                        throw new Error(cloneResult.message || 'Erro ao criar clone');
                    }
                    
                    // Usar o ID do clone
                    id = cloneResult.clone_id;
                    
                    // Recarregar lista para mostrar o clone
                    await reloadBlocosList();
                    
                    showAlert('⚠️ Modo de edição: Suas alterações ficarão pendentes até aprovação.', 'warning');
                }
                
                currentTutorialId = id;
                document.getElementById('tutorialId').value = id;
                
                // Verificar se estava rejeitado e carregar nome do tutorial
                const blocoResponse = await fetch(`../src/php/crud_blocos.php?action=get&id=${id}`);
                const blocoData = await blocoResponse.json();
                isRejectedTutorial = blocoData.success && blocoData.bloco && blocoData.bloco.accept == 2;
                
                // Preencher nome do tutorial no campo
                if (blocoData.success && blocoData.bloco) {
                    document.getElementById('tutorialName').value = blocoData.bloco.name;
                }
                
                // Carregar dados do tutorial
                await loadTutorialSteps();
                
                // Ir direto para a etapa 2
                document.getElementById('step1').classList.remove('active');
                document.getElementById('step2').classList.add('active');
                document.getElementById('tutorialModal').classList.add('active');
                
                // Mostrar botão Salvar Tutorial no header
                document.getElementById('btnSaveTutorial').style.display = 'block';
                
                // Limpar editor ao abrir novo tutorial
                initializeStepEditor();
                
                // Reabilitar botão
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
            } catch (error) {
                console.error('Erro ao editar:', error);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
                showAlert('Erro ao carregar tutorial para edição', 'error');
            }
        }

        // ========== ENVIAR PARA ANÁLISE ==========
        async function sendToReview(type, id, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const itemName = type === 'tutorial' ? 'tutorial' : 'serviço';
            const confirmed = await showConfirm(`Deseja enviar este ${itemName} para análise? Os administradores serão notificados.`);
            if (!confirmed) return;
            
            showConfirmLoading(`Enviando ${itemName} para análise...`);
            
            const btn = event.target;
            btn.disabled = true;
            
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
                    showConfirmSuccess(result.message);
                    
                    setTimeout(() => {
                        closeCustomConfirm();
                        location.reload();
                    }, 2000);
                } else {
                    showConfirmError(result.message || 'Erro ao enviar para análise');
                    btn.disabled = false;
                    
                    setTimeout(() => {
                        closeCustomConfirm();
                    }, 3000);
                }
            } catch (error) {
                console.error('Erro ao enviar para análise:', error);
                showConfirmError('Erro ao enviar para análise');
                btn.disabled = false;
                
                setTimeout(() => {
                    closeCustomConfirm();
                }, 3000);
            }
        }

        async function approveTutorial(id, event) {
            event.preventDefault();
            event.stopPropagation();
            
            const confirmed = await showConfirm('Deseja aprovar este tutorial? Ele ficará disponível para uso.');
            if (!confirmed) return;
            
            // Mostrar loading no modal
            showConfirmLoading('Aprovando tutorial...');
            
            const btn = event.target;
            btn.disabled = true;
            
            try {
                const response = await fetch('../src/php/crud_tutoriais_flow.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'approve',
                        tutorial_id: id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showConfirmSuccess('Tutorial aprovado com sucesso!');
                    
                    // Recarregar página após 2 segundos
                    setTimeout(() => {
                        closeCustomConfirm();
                        location.reload();
                    }, 2000);
                } else {
                    showConfirmError(result.message || 'Erro ao aprovar tutorial');
                    btn.disabled = false;
                    
                    setTimeout(() => {
                        closeCustomConfirm();
                    }, 3000);
                }
            } catch (error) {
                console.error('Erro ao aprovar tutorial:', error);
                showConfirmError('Erro ao aprovar tutorial');
                btn.disabled = false;
                
                setTimeout(() => {
                    closeCustomConfirm();
                }, 3000);
            }
        }
        
        async function deleteTutorial(id, event) {
            const confirmed = await showConfirm('Tem certeza que deseja excluir este tutorial?', null, 'Excluir Tutorial');
            if (!confirmed) return;
            
            // Desabilitar botão enquanto processa
            const btn = event ? event.target : null;
            let originalContent = '';
            if (btn) {
                originalContent = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span style="display: inline-block; animation: spin 1s linear infinite;">⏳</span>';
            }
            
            try {
                const response = await fetch('../src/php/crud_blocos.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete&id=' + id
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (btn) btn.innerHTML = '✓';
                    showAlert(result.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = originalContent;
                    }
                    showAlert(result.message, 'error', 'Erro ao excluir');
                }
            } catch (error) {
                console.error('Erro:', error);
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = originalContent;
                }
                showAlert('Erro ao excluir tutorial', 'error');
            }
        }
        
        // ========== VERIFICAR SE VEIO DO REDIRECT COM PARÂMETRO ?edit=ID ==========
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const editId = urlParams.get('edit');
            
            if (editId) {
                console.log('Abrindo modal para editar tutorial ID:', editId);
                
                // Mostrar loading overlay
                const loadingOverlay = document.getElementById('pageLoadingOverlay');
                if (loadingOverlay) {
                    loadingOverlay.classList.add('active');
                    document.body.classList.add('loading-tutorial');
                }
                
                // Abrir modal de edição automaticamente
                setTimeout(async () => {
                    await editTutorial(editId);
                    
                    // Remover loading após carregar
                    setTimeout(() => {
                        if (loadingOverlay) {
                            loadingOverlay.classList.remove('active');
                            document.body.classList.remove('loading-tutorial');
                        }
                    }, 800);
                }, 500);
            }
        });
    </script>
</body>
</html>
