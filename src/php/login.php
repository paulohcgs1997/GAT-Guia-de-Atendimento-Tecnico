<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

// Verificar se a conexão foi estabelecida
if (!isset($mysqli) || $mysqli === null || $mysqli->connect_errno) {
    error_log('Erro: Conexão com banco não disponível no login.php');
    echo json_encode(['success' => false, 'erro' => 'Erro de conexão com o banco de dados']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_original = $_POST['user'];
    $senha = $_POST['senha'];
    
    // Se contém @, é email - não remove nada
    // Se não contém @, pode ser CPF/telefone - remove formatação
    if (strpos($username_original, '@') !== false) {
        $username_limpo = $username_original; // Email mantém tudo
    } else {
        // Remove APENAS pontos, traços, parênteses, espaços e barras (para CPF/CNPJ/Telefone)
        $username_limpo = preg_replace('/[.\-\/\(\)\s]/', '', $username_original);
    }
    
    error_log("Login attempt - Username original: $username_original");
    error_log("Login attempt - Username limpo: $username_limpo");
    
    // Escapa ambas as versões para SQL
    $username = $mysqli->real_escape_string($username_original);
    $username_clean = $mysqli->real_escape_string($username_limpo);
    
    // Busca por username original OU username sem formatação
    $sql = "SELECT * FROM usuarios WHERE user = '$username' OR user = '$username_clean'";
    $result = $mysqli->query($sql);
    
    error_log("Login - Query result rows: " . $result->num_rows);
    
    if ($result->num_rows == 1) {
        $userData = $result->fetch_assoc();
        
        error_log("Login - User found: " . $userData['user'] . ", Active: " . $userData['active']);
        error_log("Login - Password hash from DB: " . $userData['password']);
        error_log("Login - Attempting password_verify");
        
        if ($userData["active"] != 1) {
            error_log("Login - User inactive");
            echo json_encode(['success' => false, 'erro' => 'Usuário inativo']);
            exit;
        }
        
        $passwordVerifyResult = password_verify($senha, $userData['password']);
        error_log("Login - password_verify result: " . ($passwordVerifyResult ? 'TRUE' : 'FALSE'));
        
        if ($passwordVerifyResult) {
            $user_id = $userData['id'];
            $user_name = $userData['user'];
            
            error_log("Login - SUCCESS for user: $user_name");
            
            $_SESSION['user'] = $user_name;
            $_SESSION['perfil'] = $userData['perfil'];
            $_SESSION['user_id'] = $user_id;
            
            // Gera hash com timestamp fixo
            $login_timestamp = time();
            $hash = hash_login($user_name, $login_timestamp);
            $_SESSION['user_hash_login'] = $hash;
            
            // Define validade do hash (24 horas)
            $validity = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Verifica se já existe hash para este usuário
            $check_sql = "SELECT id FROM hash_login WHERE user_id = $user_id";
            $check_result = $mysqli->query($check_sql);
            
            if ($check_result->num_rows > 0) {
                // Atualiza hash existente
                $update_sql = "UPDATE hash_login SET login_hash = '$hash', validity = '$validity' WHERE user_id = $user_id";
                $mysqli->query($update_sql);
            } else {
                // Insere novo hash
                $insert_sql = "INSERT INTO hash_login (user_id, login_hash, validity) VALUES ($user_id, '$hash', '$validity')";
                $mysqli->query($insert_sql);
            }
            
            // Atualiza último login
            $mysqli->query("UPDATE usuarios SET last_login = NOW() WHERE id = $user_id");
            
            echo json_encode(['success' => true]);
            exit;
        } else {
            error_log("Login - Password verification FAILED");
        }
    } else {
        error_log("Login - User not found in database");
    }
    error_log("Login - FAILED - Invalid credentials");
    echo json_encode(['success' => false, 'erro' => 'Usuario ou senha inválidos!']);
    exit;
};
function hash_login($user, $timestamp) {
    // Usa a constante definida em conexao.php ou fallback
    if (!defined('SYSTEM_SESSION_KEY')) {
        require_once __DIR__ . '/../config/conexao.php';
    }
    
    // Fallback caso a constante ainda não exista
    $session_key = defined('SYSTEM_SESSION_KEY') ? SYSTEM_SESSION_KEY : 'gat_secure_key_' . md5('gat_system');
    
    $id_user = $user;
    
    // Cria um hash seguro combinando ID do usuário, chave e timestamp fixo
    $data_to_hash = $id_user . '|' . $session_key . '|' . $timestamp;
    $hash = hash_hmac('sha256', $data_to_hash, $session_key);
    
    return $hash;
};