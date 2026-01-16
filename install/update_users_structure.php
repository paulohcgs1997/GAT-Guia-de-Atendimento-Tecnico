<?php
/**
 * Script para atualizar a estrutura da tabela de usuários
 * Adiciona campos para perfil completo: nome_completo, email, telefone, foto
 * 
 * Execute este arquivo UMA ÚNICA VEZ acessando:
 * http://seudominio/install/update_users_structure.php
 */

require_once(__DIR__ . '/../src/config/conexao.php');

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <title>Atualizar Estrutura de Usuários</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 12px;
            border-radius: 5px;
            margin: 10px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔧 Atualização da Estrutura de Usuários</h1>";

$errors = [];
$success = [];

// Verificar se as colunas já existem
$check_query = "SHOW COLUMNS FROM usuarios LIKE 'nome_completo'";
$result = $mysqli->query($check_query);

if ($result->num_rows > 0) {
    echo "<div class='info'>✓ As colunas já foram adicionadas anteriormente. Nenhuma ação necessária.</div>";
} else {
    echo "<div class='info'>Adicionando novos campos à tabela de usuários...</div>";
    
    // Adicionar coluna nome_completo
    $sql1 = "ALTER TABLE `usuarios` ADD COLUMN `nome_completo` VARCHAR(200) NULL AFTER `user`";
    if ($mysqli->query($sql1)) {
        $success[] = "✓ Campo 'nome_completo' adicionado";
    } else {
        $errors[] = "✗ Erro ao adicionar 'nome_completo': " . $mysqli->error;
    }
    
    // Adicionar coluna email
    $sql2 = "ALTER TABLE `usuarios` ADD COLUMN `email` VARCHAR(200) NULL AFTER `nome_completo`";
    if ($mysqli->query($sql2)) {
        $success[] = "✓ Campo 'email' adicionado";
    } else {
        $errors[] = "✗ Erro ao adicionar 'email': " . $mysqli->error;
    }
    
    // Adicionar coluna telefone
    $sql3 = "ALTER TABLE `usuarios` ADD COLUMN `telefone` VARCHAR(20) NULL AFTER `email`";
    if ($mysqli->query($sql3)) {
        $success[] = "✓ Campo 'telefone' adicionado";
    } else {
        $errors[] = "✗ Erro ao adicionar 'telefone': " . $mysqli->error;
    }
    
    // Adicionar coluna foto
    $sql4 = "ALTER TABLE `usuarios` ADD COLUMN `foto` VARCHAR(500) NULL AFTER `telefone`";
    if ($mysqli->query($sql4)) {
        $success[] = "✓ Campo 'foto' adicionado";
    } else {
        $errors[] = "✗ Erro ao adicionar 'foto': " . $mysqli->error;
    }
    
    // Adicionar coluna created_at
    $sql5 = "ALTER TABLE `usuarios` ADD COLUMN `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP AFTER `last_login`";
    if ($mysqli->query($sql5)) {
        $success[] = "✓ Campo 'created_at' adicionado";
    } else {
        // Não é erro crítico se já existir
        if (!strpos($mysqli->error, 'Duplicate column')) {
            $errors[] = "✗ Erro ao adicionar 'created_at': " . $mysqli->error;
        }
    }
    
    // Adicionar coluna updated_at
    $sql6 = "ALTER TABLE `usuarios` ADD COLUMN `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`";
    if ($mysqli->query($sql6)) {
        $success[] = "✓ Campo 'updated_at' adicionado";
    } else {
        if (!strpos($mysqli->error, 'Duplicate column')) {
            $errors[] = "✗ Erro ao adicionar 'updated_at': " . $mysqli->error;
        }
    }
    
    // Atualizar usuários existentes com email padrão
    if (count($errors) == 0) {
        $sql7 = "UPDATE `usuarios` SET `email` = CONCAT(`user`, '@sistema.local') WHERE `email` IS NULL OR `email` = ''";
        if ($mysqli->query($sql7)) {
            $affected = $mysqli->affected_rows;
            if ($affected > 0) {
                $success[] = "✓ {$affected} usuário(s) atualizado(s) com email padrão";
            }
        }
        
        // Adicionar índice único para email (se não existir)
        $check_index = "SHOW INDEX FROM usuarios WHERE Key_name = 'email'";
        $result = $mysqli->query($check_index);
        
        if ($result->num_rows == 0) {
            $sql8 = "ALTER TABLE `usuarios` ADD UNIQUE KEY `email` (`email`)";
            if ($mysqli->query($sql8)) {
                $success[] = "✓ Índice único adicionado ao campo 'email'";
            } else {
                $errors[] = "✗ Erro ao adicionar índice: " . $mysqli->error;
            }
        }
    }
}

// Mostrar resultados
foreach ($success as $msg) {
    echo "<div class='success'>{$msg}</div>";
}

foreach ($errors as $msg) {
    echo "<div class='error'>{$msg}</div>";
}

if (count($errors) == 0) {
    echo "<div class='success'><strong>✓ Atualização concluída com sucesso!</strong></div>";
    echo "<div class='info'>
        <strong>Próximos passos:</strong><br>
        1. Acesse o sistema normalmente<br>
        2. Vá em <code>Meu Perfil</code> no menu do usuário<br>
        3. Complete suas informações pessoais<br>
        4. Adicione uma foto de perfil<br>
        5. <strong>IMPORTANTE:</strong> Delete este arquivo (update_users_structure.php) após a atualização
    </div>";
} else {
    echo "<div class='error'><strong>✗ A atualização encontrou erros. Verifique as mensagens acima.</strong></div>";
}

echo "
    </div>
</body>
</html>";

$mysqli->close();
?>
