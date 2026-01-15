<?php
// Bloqueia acesso se já estiver instalado
$installFlag = __DIR__ . '/.installed';
if (file_exists($installFlag)) {
    header('Location: ../viwer/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Já Instalado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        h1 { color: #333; margin-bottom: 20px; }
        p { color: #666; margin-bottom: 30px; line-height: 1.6; }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Sistema Já Instalado</h1>
        <p>O sistema GAT já foi instalado anteriormente neste servidor.</p>
        
        <div class="warning">
            <strong>⚠️ Atenção:</strong> Se você deseja reinstalar o sistema, você precisará remover o arquivo <code>.installed</code> da pasta <code>/install</code> e reconfigurar o banco de dados.
        </div>
        
        <a href="../viwer/login.php" class="btn">Ir para Login</a>
    </div>
</body>
</html>
