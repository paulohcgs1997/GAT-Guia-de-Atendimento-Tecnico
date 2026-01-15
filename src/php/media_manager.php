<?php
/**
 * Gerenciador de Mídias
 * Funções para gerenciar upload e exclusão de arquivos de mídia
 */

// Função para deletar arquivo de mídia do servidor
function deleteMediaFile($mediaPath) {
    if (empty($mediaPath)) {
        error_log("Media Manager - deleteMediaFile: Path vazio");
        return false;
    }
    
    error_log("Media Manager - deleteMediaFile: Path recebido: '$mediaPath'");
    
    // Converter path relativo para absoluto
    $basePath = __DIR__ . '/..';
    
    // Limpar o path - remover ../, ..\ e src/ do início
    $cleanPath = $mediaPath;
    $cleanPath = str_replace(['../', '..\\'], '', $cleanPath);
    $cleanPath = preg_replace('#^src/#', '', $cleanPath);
    
    error_log("Media Manager - deleteMediaFile: BasePath: '$basePath'");
    error_log("Media Manager - deleteMediaFile: CleanPath: '$cleanPath'");
    
    // Construir caminho completo
    $fullPath = $basePath . '/' . $cleanPath;
    $fullPath = str_replace('\\', '/', $fullPath); // Normalizar barras
    
    error_log("Media Manager - deleteMediaFile: FullPath: '$fullPath'");
    error_log("Media Manager - deleteMediaFile: File exists? " . (file_exists($fullPath) ? 'SIM' : 'NÃO'));
    
    // Verificar se o arquivo existe e deletar
    if (file_exists($fullPath) && is_file($fullPath)) {
        $deleted = unlink($fullPath);
        error_log("Media Manager - deleteMediaFile: Tentativa de deletar - " . ($deleted ? 'SUCESSO' : 'FALHA'));
        return $deleted;
    }
    
    error_log("Media Manager - deleteMediaFile: Arquivo não encontrado para deletar");
    return false;
}

