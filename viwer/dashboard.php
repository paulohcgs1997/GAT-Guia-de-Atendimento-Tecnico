<?php
include_once("includes.php");
check_login();
check_permission_viewer();

// Redirecionar departamento para página de aprovações
if ($_SESSION['perfil'] == '3') {
    header('Location: aprovacoes.php');
    exit;
}

?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once PROJECT_ROOT . '/src/includes/head_config.php'; ?>
    <link rel="stylesheet" href="../src/css/style.css">
</head>

<body>
    
        <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>
        

    <main>
        <!-- Conteúdo da página -->
        <?php
        echo '<pre>';
        print_r($_SESSION);
        echo '</pre>';

        ?>
    </main>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>



</body>


</html>