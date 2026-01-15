<?php
session_start();
require_once __DIR__ . '/../config/conexao.php';

header('Content-Type: application/json');

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// GET - Listar departamentos
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $action == 'list') {
    $sql = "SELECT * FROM departaments ORDER BY name ASC";
    $result = $mysqli->query($sql);
    
    $departamentos = [];
    while ($row = $result->fetch_assoc()) {
        // Mapear para nomes mais amigáveis no frontend
        $departamentos[] = [
            'id' => $row['id'],
            'nome' => $row['name'],
            'site' => $row['url'],
            'logo' => $row['src']
        ];
    }
    
    echo json_encode(['success' => true, 'data' => $departamentos]);
    exit;
}

// GET - Buscar departamento específico
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $action == 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $sql = "SELECT * FROM departaments WHERE id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $departamento = $result->fetch_assoc();
        echo json_encode(['success' => true, 'data' => $departamento]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Departamento não encontrado']);
    }
    exit;
}

// POST - Criar, Editar ou Deletar
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // DELETE
    if ($action == 'delete') {
        $id = intval($_POST['id']);
        
        // Buscar logo para deletar o arquivo
        $sql = "SELECT src FROM departaments WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $dept = $result->fetch_assoc();
            
            // Deletar arquivo de logo se existir
            if (!empty($dept['src'])) {
                $logo_path = '../../' . $dept['src'];
                if (file_exists($logo_path)) {
                    unlink($logo_path);
                }
            }
            
            // Deletar registro
            $sql = "DELETE FROM departaments WHERE id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Departamento excluído com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao excluir departamento']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Departamento não encontrado']);
        }
        exit;
    }
    
    // Validação
    $nome = trim($_POST['nome'] ?? '');
    $site = trim($_POST['site'] ?? '');
    
    // Adicionar https:// se não houver protocolo
    if (!empty($site) && !preg_match('/^https?:\/\//i', $site)) {
        $site = 'https://' . $site;
    }
    
    if (empty($nome)) {
        echo json_encode(['success' => false, 'message' => 'Nome é obrigatório']);
        exit;
    }
    
    // EDIT
    if ($action == 'edit') {
        $id = intval($_POST['id']);
        
        // Buscar departamento atual
        $sql = "SELECT src FROM departaments WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Departamento não encontrado']);
            exit;
        }
        
        $dept_atual = $result->fetch_assoc();
        $logo_path = $dept_atual['src'];
        
        // Processar upload de nova logo se houver
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../../src/uploads/departamentos/';
            
            // Criar diretório se não existir
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Deletar logo antiga se existir
            if (!empty($logo_path)) {
                $old_logo = '../../' . $logo_path;
                if (file_exists($old_logo)) {
                    unlink($old_logo);
                }
            }
            
            // Upload nova logo
            $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Formato de imagem inválido']);
                exit;
            }
            
            $new_filename = 'dept_' . $id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = 'src/uploads/departamentos/' . $new_filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da logo']);
                exit;
            }
        }
        
        // Atualizar departamento
        $sql = "UPDATE departaments SET name = ?, url = ?, src = ? WHERE id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sssi', $nome, $site, $logo_path, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Departamento atualizado com sucesso']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar departamento']);
        }
        exit;
    }
    
    // CREATE
    if ($action == 'create') {
        $logo_path = '';
        
        // Processar upload de logo
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = '../../src/uploads/departamentos/';
            
            // Criar diretório se não existir
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                echo json_encode(['success' => false, 'message' => 'Formato de imagem inválido. Use JPG, PNG, GIF, SVG ou WebP']);
                exit;
            }
            
            $new_filename = 'dept_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_path)) {
                $logo_path = 'src/uploads/departamentos/' . $new_filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao fazer upload da logo']);
                exit;
            }
        }
        
        // Inserir departamento
        $sql = "INSERT INTO departaments (name, url, src) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('sss', $nome, $site, $logo_path);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Departamento criado com sucesso', 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar departamento']);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);
?>
