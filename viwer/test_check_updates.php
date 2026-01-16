<?php
/**
 * Teste direto da API de Check Updates
 */
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔍 Teste de Check Updates</h1>";
echo "<hr>";

// Forçar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir o arquivo check_updates.php e capturar a saída
echo "<h2>Resposta de check_updates.php:</h2>";
echo "<pre>";

ob_start();
include __DIR__ . '/../src/php/check_updates.php';
$response = ob_get_clean();

echo htmlspecialchars($response);
echo "</pre>";

echo "<hr>";
echo "<h2>Decodificado:</h2>";
$data = json_decode($response, true);
echo "<pre>";
print_r($data);
echo "</pre>";

echo "<hr>";
echo "<a href='gestao_configuracoes.php'>← Voltar para Configurações</a>";
?>
