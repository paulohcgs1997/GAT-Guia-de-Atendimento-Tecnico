<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_viewer();

include_once(__DIR__ . '/../src/config/conexao.php');

$tutorialId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$tutorialId) {
    die('ID do tutorial não fornecido');
}

// Buscar dados do tutorial
$stmt = $mysqli->prepare("SELECT * FROM blocos WHERE id = ? AND active = 1");
$stmt->bind_param('i', $tutorialId);
$stmt->execute();
$result = $stmt->get_result();
$tutorial = $result->fetch_assoc();

if (!$tutorial) {
    die('Tutorial não encontrado');
}

// Buscar steps do tutorial
$steps = [];
if (!empty($tutorial['id_step'])) {
    $stepIds = explode(',', $tutorial['id_step']);
    foreach ($stepIds as $stepId) {
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
                    $stmt = $mysqli->prepare("SELECT * FROM questions WHERE id = ?");
                    $stmt->bind_param('i', $qId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($question = $result->fetch_assoc()) {
                        // Buscar nome do destino
                        if ($question['proximo'] == 505) {
                            $question['destino_nome'] = 'Próximo Bloco';
                        } else {
                            $destStmt = $mysqli->prepare("SELECT name FROM steps WHERE id = ?");
                            $destStmt->bind_param('i', $question['proximo']);
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
    <title>Preview: <?= htmlspecialchars($tutorial['name']) ?></title>
    <link rel="stylesheet" href="../src/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f5f5f5;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            overflow: hidden;
        }
        
        .preview-layout {
            display: grid;
            grid-template-columns: 350px 1fr;
            height: 100vh;
            gap: 0;
        }
        
        /* Coluna da Árvore */
        .tree-column {
            background: white;
            border-right: 2px solid #e5e7eb;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0,0,0,0.05);
        }
        
        .tree-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .tree-header h2 {
            font-size: 18px;
            margin-bottom: 4px;
        }
        
        .tree-meta {
            font-size: 12px;
            opacity: 0.9;
        }
        
        .tree-content {
            padding: 20px;
        }
        
        /* Nós da Árvore */
        .tree-node {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .tree-node:hover {
            border-color: #667eea;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.15);
        }
        
        .tree-node.active {
            background: #eef2ff;
            border-color: #667eea;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.2);
        }
        
        .tree-node-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tree-node-number {
            background: #667eea;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tree-questions {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
        }
        
        .tree-question {
            font-size: 12px;
            color: #6b7280;
            padding: 4px 8px;
            background: white;
            border-radius: 4px;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .tree-question-arrow {
            color: #667eea;
            font-weight: 600;
        }
        
        /* Coluna do Conteúdo */
        .content-column {
            overflow-y: auto;
            background: #f9fafb;
        }
        
        .content-header {
            background: white;
            padding: 24px;
            border-bottom: 2px solid #e5e7eb;
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .content-title {
            font-size: 28px;
            color: #1f2937;
            margin-bottom: 8px;
        }
        
        .content-subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        
        .content-body {
            padding: 30px;
            max-width: 700px;
        }
        
        .step-viewer {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .step-html-content {
            margin-bottom: 24px;
            line-height: 1.8;
            color: #374151;
        }
        
        .step-html-content h1 { font-size: 24px; margin: 16px 0; }
        .step-html-content h2 { font-size: 20px; margin: 14px 0; }
        .step-html-content h3 { font-size: 18px; margin: 12px 0; }
        .step-html-content p { margin: 12px 0; }
        .step-html-content ul, .step-html-content ol { margin: 12px 0 12px 24px; }
        
        .step-media {
            margin: 24px 0;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .step-media img,
        .step-media video {
            width: 100%;
            display: block;
        }
        
        .step-questions {
            margin-top: 32px;
            padding: 20px;
            background: #f3f4f6;
            border-radius: 8px;
        }
        
        .questions-header {
            font-weight: 600;
            color: #374151;
            margin-bottom: 16px;
            font-size: 16px;
        }
        
        .question-button {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
            color: #374151;
            width: 100%;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .question-button:hover {
            border-color: #667eea;
            background: #eef2ff;
            transform: translateX(4px);
        }
        
        .question-icon {
            font-size: 18px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .close-btn {
            position: fixed;
            top: 16px;
            right: 16px;
            background: white;
            border: 2px solid #e5e7eb;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            z-index: 1000;
            color: #6b7280;
        }
        
        .close-btn:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: #dc2626;
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <button class="close-btn" onclick="window.close()" title="Fechar">×</button>
    
    <div class="preview-layout">
        <!-- Árvore de Passos -->
        <div class="tree-column">
            <div class="tree-header">
                <h2>📚 <?= htmlspecialchars($tutorial['name']) ?></h2>
                <div class="tree-meta">
                    <?= count($steps) ?> passo(s) | 
                    <?= $tutorial['accept'] ? '✓ Aprovado' : '⏳ Pendente' ?>
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
                // Se o link não começa com http://, https://, mailto:, tel:, #, ou /
                if (href && !href.match(/^(https?:\/\/|mailto:|tel:|#|\/)/i)) {
                    // Adicionar https:// no início
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
            
            // Atualizar árvore - remover active de todos e adicionar no atual
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
            
            // Mídia (imagem ou vídeo)
            if (step.src) {
                // Corrigir caminho relativo
                const mediaSrc = step.src.startsWith('http') ? step.src : (step.src.startsWith('../') ? step.src : '../' + step.src);
                
                const extension = step.src.split('.').pop().toLowerCase();
                const isVideo = ['mp4', 'webm', 'ogg'].includes(extension);
                
                html += '<div class="step-media">';
                if (isVideo) {
                    html += `<video controls key="${Date.now()}">
                                <source src="${mediaSrc}" type="video/${extension}">
                                Seu navegador não suporta vídeos.
                             </video>`;
                } else {
                    html += `<img src="${mediaSrc}" alt="${step.name}">`;
                }
                html += '</div>';
            }
            
            // Perguntas
            if (step.questions_data && step.questions_data.length > 0) {
                html += '<div class="step-questions">';
                html += '<div class="questions-header">❓ Próximas Ações</div>';
                
                step.questions_data.forEach(question => {
                    const nextStepId = question.proximo;
                    html += `<button class="question-button" onclick="handleQuestion(${nextStepId})">
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
            
            // Scroll para o topo do conteúdo
            document.querySelector('.content-column').scrollTop = 0;
        }
        
        function handleQuestion(nextStepId) {
            if (nextStepId == 505) {
                alert('🎉 Fim do tutorial!\n\nEste é o último passo.');
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
