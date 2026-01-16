<?php
/**
 * IMPORTANTE: Para fazer upload de arquivos grandes, edite o arquivo php.ini:
 * 
 * post_max_size = 100M
 * upload_max_filesize = 100M
 * max_execution_time = 300
 * max_input_time = 300
 * memory_limit = 256M
 * 
 * Após editar, reinicie o servidor PHP/Apache
 */

session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_gestor();

include_once(__DIR__ . '/../config/conexao.php');
require_once(__DIR__ . '/media_manager.php'); // Gerenciador de mídias

// GET - Buscar passos do tutorial
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_steps') {
    $tutorialId = intval($_GET['tutorial_id']);
    
    // Buscar o tutorial
    $sqlTutorial = "SELECT id_step FROM blocos WHERE id = ? AND active = 1";
    $stmtTutorial = $mysqli->prepare($sqlTutorial);
    $stmtTutorial->bind_param('i', $tutorialId);
    $stmtTutorial->execute();
    $resultTutorial = $stmtTutorial->get_result();
    $tutorial = $resultTutorial->fetch_assoc();
    
    if (!$tutorial || empty($tutorial['id_step'])) {
        echo json_encode(['success' => true, 'steps' => []]);
        exit;
    }
    
    $stepIds = explode(',', $tutorial['id_step']);
    $steps = [];
    
    foreach ($stepIds as $stepId) {
        $sqlStep = "SELECT * FROM steps WHERE id = ? AND active = 1";
        $stmtStep = $mysqli->prepare($sqlStep);
        $stmtStep->bind_param('i', $stepId);
        $stmtStep->execute();
        $resultStep = $stmtStep->get_result();
        $step = $resultStep->fetch_assoc();
        
        if ($step) {
            // Salvar os IDs das perguntas antes de sobrescrever
            $questionsIdsStr = $step['questions'];
            
            // Buscar perguntas NA ORDEM definida na coluna questions
            $step['questions'] = [];
            if (!empty($questionsIdsStr)) {
                $questionIds = explode(',', $questionsIdsStr);
                
                // Iterar na ordem definida
                foreach ($questionIds as $qId) {
                    $qId = intval(trim($qId));
                    if ($qId <= 0) continue;
                    
                    $sqlQ = "SELECT id, name, text, proximo FROM questions WHERE id = ?";
                    $stmtQ = $mysqli->prepare($sqlQ);
                    $stmtQ->bind_param('i', $qId);
                    $stmtQ->execute();
                    $resultQ = $stmtQ->get_result();
                    $question = $resultQ->fetch_assoc();
                    
                    if ($question) {
                        // Determinar nome do destino
                        if ($question['proximo'] == 'next_block' || $question['proximo'] == 505) {
                            $question['destination_name'] = 'Próximo bloco';
                        } else {
                            $sqlDest = "SELECT name FROM steps WHERE id = ?";
                            $stmtDest = $mysqli->prepare($sqlDest);
                            $stmtDest->bind_param('i', $question['proximo']);
                            $stmtDest->execute();
                            $resultDest = $stmtDest->get_result();
                            $dest = $resultDest->fetch_assoc();
                            $question['destination_name'] = $dest ? $dest['name'] : 'Desconhecido';
                        }
                        
                        $step['questions'][] = $question;
                    }
                }
            }
            
            $steps[] = $step;
        }
    }
    
    echo json_encode(['success' => true, 'steps' => $steps]);
    exit;
}

// GET - Verificar status de aprovação
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'check_status') {
    $tutorialId = intval($_GET['tutorial_id']);
    
    $sql = "SELECT accept, is_clone FROM blocos WHERE id = ? AND active = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $tutorialId);
    $stmt->execute();
    $result = $stmt->get_result();
    $tutorial = $result->fetch_assoc();
    
    if (!$tutorial) {
        echo json_encode(['success' => false, 'message' => 'Tutorial não encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'is_approved' => $tutorial['accept'] == 1,
        'is_clone' => $tutorial['is_clone'] == 1
    ]);
    exit;
}

// POST - Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Tentar ler como JSON primeiro
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Se não é JSON válido ou está vazio, usar $_POST (FormData)
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        $data = $_POST;
    }
    
    $action = $data['action'] ?? '';
} else {
    $action = '';
}

