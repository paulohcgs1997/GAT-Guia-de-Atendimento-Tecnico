<?php
// index.php (na pasta D:\dev\GAT-testes)
define('PROJECT_ROOT', __DIR__);

// Verifica se o sistema está instalado
$installFlag = __DIR__ . '/install/.installed';
if (!file_exists($installFlag)) {
    header('Location: install/index.php');
    exit;
}

session_start();
if (isset($_SESSION['user_hash_login'])) {
    header('Location: viwer/dashboard.php');
    exit;
} else {
    header('Location: viwer/login.php');
    exit;
}
?>