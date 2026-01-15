<?php
include_once(__DIR__ . "/../src/php/hash_check.php");

// Bloquear acesso direto pelo navegador
if (basename($_SERVER['PHP_SELF']) === basename(__FILE__)) {
    http_response_code(403);
    die('Acesso direto não permitido.');
}

// index.php (na pasta D:\dev\GAT-testes)
define('PROJECT_ROOT', realpath(__DIR__ . '/..'));


function check_login()
{
    // Redireciona se não autenticado
    if (!isset($_SESSION['user_hash_login'])) {
        header('Location: login.php');
        exit;
    }
}




// Inicia a sessão se ainda não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Verificar hash de login
check_hash_login();

// ========== FUNÇÕES DE AUTORIZAÇÃO ==========

// Busca dados do usuário no banco de dados
function get_user_from_database() {
    if (!isset($_SESSION['user_id'])) {
        showErrorModal('Sessão inválida. Por favor, faça login novamente.', 'permission');
        exit;
    }
    
    require_once(__DIR__ . '/../src/config/conexao.php');
    global $mysqli;
    
    if (!isset($mysqli) || $mysqli === null) {
        showErrorModal('Erro de conexão com o banco de dados.', 'permission');
        exit;
    }
    
    $user_id = intval($_SESSION['user_id']);
    $stmt = $mysqli->prepare("SELECT id, user, perfil, departamento, active FROM usuarios WHERE id = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        showErrorModal('Usuário não encontrado. Por favor, faça login novamente.', 'permission');
        exit;
    }
    
    $user = $result->fetch_assoc();
    
    // Verificar se o usuário está ativo
    if ($user['active'] != 1) {
        session_destroy();
        showErrorModal('Sua conta foi desativada. Entre em contato com o administrador.', 'permission');
        exit;
    }
    
    // Atualizar sessão com dados do banco (segurança)
    $_SESSION['perfil'] = $user['perfil'];
    $_SESSION['departamento'] = $user['departamento'];
    
    return $user;
}

// Apenas Admin (perfil 1)
function check_permission_admin() {
    $user = get_user_from_database();
    
    if ($user['perfil'] != '1') {
        showErrorModal('Acesso restrito a administradores.', 'permission');
        exit;
    }
}

// Admin e Criador (perfis 1 e 2) - para gestão de conteúdo
function check_permission_creator() {
    $user = get_user_from_database();
    
    if ($user['perfil'] != '1' && $user['perfil'] != '2') {
        showErrorModal('Você não tem permissão para gerenciar conteúdo.', 'permission');
        exit;
    }
}

// Admin e Departamento (perfis 1 e 3) - para aprovações
function check_permission_approver() {
    $user = get_user_from_database();
    
    if ($user['perfil'] != '1' && $user['perfil'] != '3') {
        showErrorModal('Você não tem permissão para aprovar itens.', 'permission');
        exit;
    }
}

// Todos exceto Colaborador (perfis 1, 2, 3) - gestão geral
function check_permission_gestor() {
    $user = get_user_from_database();
    
    if ($user['perfil'] == '4') {
        showErrorModal('Você não tem permissão para acessar esta página.', 'permission');
        exit;
    }
}

// Todos os perfis (1, 2, 3, 4) - apenas visualização
function check_permission_viewer() {
    $user = get_user_from_database();
    
    // Todos podem ver, a função get_user_from_database já valida se está ativo
    return $user;
}