<?php
header('Content-Type: application/json');

require_once(__DIR__ . '/../config/conexao.php');

try {
    $username = trim($_GET['username'] ?? '');
    
    if (empty($username)) {
        echo json_encode(['available' => true]);
        exit;
    }
    
    if (strlen($username) < 3) {
        echo json_encode(['available' => false, 'message' => 'Mínimo 3 caracteres']);
        exit;
    }
    
    // Verificar se o usuário já existe
    $sql = "SELECT id FROM usuarios WHERE user = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $available = ($result->num_rows === 0);
    
    echo json_encode([
        'available' => $available,
        'message' => $available ? 'Usuário disponível' : 'Usuário já existe'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['available' => true, 'error' => true]);
}
?>
