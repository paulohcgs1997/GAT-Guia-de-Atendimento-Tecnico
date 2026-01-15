<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_viewer();

include_once(__DIR__ . '/../config/conexao.php');
global $mysqli;

header('Content-Type: application/json');

if (!isset($_POST['step_id']) || empty($_POST['step_id'])) {
    echo json_encode(['error' => 'ID do step não fornecido']);
    exit;
}

$step_id = (int)$_POST['step_id'];

try {
    // Buscar step
    $sql = "SELECT id, name, html, src, questions FROM steps WHERE id = ? AND active = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $step_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Passo não encontrado']);
        exit;
    }
    
    $step = $result->fetch_assoc();
    
    // Buscar perguntas deste step
    $questions_ids = $step['questions'];
    $step['questions'] = [];
    
    if (!empty($questions_ids)) {
        $sql_questions = "SELECT id, name, text, proximo FROM questions WHERE id IN ($questions_ids) ORDER BY id";
        $result_questions = $mysqli->query($sql_questions);
        
        while ($question = $result_questions->fetch_assoc()) {
            $step['questions'][] = $question;
        }
    }
    
    echo json_encode($step);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar step: ' . $e->getMessage()]);
}
?>
