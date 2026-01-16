<?php
/**
 * Teste direto do processo de desinstalação
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Teste de Desinstalação</h1>";
echo "<hr>";

// Simular dados de entrada
$testData = [
    'db_host' => 'localhost',
    'db_name' => 'gat',
    'db_user' => 'root',
    'db_pass' => ''
];

echo "<h2>1. Verificar banco ANTES da desinstalação</h2>";

try {
    $mysqli = new mysqli(
        $testData['db_host'],
        $testData['db_user'],
        $testData['db_pass']
    );
    
    if ($mysqli->connect_errno) {
        echo "❌ Erro de conexão: " . $mysqli->connect_error . "<br>";
    } else {
        echo "✅ Conectado ao MySQL<br>";
        
        // Verificar se banco existe
        $result = $mysqli->query("SHOW DATABASES LIKE '{$testData['db_name']}'");
        if ($result && $result->num_rows > 0) {
            echo "✅ Banco '{$testData['db_name']}' existe<br>";
            
            // Selecionar banco e listar tabelas
            $mysqli->select_db($testData['db_name']);
            $tables = [];
            $result = $mysqli->query("SHOW TABLES");
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                echo "📊 <strong>Tabelas encontradas (" . count($tables) . "):</strong><br>";
                echo "<ul>";
                foreach ($tables as $table) {
                    // Contar registros
                    $count = $mysqli->query("SELECT COUNT(*) as total FROM `{$table}`");
                    $total = $count ? $count->fetch_assoc()['total'] : 0;
                    echo "<li>{$table} ({$total} registros)</li>";
                }
                echo "</ul>";
            }
        } else {
            echo "⚠️ Banco '{$testData['db_name']}' não existe<br>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>2. Testar Desinstalação (via cURL ou fetch)</h2>";

echo "<div style='background: #fffacd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p><strong>⚠️ Teste Manual:</strong></p>";
echo "<p>1. Abra o console do navegador (F12)</p>";
echo "<p>2. Execute o seguinte código:</p>";
echo "<pre style='background: #f5f5f5; padding: 10px;'>";
echo htmlspecialchars("
fetch('uninstall_process.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        db_host: 'localhost',
        db_name: 'gat',
        db_user: 'root',
        db_pass: ''
    })
})
.then(r => r.json())
.then(data => {
    console.log('Resposta:', data);
    console.log('Debug:', data.debug);
    alert(data.success ? 'Sucesso!' : 'Erro: ' + data.message);
});
");
echo "</pre>";
echo "<button onclick='testUninstall()' style='padding: 10px 20px; background: #f44336; color: white; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>🗑️ Executar Desinstalação Agora</button>";
echo "</div>";

echo "<div id='testResult' style='margin-top: 20px;'></div>";

echo "<script>
async function testUninstall() {
    if (!confirm('⚠️ Isso vai APAGAR o banco de dados! Confirma?')) {
        return;
    }
    
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = '<p>⏳ Executando desinstalação...</p>';
    
    try {
        const response = await fetch('uninstall_process.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                db_host: 'localhost',
                db_name: 'gat',
                db_user: 'root',
                db_pass: ''
            })
        });
        
        const data = await response.json();
        console.log('Resposta completa:', data);
        console.log('Debug log:', data.debug);
        
        let html = '<div style=\"background: ' + (data.success ? '#d4edda' : '#f8d7da') + '; padding: 15px; border-radius: 5px; border: 2px solid ' + (data.success ? '#28a745' : '#dc3545') + ';\">';
        html += '<h3>' + (data.success ? '✅ Sucesso!' : '❌ Erro') + '</h3>';
        html += '<p><strong>Mensagem:</strong> ' + (data.message || 'N/A') + '</p>';
        
        if (data.debug) {
            html += '<details><summary>📋 Debug Log (' + data.debug.length + ' linhas)</summary><ul>';
            data.debug.forEach(log => {
                html += '<li>' + log + '</li>';
            });
            html += '</ul></details>';
        }
        
        html += '<p style=\"margin-top: 15px;\"><button onclick=\"location.reload()\" style=\"padding: 8px 16px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;\">🔄 Recarregar Página</button></p>';
        html += '</div>';
        
        resultDiv.innerHTML = html;
    } catch (error) {
        console.error('Erro:', error);
        resultDiv.innerHTML = '<div style=\"background: #f8d7da; padding: 15px; border-radius: 5px;\"><p>❌ Erro: ' + error.message + '</p></div>';
    }
}
</script>";

echo "<hr>";
echo "<h2>3. Verificar banco DEPOIS da desinstalação</h2>";

try {
    $mysqli = new mysqli(
        $testData['db_host'],
        $testData['db_user'],
        $testData['db_pass']
    );
    
    if ($mysqli->connect_errno) {
        echo "❌ Erro de conexão: " . $mysqli->connect_error . "<br>";
    } else {
        echo "✅ Conectado ao MySQL<br>";
        
        // Verificar se banco ainda existe
        $result = $mysqli->query("SHOW DATABASES LIKE '{$testData['db_name']}'");
        if ($result && $result->num_rows > 0) {
            echo "⚠️ <strong style='color: orange;'>Banco '{$testData['db_name']}' ainda existe!</strong><br>";
            
            // Listar tabelas restantes
            $mysqli->select_db($testData['db_name']);
            $tables = [];
            $result = $mysqli->query("SHOW TABLES");
            if ($result) {
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                if (count($tables) > 0) {
                    echo "❌ <strong>Tabelas que não foram removidas (" . count($tables) . "):</strong><br>";
                    echo "<ul style='color: red;'>";
                    foreach ($tables as $table) {
                        echo "<li>{$table}</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "✅ Nenhuma tabela no banco (banco vazio)<br>";
                }
            }
        } else {
            echo "✅ <strong style='color: green;'>Banco '{$testData['db_name']}' foi removido com sucesso!</strong><br>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<h2>4. Verificar arquivos</h2>";

$configFile = dirname(__DIR__) . '/src/config/conexao.php';
$flagFile = __DIR__ . '/.installed';

echo "<ul>";
echo "<li>conexao.php: " . (file_exists($configFile) ? '❌ Ainda existe' : '✅ Removido') . "</li>";
echo "<li>.installed: " . (file_exists($flagFile) ? '❌ Ainda existe' : '✅ Removido') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='uninstall.php'>← Voltar para Desinstalação</a></p>";
?>
