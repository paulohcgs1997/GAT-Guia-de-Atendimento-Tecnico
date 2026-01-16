<?php
include_once("includes.php");
check_login();
check_permission_creator();
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
    
    <?php include_once __DIR__ . '/includes/quick_menu.php'; ?>
    
    <main>
        <div class="page-header">
            <h1>Gestão de Departamentos</h1>
            <p>Gerencie os departamentos da empresa</p>
        </div>
        
        <div class="departamentos-grid" id="departamentosGrid">
            <!-- Departamentos serão carregados aqui -->
        </div>
        
        <button class="add-button" onclick="openCreateModal()" title="Novo Departamento">+</button>
    </main>
    
    <!-- Modal Criar/Editar -->
    <div id="modalDepartamento" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Novo Departamento</h2>
            
            <form id="formDepartamento" enctype="multipart/form-data">
                <input type="hidden" id="departamentoId" name="id">
                <input type="hidden" id="action" name="action">
                
                <div class="modal-form-group">
                    <label for="nome">Nome do Departamento *</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="modal-form-group">
                    <label for="site">Site do Departamento</label>
                    <input type="text" id="site" name="site" placeholder="exemplo.com ou https://exemplo.com">
                </div>
                
                <div class="modal-form-group">
                    <label for="logo">Logo do Departamento</label>
                    <input type="file" id="logo" name="logo" accept="image/*" onchange="previewLogo(event)">
                    <small>Formatos aceitos: JPG, PNG, GIF, SVG, WebP</small>
                    <div id="logoPreviewContainer"></div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" onclick="closeModal()">Cancelar</button>
                    <button type="submit">Salvar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let departamentos = [];
        
        // Carregar departamentos
        async function loadDepartamentos() {
            try {
                const response = await fetch('../src/php/crud_departamentos.php?action=list');
                const text = await response.text();
                console.log('Response:', text);
                
                const data = JSON.parse(text);
                
                if (data.success) {
                    departamentos = data.data;
                    renderDepartamentos();
                } else {
                    console.error('Erro na resposta:', data);
                    alert('Erro ao carregar departamentos: ' + (data.message || 'Erro desconhecido'));
                }
            } catch (error) {
                console.error('Erro completo:', error);
                alert('Erro ao carregar departamentos: ' + error.message);
            }
        }
        
        // Renderizar departamentos
        function renderDepartamentos() {
            const grid = document.getElementById('departamentosGrid');
            
            if (departamentos.length === 0) {
                grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; color: #666;">Nenhum departamento cadastrado</p>';
                return;
            }
            
            grid.innerHTML = departamentos.map(dept => `
                <div class="departamento-card">
                    ${dept.logo ? 
                        `<img src="../${dept.logo}" alt="${dept.nome}" class="departamento-logo">` : 
                        `<div class="no-logo">Sem logo</div>`
                    }
                    <div class="departamento-info">
                        <h3>${dept.nome}</h3>
                        ${dept.site ? 
                            `<a href="${dept.site}" target="_blank" class="departamento-site">${dept.site}</a>` : 
                            '<span style="color: #999;">Sem site cadastrado</span>'
                        }
                    </div>
                    <div class="departamento-actions">
                        <button class="btn-edit" onclick="editDepartamento(${dept.id})">Editar</button>
                        <button class="btn-delete" onclick="deleteDepartamento(${dept.id}, '${dept.nome}')">Excluir</button>
                    </div>
                </div>
            `).join('');
        }
        
        // Abrir modal criar
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Novo Departamento';
            document.getElementById('action').value = 'create';
            document.getElementById('formDepartamento').reset();
            document.getElementById('departamentoId').value = '';
            document.getElementById('logoPreviewContainer').innerHTML = '';
            document.getElementById('modalDepartamento').style.display = 'flex';
        }
        
        // Editar departamento
        async function editDepartamento(id) {
            const dept = departamentos.find(d => d.id == id);
            if (!dept) return;
            
            document.getElementById('modalTitle').textContent = 'Editar Departamento';
            document.getElementById('action').value = 'edit';
            document.getElementById('departamentoId').value = dept.id;
            document.getElementById('nome').value = dept.nome;
            
            // Remover https:// ou http:// do site ao editar
            let siteValue = dept.site || '';
            if (siteValue) {
                siteValue = siteValue.replace(/^https?:\/\//i, '');
            }
            document.getElementById('site').value = siteValue;
            
            // Mostrar logo atual
            const previewContainer = document.getElementById('logoPreviewContainer');
            if (dept.logo) {
                previewContainer.innerHTML = `
                    <p style="margin-top: 10px;">Logo atual:</p>
                    <img src="../${dept.logo}" class="current-logo">
                    <p style="font-size: 12px; color: #666;">Selecione uma nova imagem para substituir</p>
                `;
            } else {
                previewContainer.innerHTML = '';
            }
            
            document.getElementById('modalDepartamento').style.display = 'flex';
        }
        
        // Deletar departamento
        async function deleteDepartamento(id, nome) {
            if (!confirm(`Tem certeza que deseja excluir o departamento "${nome}"?`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                
                const response = await fetch('../src/php/crud_departamentos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Departamento excluído com sucesso');
                    loadDepartamentos();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir departamento');
            }
        }
        
        // Preview da logo
        function previewLogo(event) {
            const file = event.target.files[0];
            const container = document.getElementById('logoPreviewContainer');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    container.innerHTML = `
                        <p style="margin-top: 10px;">Preview:</p>
                        <img src="${e.target.result}" class="logo-preview">
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                container.innerHTML = '';
            }
        }
        
        // Fechar modal
        function closeModal() {
            document.getElementById('modalDepartamento').style.display = 'none';
        }
        
        // Submit do formulário
        document.getElementById('formDepartamento').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('../src/php/crud_departamentos.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message);
                    closeModal();
                    loadDepartamentos();
                } else {
                    alert('Erro: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar departamento');
            }
        });
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            const modal = document.getElementById('modalDepartamento');
            if (event.target == modal) {
                closeModal();
            }
        };
        
        // Carregar ao iniciar
        loadDepartamentos();
    </script>
</body>
</html>
