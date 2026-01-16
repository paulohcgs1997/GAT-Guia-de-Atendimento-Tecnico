// config-system.js - Configurações do Sistema

// Carregar configurações atuais
async function loadConfigurations() {
    try {
        const response = await fetch('../src/php/get_configuracoes.php');
        const data = await response.json();
        
        if (data.success) {
            const configs = data.configs;
            
            // Preencher campos de texto
            document.getElementById('system_name').value = configs.system_name || '';
            document.getElementById('system_description').value = configs.system_description || '';
            document.getElementById('system_email').value = configs.system_email || '';
            document.getElementById('system_phone').value = configs.system_phone || '';
            
            // Mostrar preview do logo
            if (configs.system_logo) {
                const logoImg = document.getElementById('logoImg');
                logoImg.src = configs.system_logo;
                logoImg.style.display = 'block';
                document.querySelector('#logoPreview .preview-placeholder').style.display = 'none';
            }
            
            // Mostrar preview do favicon
            if (configs.system_favicon) {
                const faviconImg = document.getElementById('faviconImg');
                faviconImg.src = configs.system_favicon;
                faviconImg.style.display = 'block';
                document.querySelector('#faviconPreview .preview-placeholder').style.display = 'none';
            }
        }
    } catch (error) {
        console.error('Erro ao carregar configurações:', error);
    }
}

// Preview de imagem ao selecionar arquivo
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('system_logo').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const logoImg = document.getElementById('logoImg');
                logoImg.src = event.target.result;
                logoImg.style.display = 'block';
                document.querySelector('#logoPreview .preview-placeholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });
    
    document.getElementById('system_favicon').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                const faviconImg = document.getElementById('faviconImg');
                faviconImg.src = event.target.result;
                faviconImg.style.display = 'block';
                document.querySelector('#faviconPreview .preview-placeholder').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Salvar configurações (Guia Info)
    document.getElementById('configForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'save_config');
        
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.display = 'flex';
        
        try {
            const response = await fetch('../src/php/crud_configuracoes.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('✅ ' + data.message);
                window.location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao salvar configurações:', error);
            alert('❌ Erro ao salvar configurações');
        } finally {
            loadingOverlay.style.display = 'none';
        }
    });
    
    // Salvar imagens (Guia Visual)
    document.getElementById('visualForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'save_config');
        
        // Adicionar campos do outro formulário
        formData.append('system_name', document.getElementById('system_name').value);
        formData.append('system_description', document.getElementById('system_description').value);
        formData.append('system_email', document.getElementById('system_email').value);
        formData.append('system_phone', document.getElementById('system_phone').value);
        
        const loadingOverlay = document.getElementById('loadingOverlay');
        loadingOverlay.style.display = 'flex';
        
        try {
            const response = await fetch('../src/php/crud_configuracoes.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('✅ ' + data.message);
                window.location.reload();
            } else {
                alert('❌ ' + data.message);
            }
        } catch (error) {
            console.error('Erro ao salvar imagens:', error);
            alert('❌ Erro ao salvar imagens');
        } finally {
            loadingOverlay.style.display = 'none';
        }
    });
    
    // Carregar configurações
    loadConfigurations();
});
