<?php
// Menu Rápido - Incluir em todas as páginas de gestão
?>
<!-- Menu Rápido -->
<div class="quick-menu">
    <div class="quick-menu-container">
        <?php if ($_SESSION['perfil'] == '1' || $_SESSION['perfil'] == '2'): ?>
        <a href="gestao_services.php" class="quick-menu-item">
            <span class="quick-icon">🔍</span>
            <span>Serviços</span>
        </a>
        <a href="gestao_blocos.php" class="quick-menu-item">
            <span class="quick-icon">📚</span>
            <span>Tutoriais</span>
        </a>
        <?php endif; ?>
        <?php if ($_SESSION['perfil'] == '1'): ?>
        <a href="gestao_users.php" class="quick-menu-item">
            <span class="quick-icon">👥</span>
            <span>Usuários</span>
        </a>
        <?php endif; ?>
        <?php if ($_SESSION['perfil'] == '1' || $_SESSION['perfil'] == '3'): ?>
        <a href="aprovacoes.php" class="quick-menu-item">
            <span class="quick-icon">✅</span>
            <span>Aprovações</span>
        </a>
        <?php endif; ?>
        <?php if ($_SESSION['perfil'] == '1' || $_SESSION['perfil'] == '2'): ?>
        <a href="gestao_reprovados.php" class="quick-menu-item" id="quickReprovados">
            <span class="quick-icon">📛</span>
            <span>Reprovados</span>
            <span class="quick-badge" id="quickBadge" style="display: none;">0</span>
        </a>
        <?php endif; ?>
        <?php if ($_SESSION['perfil'] == '1'): ?>
        <a href="gestao_departamentos.php" class="quick-menu-item">
            <span class="quick-icon">🏢</span>
            <span>Departamentos</span>
        </a>
        <a href="gestao_configuracoes.php" class="quick-menu-item">
            <span class="quick-icon">⚙️</span>
            <span>Configurações</span>
        </a>
        <?php endif; ?>
    </div>
</div>

<script>
// Carregar contador de itens reprovados para o menu rápido
async function loadQuickMenuBadge() {
    try {
        const response = await fetch('../src/php/get_rejected_items.php');
        const data = await response.json();
        
        if (data.success && data.total > 0) {
            const quickBadge = document.getElementById('quickBadge');
            if (quickBadge) {
                quickBadge.textContent = data.total;
                quickBadge.style.display = 'inline-block';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar badge do menu rápido:', error);
    }
}

// Marcar item ativo no menu
document.addEventListener('DOMContentLoaded', function() {
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.quick-menu-item').forEach(item => {
        if (item.getAttribute('href') === currentPage) {
            item.classList.add('active');
        }
    });
    
    // Carregar badge
    loadQuickMenuBadge();
});
</script>
