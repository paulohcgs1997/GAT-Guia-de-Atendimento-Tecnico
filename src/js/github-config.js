// github-config.js - Configuração do Repositório GitHub

// Função para extrair owner/repo de qualquer formato de URL do GitHub
function parseGithubUrl(url) {
    if (!url) return null;
    
    // Remover espaços
    url = url.trim();
    
    // Padrões suportados:
    // https://github.com/owner/repo
    // https://github.com/owner/repo.git
    // git@github.com:owner/repo.git
    // github.com/owner/repo
    
    const patterns = [
        /github\.com[\/:]([^\/]+)\/([^\/\s\.]+)/i,  // Padrão geral
        /^([^\/]+)\/([^\/\s\.]+)$/  // Apenas owner/repo
    ];
    
    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match) {
            return {
                owner: match[1],
                repo: match[2].replace(/\.git$/, '')
            };
        }
    }
    
    return null;
}

// Mostrar seção de configuração
function showGithubConfig() {
    const section = document.getElementById('githubConfigSection');
    if (section) {
        section.style.display = 'block';
        loadCurrentGithubConfig();
    } else {
        console.error('Elemento githubConfigSection não encontrado');
    }
}

// Ocultar seção de configuração
function hideGithubConfig() {
    const section = document.getElementById('githubConfigSection');
    if (section) {
        section.style.display = 'none';
    }
}

// Carregar configuração atual do GitHub
async function loadCurrentGithubConfig() {
    try {
        const response = await fetch('../src/php/get_github_config.php');
        const result = await response.json();
        
        if (result.success && result.config) {
            const owner = result.config.owner || '';
            const repo = result.config.repo || '';
            
            if (owner && repo) {
                const urlInput = document.getElementById('github_url');
                if (urlInput) {
                    urlInput.value = `https://github.com/${owner}/${repo}`;
                    
                    // Triggerar evento para mostrar detecção
                    urlInput.dispatchEvent(new Event('input'));
                }
            }
        }
    } catch (error) {
        console.error('Erro ao carregar configuração:', error);
    }
}

// Salvar configuração do GitHub
async function saveGithubConfig(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    const urlInput = document.getElementById('github_url');
    const url = urlInput.value.trim();
    
    // Validar URL antes de enviar
    const parsed = parseGithubUrl(url);
    if (!parsed) {
        alert('❌ URL do GitHub inválida!\n\nUse o formato:\nhttps://github.com/usuario/repositorio');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Testando repositório...';
    
    const formData = new FormData();
    formData.append('github_url', url);
    
    try {
        const response = await fetch('../src/php/save_github_config.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Repositório configurado com sucesso!\n\n📦 ${result.repository}\n\nAgora você pode verificar atualizações.`);
            hideGithubConfig();
            
            // Verificar atualizações automaticamente
            setTimeout(() => checkSystemUpdates(), 500);
        } else {
            alert('❌ Erro ao salvar: ' + result.message);
        }
    } catch (error) {
        console.error('Erro:', error);
        alert('❌ Erro ao salvar configuração do GitHub');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
}

// Detectar informações da URL automaticamente
document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('github_url');
    const detectionResult = document.getElementById('urlDetectionResult');
    const detectedInfo = document.getElementById('detectedInfo');
    
    if (urlInput && detectionResult && detectedInfo) {
        urlInput.addEventListener('input', function() {
            const url = this.value.trim();
            const parsed = parseGithubUrl(url);
            
            if (parsed) {
                detectionResult.style.display = 'block';
                detectedInfo.parentElement.className = 'alert alert-success';
                detectedInfo.innerHTML = `
                    <strong>Proprietário:</strong> ${parsed.owner}<br>
                    <strong>Repositório:</strong> ${parsed.repo}<br>
                    <strong>URL:</strong> <a href="https://github.com/${parsed.owner}/${parsed.repo}" target="_blank">
                        https://github.com/${parsed.owner}/${parsed.repo}
                    </a>
                `;
            } else if (url.length > 10) {
                detectionResult.style.display = 'block';
                detectedInfo.parentElement.className = 'alert alert-warning';
                detectedInfo.innerHTML = '<strong>⚠️ URL inválida.</strong> Use o formato: https://github.com/usuario/repositorio';
            } else {
                detectionResult.style.display = 'none';
            }
        });
    }
});
