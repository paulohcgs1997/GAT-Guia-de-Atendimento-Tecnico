<?php
// install_process.php - Processa a instalação do sistema
header('Content-Type: application/json');

// Recebe os dados JSON
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// Função para criar arquivo de configuração
function createConfigFile($host, $dbname, $user, $pass) {
    $configPath = dirname(__DIR__) . '/src/config/conexao.php';
    
    // Escapa aspas simples nos valores
    $host = str_replace("'", "\\'", $host);
    $dbname = str_replace("'", "\\'", $dbname);
    $user = str_replace("'", "\\'", $user);
    $pass = str_replace("'", "\\'", $pass);
    
    $content = "<?php
// Configurações de Conexão com Banco de Dados
// Gerado automaticamente pelo instalador

define('DB_HOST', '{$host}');
define('DB_NAME', '{$dbname}');
define('DB_USER', '{$user}');
define('DB_PASS', '{$pass}');

// Evitar reconexão se já existir
if (!isset(\$GLOBALS['mysqli']) || !(\$GLOBALS['mysqli'] instanceof mysqli)) {
    try {
        \$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if (\$mysqli->connect_errno) {
            error_log('Erro de conexão MySQL: ' . \$mysqli->connect_error);
            die('Erro de conexão com o banco de dados. Código: ' . \$mysqli->connect_errno);
        }
        
        \$mysqli->set_charset('utf8mb4');
        
        // Armazenar no GLOBALS para garantir disponibilidade
        \$GLOBALS['mysqli'] = \$mysqli;
    } catch (Exception \$e) {
        error_log('Exceção MySQL: ' . \$e->getMessage());
        die('Erro ao conectar ao banco de dados: ' . \$e->getMessage());
    }
} else {
    // Reutilizar conexão existente
    \$mysqli = \$GLOBALS['mysqli'];
}
?>";
    
    return file_put_contents($configPath, $content);
}

// Função para criar arquivo de flag de instalação
function createInstallFlag() {
    $flagPath = dirname(__DIR__) . '/install/.installed';
    return file_put_contents($flagPath, date('Y-m-d H:i:s'));
}

// Ação: Testar conexão com banco
if ($action === 'test_db') {
    $host = $input['db_host'] ?? 'localhost';
    $dbname = $input['db_name'] ?? 'gat';
    $user = $input['db_user'] ?? 'root';
    $pass = $input['db_pass'] ?? '';
    
    try {
        // Tenta conectar sem especificar o banco
        $mysqli = new mysqli($host, $user, $pass);
        
        if ($mysqli->connect_errno) {
            echo json_encode([
                'success' => false,
                'message' => 'Erro de conexão: ' . $mysqli->connect_error
            ]);
            exit;
        }
        
        // Verifica se o banco já existe
        $result = $mysqli->query("SHOW DATABASES LIKE '{$dbname}'");
        $dbExists = $result && $result->num_rows > 0;
        
        $mysqli->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Conexão bem-sucedida!',
            'db_exists' => $dbExists
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Ação: Instalar sistema
if ($action === 'install') {
    $db = $input['db'] ?? [];
    $admin = $input['admin'] ?? [];
    
    $host = $db['db_host'] ?? 'localhost';
    $dbname = $db['db_name'] ?? 'gat';
    $user = $db['db_user'] ?? 'root';
    $pass = $db['db_pass'] ?? '';
    
    $adminUser = $admin['admin_user'] ?? 'admin';
    $adminPass = $admin['admin_pass'] ?? '';
    
    if (empty($adminPass)) {
        echo json_encode([
            'success' => false,
            'message' => 'Senha do administrador não pode ser vazia'
        ]);
        exit;
    }
    
    try {
        // Conecta ao MySQL
        $mysqli = new mysqli($host, $user, $pass);
        $mysqli->set_charset('utf8mb4');
        
        if ($mysqli->connect_errno) {
            throw new Exception('Erro de conexão: ' . $mysqli->connect_error);
        }
        
        // Cria o banco de dados
        $mysqli->query("CREATE DATABASE IF NOT EXISTS `{$dbname}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $mysqli->select_db($dbname);
        
        // Lê e executa o SQL
        $sqlFile = __DIR__ . '/database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception('Arquivo database.sql não encontrado');
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Remove comentários e divide em queries individuais
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Executa cada query
        $mysqli->multi_query($sql);
        
        // Aguarda todas as queries serem executadas
        do {
            if ($result = $mysqli->store_result()) {
                $result->free();
            }
        } while ($mysqli->more_results() && $mysqli->next_result());
        
        // Cria o hash da senha do admin
        $passwordHash = password_hash($adminPass, PASSWORD_BCRYPT);
        
        // Insere o usuário admin
        $stmt = $mysqli->prepare("INSERT INTO usuarios (user, password, active, perfil) VALUES (?, ?, 1, 1)");
        $stmt->bind_param('ss', $adminUser, $passwordHash);
        
        if (!$stmt->execute()) {
            throw new Exception('Erro ao criar usuário admin: ' . $stmt->error);
        }
        
        $stmt->close();
        $mysqli->close();
        
        // Cria o arquivo de configuração
        if (!createConfigFile($host, $dbname, $user, $pass)) {
            throw new Exception('Erro ao criar arquivo de configuração');
        }
        
        // Cria flag de instalação
        createInstallFlag();
        
        echo json_encode([
            'success' => true,
            'message' => 'Sistema instalado com sucesso!'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Ação inválida
echo json_encode([
    'success' => false,
    'message' => 'Ação inválida'
]);
?>
