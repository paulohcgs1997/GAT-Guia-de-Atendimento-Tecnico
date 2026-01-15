<?php
session_start();
include_once(__DIR__ . '/../config/conexao.php');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

// Receber dados JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['name']) || !isset($data['steps'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$tutorialName = $data['name'];
$steps = $data['steps'];
$tutorialId = $data['id'] ?? null;

if (empty($steps)) {
    echo json_encode(['success' => false, 'message' => 'Adicione pelo menos um passo ao tutorial']);
    exit;
}

try {
    $mysqli->begin_transaction();
    
    $stepIds = [];
    
    // Processar cada passo do tutorial
    foreach ($steps as $index => $stepData) {
        // 1. Criar as perguntas (success e error)
        $questionIds = [];
        
        // Pergunta de sucesso
        $nextStepRef = ($index < count($steps) - 1) ? 'TEMP_NEXT' : 'next_block'; // 'next_block' = fim ou próximo bloco
        $sqlQuestion = "INSERT INTO questions (name, text, proximo) VALUES (?, ?, ?)";
        $stmtQuestion = $mysqli->prepare($sqlQuestion);
        $stmtQuestion->bind_param('sss', 
            $stepData['question_success_label'],
            $stepData['question_success_text'],
            $nextStepRef
        );
        $stmtQuestion->execute();
        $questionSuccessId = $mysqli->insert_id;
        $questionIds[] = $questionSuccessId;
        
        // Pergunta de erro
        $errorNextRef = 'TEMP_CURRENT'; // Vai apontar para o step atual
        $stmtQuestion = $mysqli->prepare($sqlQuestion);
        $stmtQuestion->bind_param('sss',
            $stepData['question_error_label'],
            $stepData['question_error_text'],
            $errorNextRef
        );
        $stmtQuestion->execute();
        $questionErrorId = $mysqli->insert_id;
        $questionIds[] = $questionErrorId;
        
        // 2. Criar o step com as perguntas
        $questionsString = implode(',', $questionIds);
        $sqlStep = "INSERT INTO steps (name, html, src, questions, accept, active) VALUES (?, ?, ?, ?, 0, 1)";
        $stmtStep = $mysqli->prepare($sqlStep);
        $stmtStep->bind_param('ssss',
            $stepData['name'],
            $stepData['html'],
            $stepData['src'],
            $questionsString
        );
        $stmtStep->execute();
        $stepId = $mysqli->insert_id;
        $stepIds[] = $stepId;
        
        // 3. Atualizar a pergunta de erro para apontar para o próprio step
        $sqlUpdateError = "UPDATE questions SET proximo = ? WHERE id = ?";
        $stmtUpdateError = $mysqli->prepare($sqlUpdateError);
        $stmtUpdateError->bind_param('si', $stepId, $questionErrorId);
        $stmtUpdateError->execute();
        
        // 4. Se não é o último step, atualizar a pergunta de sucesso para apontar para o próximo
        if ($index < count($steps) - 1) {
            // Será atualizado após criar o próximo step
        } else {
            // É o último step, manter como 'next_block' para avançar ao próximo tutorial
        }
    }
    
    // Atualizar as perguntas de sucesso para apontarem para os próximos steps
    for ($i = 0; $i < count($stepIds) - 1; $i++) {
        // Buscar a primeira pergunta (success) do step atual
        $sqlGetQuestion = "SELECT questions FROM steps WHERE id = ?";
        $stmtGet = $mysqli->prepare($sqlGetQuestion);
        $stmtGet->bind_param('i', $stepIds[$i]);
        $stmtGet->execute();
        $result = $stmtGet->get_result();
        $row = $result->fetch_assoc();
        $questions = explode(',', $row['questions']);
        $successQuestionId = $questions[0];
        
        // Atualizar para apontar para o próximo step
        $sqlUpdateSuccess = "UPDATE questions SET proximo = ? WHERE id = ?";
        $stmtUpdateSuccess = $mysqli->prepare($sqlUpdateSuccess);
        $stmtUpdateSuccess->bind_param('si', $stepIds[$i + 1], $successQuestionId);
        $stmtUpdateSuccess->execute();
    }
    
    // Criar ou atualizar o tutorial (bloco)
    $stepsString = implode(',', $stepIds);
    
    if ($tutorialId) {
        // Atualizar tutorial existente
        $sqlTutorial = "UPDATE blocos SET name = ?, id_step = ?, last_modification = NOW() WHERE id = ?";
        $stmtTutorial = $mysqli->prepare($sqlTutorial);
        $stmtTutorial->bind_param('ssi', $tutorialName, $stepsString, $tutorialId);
    } else {
        // Criar novo tutorial
        $sqlTutorial = "INSERT INTO blocos (name, id_step, accept, active) VALUES (?, ?, 0, 1)";
        $stmtTutorial = $mysqli->prepare($sqlTutorial);
        $stmtTutorial->bind_param('ss', $tutorialName, $stepsString);
    }
    
    $stmtTutorial->execute();
    
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tutorial criado com sucesso!',
        'tutorial_id' => $tutorialId ?? $mysqli->insert_id
    ]);
    
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao criar tutorial: ' . $e->getMessage()
    ]);
}
