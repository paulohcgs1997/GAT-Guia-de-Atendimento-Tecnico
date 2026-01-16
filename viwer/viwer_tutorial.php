<?php
include_once("includes.php");
check_login();
check_permission_viewer();

require_once(__DIR__ . '/../src/config/conexao.php');

$tutorial_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$tutorial_id) {
    header('Location: dashboard.php');
    exit;
}

// Buscar dados do tutorial
$stmt = $mysqli->prepare("SELECT b.*, d.name as dept_name FROM blocos b 
                          LEFT JOIN departaments d ON b.departamento = d.id 
                          WHERE b.id = ? AND b.active = 1");
$stmt->bind_param("i", $tutorial_id);
$stmt->execute();
$tutorial = $stmt->get_result()->fetch_assoc();

if (!$tutorial) {
    header('Location: dashboard.php');
    exit;
}

// Buscar steps do tutorial
$steps = [];
if (!empty($tutorial['id_step'])) {
    $stepIds = explode(',', $tutorial['id_step']);
    foreach ($stepIds as $stepId) {
        $stepId = trim($stepId);
        $stmt = $mysqli->prepare("SELECT * FROM steps WHERE id = ? AND active = 1");
        $stmt->bind_param('i', $stepId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($step = $result->fetch_assoc()) {
            // Buscar perguntas do step
            $step['questions_data'] = [];
            if (!empty($step['questions'])) {
                $questionIds = explode(',', $step['questions']);
                foreach ($questionIds as $qId) {
                    $qId = trim($qId);
                    $qStmt = $mysqli->prepare("SELECT * FROM questions WHERE id = ?");
                    $qStmt->bind_param('i', $qId);
                    $qStmt->execute();
                    $qResult = $qStmt->get_result();
                    if ($question = $qResult->fetch_assoc()) {
                        // Buscar nome do destino
                        if ($question['proximo'] == 'next_block') {
                            $question['destino_nome'] = 'Próximo Bloco';
                        } else {
                            $destId = $question['proximo'];
                            $destStmt = $mysqli->prepare("SELECT name FROM steps WHERE id = ?");
                            $destStmt->bind_param('i', $destId);
                            $destStmt->execute();
                            $destResult = $destStmt->get_result();
                            $dest = $destResult->fetch_assoc();
                            $question['destino_nome'] = $dest ? $dest['name'] : 'Desconhecido';
                        }
                        $step['questions_data'][] = $question;
                    }
                }
            }
            $steps[] = $step;
        }
    }
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

    <a href="dashboard.php" class="back-button">
        ← Voltar para o Dashboard
    </a>
    
    <div class="preview-layout">
        <!-- Árvore de Passos -->
        <div class="tree-column">
            <div class="tree-header">
                <h2>📖 <?= htmlspecialchars($tutorial['name']) ?></h2>
                <div class="tree-meta">
                    <?= count($steps) ?> passo(s)
                    <?php if (!empty($tutorial['dept_name'])): ?>
                         | 🏢 <?= htmlspecialchars($tutorial['dept_name']) ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="tree-content">
                <?php if (empty($steps)): ?>
                    <div style="text-align: center; padding: 20px; color: #9ca3af; font-size: 13px;">
                        Nenhum passo cadastrado
                    </div>
                <?php else: ?>
                    <?php foreach ($steps as $index => $step): ?>
                        <div class="tree-node" onclick="goToStep(<?= $step['id'] ?>)" id="tree-node-<?= $step['id'] ?>">
                            <div class="tree-node-title">
                                <span class="tree-node-number"><?= $index + 1 ?></span>
                                <?= htmlspecialchars($step['name']) ?>
                            </div>
                            
                            <?php if (!empty($step['questions_data'])): ?>
                                <div class="tree-questions">
                                    <?php foreach ($step['questions_data'] as $question): ?>
                                        <div class="tree-question">
                                            <span>❓</span>
                                            <span style="flex: 1;"><?= htmlspecialchars($question['text']) ?></span>
                                            <span class="tree-question-arrow">→</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Conteúdo do Passo -->
        <div class="content-column">
            <div class="content-header">
                <div class="content-title" id="currentStepTitle">Selecione um passo</div>
                <div class="content-subtitle" id="currentStepSubtitle">Clique em um passo na árvore para visualizar</div>
            </div>
            
            <div class="content-body" id="stepViewer">
                <?php if (empty($steps)): ?>
                    <div class="empty-state">
                        <div style="font-size: 64px; margin-bottom: 16px;">📋</div>
                        <div style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">Nenhum passo cadastrado</div>
                        <div style="font-size: 14px;">Este tutorial ainda não possui passos configurados</div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div style="font-size: 64px; margin-bottom: 16px;">👈</div>
                        <div style="font-size: 20px; font-weight: 600; margin-bottom: 8px;">Navegue pela árvore</div>
                        <div style="font-size: 14px;">Clique em um passo à esquerda para visualizar seu conteúdo</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer>
        <p>Sistema de Tutoriais - GAT</p>
    </footer>

    <script>
        const steps = <?= json_encode($steps) ?>;
        let currentStepId = null;
        
        // Função para corrigir links sem protocolo
        function fixLinks(html) {
            const div = document.createElement('div');
            div.innerHTML = html;
            
            const links = div.querySelectorAll('a[href]');
            links.forEach(link => {
                const href = link.getAttribute('href');
                if (href && !href.match(/^(https?:\/\/|mailto:|tel:|#|\/)/i)) {
                    link.setAttribute('href', 'https://' + href);
                }
            });
            
            return div.innerHTML;
        }
        
        // Carregar primeiro passo automaticamente
        <?php if (!empty($steps)): ?>
            window.addEventListener('DOMContentLoaded', () => {
                goToStep(<?= $steps[0]['id'] ?>);
            });
        <?php endif; ?>
        
        function goToStep(stepId) {
            const step = steps.find(s => s.id == stepId);
            if (!step) return;
            
            currentStepId = stepId;
            
            // Atualizar árvore
            document.querySelectorAll('.tree-node').forEach(node => {
                node.classList.remove('active');
            });
            document.getElementById('tree-node-' + stepId).classList.add('active');
            
            // Atualizar header
            document.getElementById('currentStepTitle').textContent = step.name;
            const stepIndex = steps.findIndex(s => s.id == stepId);
            document.getElementById('currentStepSubtitle').textContent = `Passo ${stepIndex + 1} de ${steps.length}`;
            
            // Renderizar conteúdo
            let html = '<div class="step-viewer">';
            
            // Conteúdo HTML
            if (step.html) {
                html += '<div class="step-html-content">' + fixLinks(step.html) + '</div>';
            }
            
            // Mídia (imagem ou vídeo) - suporta múltiplas URLs separadas por vírgula
            if (step.src) {
                const mediaUrls = step.src.split(/[,\n\r]+/).map(url => url.trim()).filter(url => url);
                
                mediaUrls.forEach(mediaSrc => {
                    // Corrigir caminho relativo
                    if (!mediaSrc.startsWith('http')) {
                        mediaSrc = mediaSrc.startsWith('../') ? mediaSrc : '../' + mediaSrc;
                    }
                    
                    const extension = mediaSrc.split('.').pop().toLowerCase();
                    const isVideo = ['mp4', 'webm', 'ogg', 'mov'].includes(extension);
                    
                    html += '<div class="step-media">';
                    if (isVideo) {
                        html += `<video controls key="${Date.now()}">
                                    <source src="${mediaSrc}" type="video/${extension}">
                                    Seu navegador não suporta vídeos.
                                 </video>`;
                    } else {
                        html += `<img src="${mediaSrc}" alt="${step.name}" loading="lazy">`;
                    }
                    html += '</div>';
                });
            }
            
            // Perguntas
            if (step.questions_data && step.questions_data.length > 0) {
                html += '<div class="step-questions">';
                html += '<div class="questions-header">❓ Próximas Ações</div>';
                
                step.questions_data.forEach(question => {
                    const nextStepId = question.proximo;
                    const nextStepParam = isNaN(nextStepId) ? `'${nextStepId}'` : nextStepId;
                    html += `<button class="question-button" onclick="handleQuestion(${nextStepParam})">
                                <span class="question-icon">💬</span>
                                <span style="flex: 1;">${question.text}</span>
                                <span style="color: #667eea;">→</span>
                             </button>`;
                });
                
                html += '</div>';
            }
            
            html += '</div>';
            
            document.getElementById('stepViewer').innerHTML = html;
            
            // Forçar carregamento de vídeos
            const videos = document.querySelectorAll('.step-media video');
            videos.forEach(video => video.load());
            
            // Scroll para o topo
            document.querySelector('.content-column').scrollTop = 0;
        }
        
        function handleQuestion(nextStepId) {
            if (nextStepId == 'next_block') {
                alert('🎉 Fim deste tutorial!\n\nVocê concluiu todos os passos deste tutorial.');
                return;
            }
            
            const nextStep = steps.find(s => s.id == nextStepId);
            if (nextStep) {
                goToStep(nextStepId);
            } else {
                alert('⚠️ Passo de destino não encontrado!\n\nID: ' + nextStepId);
            }
        }
    </script>
</body>
</html>
