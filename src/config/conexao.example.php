<?php
// Configurações de Conexão com Banco de Dados
// Configure os valores abaixo com suas credenciais

define('DB_HOST', 'localhost');
define('DB_NAME', 'gat');
define('DB_USER', 'root');
define('DB_PASS', '');

// Chave de segurança para hash de sessão
// Altere esta chave para um valor único e aleatório
define('SYSTEM_SESSION_KEY', 'gat_secure_key_' . md5('gat_system'));

// Evitar reconexão se já existir
if (!isset($GLOBALS['mysqli']) || !($GLOBALS['mysqli'] instanceof mysqli)) {
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($mysqli->connect_errno) {
            error_log('Erro de conexão MySQL: ' . $mysqli->connect_error);
            die('Erro de conexão com o banco de dados. Código: ' . $mysqli->connect_errno);
        }
        
        $mysqli->set_charset('utf8mb4');
        
        // Armazenar no GLOBALS para garantir disponibilidade
        $GLOBALS['mysqli'] = $mysqli;
    } catch (Exception $e) {
        error_log('Exceção MySQL: ' . $e->getMessage());
        die('Erro ao conectar ao banco de dados: ' . $e->getMessage());
    }
} else {
    // Reutilizar conexão existente
    $mysqli = $GLOBALS['mysqli'];
}
?>
