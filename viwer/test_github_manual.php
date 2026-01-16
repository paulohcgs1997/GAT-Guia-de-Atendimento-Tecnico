<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Configuração GitHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <div class="container mt-5">
        <h1>🧪 Teste Manual de Configuração GitHub</h1>
        <hr>
        
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">1. Testar get_github_config.php</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" onclick="testGetConfig()">
                    <i class="bi bi-play-fill"></i> Executar Teste
                </button>
                <div id="getConfigResult" class="mt-3"></div>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">2. Testar save_github_config.php</h5>
            </div>
            <div class="card-body">
                <form onsubmit="testSaveConfig(event)">
                    <div class="mb-3">
                        <label class="form-label">URL do GitHub:</label>
                        <input type="text" class="form-control" id="testUrl" 
                               value="https://github.com/paulohcgs1997/GAT-Guia-de-Atendimento-Tecnico">
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save"></i> Salvar
                    </button>
                </form>
                <div id="saveConfigResult" class="mt-3"></div>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">3. Testar check_updates.php</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-info text-white" onclick="testCheckUpdates()">
                    <i class="bi bi-cloud-download"></i> Verificar Atualizações
                </button>
                <div id="checkUpdatesResult" class="mt-3"></div>
            </div>
        </div>
        
        <hr>
        <a href="gestao_configuracoes.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Voltar para Configurações
        </a>
    </div>

    <script>
        async function testGetConfig() {
            const resultDiv = document.getElementById('getConfigResult');
            resultDiv.innerHTML = '<div class="spinner-border" role="status"></div> Carregando...';
            
            try {
                const response = await fetch('../src/php/get_github_config.php');
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="alert alert-${data.success ? 'success' : 'warning'}">
                        <strong>Status:</strong> ${response.status}<br>
                        <strong>Success:</strong> ${data.success}<br>
                        <pre class="mt-2">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erro:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        async function testSaveConfig(event) {
            event.preventDefault();
            const resultDiv = document.getElementById('saveConfigResult');
            const url = document.getElementById('testUrl').value;
            
            resultDiv.innerHTML = '<div class="spinner-border" role="status"></div> Salvando...';
            
            try {
                const formData = new FormData();
                formData.append('github_url', url);
                
                const response = await fetch('../src/php/save_github_config.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="alert alert-${data.success ? 'success' : 'danger'}">
                        <strong>Status:</strong> ${response.status}<br>
                        <strong>Success:</strong> ${data.success}<br>
                        <strong>Message:</strong> ${data.message}<br>
                        <pre class="mt-2">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
                
                if (data.success) {
                    setTimeout(() => testCheckUpdates(), 1000);
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erro:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        async function testCheckUpdates() {
            const resultDiv = document.getElementById('checkUpdatesResult');
            resultDiv.innerHTML = '<div class="spinner-border" role="status"></div> Verificando...';
            
            try {
                const response = await fetch('../src/php/check_updates.php');
                const data = await response.json();
                
                resultDiv.innerHTML = `
                    <div class="alert alert-${data.success ? 'success' : 'warning'}">
                        <strong>Status:</strong> ${response.status}<br>
                        <strong>Success:</strong> ${data.success}<br>
                        <strong>Current Version:</strong> ${data.current_version || 'N/A'}<br>
                        <strong>Has Update:</strong> ${data.has_update || false}<br>
                        <pre class="mt-2">${JSON.stringify(data, null, 2)}</pre>
                    </div>
                `;
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <strong>Erro:</strong> ${error.message}
                    </div>
                `;
            }
        }
        
        // Executar teste automático ao carregar
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 Página de teste carregada');
            testGetConfig();
        });
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
