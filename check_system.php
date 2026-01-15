<?php
/**
 * Script de Verificação e Correção do Sistema
 * Execute este arquivo para verificar e corrigir problemas comuns
 */

echo "=== Verificação do Sistema GAT ===\n\n";

// 1. Verificar se o arquivo de configuração existe
echo "1. Verificando arquivo de configuração...\n";
$configFile = __DIR__ . '/src/config/conexao.php';

if (!file_exists($configFile)) {
    echo "   ❌ ERRO: Arquivo conexao.php não encontrado!\n";
    echo "   Solução: Copie o arquivo conexao.example.php para conexao.php e configure\n\n";
} else {
    echo "   ✅ Arquivo encontrado\n\n";
    
    // 2. Tentar incluir e testar conexão
    echo "2. Testando conexão com banco de dados...\n";
    
    try {
        include $configFile;
        
        if (!isset($mysqli) || $mysqli === null) {
            echo "   ❌ ERRO: Variável \$mysqli não foi criada\n";
            echo "   Solução: Verifique se o arquivo conexao.php está correto\n\n";
        } elseif ($mysqli->connect_errno) {
            echo "   ❌ ERRO: " . $mysqli->connect_error . "\n";
            echo "   Solução: Verifique as credenciais do banco de dados\n\n";
        } else {
            echo "   ✅ Conexão bem-sucedida!\n";
            echo "   Host: " . DB_HOST . "\n";
            echo "   Banco: " . DB_NAME . "\n";
            echo "   Usuário: " . DB_USER . "\n\n";
            
            // 3. Verificar tabelas
            echo "3. Verificando tabelas do banco...\n";
            $tables = ['usuarios', 'perfil', 'departaments', 'services', 'blocos', 'steps', 'questions', 'system_config'];
            $missingTables = [];
            
            foreach ($tables as $table) {
                $result = $mysqli->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows === 0) {
                    echo "   ❌ Tabela '$table' não encontrada\n";
                    $missingTables[] = $table;
                } else {
                    echo "   ✅ Tabela '$table' OK\n";
                }
            }
            
            if (count($missingTables) > 0) {
                echo "\n   ⚠️ Tabelas faltando: " . implode(', ', $missingTables) . "\n";
                echo "   Solução: Execute o arquivo install/database.sql no banco\n\n";
            } else {
                echo "\n   ✅ Todas as tabelas estão presentes\n\n";
            }
            
            // 4. Verificar usuário admin
            echo "4. Verificando usuário administrador...\n";
            $result = $mysqli->query("SELECT COUNT(*) as total FROM usuarios WHERE perfil = 1");
            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['total'] > 0) {
                    echo "   ✅ Usuário admin encontrado\n\n";
                } else {
                    echo "   ❌ Nenhum usuário admin encontrado\n";
                    echo "   Solução: Execute o instalador ou crie um usuário manualmente\n\n";
                }
            }
            
            // 5. Verificar permissões de pastas
            echo "5. Verificando permissões de escrita...\n";
            $folders = [
                'src/uploads',
                'src/config',
                'install'
            ];
            
            foreach ($folders as $folder) {
                $path = __DIR__ . '/' . $folder;
                if (!is_dir($path)) {
                    echo "   ⚠️ Pasta '$folder' não existe\n";
                } elseif (!is_writable($path)) {
                    echo "   ❌ Pasta '$folder' sem permissão de escrita\n";
                } else {
                    echo "   ✅ Pasta '$folder' OK\n";
                }
            }
            
            echo "\n=== Verificação Concluída ===\n";
        }
    } catch (Exception $e) {
        echo "   ❌ ERRO: " . $e->getMessage() . "\n\n";
    }
}

echo "\n=== Instruções ===\n";
echo "Se houver erros, siga as soluções indicadas acima.\n";
echo "Para reinstalar, remova o arquivo 'install/.installed' e acesse o instalador.\n";
?>
