<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_login();

include_once(__DIR__ . '/../config/conexao.php');

// Função para buscar configuração
function getConfig($mysqli, $key, $default = '') {
    $sql = "SELECT config_value FROM system_config WHERE config_key = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return !empty($row['config_value']) ? $row['config_value'] : $default;
    }
    
    return $default;
}

// Se for requisição AJAX, retornar JSON
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $configs = [
        'system_name' => getConfig($mysqli, 'system_name', 'Sistema de Gestão'),
        'system_logo' => getConfig($mysqli, 'system_logo', ''),
        'system_favicon' => getConfig($mysqli, 'system_favicon', ''),
        'system_description' => getConfig($mysqli, 'system_description', 'Sistema Empresarial de Gestão'),
        'system_email' => getConfig($mysqli, 'system_email', 'contato@empresa.com'),
        'system_phone' => getConfig($mysqli, 'system_phone', '(00) 0000-0000')
    ];
    
    echo json_encode(['success' => true, 'configs' => $configs]);
    exit;
}