// Função para deletar todas as mídias de um step
function deleteStepMedia($mysqli, $stepId) {
    error_log("Media Manager - deleteStepMedia: Iniciando para step $stepId");
    
    $stmt = $mysqli->prepare("SELECT src FROM steps WHERE id = ?");
    $stmt->bind_param('i', $stepId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($step = $result->fetch_assoc()) {
        error_log("Media Manager - deleteStepMedia: Step encontrado, src = '" . ($step['src'] ?? 'vazio') . "'");
        
        if (!empty($step['src'])) {
            $deleted = deleteMediaFile($step['src']);
            error_log("Media Manager - deleteStepMedia: Resultado da deleção = " . ($deleted ? 'SUCESSO' : 'FALHA'));
        } else {
            error_log("Media Manager - deleteStepMedia: Step não tem mídia para deletar");
        }
    } else {
        error_log("Media Manager - deleteStepMedia: Step $stepId não encontrado no banco");
    }
}

// Função para deletar todas as mídias de um tutorial
function deleteTutorialMedia($mysqli, $tutorialId) {
    error_log("Media Manager - deleteTutorialMedia: Iniciando para tutorial $tutorialId");
    
    // Buscar IDs dos steps do tutorial
    $stmt = $mysqli->prepare("SELECT id_step FROM blocos WHERE id = ? AND active = 1");
    $stmt->bind_param('i', $tutorialId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($tutorial = $result->fetch_assoc()) {
        error_log("Media Manager - deleteTutorialMedia: Tutorial encontrado, id_step = '" . ($tutorial['id_step'] ?? 'vazio') . "'");
        
        if (!empty($tutorial['id_step'])) {
            $stepIds = explode(',', $tutorial['id_step']);
            error_log("Media Manager - deleteTutorialMedia: " . count($stepIds) . " steps para processar");
            
            foreach ($stepIds as $stepId) {
                $stepId = intval(trim($stepId));
                if ($stepId > 0) {
                    error_log("Media Manager - deleteTutorialMedia: Processando step $stepId");
                    deleteStepMedia($mysqli, $stepId);
                }
            }
            
            error_log("Media Manager - deleteTutorialMedia: Concluído para tutorial $tutorialId");
        } else {
            error_log("Media Manager - deleteTutorialMedia: Tutorial não tem steps");
        }
    } else {
        error_log("Media Manager - deleteTutorialMedia: Tutorial $tutorialId não encontrado");
    }
}

// Função para limpar mídias órfãs (steps que foram deletados mas mídias permaneceram)
function cleanOrphanMedia($mysqli) {
    $uploadDir = __DIR__ . '/../uploads/';
    $cleaned = 0;
    
    if (!is_dir($uploadDir)) {
        return $cleaned;
    }
    
    // Buscar todos os arquivos no diretório de uploads
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($uploadDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if ($file->isFile()) {
            $filePath = $file->getPathname();
            $relativePath = str_replace(__DIR__ . '/../', '', $filePath);
            
            // Verificar se o arquivo está sendo usado em algum step
            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM steps WHERE src LIKE ?");
            $searchPath = '%' . basename($filePath) . '%';
            $stmt->bind_param('s', $searchPath);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            // Se não está sendo usado, deletar
            if ($result['count'] == 0) {
                if (unlink($filePath)) {
                    $cleaned++;
                    error_log("Media Manager - Arquivo órfão deletado: $relativePath");
                }
            }
        }
    }
    
    return $cleaned;
}

// Função para atualizar mídia de um step (deleta a antiga se houver)
function updateStepMedia($mysqli, $stepId, $newMediaPath) {
    error_log("Media Manager - updateStepMedia: Step $stepId, Nova mídia: '$newMediaPath'");
    
    // Buscar mídia antiga
    $stmt = $mysqli->prepare("SELECT src FROM steps WHERE id = ?");
    $stmt->bind_param('i', $stepId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($step = $result->fetch_assoc()) {
        $oldMedia = $step['src'];
        
        error_log("Media Manager - updateStepMedia: Mídia antiga: '" . ($oldMedia ?? 'vazio') . "'");
        
        // Se há uma mídia antiga e é diferente da nova, deletar a antiga
        if (!empty($oldMedia) && $oldMedia !== $newMediaPath) {
            error_log("Media Manager - updateStepMedia: Deletando mídia antiga...");
            $deleted = deleteMediaFile($oldMedia);
            error_log("Media Manager - updateStepMedia: Mídia antiga deletada: " . ($deleted ? 'SUCESSO' : 'FALHA'));
        } else if (empty($oldMedia)) {
            error_log("Media Manager - updateStepMedia: Não havia mídia antiga");
        } else {
            error_log("Media Manager - updateStepMedia: Mídia nova é igual à antiga, não deletar");
        }
    } else {
        error_log("Media Manager - updateStepMedia: Step $stepId não encontrado");
    }
}

// Função para validar extensão de arquivo
function isValidMediaExtension($filename) {
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'webm', 'ogg', 'avi', 'mov', 'pdf'];
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($extension, $allowedExtensions);
}

// Função para obter tamanho de arquivo formatado
function getFormattedFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Função para copiar arquivo de mídia (usado ao clonar)
function copyMediaFile($originalPath) {
    if (empty($originalPath)) {
        error_log("Media Manager - copyMediaFile: Path vazio recebido");
        return '';
    }
    
    error_log("Media Manager - copyMediaFile: Tentando copiar: $originalPath");
    
    // Converter path relativo para absoluto
    $basePath = __DIR__ . '/..';
    $cleanPath = str_replace(['../', '..\\'], '', $originalPath);
    
    // Remover src/ do início se existir
    $cleanPath = preg_replace('#^src/#', '', $cleanPath);
    
    $fullPath = realpath($basePath . '/' . $cleanPath);
    
    error_log("Media Manager - copyMediaFile: BasePath: $basePath");
    error_log("Media Manager - copyMediaFile: CleanPath: $cleanPath");
    error_log("Media Manager - copyMediaFile: FullPath: $fullPath");
    
    // Verificar se o arquivo existe
    if (!$fullPath || !file_exists($fullPath) || !is_file($fullPath)) {
        error_log("Media Manager - Arquivo original não encontrado para copiar: $fullPath (original: $originalPath)");
        return $originalPath; // Retorna path original se não conseguir copiar
    }
    
    // Gerar novo nome de arquivo
    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
    $newFileName = 'clone_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
    $uploadDir = $basePath . '/uploads/';
    
    // Criar diretório se não existir
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
        error_log("Media Manager - Diretório de uploads criado: $uploadDir");
    }
    
    $newFullPath = $uploadDir . $newFileName;
    
    error_log("Media Manager - copyMediaFile: Tentando copiar de '$fullPath' para '$newFullPath'");
    
    // Copiar arquivo
    if (copy($fullPath, $newFullPath)) {
        $newRelativePath = 'src/uploads/' . $newFileName;
        error_log("Media Manager - Mídia copiada com SUCESSO: $originalPath -> $newRelativePath");
        return $newRelativePath;
    }
    
    error_log("Media Manager - Falha ao copiar mídia: $fullPath");
    return $originalPath; // Retorna path original se falhar
}

// Função para deletar mídias de um clone rejeitado
function deleteCloneMedia($mysqli, $cloneId) {
    // Buscar steps do clone
    $stmt = $mysqli->prepare("SELECT id_step FROM blocos WHERE id = ? AND is_clone = 1 AND active = 1");
    $stmt->bind_param('i', $cloneId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($clone = $result->fetch_assoc()) {
        if (!empty($clone['id_step'])) {
            $stepIds = explode(',', $clone['id_step']);
            
            foreach ($stepIds as $stepId) {
                $stepId = intval(trim($stepId));
                if ($stepId > 0) {
                    // Deletar mídia e step do clone
                    deleteStepMedia($mysqli, $stepId);
                    
                    // Deletar questions associadas
                    $qStmt = $mysqli->prepare("SELECT questions FROM steps WHERE id = ?");
                    $qStmt->bind_param('i', $stepId);
                    $qStmt->execute();
                    $qResult = $qStmt->get_result();
                    $stepData = $qResult->fetch_assoc();
                    
                    if (!empty($stepData['questions'])) {
                        $questionIds = explode(',', $stepData['questions']);
                        foreach ($questionIds as $qId) {
                            $qId = intval(trim($qId));
                            if ($qId > 0) {
                                $delQ = $mysqli->prepare("DELETE FROM questions WHERE id = ?");
                                $delQ->bind_param('i', $qId);
                                $delQ->execute();
                            }
                        }
                    }
                    
                    // Deletar step
                    $delStep = $mysqli->prepare("DELETE FROM steps WHERE id = ?");
                    $delStep->bind_param('i', $stepId);
                    $delStep->execute();
                }
            }
            
            error_log("Media Manager - Todas as mídias e dados do clone $cloneId foram deletados");
        }
    }
}

// Função para substituir tutorial original por clone aprovado
function replaceOriginalWithClone($mysqli, $cloneId, $originalId) {
    // Deletar mídias e steps do original antes de substituir
    deleteTutorialMedia($mysqli, $originalId);
    
    // Buscar steps do original para deletá-los completamente
    $stmt = $mysqli->prepare("SELECT id_step FROM blocos WHERE id = ?");
    $stmt->bind_param('i', $originalId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($original = $result->fetch_assoc()) {
        if (!empty($original['id_step'])) {
            $stepIds = explode(',', $original['id_step']);
            
            foreach ($stepIds as $stepId) {
                $stepId = intval(trim($stepId));
                if ($stepId > 0) {
                    // Deletar questions do step original
                    $qStmt = $mysqli->prepare("SELECT questions FROM steps WHERE id = ?");
                    $qStmt->bind_param('i', $stepId);
                    $qStmt->execute();
                    $qResult = $qStmt->get_result();
                    $stepData = $qResult->fetch_assoc();
                    
                    if (!empty($stepData['questions'])) {
                        $questionIds = explode(',', $stepData['questions']);
                        foreach ($questionIds as $qId) {
                            $qId = intval(trim($qId));
                            if ($qId > 0) {
                                $delQ = $mysqli->prepare("DELETE FROM questions WHERE id = ?");
                                $delQ->bind_param('i', $qId);
                                $delQ->execute();
                            }
                        }
                    }
                    
                    // Deletar step original
                    $delStep = $mysqli->prepare("DELETE FROM steps WHERE id = ?");
                    $delStep->bind_param('i', $stepId);
                    $delStep->execute();
                }
            }
        }
    }
    
    error_log("Media Manager - Original $originalId substituído por clone $cloneId");
}
