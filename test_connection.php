<?php
/**
 * Teste de Conexão com Banco de Dados
 * Execute este arquivo para verificar se a conexão está funcionando
 */

echo "=== TESTE DE CONEXÃO GAT ===\n\n";

// 1. Verificar se o arquivo de configuração existe
echo "1. Verificando arquivo de configuração...\n";
$configFile = __DIR__ . '/src/config/conexao.php';

if (!file_exists($configFile)) {
    echo "   ❌ ERRO: Arquivo conexao.php não encontrado!\n";
    echo "   Local esperado: {$configFile}\n\n";
    exit(1);
}

echo "   ✅ Arquivo encontrado: {$configFile}\n\n";

// 2. Tentar incluir o arquivo de conexão
echo "2. Carregando arquivo de conexão...\n";

try {
    require_once $configFile;
    echo "   ✅ Arquivo carregado com sucesso\n\n";
} catch (Exception $e) {
    echo "   ❌ ERRO ao carregar: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 3. Verificar se a variável $mysqli foi criada
echo "3. Verificando variável \$mysqli...\n";

if (!isset($mysqli)) {
    echo "   ❌ ERRO: Variável \$mysqli não foi definida\n\n";
    exit(1);
}

if ($mysqli === null) {
    echo "   ❌ ERRO: Variável \$mysqli é NULL\n\n";
    exit(1);
}

if (!($mysqli instanceof mysqli)) {
    echo "   ❌ ERRO: \$mysqli não é uma instância de mysqli\n";
    echo "   Tipo encontrado: " . gettype($mysqli) . "\n\n";
    exit(1);
}

echo "   ✅ Variável \$mysqli criada corretamente\n\n";

// 4. Testar conexão
echo "4. Testando conexão...\n";

if ($mysqli->connect_errno) {
    echo "   ❌ ERRO de conexão: " . $mysqli->connect_error . "\n";
    echo "   Código: " . $mysqli->connect_errno . "\n\n";
    exit(1);
}

echo "   ✅ Conexão estabelecida!\n";
echo "   Host: " . DB_HOST . "\n";
echo "   Banco: " . DB_NAME . "\n";
echo "   Usuário: " . DB_USER . "\n";
echo "   Charset: " . $mysqli->character_set_name() . "\n\n";

// 5. Testar query simples
echo "5. Testando query no banco...\n";

$result = $mysqli->query("SELECT DATABASE() as db");
if (!$result) {
    echo "   ❌ ERRO na query: " . $mysqli->error . "\n\n";
    exit(1);
}

$row = $result->fetch_assoc();
echo "   ✅ Query executada com sucesso\n";
echo "   Banco em uso: " . $row['db'] . "\n\n";

// 6. Verificar tabelas principais
echo "6. Verificando tabelas...\n";
$tables = ['usuarios', 'perfil', 'departaments', 'services', 'blocos', 'steps'];
$allOk = true;

foreach ($tables as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        echo "   ✅ Tabela '$table'\n";
    } else {
        echo "   ❌ Tabela '$table' não encontrada\n";
        $allOk = false;
    }
}

echo "\n";

// 7. Verificar usuário admin
echo "7. Verificando usuário admin...\n";
$result = $mysqli->query("SELECT id, user, perfil, active FROM usuarios WHERE perfil = 1 LIMIT 1");

if (!$result) {
    echo "   ❌ ERRO ao consultar usuários: " . $mysqli->error . "\n\n";
} elseif ($result->num_rows === 0) {
    echo "   ⚠️ AVISO: Nenhum usuário admin encontrado\n\n";
} else {
    $admin = $result->fetch_assoc();
    echo "   ✅ Admin encontrado\n";
    echo "   ID: " . $admin['id'] . "\n";
    echo "   User: " . $admin['user'] . "\n";
    echo "   Perfil: " . $admin['perfil'] . "\n";
    echo "   Ativo: " . ($admin['active'] ? 'Sim' : 'Não') . "\n\n";
}

// Resultado final
echo "=== RESULTADO ===\n";
if ($allOk) {
    echo "✅ TUDO OK! Conexão funcionando perfeitamente!\n";
    echo "Você pode fazer login no sistema.\n";
} else {
    echo "⚠️ ATENÇÃO: Algumas tabelas estão faltando.\n";
    echo "Execute o arquivo install/database.sql no banco.\n";
}

$mysqli->close();
?>
