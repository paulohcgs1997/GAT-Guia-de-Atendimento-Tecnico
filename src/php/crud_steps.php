<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_gestor();

include_once(__DIR__ . '/../config/conexao.php');

// Listar steps (para o select de próximo step)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    $sql = "SELECT id, name FROM steps WHERE active = 1 ORDER BY name";
    $result = $mysqli->query($sql);
    
    $steps = [];
    while($row = $result->fetch_assoc()) {
        $steps[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $steps]);
    exit;
}

$action = $_POST['action'] ?? 'save';

if ($action === 'approve') {
    // Verificar se usuário é admin
    if ($_SESSION['perfil'] != '1') {
        echo json_encode(['success' => false, 'message' => 'Apenas administradores podem aprovar steps']);
        exit;
    }
    
    $id = intval($_POST['id']);
    
    $sql = "UPDATE steps SET accept = 1, last_modification = NOW() WHERE id = ? AND active = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Step aprovado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao aprovar step']);
    }
    exit;
}

if ($action === 'delete') {
    $id = intval($_POST['id']);
    
    // Buscar o caminho da mídia antes de excluir
    $sqlSelect = "SELECT src FROM steps WHERE id = ?";
    $stmtSelect = $mysqli->prepare($sqlSelect);
    $stmtSelect->bind_param('i', $id);
    $stmtSelect->execute();
    $result = $stmtSelect->get_result();
    $step = $result->fetch_assoc();
    
    // Excluir arquivo de mídia se existir
    if (!empty($step['src'])) {
        $filePath = __DIR__ . '/../' . str_replace('../src/', '', $step['src']);
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    // Desativar step no banco
    $sql = "UPDATE steps SET active = 0 WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Step excluído com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir step']);
    }
    exit;
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$html = $_POST['html'] ?? '';
$oldMediaSrc = $_POST['oldMediaSrc'] ?? '';

// Manter mídia antiga por padrão
$src = $oldMediaSrc;

// Processar upload de arquivo se houver
if (isset($_FILES['mediaFile']) && $_FILES['mediaFile']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Excluir mídia antiga se existir
    if (!empty($oldMediaSrc)) {
        $oldFilePath = __DIR__ . '/../' . str_replace('../src/', '', $oldMediaSrc);
        if (file_exists($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
    
    $file = $_FILES['mediaFile'];
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $newFileName = uniqid('media_', true) . '.' . $fileExt;
    $uploadPath = $uploadDir . $newFileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $src = '../src/uploads/' . $newFileName;
    }
}

// Processar perguntas
$questionsData = $_POST['questions'] ?? [];
$questionIds = [];

if (!empty($questionsData)) {
    foreach ($questionsData as $questionData) {
        if (!is_array($questionData)) continue;
        
        $questionId = intval($questionData['id'] ?? 0);
        $questionText = trim($questionData['text'] ?? '');
        $nextStepId = trim($questionData['nextStep'] ?? '');
        
        if (empty($questionText) || empty($nextStepId)) continue;
        
        // Se nextStep é 'new', criar um novo step
        if ($nextStepId === 'new') {
            $newStepName = "Step criado automaticamente";
            $newStepHtml = "<p>Este step foi criado automaticamente. Edite o conteúdo.</p>";
            
            $sqlNewStep = "INSERT INTO steps (name, html, active) VALUES (?, ?, 1)";
            $stmtNewStep = $mysqli->prepare($sqlNewStep);
            $stmtNewStep->bind_param('ss', $newStepName, $newStepHtml);
            $stmtNewStep->execute();
            $nextStepId = $mysqli->insert_id;
        }
        
        // Gerar nome da pergunta (primeiras palavras do texto)
        $questionName = substr($questionText, 0, 50);
        
        if ($questionId > 0) {
            // Atualizar pergunta existente
            $sqlQuestion = "UPDATE questions SET name = ?, text = ?, proximo = ? WHERE id = ?";
            $stmtQuestion = $mysqli->prepare($sqlQuestion);
            $stmtQuestion->bind_param('ssii', $questionName, $questionText, $nextStepId, $questionId);
            $stmtQuestion->execute();
            $questionIds[] = $questionId;
        } else {
            // Inserir nova pergunta
            $sqlQuestion = "INSERT INTO questions (name, text, proximo) VALUES (?, ?, ?)";
            $stmtQuestion = $mysqli->prepare($sqlQuestion);
            $stmtQuestion->bind_param('ssi', $questionName, $questionText, $nextStepId);
            
            if ($stmtQuestion->execute()) {
                $questionIds[] = $mysqli->insert_id;
            }
        }
    }
}

$questions = implode(',', $questionIds);

if (empty($name) || empty($html)) {
    echo json_encode(['success' => false, 'message' => 'Nome e conteúdo HTML são obrigatórios']);
    exit;
}

if ($id) {
    $sql = "UPDATE steps SET 
            name = ?, 
            html = ?,
            src = ?,
            questions = ?,
            last_modification = NOW()
            WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssssi', $name, $html, $src, $questions, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Step atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar step']);
    }
} else {
    $sql = "INSERT INTO steps (name, html, src, questions, active) VALUES (?, ?, ?, ?, 1)";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssss', $name, $html, $src, $questions);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Step criado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar step']);
    }
}
?>

