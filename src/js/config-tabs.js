// config-tabs.js - Sistema de Guias/Tabs

function initTabs() {
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetTab = button.getAttribute('data-tab');
            
            // Remove active de todos os botões e conteúdos
            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Adiciona active no botão clicado e no conteúdo correspondente
            button.classList.add('active');
            const targetContent = document.getElementById(`tab-${targetTab}`);
            if (targetContent) {
                targetContent.classList.add('active');
            }
            
            // Salvar guia ativa no localStorage
            localStorage.setItem('activeConfigTab', targetTab);
        });
    });
    
    // Restaurar guia ativa do localStorage
    const savedTab = localStorage.getItem('activeConfigTab');
    if (savedTab) {
        const savedButton = document.querySelector(`[data-tab="${savedTab}"]`);
        if (savedButton) {
            savedButton.click();
        }
    }
}

// Inicializar tabs ao carregar
document.addEventListener('DOMContentLoaded', initTabs);
