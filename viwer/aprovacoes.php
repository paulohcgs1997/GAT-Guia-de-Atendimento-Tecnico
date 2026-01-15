<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_approver();

// Apenas admin e departamento podem aprovar
if ($_SESSION['perfil'] != '1' && $_SESSION['perfil'] != '3') {
    header('Location: dashboard.php');
    exit;
}

include_once(__DIR__ . '/../src/config/conexao.php');

// Buscar dados do usuário logado
$user_query = "SELECT perfil, departamento FROM usuarios WHERE id = ?";
$stmt = $mysqli->prepare($user_query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$user_perfil = $user_data['perfil'];
$user_departamento = $user_data['departamento'];

// Filtro de departamento para usuários tipo departamento
$dept_filter = '';
if ($user_perfil == '3' && $user_departamento) {
    $dept_filter = " AND b.departamento = $user_departamento";
}

// Buscar tutoriais pendentes (apenas accept = 0 e sem motivo de rejeição)
$tutoriais_query = "SELECT b.*, 
                    CASE 
                        WHEN b.is_clone = 1 THEN CONCAT('📝 Atualização de: ', bo.name)
                        ELSE b.name
                    END as display_name,
                    b.is_clone,
                    bo.name as original_name,
                    d.name as dept_name
                    FROM blocos b
                    LEFT JOIN blocos bo ON b.original_id = bo.id
                    LEFT JOIN departaments d ON b.departamento = d.id
                    WHERE b.accept = 0 AND b.active = 1 AND (b.rejection_reason IS NULL OR b.rejection_reason = '')
                    $dept_filter
                    ORDER BY b.is_clone DESC, b.last_modification DESC";
$tutoriais = $mysqli->query($tutoriais_query);

// Filtro de departamento para serviços
$dept_filter_services = '';
if ($user_perfil == '3' && $user_departamento) {
    $dept_filter_services = " AND s.departamento = $user_departamento";
}

// Buscar serviços pendentes com tutoriais vinculados (apenas accept = 0 e sem motivo de rejeição)
$servicos_query = "SELECT s.*, d.name as dept_name,
                   CASE 
                       WHEN s.is_clone = 1 THEN CONCAT('📝 Atualização de: ', so.name)
                       ELSE s.name
                   END as display_name,
                   s.is_clone,
                   so.name as original_name
                   FROM services s
                   LEFT JOIN departaments d ON s.departamento = d.id
                   LEFT JOIN services so ON s.original_id = so.id
                   WHERE s.accept = 0 AND s.active = 1 AND (s.rejection_reason IS NULL OR s.rejection_reason = '')
                   $dept_filter_services
                   ORDER BY s.is_clone DESC, s.last_modification DESC";
$servicos = $mysqli->query($servicos_query);

// Função para buscar nomes dos tutoriais
function getTutoriaisNomes($mysqli, $blocoIds) {
    if (empty($blocoIds)) return [];
    $ids = explode(',', $blocoIds);
    $tutoriais = [];
    foreach ($ids as $id) {
        $stmt = $mysqli->prepare("SELECT id, name FROM blocos WHERE id = ? AND active = 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($tutorial = $result->fetch_assoc()) {
            $tutoriais[] = $tutorial;
        }
    }
    return $tutoriais;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once PROJECT_ROOT . '/src/includes/head_config.php'; ?>
    <link rel="stylesheet" href="../src/css/style.css">
    <style>
        .approval-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .approval-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .approval-section h2 {
            margin: 0 0 20px 0;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .approval-item {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .approval-item:hover {
            border-color: #2563eb;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
        }
        
        .approval-item.is-update {
            background: #fef3c7;
            border-color: #f59e0b;
        }
        
        .approval-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .approval-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .approval-meta {
            font-size: 13px;
            color: #6b7280;
        }
        
        .approval-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-new {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-update {
            background: #fef3c7;
            color: #92400e;
        }
        
        .approval-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-preview {
            background: #6b7280;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        
        .update-warning {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 13px;
            color: #92400e;
        }
        
        /* Estilos para preview de tutoriais */
        .service-approval {
            border: 3px solid #3b82f6;
        }
        
        .tutorials-container {
            margin: 20px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        
        .tutorials-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #cbd5e1;
        }
        
        .tutorials-header h4 {
            margin: 0;
            color: #1e293b;
            font-size: 16px;
        }
        
        .info-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .tutorial-preview-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .tutorial-preview-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .tutorial-preview-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            background: #f1f5f9;
        }
        
        .tutorial-preview-header h5 {
            margin: 0 0 4px 0;
            color: #1e293b;
            font-size: 15px;
        }
        
        .tutorial-steps-count {
            font-size: 12px;
            color: #64748b;
            background: white;
            padding: 2px 8px;
            border-radius: 12px;
            display: inline-block;
        }
        
        .tutorial-actions-mini {
            display: flex;
            gap: 8px;
        }
        
        .btn-preview-mini {
            background: #3b82f6;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .btn-preview-mini:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .tutorial-preview-content {
            padding: 20px;
            background: white;
            border-top: 1px solid #e2e8f0;
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                max-height: 0;
            }
            to {
                opacity: 1;
                max-height: 1000px;
            }
        }
        
        .steps-flow {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .step-preview-item {
            display: flex;
            gap: 16px;
            padding: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .step-preview-item:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .step-number {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
        }
        
        .step-details {
            flex: 1;
        }
        
        .step-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .step-html {
            color: #475569;
            font-size: 13px;
            line-height: 1.5;
            margin-bottom: 8px;
        }
        
        .step-media-indicator,
        .step-questions-indicator {
            display: inline-block;
            padding: 4px 10px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 11px;
            color: #64748b;
            margin-right: 8px;
        }
        
        .no-tutorials-warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
            color: #92400e;
            font-size: 14px;
        }
        
        .btn-approve-all {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s;
            flex: 1;
        }
        
        .btn-approve-all:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .btn-approve-all:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }
    </style>
</head>
<body>
    
        <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>

    <?php include_once __DIR__ . '/includes/quick_menu.php'; ?>

    <main>
        <div class="approval-container">
            <div class="page-header">
                <h1>✅ Aprovações Pendentes</h1>
            </div>

            <!-- Tutoriais Pendentes -->
            <div class="approval-section">
                <h2>📚 Tutoriais Pendentes</h2>
                <?php if ($tutoriais->num_rows > 0): ?>
                    <?php while($tutorial = $tutoriais->fetch_assoc()): ?>
                        <div class="approval-item <?= $tutorial['is_clone'] ? 'is-update' : '' ?>">
                            <?php if ($tutorial['is_clone']): ?>
                                <div class="update-warning">
                                    ⚠️ <strong>Atualização:</strong> Ao aprovar, o tutorial original "<?= htmlspecialchars($tutorial['original_name']) ?>" será substituído por esta versão.
                                </div>
                            <?php endif; ?>
                            
                            <div class="approval-header">
                                <div>
                                    <div class="approval-title">
                                        <?= htmlspecialchars($tutorial['display_name']) ?>
                                    </div>
                                    <div class="approval-meta">
                                        ID: <?= $tutorial['id'] ?> | 
                                        Modificado em: <?= date('d/m/Y H:i', strtotime($tutorial['last_modification'])) ?>
                                    </div>
                                </div>
                                <span class="approval-badge <?= $tutorial['is_clone'] ? 'badge-update' : 'badge-new' ?>">
                                    <?= $tutorial['is_clone'] ? '🔄 Atualização' : '🆕 Novo' ?>
                                </span>
                            </div>
                            
                            <div class="approval-actions">
                                <button class="btn-approve" onclick="aprovarItem('tutorial', <?= $tutorial['id'] ?>, <?= $tutorial['is_clone'] ?>)">
                                    ✅ Aprovar
                                </button>
                                <button class="btn-reject" onclick="rejeitarItem('tutorial', <?= $tutorial['id'] ?>)">
                                    ❌ Rejeitar
                                </button>
                                <button class="btn-preview" onclick="visualizarTutorial(<?= $tutorial['id'] ?>)">
                                    👁️ Visualizar
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Nenhum tutorial pendente</div>
                        <div style="font-size: 14px;">Todos os tutoriais foram aprovados</div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Serviços Pendentes -->
            <div class="approval-section">
                <h2>🛠️ Serviços Pendentes (com Tutoriais Vinculados)</h2>
                <?php if ($servicos->num_rows > 0): ?>
                    <?php while($servico = $servicos->fetch_assoc()): 
                        $tutoriaisVinculados = getTutoriaisNomes($mysqli, $servico['blocos']);
                    ?>
                        <div class="approval-item service-approval <?= $servico['is_clone'] ? 'is-update' : '' ?>">
                            <?php if ($servico['is_clone']): ?>
                                <div class="update-warning">
                                    ⚠️ <strong>Atualização:</strong> Ao aprovar, o serviço original "<?= htmlspecialchars($servico['original_name']) ?>" será substituído por esta versão.
                                </div>
                            <?php endif; ?>
                            
                            <div class="approval-header">
                                <div style="flex: 1;">
                                    <div class="approval-title">
                                        🛠️ <?= htmlspecialchars($servico['display_name']) ?>
                                    </div>
                                    <div class="approval-meta">
                                        ID: <?= $servico['id'] ?> | 
                                        Departamento: <strong><?= htmlspecialchars($servico['dept_name']) ?></strong> | 
                                        Modificado em: <?= date('d/m/Y H:i', strtotime($servico['last_modification'])) ?>
                                    </div>
                                    <?php if ($servico['description']): ?>
                                        <div style="margin-top: 8px; color: #6b7280; font-size: 14px;">
                                            <?= nl2br(htmlspecialchars($servico['description'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <span class="approval-badge <?= $servico['is_clone'] ? 'badge-update' : 'badge-new' ?>">
                                    <?= $servico['is_clone'] ? '🔄 Atualização' : '🆕 Novo' ?>
                                </span>
                            </div>
                            
                            <!-- Tutoriais Vinculados com Preview -->
                            <?php if (!empty($tutoriaisVinculados)): ?>
                                <div class="tutorials-container">
                                    <div class="tutorials-header">
                                        <h4>📚 Tutoriais Vinculados (<?= count($tutoriaisVinculados) ?>)</h4>
                                        <span class="info-badge">✅ Aprovar tudo em conjunto</span>
                                    </div>
                                    
                                    <?php foreach ($tutoriaisVinculados as $tutorial): 
                                        // Buscar dados completos do tutorial
                                        $tutorial_stmt = $mysqli->prepare("SELECT * FROM blocos WHERE id = ? AND active = 1");
                                        $tutorial_stmt->bind_param('i', $tutorial['id']);
                                        $tutorial_stmt->execute();
                                        $tutorial_full = $tutorial_stmt->get_result()->fetch_assoc();
                                        
                                        if (!$tutorial_full) continue;
                                        
                                        // Buscar steps do tutorial
                                        $steps = [];
                                        if (!empty($tutorial_full['id_step'])) {
                                            $stepIds = explode(',', $tutorial_full['id_step']);
                                            foreach ($stepIds as $stepId) {
                                                $step_stmt = $mysqli->prepare("SELECT s.*, GROUP_CONCAT(q.id) as question_ids, GROUP_CONCAT(q.name) as question_names 
                                                                               FROM steps s 
                                                                               LEFT JOIN questions q ON FIND_IN_SET(q.id, s.questions) 
                                                                               WHERE s.id = ? AND s.active = 1 
                                                                               GROUP BY s.id");
                                                $step_stmt->bind_param('i', $stepId);
                                                $step_stmt->execute();
                                                $step = $step_stmt->get_result()->fetch_assoc();
                                                if ($step) $steps[] = $step;
                                            }
                                        }
                                    ?>
                                        <div class="tutorial-preview-card">
                                            <div class="tutorial-preview-header">
                                                <div>
                                                    <h5>📖 <?= htmlspecialchars($tutorial_full['name']) ?></h5>
                                                    <span class="tutorial-steps-count"><?= count($steps) ?> passos</span>
                                                </div>
                                                <div class="tutorial-actions-mini">
                                                    <button class="btn-preview-mini" onclick="toggleTutorialPreview(<?= $tutorial['id'] ?>)">
                                                        <span id="preview-icon-<?= $tutorial['id'] ?>">👁️</span> Ver Conteúdo
                                                    </button>
                                                    <button class="btn-preview-mini" onclick="window.open('preview_tutorial.php?id=<?= $tutorial['id'] ?>', '_blank', 'width=1200,height=800')">
                                                        🔍 Abrir em Janela
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <!-- Preview Expandível -->
                                            <div class="tutorial-preview-content" id="tutorial-preview-<?= $tutorial['id'] ?>" style="display: none;">
                                                <div class="steps-flow">
                                                    <?php foreach ($steps as $index => $step): ?>
                                                        <div class="step-preview-item">
                                                            <div class="step-number"><?= $index + 1 ?></div>
                                                            <div class="step-details">
                                                                <div class="step-name"><?= htmlspecialchars($step['name']) ?></div>
                                                                <div class="step-html"><?= substr(strip_tags($step['html']), 0, 200) ?>...</div>
                                                                <?php if ($step['src']): ?>
                                                                    <div class="step-media-indicator">🎬 Contém mídia</div>
                                                                <?php endif; ?>
                                                                <?php if ($step['questions']): ?>
                                                                    <div class="step-questions-indicator">
                                                                        ❓ <?= substr_count($step['questions'], ',') + 1 ?> perguntas
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-tutorials-warning">
                                    ⚠️ <strong>Atenção:</strong> Este serviço não possui tutoriais vinculados
                                </div>
                            <?php endif; ?>
                            
                            <div class="approval-actions">
                                <button class="btn-approve-all" onclick="aprovarServicoCompleto(<?= $servico['id'] ?>, <?= $servico['is_clone'] ?>)">
                                    ✅ Aprovar Serviço + Todos os Tutoriais
                                </button>
                                <button class="btn-reject" onclick="rejeitarItem('servico', <?= $servico['id'] ?>)">
                                    ❌ Rejeitar Serviço
                                </button>
                                <button class="btn-preview" onclick="visualizarServico(<?= $servico['id'] ?>)">
                                    🚀 Testar Serviço Completo
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📋</div>
                        <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Nenhum serviço pendente</div>
                        <div style="font-size: 14px;">Todos os serviços foram aprovados</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

    <!-- Modal de Rejeição -->
    <div id="rejectModal" class="reject-modal" style="display: none;">
        <div class="reject-modal-content">
            <div class="reject-modal-header">
                <h3>❌ Motivo da Rejeição</h3>
                <button class="close-reject-modal" onclick="closeRejectModal()">&times;</button>
            </div>
            <div class="reject-modal-body">
                <p>Informe o motivo da rejeição para que o criador possa corrigir:</p>
                <textarea id="rejectReason" placeholder="Ex: O passo 3 está com informações incorretas sobre..." rows="5"></textarea>
                <p style="font-size: 11px; color: #9ca3af; margin-top: 8px;">💡 Dica: Pressione Ctrl+Enter para confirmar rapidamente</p>
                <div class="reject-actions">
                    <button class="btn-reject-confirm" onclick="confirmReject()">Confirmar Rejeição</button>
                    <button class="btn-reject-cancel" onclick="closeRejectModal()">Cancelar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .reject-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.2s;
        }
        
        .reject-modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s;
        }
        
        .reject-modal-header {
            padding: 20px 24px;
            border-bottom: 2px solid #fee2e2;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .reject-modal-header h3 {
            margin: 0;
            color: #dc2626;
        }
        
        .close-reject-modal {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #9ca3af;
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s;
        }
        
        .close-reject-modal:hover {
            background: #f3f4f6;
            color: #4b5563;
        }
        
        .reject-modal-body {
            padding: 24px;
        }
        
        .reject-modal-body p {
            margin: 0 0 16px 0;
            color: #4b5563;
            font-size: 14px;
        }
        
        #rejectReason {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            transition: border-color 0.2s;
        }
        
        #rejectReason:focus {
            outline: none;
            border-color: #ef4444;
        }
        
        .reject-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn-reject-confirm {
            flex: 1;
            background: #ef4444;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-reject-confirm:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .btn-reject-cancel {
            flex: 1;
            background: #f3f4f6;
            color: #4b5563;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-reject-cancel:hover {
            background: #e5e7eb;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

    <script>
        async function aprovarItem(tipo, id, isClone) {
            const tipoLabel = tipo === 'tutorial' ? 'tutorial' : 'serviço';
            let message = `Deseja aprovar este ${tipoLabel}?`;
            
            if (isClone) {
                message = `⚠️ ATENÇÃO: Este é uma atualização!\n\nAo aprovar, o ${tipoLabel} original será SUBSTITUÍDO por esta versão.\n\nDeseja continuar?`;
            }
            
            if (!confirm(message)) return;
            
            try {
                const response = await fetch('../src/php/approve_items.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'approve',
                        type: tipo,
                        id: id
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao aprovar item');
            }
        }
        
        async function aprovarServicoCompleto(servicoId, isClone) {
            let message = '✅ Aprovar o serviço e TODOS os tutoriais vinculados?\n\nIsso aprovará tudo de uma vez!';
            
            if (isClone) {
                message = '⚠️ ATENÇÃO: Este é uma atualização!\n\nAo aprovar, o serviço original será SUBSTITUÍDO por esta versão.\n\n✅ Aprovar o serviço e TODOS os tutoriais vinculados?';
            }
            
            if (!confirm(message)) return;
            
            // Desabilitar botão
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ Processando aprovações...';
            
            try {
                const response = await fetch('../src/php/approve_items.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'approve_service_complete',
                        service_id: servicoId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert(`✅ ${result.message}\n\n📊 Resumo:\n• Serviço aprovado: ${result.data.service_name}\n• Tutoriais aprovados: ${result.data.tutorials_approved}`);
                    location.reload();
                } else {
                    alert('❌ ' + result.message);
                    btn.disabled = false;
                    btn.textContent = '✅ Aprovar Serviço + Todos os Tutoriais';
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao aprovar: ' + error.message);
                btn.disabled = false;
                btn.textContent = '✅ Aprovar Serviço + Todos os Tutoriais';
            }
        }
        
        function toggleTutorialPreview(tutorialId) {
            const previewDiv = document.getElementById(`tutorial-preview-${tutorialId}`);
            const icon = document.getElementById(`preview-icon-${tutorialId}`);
            
            if (previewDiv.style.display === 'none') {
                previewDiv.style.display = 'block';
                icon.textContent = '🔼';
            } else {
                previewDiv.style.display = 'none';
                icon.textContent = '👁️';
            }
        }
        
        let currentRejectType = null;
        let currentRejectId = null;
        
        function rejeitarItem(tipo, id) {
            currentRejectType = tipo;
            currentRejectId = id;
            document.getElementById('rejectModal').style.display = 'flex';
            document.getElementById('rejectReason').value = '';
            document.getElementById('rejectReason').focus();
        }
        
        function closeRejectModal() {
            document.getElementById('rejectModal').style.display = 'none';
            currentRejectType = null;
            currentRejectId = null;
        }
        
        // Adicionar listener para Ctrl+Enter no textarea
        document.addEventListener('DOMContentLoaded', function() {
            const textarea = document.getElementById('rejectReason');
            if (textarea) {
                textarea.addEventListener('keydown', function(e) {
                    if (e.ctrlKey && e.key === 'Enter') {
                        e.preventDefault();
                        confirmReject();
                    }
                    if (e.key === 'Escape') {
                        closeRejectModal();
                    }
                });
            }
            
            // Fechar modal ao clicar fora
            document.getElementById('rejectModal')?.addEventListener('click', function(e) {
                if (e.target.id === 'rejectModal') {
                    closeRejectModal();
                }
            });
        });
        
        async function confirmReject() {
            const reason = document.getElementById('rejectReason').value.trim();
            
            if (!reason) {
                alert('⚠️ Por favor, informe o motivo da rejeição');
                return;
            }
            
            if (reason.length < 10) {
                alert('⚠️ O motivo deve ter pelo menos 10 caracteres');
                return;
            }
            
            // Desabilitar botão para evitar cliques múltiplos
            const confirmBtn = document.querySelector('.btn-reject-confirm');
            const originalText = confirmBtn.textContent;
            confirmBtn.disabled = true;
            confirmBtn.textContent = '⏳ Processando...';
            
            try {
                const response = await fetch('../src/php/approve_items.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'reject',
                        type: currentRejectType,
                        id: currentRejectId,
                        reason: reason
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ ' + result.message);
                    // Fechar modal e recarregar imediatamente
                    closeRejectModal();
                    window.location.reload();
                } else {
                    alert('❌ ' + result.message);
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = originalText;
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('❌ Erro ao rejeitar item: ' + error.message);
                confirmBtn.disabled = false;
                confirmBtn.textContent = originalText;
            }
        }
        
        function visualizarTutorial(id) {
            window.open(`preview_tutorial.php?id=${id}`, '_blank', 'width=1200,height=1080');
        }
        
        function visualizarServico(servicoId) {
            // Abrir viwer.php com o serviço selecionado
            window.open(`viwer.php?service_id=${servicoId}`, '_blank');
        }
    </script>
</body>
</html>
