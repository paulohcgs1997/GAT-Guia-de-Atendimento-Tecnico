<?php
function showErrorModal($message, $type) {
    echo '
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 400px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
        }
        .modal-icon.error {
            font-size: 50px;
            margin-bottom: 15px;
        }
        .modal-content h3 {
            margin: 0 0 15px 0;
            color: #333;
        }
        .modal-content p {
            margin: 0 0 20px 0;
            color: #666;
            line-height: 1.5;
        }
        .modal-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        .modal-btn:hover {
            background: #c82333;
        }
    </style>
    
    <!-- Modal de Erro de Sessão -->
    <div class="modal-overlay active" id="sessionErrorModal">
        <div class="modal-content">
            <div class="modal-icon error">⚠️</div>
            <h3>Atenção</h3>
            <p id="modalMessage">' . htmlspecialchars($message) . '</p>
            ' . ($type == 'error' ? 
                '<form method="post" action="logout.php" style="margin: 0;">
                    <button type="submit" class="modal-btn">Fazer Logout</button>
                </form>' : '') 
                . ($type == 'permission' ? '
                <form method="post" action="dashboard.php" style="margin: 0;">
                    <button type="submit" class="modal-btn">inicio</button>
                </form>' : '') . '
        </div>
    </div>';
}

function check_hash_login()
{
    // Verifica se a sessão já foi iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Verifica se as variáveis necessárias existem na sessão
    if (!isset($_SESSION['user_hash_login']) || 
        !isset($_SESSION['user_id']) || 
        !isset($_SESSION['user'])) {
            
        showErrorModal('Não foi possível validar sua sessão. Por favor, faça login novamente.' ,'error');
        return false;
    }

    $user_id = $_SESSION['user_id'];
    
    $hash_sessao = $_SESSION['user_hash_login'];

    // Conecta ao banco de dados
    include_once __DIR__ . '/../config/conexao.php';

    // Busca o hash do banco de dados
    $sql = "SELECT login_hash, validity FROM hash_login WHERE user_id = $user_id";
    $result = $mysqli->query($sql);

    // Se não encontrou hash no banco
    if ($result->num_rows == 0) {
        showErrorModal('Não foi possível validar sua sessão. Por favor, faça login novamente.' , 'error');
        return false;
    }

    $row = $result->fetch_assoc();
    $hash_banco = $row['login_hash'];
    $validity = $row['validity'];

    // Verifica se o hash ainda é válido (não expirou)
    $validity_timestamp = strtotime($validity);
    if (time() > $validity_timestamp) {
        // Hash expirado
        showErrorModal('Sua sessão expirou por inatividade. Por favor, faça login novamente para continuar.' ,'error');
        return false;
    }

    // Compara o hash da sessão com o hash do banco de forma segura
    if (!hash_equals($hash_banco, $hash_sessao)) {
        showErrorModal('Detectamos que sua conta foi acessada em outro dispositivo ou navegador. Por segurança, você precisa fazer login novamente.' ,'error');
        return false;
    }
    //echo'Usuário autenticado com sucesso.';
    // Tudo OK - usuário autenticado
    return true;
    
}
