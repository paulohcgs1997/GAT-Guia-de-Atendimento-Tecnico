<?php
session_start();
require_once(__DIR__ . '/../../viwer/includes.php');
check_permission_gestor();
require_once(__DIR__ . '/media_manager.php'); // Gerenciador de mídias

header('Content-Type: application/json');

// Diretório de upload
$uploadDir = __DIR__ . '/../uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Verificar se há arquivo
if (!isset($_FILES['mediaFile']) || $_FILES['mediaFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Nenhum arquivo enviado ou erro no upload']);
    exit;
}

$file = $_FILES['mediaFile'];
$fileName = $file['name'];
$fileTmpName = $file['tmp_name'];
$fileSize = $file['size'];
$fileError = $file['error'];

// Obter extensão do arquivo
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Extensões permitidas
$allowedImages = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$allowedVideos = ['mp4', 'webm', 'ogg', 'mov'];
$allowed = array_merge($allowedImages, $allowedVideos);

if (!in_array($fileExt, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Tipo de arquivo não permitido. Use: ' . implode(', ', $allowed)]);
    exit;
}

// Verificar tamanho (máximo 50MB)
$maxSize = 50 * 1024 * 1024; // 50MB
if ($fileSize > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Arquivo muito grande. Tamanho máximo: 50MB']);
    exit;
}

// Gerar nome único para o arquivo
$newFileName = uniqid('media_', true) . '.' . $fileExt;
$uploadPath = $uploadDir . $newFileName;

// Mover arquivo
if (move_uploaded_file($fileTmpName, $uploadPath)) {
    // Retornar URL relativa
    $fileUrl = '../src/uploads/' . $newFileName;
    
    echo json_encode([
        'success' => true,
        'message' => 'Upload realizado com sucesso',
        'url' => $fileUrl,
        'filename' => $newFileName,
        'type' => in_array($fileExt, $allowedImages) ? 'image' : 'video'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar arquivo']);
}
?>
