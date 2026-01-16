<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_viewer();

include_once(__DIR__ . '/../config/conexao.php');
global $mysqli;

header('Content-Type: application/json');

if (!isset($_POST['servico_id']) || empty($_POST['servico_id'])) {
    echo json_encode(['error' => 'ID do serviço não fornecido']);
    exit;
}

$servico_id = (int)$_POST['servico_id'];

try {
    // Buscar serviço e seus blocos
    $sql = "SELECT s.*, d.name as dept_name FROM services s 
            LEFT JOIN departaments d ON s.departamento = d.id 
            WHERE s.id = ? AND s.active = 1";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $servico_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Serviço não encontrado']);
        exit;
    }
    
    $servico = $result->fetch_assoc();
    $blocos_ids = $servico['blocos'];
    
    if (empty($blocos_ids)) {
        echo json_encode([
            'error' => 'Serviço não possui blocos configurados',
            'servico_name' => $servico['name'],
            'servico_description' => $servico['description']
        ]);
        exit;
    }
    
    // Buscar blocos na ordem definida no serviço
    $blocos_array = explode(',', $blocos_ids);
    $sql = "SELECT id, name, id_step FROM blocos WHERE id IN ($blocos_ids) AND active = 1 ORDER BY FIELD(id, $blocos_ids)";
    $result = $mysqli->query($sql);
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Nenhum bloco encontrado']);
        exit;
    }
    
    $steps_completos = [];
    $blocos_info = []; // Armazenar informações sobre cada bloco
    
    while ($bloco = $result->fetch_assoc()) {
        $bloco_steps = []; // Steps deste bloco específico
        $step_ids = $bloco['id_step'];
        
        if (!empty($step_ids)) {
            // Buscar steps deste bloco na ordem definida
            $sql_steps = "SELECT id, name, html, src, questions FROM steps WHERE id IN ($step_ids) AND active = 1 ORDER BY FIELD(id, $step_ids)";
            $result_steps = $mysqli->query($sql_steps);
            
            while ($step = $result_steps->fetch_assoc()) {
                // Buscar perguntas deste step
                $questions_ids = $step['questions'];
                $step['questions'] = [];
                
                if (!empty($questions_ids)) {
                    $sql_questions = "SELECT id, name, text, proximo FROM questions WHERE id IN ($questions_ids) ORDER BY FIELD(id, $questions_ids)";
                    $result_questions = $mysqli->query($sql_questions);
                    
                    while ($question = $result_questions->fetch_assoc()) {
                        $step['questions'][] = $question;
                    }
                }
                
                $bloco_steps[] = $step;
                $steps_completos[] = $step; // Manter compatibilidade
            }
        }
        
        // Armazenar informação do bloco
        $blocos_info[] = [
            'id' => $bloco['id'],
            'name' => $bloco['name'],
            'steps' => $bloco_steps
        ];
    }
    
    echo json_encode([
        'steps' => $steps_completos, // Array completo para compatibilidade
        'blocos' => $blocos_info, // Array de blocos separados
        'servico_name' => $servico['name'],
        'servico_description' => $servico['description'],
        'servico_departamento' => $servico['dept_name']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Erro ao buscar dados: ' . $e->getMessage()]);
}
?>
