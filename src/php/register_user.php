<?php
// Desabilitar exibição de erros para não quebrar o JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Capturar qualquer output indesejado
ob_start();

// Limpar qualquer output anterior e definir header JSON
ob_clean();
header('Content-Type: application/json');

session_start();

// Conexão com o banco
require_once(__DIR__ . '/../config/conexao.php');

try {
    // Verificar se a requisição é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Receber dados do formulário
    $user = trim($_POST['user'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirm = $_POST['senha_confirm'] ?? '';
    $nome_completo = trim($_POST['nome_completo'] ?? '');
    
    // Validações
    if (empty($user) || empty($email) || empty($senha)) {
        throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
    }
    
    if (strlen($user) < 3) {
        throw new Exception('O nome de usuário deve ter no mínimo 3 caracteres');
    }
    
    if (strlen($user) > 50) {
        throw new Exception('O nome de usuário não pode ter mais de 50 caracteres');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('E-mail inválido');
    }
    
    if (strlen($senha) < 6) {
        throw new Exception('A senha deve ter no mínimo 6 caracteres');
    }
    
    if ($senha !== $senha_confirm) {
        throw new Exception('As senhas não coincidem');
    }
    
    // Verificar se o usuário já existe
    $sql = "SELECT id FROM usuarios WHERE user = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $user);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        throw new Exception('Este nome de usuário já está em uso');
    }
    
    // Verificar se o e-mail já existe (se tiver coluna email)
    $sql_check_email = "SHOW COLUMNS FROM usuarios LIKE 'email'";
    $result_check = $mysqli->query($sql_check_email);
    
    if ($result_check->num_rows > 0) {
        $sql = "SELECT id FROM usuarios WHERE email = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Este e-mail já está cadastrado');
        }
    }
    
    // Hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
    
    // Buscar ID do perfil "colaborador" (id = 4)
    $perfil_id = 4;
    
    // Verificar se existe coluna 'status' na tabela usuarios
    $sql_check_status = "SHOW COLUMNS FROM usuarios LIKE 'status'";
    $result_check_status = $mysqli->query($sql_check_status);
    $has_status_column = ($result_check_status->num_rows > 0);
    
    // Verificar se existe coluna 'email' na tabela usuarios
    $has_email_column = ($result_check->num_rows > 0);
    
    // Verificar se existe coluna 'nome_completo' na tabela usuarios
    $sql_check_nome = "SHOW COLUMNS FROM usuarios LIKE 'nome_completo'";
    $result_check_nome = $mysqli->query($sql_check_nome);
    $has_nome_column = ($result_check_nome->num_rows > 0);
    
    // Inserir novo usuário
    if ($has_status_column && $has_email_column && $has_nome_column) {
        // Com todas as colunas
        $sql = "INSERT INTO usuarios (user, password, perfil, email, nome_completo, status, active) VALUES (?, ?, ?, ?, ?, 'pending', 0)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssiss', $user, $senha_hash, $perfil_id, $email, $nome_completo);
    } elseif ($has_status_column && $has_email_column) {
        // Com status e email
        $sql = "INSERT INTO usuarios (user, password, perfil, email, status, active) VALUES (?, ?, ?, ?, 'pending', 0)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssis', $user, $senha_hash, $perfil_id, $email);
    } elseif ($has_status_column) {
        // Só com status
        $sql = "INSERT INTO usuarios (user, password, perfil, status, active) VALUES (?, ?, ?, 'pending', 0)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $user, $senha_hash, $perfil_id);
    } else {
        // Sem coluna status (usuário fica inativo até admin ativar manualmente)
        $sql = "INSERT INTO usuarios (user, password, perfil, active) VALUES (?, ?, ?, 0)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('ssi', $user, $senha_hash, $perfil_id);
    }
    
    if (!$stmt->execute()) {
        throw new Exception('Erro ao cadastrar usuário: ' . $stmt->error);
    }
    
    error_log('Novo usuário cadastrado: ' . $user . ' (ID: ' . $mysqli->insert_id . ')');
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Cadastro realizado com sucesso! Sua conta será ativada após aprovação do administrador.'
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'erro' => $e->getMessage()
    ]);
    error_log('Erro no registro: ' . $e->getMessage());
}
?>