if (empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Ação não especificada']);
    exit;
}

try {
    $mysqli->begin_transaction();
    
    // ========== APROVAR TUTORIAL ==========
    if ($action === 'approve') {
        // Verificar se usuário é admin
        if ($_SESSION['perfil'] != '1') {
            throw new Exception('Apenas administradores podem aprovar tutoriais');
        }
        
        $tutorialId = intval($data['tutorial_id']);
        
        // Atualizar accept e status se o campo existir
        $sql = "UPDATE blocos SET accept = 1, last_modification = NOW()";
        
        // Verificar se campo status existe
        $check_status = $mysqli->query("SHOW COLUMNS FROM blocos LIKE 'status'");
        if ($check_status->num_rows > 0) {
            $sql .= ", status = 'approved'";
        }
        
        $sql .= " WHERE id = ? AND active = 1";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $tutorialId);
        $stmt->execute();
        
        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Tutorial aprovado com sucesso']);
        exit;
    }
    
    // ========== CRIAR TUTORIAL ==========
    // Verificar permissões para ações de edição
    if ($action === 'create_tutorial' || $action === 'clone_tutorial' || $action === 'save_step' || $action === 'edit_question' || $action === 'delete_question' || $action === 'delete_step') {
        // Apenas Admin (1) e Gestor (2) podem editar tutoriais
        if ($_SESSION['perfil'] != '1' && $_SESSION['perfil'] != '2') {
            echo json_encode(['success' => false, 'message' => 'Você não tem permissão para editar tutoriais']);
            exit;
        }
    }
    
    if ($action === 'create_tutorial') {
        $name = $data['name'];
        $departamento = !empty($data['departamento']) ? intval($data['departamento']) : null;
        
        // Verificar se campo status existe
        $check_status = $mysqli->query("SHOW COLUMNS FROM blocos LIKE 'status'");
        $has_status = $check_status->num_rows > 0;
        
        if ($has_status) {
            $sql = "INSERT INTO blocos (name, id_step, accept, active, created_by, departamento, status) VALUES (?, '', 0, 1, ?, ?, 'draft')";
        } else {
            $sql = "INSERT INTO blocos (name, id_step, accept, active, created_by, departamento) VALUES (?, '', 0, 1, ?, ?)";
        }
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sii', $name, $_SESSION['user_id'], $departamento);
        $stmt->execute();
        $tutorialId = $mysqli->insert_id;
        
        $mysqli->commit();
        echo json_encode(['success' => true, 'tutorial_id' => $tutorialId]);
        exit;
    }
    
    // ========== EDITAR TUTORIAL APROVADO (CRIAR CLONE) ==========
    if ($action === 'clone_tutorial') {
        try {
            $originalId = intval($data['tutorial_id']);
            
            error_log("Clone Tutorial - Original ID: $originalId");
            
            // Buscar dados do tutorial original
            $sql = "SELECT * FROM blocos WHERE id = ? AND active = 1";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('i', $originalId);
            $stmt->execute();
            $result = $stmt->get_result();
            $original = $result->fetch_assoc();
            
            if (!$original) {
                throw new Exception('Tutorial não encontrado');
            }
            
            error_log("Clone Tutorial - Original name: " . $original['name']);
            
            // Mapear IDs antigos para novos IDs de steps
            $stepIdMap = [];
            $newStepIds = [];
            
            // Clonar todos os steps
            if (!empty($original['id_step'])) {
                $originalStepIds = explode(',', $original['id_step']);
                error_log("Clone Tutorial - Steps to clone: " . count($originalStepIds));
                
                foreach ($originalStepIds as $originalStepId) {
                    $originalStepId = trim($originalStepId);
                    if (empty($originalStepId)) continue;
                    
                    // Buscar step original
                    $sql = "SELECT * FROM steps WHERE id = ? AND active = 1";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('i', $originalStepId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $originalStep = $result->fetch_assoc();
                    
                    if ($originalStep) {
                        // Copiar arquivo de mídia para o clone (não compartilhar com original)
                        $stepHtml = $originalStep['html'] ?? '';
                        $stepSrc = $originalStep['src'] ?? '';
                        
                        error_log("Clone Tutorial - Step $originalStepId - SRC original: '$stepSrc'");
                        
                        $clonedSrc = copyMediaFile($stepSrc); // Nova função que copia o arquivo
                        
                        error_log("Clone Tutorial - Step $originalStepId - SRC clonado: '$clonedSrc'");
                        
                        // Criar clone do step (sem questions por enquanto, será preenchido depois)
                        $sql = "INSERT INTO steps (name, html, src, questions, active) VALUES (?, ?, ?, '', 1)";
                        $stmt = $mysqli->prepare($sql);
                        $stmt->bind_param('sss', $originalStep['name'], $stepHtml, $clonedSrc);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Erro ao clonar step: " . $stmt->error);
                        }
                        
                        $newStepId = $mysqli->insert_id;
                        
                        // Mapear ID antigo para novo
                        $stepIdMap[$originalStepId] = $newStepId;
                        $newStepIds[] = $newStepId;
                        
                        error_log("Clone Tutorial - Cloned step $originalStepId -> $newStepId (media: $clonedSrc)");
                    }
                }
                
                // Agora clonar as questions e atualizar os destinos
                foreach ($originalStepIds as $originalStepId) {
                    $originalStepId = trim($originalStepId);
                    if (!isset($stepIdMap[$originalStepId])) continue;
                    
                    $newStepId = $stepIdMap[$originalStepId];
                    
                    // Buscar questions do step original
                    $sql = "SELECT questions FROM steps WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('i', $originalStepId);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stepData = $result->fetch_assoc();
                    
                    if (!empty($stepData['questions'])) {
                        $originalQuestionIds = explode(',', $stepData['questions']);
                        $newQuestionIds = [];
                        
                        foreach ($originalQuestionIds as $originalQuestionId) {
                            $originalQuestionId = trim($originalQuestionId);
                            if (empty($originalQuestionId)) continue;
                            
                            // Buscar question original
                            $sql = "SELECT * FROM questions WHERE id = ?";
                            $stmt = $mysqli->prepare($sql);
                            $stmt->bind_param('i', $originalQuestionId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $originalQuestion = $result->fetch_assoc();
                            
                            if ($originalQuestion) {
                                // Determinar novo destino
                                $newProximo = $originalQuestion['proximo'];
                                if ($newProximo != 'next_block' && $newProximo != 505 && isset($stepIdMap[$newProximo])) {
                                    $newProximo = $stepIdMap[$newProximo];
                                }
                                
                                // Criar clone da question (incluir name e text)
                                $sql = "INSERT INTO questions (name, text, proximo) VALUES (?, ?, ?)";
                                $stmt = $mysqli->prepare($sql);
                                $questionName = $originalQuestion['name'] ?? $originalQuestion['text'];
                                $stmt->bind_param('sss', $questionName, $originalQuestion['text'], $newProximo);
                                
                                if (!$stmt->execute()) {
                                    throw new Exception("Erro ao clonar question: " . $stmt->error);
                                }
                                
                                $newQuestionIds[] = $mysqli->insert_id;
                            }
                        }
                        
                        // Atualizar step com novas questions
                        if (!empty($newQuestionIds)) {
                            $questionsStr = implode(',', $newQuestionIds);
                            $sql = "UPDATE steps SET questions = ? WHERE id = ?";
                            $stmt = $mysqli->prepare($sql);
                            $stmt->bind_param('si', $questionsStr, $newStepId);
                            
                            if (!$stmt->execute()) {
                                throw new Exception("Erro ao atualizar questions do step: " . $stmt->error);
                            }
                        }
                    }
                }
            }
            
            // Criar o clone do tutorial com os novos step IDs
            $newStepIdsStr = !empty($newStepIds) ? implode(',', $newStepIds) : '';
            $sql = "INSERT INTO blocos (name, id_step, accept, active, original_id, is_clone, departamento) 
                    VALUES (?, ?, 0, 1, ?, 1, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ssii', $original['name'], $newStepIdsStr, $originalId, $original['departamento']);
            
            if (!$stmt->execute()) {
                throw new Exception("Erro ao criar clone do tutorial: " . $stmt->error);
            }
            
            $cloneId = $mysqli->insert_id;
            
            error_log("Clone Tutorial - Success! Clone ID: $cloneId with " . count($newStepIds) . " steps");
            
            $mysqli->commit();
            echo json_encode([
                'success' => true, 
                'clone_id' => $cloneId,
                'message' => 'Clone criado com ' . count($newStepIds) . ' passo(s). Suas alterações ficarão pendentes até aprovação.'
            ]);
            exit;
            
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Clone Tutorial Error: " . $e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => 'Erro ao criar clone: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    
    // ========== SALVAR PASSO ==========
    if ($action === 'save_step') {
        $tutorialId = intval($_POST['tutorial_id']);
        $stepId = isset($_POST['step_id']) && $_POST['step_id'] !== '' ? intval($_POST['step_id']) : null;
        $name = $_POST['name'];
        $html = $_POST['html'];
        
        error_log("SAVE STEP - Tutorial ID: $tutorialId, Step ID: " . ($stepId ?? 'NULL (novo)') . ", Name: $name");
        
        // Upload de arquivo (se houver)
        $mediaSrc = '';
        if (isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === 0) {
            $uploadDir = __DIR__ . '/../uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['mediaFile']['name']);
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['mediaFile']['tmp_name'], $targetPath)) {
                $mediaSrc = 'src/uploads/' . $fileName;
                error_log("Upload success - File saved to: $targetPath, DB path: $mediaSrc");
            } else {
                error_log("Upload failed - Could not move file to: $targetPath");
            }
        } else if (isset($_FILES['mediaFile'])) {
            error_log("Upload error - Error code: " . $_FILES['mediaFile']['error']);
        }
        
        if ($stepId) {
            // Atualizar passo existente
            if (!empty($mediaSrc)) {
                // Se há nova mídia, deletar a antiga antes de atualizar
                error_log("SAVE STEP - Chamando updateStepMedia para step $stepId com nova mídia: $mediaSrc");
                updateStepMedia($mysqli, $stepId, $mediaSrc);
                error_log("SAVE STEP - updateStepMedia concluído");
                
                $sql = "UPDATE steps SET name = ?, html = ?, src = ?, last_modification = NOW() WHERE id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('sssi', $name, $html, $mediaSrc, $stepId);
            } else {
                error_log("SAVE STEP - Atualizando step $stepId SEM nova mídia");
                $sql = "UPDATE steps SET name = ?, html = ?, last_modification = NOW() WHERE id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param('ssi', $name, $html, $stepId);
            }
            $stmt->execute();
        } else {
            // Criar novo passo
            $sql = "INSERT INTO steps (name, html, src, questions, accept, active) VALUES (?, ?, ?, '', 0, 1)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('sss', $name, $html, $mediaSrc);
            $stmt->execute();
            $stepId = $mysqli->insert_id;
            
            // Adicionar step ao tutorial
            $sqlTutorial = "SELECT id_step FROM blocos WHERE id = ?";
            $stmtTutorial = $mysqli->prepare($sqlTutorial);
            $stmtTutorial->bind_param('i', $tutorialId);
            $stmtTutorial->execute();
            $resultTutorial = $stmtTutorial->get_result();
            $tutorial = $resultTutorial->fetch_assoc();
            
            $currentSteps = !empty($tutorial['id_step']) ? $tutorial['id_step'] : '';
            
            // Verificar se o step já não está na lista (prevenir duplicação)
            $stepsArray = !empty($currentSteps) ? explode(',', $currentSteps) : [];
            if (!in_array($stepId, $stepsArray)) {
                $newSteps = !empty($currentSteps) ? $currentSteps . ',' . $stepId : $stepId;
                
                $sqlUpdateTutorial = "UPDATE blocos SET id_step = ?, last_modification = NOW() WHERE id = ?";
                $stmtUpdateTutorial = $mysqli->prepare($sqlUpdateTutorial);
                $stmtUpdateTutorial->bind_param('si', $newSteps, $tutorialId);
                $stmtUpdateTutorial->execute();
            }
        }
        
        $mysqli->commit();
        echo json_encode(['success' => true, 'step_id' => $stepId]);
        exit;
    }
    
    // ========== ADICIONAR PERGUNTA ==========
    if ($action === 'add_question') {
        $tutorialId = intval($data['tutorial_id']);
        $stepId = intval($data['step_id']);
        $questionText = $data['question_text'];
        $questionLabel = $data['question_label'];
        $destination = $data['destination'];
        $existingStepId = isset($data['existing_step_id']) ? intval($data['existing_step_id']) : null;
        
        $nextStepId = null;
        
        // Determinar o próximo passo baseado no destino
        if ($destination === 'new_step') {
            // Criar novo passo vazio
            $sql = "INSERT INTO steps (name, html, src, questions, accept, active) VALUES ('Novo Passo', '', '', '', 0, 1)";
            $stmt = $mysqli->prepare($sql);
            $stmt->execute();
            $nextStepId = $mysqli->insert_id;
            
            // Adicionar ao tutorial
            $sqlTutorial = "SELECT id_step FROM blocos WHERE id = ?";
            $stmtTutorial = $mysqli->prepare($sqlTutorial);
            $stmtTutorial->bind_param('i', $tutorialId);
            $stmtTutorial->execute();
            $resultTutorial = $stmtTutorial->get_result();
            $tutorial = $resultTutorial->fetch_assoc();
            
            $currentSteps = $tutorial['id_step'];
            $newSteps = $currentSteps . ',' . $nextStepId;
            
            $sqlUpdateTutorial = "UPDATE blocos SET id_step = ?, last_modification = NOW() WHERE id = ?";
            $stmtUpdateTutorial = $mysqli->prepare($sqlUpdateTutorial);
            $stmtUpdateTutorial->bind_param('si', $newSteps, $tutorialId);
            $stmtUpdateTutorial->execute();
            
        } elseif ($destination === 'existing_step') {
            $nextStepId = $existingStepId;
        } elseif ($destination === 'next_block') {
            $nextStepId = 'next_block'; // String para indicar próximo bloco
        }
        
        // Criar a pergunta
        $sqlQuestion = "INSERT INTO questions (name, text, proximo) VALUES (?, ?, ?)";
        $stmtQuestion = $mysqli->prepare($sqlQuestion);
        $stmtQuestion->bind_param('sss', $questionLabel, $questionText, $nextStepId);
        $stmtQuestion->execute();
        $questionId = $mysqli->insert_id;
        
        // Adicionar pergunta ao step
        $sqlStep = "SELECT questions FROM steps WHERE id = ?";
        $stmtStep = $mysqli->prepare($sqlStep);
        $stmtStep->bind_param('i', $stepId);
        $stmtStep->execute();
        $resultStep = $stmtStep->get_result();
        $step = $resultStep->fetch_assoc();
        
        $currentQuestions = !empty($step['questions']) ? $step['questions'] : '';
        $newQuestions = !empty($currentQuestions) ? $currentQuestions . ',' . $questionId : $questionId;
        
        $sqlUpdateStep = "UPDATE steps SET questions = ?, last_modification = NOW() WHERE id = ?";
        $stmtUpdateStep = $mysqli->prepare($sqlUpdateStep);
        $stmtUpdateStep->bind_param('si', $newQuestions, $stepId);
        $stmtUpdateStep->execute();
        
        $mysqli->commit();
        echo json_encode([
            'success' => true,
            'question_id' => $questionId,
            'new_step_id' => $destination === 'new_step' ? $nextStepId : null
        ]);
        exit;
    }
    
    // ========== EDITAR PERGUNTA ==========
    if ($action === 'edit_question') {
        $stepId = intval($data['step_id']);
        $questionId = intval($data['question_id']);
        $questionText = $data['question_text'];
        $questionLabel = $data['question_label'];
        $destination = $data['destination'];
        $existingStepId = isset($data['existing_step_id']) ? intval($data['existing_step_id']) : null;
        
        $nextStepId = null;
        
        // Determinar o próximo passo baseado no destino
        if ($destination === 'new_step') {
            // Criar novo passo vazio
            $sql = "INSERT INTO steps (name, html, src, questions, accept, active) VALUES ('Novo Passo', '', '', '', 0, 1)";
            $stmt = $mysqli->prepare($sql);
            $stmt->execute();
            $nextStepId = $mysqli->insert_id;
            
            // Adicionar ao tutorial
            $tutorialId = intval($data['tutorial_id']);
            $sqlTutorial = "SELECT id_step FROM blocos WHERE id = ?";
            $stmtTutorial = $mysqli->prepare($sqlTutorial);
            $stmtTutorial->bind_param('i', $tutorialId);
            $stmtTutorial->execute();
            $resultTutorial = $stmtTutorial->get_result();
            $tutorial = $resultTutorial->fetch_assoc();
            
            $currentSteps = $tutorial['id_step'];
            $newSteps = $currentSteps . ',' . $nextStepId;
            
            $sqlUpdateTutorial = "UPDATE blocos SET id_step = ?, last_modification = NOW() WHERE id = ?";
            $stmtUpdateTutorial = $mysqli->prepare($sqlUpdateTutorial);
            $stmtUpdateTutorial->bind_param('si', $newSteps, $tutorialId);
            $stmtUpdateTutorial->execute();
            
        } elseif ($destination === 'existing_step') {
            $nextStepId = $existingStepId;
        } elseif ($destination === 'next_block' || $destination === 'next_tutorial') {
            $nextStepId = 'next_block'; // String para indicar próximo bloco
        }
        
        // Atualizar a pergunta
        $sqlUpdate = "UPDATE questions SET name = ?, text = ?, proximo = ? WHERE id = ?";
        $stmtUpdate = $mysqli->prepare($sqlUpdate);
        $stmtUpdate->bind_param('sssi', $questionLabel, $questionText, $nextStepId, $questionId);
        $stmtUpdate->execute();
        
        // Atualizar data de modificação do step
        $sqlUpdateStep = "UPDATE steps SET last_modification = NOW() WHERE id = ?";
        $stmtUpdateStep = $mysqli->prepare($sqlUpdateStep);
        $stmtUpdateStep->bind_param('i', $stepId);
        $stmtUpdateStep->execute();
        
        $mysqli->commit();
        echo json_encode([
            'success' => true,
            'question_id' => $questionId,
            'new_step_id' => $destination === 'new_step' ? $nextStepId : null
        ]);
        exit;
    }
    
    // ========== EXCLUIR PERGUNTA ==========
    if ($action === 'delete_question') {
        $stepId = intval($data['step_id']);
        $questionId = intval($data['question_id']);
        
        // Remover a pergunta do step
        $sqlStep = "SELECT questions FROM steps WHERE id = ?";
        $stmtStep = $mysqli->prepare($sqlStep);
        $stmtStep->bind_param('i', $stepId);
        $stmtStep->execute();
        $resultStep = $stmtStep->get_result();
        $step = $resultStep->fetch_assoc();
        
        if (!$step) {
            throw new Exception('Passo não encontrado');
        }
        
        $currentQuestions = $step['questions'];
        $questionsArray = explode(',', $currentQuestions);
        $questionsArray = array_filter($questionsArray, function($q) use ($questionId) {
            return intval($q) != $questionId;
        });
        $newQuestions = implode(',', $questionsArray);
        
        // Atualizar o step
        $sqlUpdateStep = "UPDATE steps SET questions = ?, last_modification = NOW() WHERE id = ?";
        $stmtUpdateStep = $mysqli->prepare($sqlUpdateStep);
        $stmtUpdateStep->bind_param('si', $newQuestions, $stepId);
        $stmtUpdateStep->execute();
        
        // Excluir a pergunta da tabela questions
        $sqlDeleteQuestion = "DELETE FROM questions WHERE id = ?";
        $stmtDeleteQuestion = $mysqli->prepare($sqlDeleteQuestion);
        $stmtDeleteQuestion->bind_param('i', $questionId);
        $stmtDeleteQuestion->execute();
        
        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Pergunta excluída com sucesso']);
        exit;
    }
    
    // ========== REORDENAR PERGUNTAS ==========
    if ($action === 'reorder_questions') {
        error_log("REORDER - Iniciando reordenação de perguntas");
        
        $stepId = intval($data['step_id']);
        $questionIds = $data['question_ids'] ?? [];
        
        error_log("REORDER - Step ID: $stepId");
        error_log("REORDER - Question IDs recebidos: " . json_encode($questionIds));
        
        if ($stepId <= 0) {
            error_log("REORDER - ERRO: Step ID inválido");
            throw new Exception('ID do passo inválido');
        }
        
        if (empty($questionIds) || !is_array($questionIds)) {
            error_log("REORDER - ERRO: IDs de perguntas vazios ou não é array");
            throw new Exception('IDs de perguntas não fornecidos');
        }
        
        // Validar que os IDs são numéricos
        $questionIds = array_map('intval', $questionIds);
        $questionIds = array_filter($questionIds, function($id) { return $id > 0; });
        
        if (empty($questionIds)) {
            error_log("REORDER - ERRO: Nenhum ID válido após filtro");
            throw new Exception('IDs de perguntas inválidos');
        }
        
        error_log("REORDER - IDs válidos: " . implode(',', $questionIds));
        
        // Buscar o step atual
        $sqlStep = "SELECT questions FROM steps WHERE id = ?";
        $stmtStep = $mysqli->prepare($sqlStep);
        $stmtStep->bind_param('i', $stepId);
        $stmtStep->execute();
        $resultStep = $stmtStep->get_result();
        $step = $resultStep->fetch_assoc();
        
        if (!$step) {
            error_log("REORDER - ERRO: Step não encontrado");
            throw new Exception('Passo não encontrado');
        }
        
        error_log("REORDER - Questions atuais no step: " . $step['questions']);
        
        // Verificar se todos os IDs pertencem a este step
        $currentQuestions = !empty($step['questions']) ? explode(',', $step['questions']) : [];
        $currentQuestions = array_map('intval', $currentQuestions);
        
        foreach ($questionIds as $qId) {
            if (!in_array($qId, $currentQuestions)) {
                error_log("REORDER - ERRO: Question ID $qId não pertence ao step");
                throw new Exception('Pergunta ID ' . $qId . ' não pertence a este passo');
            }
        }
        
        // Atualizar a ordem das perguntas
        $newQuestionsStr = implode(',', $questionIds);
        error_log("REORDER - Nova ordem: $newQuestionsStr");
        
        $sqlUpdate = "UPDATE steps SET questions = ?, last_modification = NOW() WHERE id = ?";
        $stmtUpdate = $mysqli->prepare($sqlUpdate);
        $stmtUpdate->bind_param('si', $newQuestionsStr, $stepId);
        
        if (!$stmtUpdate->execute()) {
            error_log("REORDER - ERRO no UPDATE: " . $stmtUpdate->error);
            throw new Exception('Erro ao atualizar ordem das perguntas');
        }
        
        error_log("REORDER - Sucesso!");
        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Ordem das perguntas atualizada']);
        exit;
    }
    
    // ========== REMOVER MÍDIA ==========
    if ($action === 'remove_media') {
        $stepId = intval($data['step_id']);
        
        error_log("REMOVE MEDIA - Step ID recebido: $stepId");
        
        if ($stepId <= 0) {
            error_log("REMOVE MEDIA - ERRO: ID inválido");
            throw new Exception('ID do passo inválido');
        }
        
        // Buscar mídia antes de deletar para log
        $checkStmt = $mysqli->prepare("SELECT src FROM steps WHERE id = ?");
        $checkStmt->bind_param('i', $stepId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $stepData = $checkResult->fetch_assoc();
        
        if ($stepData) {
            error_log("REMOVE MEDIA - Mídia atual: " . ($stepData['src'] ?? 'vazio'));
        } else {
            error_log("REMOVE MEDIA - ERRO: Step não encontrado");
            throw new Exception('Passo não encontrado');
        }
        
        // IMPORTANTE: Deletar arquivo ANTES de atualizar banco
        // Se der erro no banco, rollback não afeta arquivo já deletado
        error_log("REMOVE MEDIA - Chamando deleteStepMedia()");
        deleteStepMedia($mysqli, $stepId);
        error_log("REMOVE MEDIA - deleteStepMedia() concluído");
        
        // Atualizar o campo src no banco para vazio
        $sqlUpdate = "UPDATE steps SET src = '', last_modification = NOW() WHERE id = ?";
        $stmtUpdate = $mysqli->prepare($sqlUpdate);
        $stmtUpdate->bind_param('i', $stepId);
        
        if (!$stmtUpdate->execute()) {
            error_log("REMOVE MEDIA - ERRO ao atualizar banco: " . $stmtUpdate->error);
            throw new Exception('Erro ao atualizar passo no banco de dados');
        }
        
        error_log("REMOVE MEDIA - Banco atualizado com sucesso");
        
        // Commit da transação
        $mysqli->commit();
        error_log("REMOVE MEDIA - Commit realizado");
        
        error_log("REMOVE MEDIA - Sucesso total!");
        echo json_encode(['success' => true, 'message' => 'Mídia removida com sucesso']);
        exit;
    }
    
    // ========== DELETAR STEP ==========
    if ($action === 'delete_step') {
        $stepId = intval($data['step_id']);
        $tutorialId = intval($data['tutorial_id']);
        
        if (!$stepId || !$tutorialId) {
            throw new Exception('ID do step ou tutorial inválido');
        }
        
        $mysqli->begin_transaction();
        
        // Deletar mídia do step antes de remover
        deleteStepMedia($mysqli, $stepId);
        
        // Buscar tutorial e remover step da lista
        $stmt = $mysqli->prepare("SELECT id_step FROM blocos WHERE id = ?");
        $stmt->bind_param('i', $tutorialId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tutorial = $result->fetch_assoc();
        
        if ($tutorial) {
            $stepIds = explode(',', $tutorial['id_step']);
            $stepIds = array_filter($stepIds, function($id) use ($stepId) {
                return intval($id) != $stepId;
            });
            $newStepList = implode(',', $stepIds);
            
            // Atualizar lista de steps do tutorial
            $updateStmt = $mysqli->prepare("UPDATE blocos SET id_step = ?, last_modification = NOW() WHERE id = ?");
            $updateStmt->bind_param('si', $newStepList, $tutorialId);
            $updateStmt->execute();
        }
        
        // Marcar step como inativo
        $deleteStmt = $mysqli->prepare("UPDATE steps SET active = 0 WHERE id = ?");
        $deleteStmt->bind_param('i', $stepId);
        $deleteStmt->execute();
        
        $mysqli->commit();
        echo json_encode(['success' => true, 'message' => 'Step deletado com sucesso']);
        exit;
    }
    
    throw new Exception('Ação não reconhecida');
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
