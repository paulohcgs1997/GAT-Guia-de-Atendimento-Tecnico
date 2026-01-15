<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_login();
check_permission_admin(); // Apenas admin pode alterar configurações

include_once(__DIR__ . '/../config/conexao.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_config') {
        try {
            $mysqli->begin_transaction();
            
            // Configurações de texto
            $textConfigs = ['system_name', 'system_description', 'system_email', 'system_phone'];
            foreach ($textConfigs as $key) {
                if (isset($_POST[$key])) {
                    $value = trim($_POST[$key]);
                    $sql = "INSERT INTO system_config (config_key, config_value, config_type) VALUES (?, ?, 'text')
                            ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('sss', $key, $value, $value);
                    $stmt->execute();
                }
            }
            
            // Upload de logo
            if (isset($_FILES['system_logo']) && $_FILES['system_logo']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/config/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $ext = pathinfo($_FILES['system_logo']['name'], PATHINFO_EXTENSION);
                $filename = 'logo_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['system_logo']['tmp_name'], $targetPath)) {
                    $relativePath = '../src/uploads/config/' . $filename;
                    
                    // Deletar logo antigo
                    $oldLogo = $mysqli->query("SELECT config_value FROM system_config WHERE config_key = 'system_logo'")->fetch_assoc();
                    if ($oldLogo && !empty($oldLogo['config_value'])) {
                        $oldPath = __DIR__ . '/../uploads/config/' . basename($oldLogo['config_value']);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    
                    $sql = "INSERT INTO system_config (config_key, config_value, config_type) VALUES ('system_logo', ?, 'image')
                            ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('ss', $relativePath, $relativePath);
                    $stmt->execute();
                }
            }
            
            // Upload de favicon
            if (isset($_FILES['system_favicon']) && $_FILES['system_favicon']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../uploads/config/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $ext = pathinfo($_FILES['system_favicon']['name'], PATHINFO_EXTENSION);
                $filename = 'favicon_' . time() . '.' . $ext;
                $targetPath = $uploadDir . $filename;
                
                if (move_uploaded_file($_FILES['system_favicon']['tmp_name'], $targetPath)) {
                    $relativePath = '../src/uploads/config/' . $filename;
                    
                    // Deletar favicon antigo
                    $oldFavicon = $mysqli->query("SELECT config_value FROM system_config WHERE config_key = 'system_favicon'")->fetch_assoc();
                    if ($oldFavicon && !empty($oldFavicon['config_value'])) {
                        $oldPath = __DIR__ . '/../uploads/config/' . basename($oldFavicon['config_value']);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    
                    $sql = "INSERT INTO system_config (config_key, config_value, config_type) VALUES ('system_favicon', ?, 'image')
                            ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param('ss', $relativePath, $relativePath);
                    $stmt->execute();
                }
            }
            
            $mysqli->commit();
            echo json_encode(['success' => true, 'message' => 'Configurações salvas com sucesso!']);
            
        } catch (Exception $e) {
            $mysqli->rollback();
            echo json_encode(['success' => false, 'message' => 'Erro ao salvar configurações: ' . $e->getMessage()]);
        }
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Ação inválida']);
