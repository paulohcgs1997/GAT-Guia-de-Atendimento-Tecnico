// Sistema de Gerenciamento de Backups

// Carregar lista de backups quando a aba é ativada
document.addEventListener('DOMContentLoaded', function() {
    const backupsTab = document.getElementById('backups-tab');
    if (backupsTab) {
        backupsTab.addEventListener('shown.bs.tab', function() {
            console.log('📦 Aba de Backups ativada');
            loadBackupsList();
        });
    }
});

// Carregar lista de backups
async function loadBackupsList() {
    const listDiv = document.getElementById('backupsList');
    
    listDiv.innerHTML = `
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <div class="spinner-border text-primary me-3" role="status"></div>
            <div>Carregando backups disponíveis...</div>
        </div>
    `;
    
    try {
        const response = await fetch('../src/php/backup_manager.php?action=list');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erro ao carregar backups');
        }
        
        if (data.backups.length === 0) {
            listDiv.innerHTML = `
                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 24px;"></i>
                    <div>
                        <strong>Nenhum backup encontrado</strong>
                        <p class="mb-0 mt-1">Crie um backup manual ou aguarde uma atualização do sistema.</p>
                    </div>
                </div>
            `;
            return;
        }
        
        let html = '<div class="table-responsive"><table class="table table-hover">';
        html += `
            <thead style="background: #f59e0b; color: white;">
                <tr>
                    <th><i class="bi bi-calendar"></i> Data e Hora</th>
                    <th><i class="bi bi-file-earmark-zip"></i> Arquivo</th>
                    <th><i class="bi bi-hdd"></i> Tamanho</th>
                    <th><i class="bi bi-tag"></i> Tipo</th>
                    <th style="text-align: center;"><i class="bi bi-tools"></i> Ações</th>
                </tr>
            </thead>
            <tbody>
        `;
        
        data.backups.forEach((backup, index) => {
            const isNewest = index === 0;
            const typeLabel = backup.type === 'auto' ? 
                '<span class="badge bg-primary">Automático</span>' : 
                '<span class="badge bg-warning">Manual</span>';
            
            html += `
                <tr ${isNewest ? 'style="background: #fef3c7;"' : ''}>
                    <td>
                        ${isNewest ? '<i class="bi bi-star-fill text-warning"></i> ' : ''}
                        <strong>${backup.date}</strong>
                    </td>
                    <td><code>${backup.filename}</code></td>
                    <td>${backup.size}</td>
                    <td>${typeLabel}</td>
                    <td style="text-align: center;">
                        <button class="btn btn-sm btn-success" onclick="restoreBackup('${backup.filename}')" title="Restaurar este backup">
                            <i class="bi bi-arrow-counterclockwise"></i> Restaurar
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBackup('${backup.filename}')" title="Excluir backup">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += '</tbody></table></div>';
        
        if (data.backups.length >= 3) {
            html += `
                <div class="alert alert-info mt-3" style="background: #dbeafe; border-left: 4px solid #3b82f6;">
                    <i class="bi bi-info-circle"></i> 
                    <strong>Limite atingido:</strong> O sistema mantém apenas os 3 backups mais recentes. 
                    Ao criar um novo backup, o mais antigo será excluído automaticamente.
                </div>
            `;
        }
        
        listDiv.innerHTML = html;
        
    } catch (error) {
        console.error('Erro ao carregar backups:', error);
        listDiv.innerHTML = `
            <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-x-circle-fill me-2" style="font-size: 24px;"></i>
                <div>
                    <strong>Erro ao carregar backups</strong>
                    <p class="mb-0 mt-1">${error.message}</p>
                </div>
            </div>
        `;
    }
}

// Criar backup manual
async function createManualBackup() {
    if (!confirm('Deseja criar um backup manual do sistema atual?\n\nEste processo pode levar alguns minutos.')) {
        return;
    }
    
    const listDiv = document.getElementById('backupsList');
    const originalContent = listDiv.innerHTML;
    
    listDiv.innerHTML = `
        <div class="alert alert-info d-flex align-items-center" role="alert">
            <div class="spinner-border text-primary me-3" role="status"></div>
            <div>
                <strong>Criando backup...</strong>
                <p class="mb-0 mt-2">Aguarde, isso pode levar alguns minutos.</p>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch('../src/php/backup_manager.php?action=create', {
            method: 'POST'
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erro ao criar backup');
        }
        
        alert('✅ Backup criado com sucesso!\n\nArquivo: ' + data.filename);
        loadBackupsList();
        
    } catch (error) {
        console.error('Erro ao criar backup:', error);
        listDiv.innerHTML = originalContent;
        alert('❌ Erro ao criar backup:\n' + error.message);
    }
}

// Restaurar backup
async function restoreBackup(filename) {
    const confirmText = `⚠️ ATENÇÃO: Esta ação substituirá todos os arquivos do sistema!\n\nDeseja realmente restaurar o backup:\n${filename}\n\nUm backup do sistema atual será criado automaticamente antes da restauração.\n\nDigite "RESTAURAR" para confirmar:`;
    const userInput = prompt(confirmText);
    
    if (userInput !== 'RESTAURAR') {
        if (userInput !== null) {
            alert('❌ Restauração cancelada. Você deve digitar exatamente "RESTAURAR" para confirmar.');
        }
        return;
    }
    
    const listDiv = document.getElementById('backupsList');
    const originalContent = listDiv.innerHTML;
    
    listDiv.innerHTML = `
        <div class="alert alert-warning d-flex align-items-center" role="alert">
            <div class="spinner-border text-warning me-3" role="status"></div>
            <div>
                <strong>Restaurando backup...</strong>
                <p class="mb-0 mt-2">Aguarde, não feche esta página!</p>
                <div class="progress mt-2" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" style="width: 100%"></div>
                </div>
            </div>
        </div>
    `;
    
    try {
        const response = await fetch('../src/php/backup_manager.php?action=restore', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'filename=' + encodeURIComponent(filename)
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erro ao restaurar backup');
        }
        
        alert('✅ Backup restaurado com sucesso!\n\nA página será recarregada.');
        window.location.reload();
        
    } catch (error) {
        console.error('Erro ao restaurar backup:', error);
        listDiv.innerHTML = originalContent;
        alert('❌ Erro ao restaurar backup:\n' + error.message);
    }
}

// Excluir backup
async function deleteBackup(filename) {
    if (!confirm(`Deseja realmente excluir o backup:\n${filename}\n\nEsta ação não pode ser desfeita!`)) {
        return;
    }
    
    try {
        const response = await fetch('../src/php/backup_manager.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'filename=' + encodeURIComponent(filename)
        });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Erro ao excluir backup');
        }
        
        alert('✅ Backup excluído com sucesso!');
        loadBackupsList();
        
    } catch (error) {
        console.error('Erro ao excluir backup:', error);
        alert('❌ Erro ao excluir backup:\n' + error.message);
    }
}
