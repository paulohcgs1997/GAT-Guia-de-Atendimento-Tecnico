<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_gestor();

include_once(__DIR__ . '/../config/conexao.php');

// Ação de listar questions (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list') {
    $sql = "SELECT id, name, text, proximo FROM questions ORDER BY name ASC";
    $result = $mysqli->query($sql);
    
    $questions = [];
    while ($row = $result->fetch_assoc()) {
        $questions[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $questions]);
    exit;
}

$action = $_POST['action'] ?? 'save';

if ($action === 'delete') {
    $id = intval($_POST['id']);
    
    $sql = "DELETE FROM questions WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Question excluída com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao excluir question']);
    }
    exit;
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$text = $_POST['text'] ?? '';
$proximo = $_POST['proximo'] ?? ''; // Agora pode ser int ou string (next_block, bloco_X)

if (empty($name) || empty($text) || empty($proximo)) {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatórios']);
    exit;
}

if ($id) {
    $sql = "UPDATE questions SET 
            name = ?, 
            text = ?,
            proximo = ?
            WHERE id = ?";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sssi', $name, $text, $proximo, $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Question atualizada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar question']);
    }
} else {
    $sql = "INSERT INTO questions (name, text, proximo) VALUES (?, ?, ?)";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('sss', $name, $text, $proximo);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Question criada com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar question']);
    }
}
?>
