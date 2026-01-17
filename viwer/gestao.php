<?php
include_once(__DIR__ . "/includes.php");
check_login();
check_permission_gestor();
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php include_once PROJECT_ROOT . '/src/includes/head_config.php'; ?>
    <link rel="stylesheet" href="../src/css/style.css">
</head>

<body>
    <?php include_once PROJECT_ROOT . '/src/includes/header.php'; ?>

    

    <main>
        <div class="gestao-container">
            <h1>Gestão do Sistema</h1>
            
            <div class="gestao-cards">
                <div class="gestao-card">
                    <div class="card-icon">🔍</div>
                    <h3>Serviços</h3>
                    <p>Gerenciar serviços disponíveis para busca</p>
                    <button class="btn-gestao" onclick="window.location.href='gestao_services.php'">Acessar</button>
                </div>

                <div class="gestao-card">
                    <div class="card-icon">📚</div>
                    <h3>Tutoriais</h3>
                    <p>Criar e gerenciar tutoriais completos</p>
                    <button class="btn-gestao" onclick="window.location.href='gestao_blocos.php'">Acessar</button>
                </div>

                <?php if ($_SESSION['perfil'] == '1'): ?>
                <div class="gestao-card">
                    <div class="card-icon">👥</div>
                    <h3>Usuários</h3>
                    <p>Gerenciar usuários do sistema</p>
                    <button class="btn-gestao" onclick="window.location.href='gestao_users.php'">Acessar</button>
                </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['perfil'] == '1' || $_SESSION['perfil'] == '3'): ?>
                <div class="gestao-card">
                    <div class="card-icon">✅</div>
                    <h3>Aprovações</h3>
                    <p>Aprovar tutoriais e serviços pendentes</p>
                    <button class="btn-gestao" onclick="window.location.href='aprovacoes.php'">Acessar</button>
                </div>
                <?php endif; ?>

                <div class="gestao-card" id="reprovadosCard" style="position: relative;">
                    <div class="card-icon">📛</div>
                    <h3>Itens Reprovados <span id="reprovadosCount" style="display: none;"></span></h3>
                    <p id="reprovadosText">Corrigir serviços e tutoriais rejeitados</p>
                    <span id="reprovadosBadge" class="card-badge" style="display: none;">0</span>
                    <button class="btn-gestao" onclick="window.location.href='gestao_reprovados.php'">Acessar</button>
                </div>

                <?php if ($_SESSION['perfil'] == '1'): ?>
                <div class="gestao-card">
                    <div class="card-icon">🏢</div>
                    <h3>Departamentos</h3>
                    <p>Gerenciar departamentos da empresa</p>
                    <button class="btn-gestao" onclick="window.location.href='gestao_departamentos.php'">Acessar</button>
                </div>
                
                <div class="gestao-card">
                    <div class="card-icon">⚙️</div>
                    <h3>Configurações</h3>
                    <p>Personalizar informações do sistema</p>
                    <button class="btn-gestao" onclick="window.location.href='gestao_configuracoes.php'">Acessar</button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>Sistema em desenvolvimento</p>
    </footer>

</body>

<script>
    // Carregar contador de itens reprovados
    async function loadRejectedCount() {
        try {
            console.log('🔄 Carregando contador de itens reprovados...');
            const response = await fetch('../src/php/get_rejected_items.php');
            const data = await response.json();
            
            console.log('📊 Dados recebidos:', data);

            if (data.success) {
                const badge = document.getElementById('reprovadosBadge');
                const card = document.getElementById('reprovadosCard');
                const countSpan = document.getElementById('reprovadosCount');
                const textP = document.getElementById('reprovadosText');
                
                console.log('✅ Total de itens reprovados:', data.total);
                
                if (data.total > 0) {
                    badge.textContent = data.total;
                    badge.style.display = 'block';
                    card.classList.add('has-items');
                    
                    // Adicionar contador no título
                    countSpan.textContent = `(${data.total})`;
                    countSpan.style.display = 'inline';
                    countSpan.style.color = '#ef4444';
                    countSpan.style.fontWeight = 'bold';
                    
                    // Atualizar texto descritivo
                    const tutoriaisText = data.tutoriais.length > 0 ? `${data.tutoriais.length} tutorial${data.tutoriais.length > 1 ? 'is' : ''}` : '';
                    const servicosText = data.servicos.length > 0 ? `${data.servicos.length} serviço${data.servicos.length > 1 ? 's' : ''}` : '';
                    
                    if (tutoriaisText && servicosText) {
                        textP.textContent = `${tutoriaisText} e ${servicosText} precisam de correção`;
                    } else if (tutoriaisText) {
                        textP.textContent = `${tutoriaisText} precisa${data.tutoriais.length > 1 ? 'm' : ''} de correção`;
                    } else if (servicosText) {
                        textP.textContent = `${servicosText} precisa${data.servicos.length > 1 ? 'm' : ''} de correção`;
                    }
                    
                    console.log('✅ Badge atualizado com', data.total, 'itens');
                } else {
                    console.log('ℹ️ Nenhum item reprovado encontrado');
                }
            } else {
                console.error('❌ Erro na resposta:', data.message);
            }
        } catch (error) {
            console.error('❌ Erro ao carregar contagem de itens reprovados:', error);
        }
    }
    
    // Carregar contador quando página carregar
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Página carregada, iniciando carregamento do contador...');
        loadRejectedCount();
    });
</script>

</html>
