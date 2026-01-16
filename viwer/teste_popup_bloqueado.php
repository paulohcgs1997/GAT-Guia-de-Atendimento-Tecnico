<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste - Modal Pop-up Bloqueado</title>
    <link rel="stylesheet" href="../src/css/style.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .test-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #1e3a8a;
            margin-bottom: 20px;
        }
        .test-info {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #0ea5e9;
            margin-bottom: 30px;
        }
        .test-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #3f66d1 0%, #2563eb 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 58, 138, 0.35);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.25);
        }
        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(22, 163, 74, 0.35);
        }
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.25);
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.35);
        }
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin-top: 20px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Teste - Modal de Pop-up Bloqueado</h1>
        
        <div class="test-info">
            <strong>ℹ️ Informações do Teste:</strong>
            <p>Este é um ambiente de teste para visualizar o modal de aviso quando os pop-ups estão bloqueados.</p>
            <p>O modal detecta automaticamente o navegador e mostra instruções específicas.</p>
        </div>

        <h3>Ações de Teste:</h3>
        <div class="test-buttons">
            <button class="btn btn-primary" onclick="testarPopup()">
                🪟 Testar Abertura de Pop-up
            </button>
            <button class="btn btn-secondary" onclick="mostrarModalDireto()">
                👁️ Visualizar Modal Direto
            </button>
            <button class="btn btn-warning" onclick="detectarNavegador()">
                🌐 Detectar Navegador
            </button>
        </div>

        <div class="code-block" id="resultadoTeste">
            <strong>Resultado:</strong> Clique em um dos botões acima para testar.
        </div>
    </div>

    <script src="../src/js/serach.js"></script>
    <script>
        function testarPopup() {
            document.getElementById('resultadoTeste').innerHTML = '<strong>Testando abertura de pop-up...</strong>';
            
            // Tentar abrir uma janela pop-up
            const popup = window.open('', 'teste', 'width=400,height=400');
            
            // Verificar se foi bloqueado
            if (!popup || popup.closed || typeof popup.closed === 'undefined') {
                document.getElementById('resultadoTeste').innerHTML = 
                    '<strong style="color: #ef4444;">❌ Pop-up Bloqueado!</strong><br>' +
                    'O navegador bloqueou a abertura da janela.<br>' +
                    'Mostrando modal de instrução...';
                
                // Chamar a função do serach.js
                if (typeof mostrarAvisoPopupBloqueado === 'function') {
                    mostrarAvisoPopupBloqueado();
                } else {
                    alert('Função mostrarAvisoPopupBloqueado não encontrada!');
                }
            } else {
                popup.close();
                document.getElementById('resultadoTeste').innerHTML = 
                    '<strong style="color: #22c55e;">✅ Pop-up Permitido!</strong><br>' +
                    'O navegador permitiu a abertura da janela.<br>' +
                    'Pop-ups estão habilitados para este site.';
            }
        }

        function mostrarModalDireto() {
            document.getElementById('resultadoTeste').innerHTML = 
                '<strong>Abrindo modal de instrução...</strong>';
            
            if (typeof mostrarAvisoPopupBloqueado === 'function') {
                mostrarAvisoPopupBloqueado();
            } else {
                alert('Função mostrarAvisoPopupBloqueado não encontrada!\nVerifique se o arquivo serach.js foi carregado.');
            }
        }

        function detectarNavegador() {
            const userAgent = navigator.userAgent.toLowerCase();
            let navegador = 'Desconhecido';
            let detalhes = '';
            
            if (userAgent.includes('chrome') && !userAgent.includes('edge')) {
                navegador = 'Google Chrome';
                detalhes = 'Baseado em Chromium';
            } else if (userAgent.includes('firefox')) {
                navegador = 'Mozilla Firefox';
                detalhes = 'Motor Gecko';
            } else if (userAgent.includes('edge')) {
                navegador = 'Microsoft Edge';
                detalhes = 'Baseado em Chromium';
            } else if (userAgent.includes('safari')) {
                navegador = 'Safari';
                detalhes = 'Motor WebKit';
            } else if (userAgent.includes('opera') || userAgent.includes('opr')) {
                navegador = 'Opera';
                detalhes = 'Baseado em Chromium';
            }
            
            document.getElementById('resultadoTeste').innerHTML = 
                '<strong>🌐 Navegador Detectado:</strong><br>' +
                `<strong style="color: #3b82f6;">${navegador}</strong><br>` +
                `${detalhes}<br><br>` +
                `<small>User Agent: ${navigator.userAgent}</small>`;
        }

        // Mensagem inicial
        console.log('🧪 Página de teste carregada!');
        console.log('📁 Arquivo serach.js:', typeof mostrarAvisoPopupBloqueado !== 'undefined' ? 'Carregado ✅' : 'Não carregado ❌');
    </script>
</body>
</html>
