<?php
// Helper para incluir no <head> de todas as páginas
require_once(__DIR__ . '/../config/conexao.php');

function getSystemConfig($key, $default = '') {
    global $mysqli;
    
    $sql = "SELECT config_value FROM system_config WHERE config_key = ?";
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) return $default;
    
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return !empty($row['config_value']) ? $row['config_value'] : $default;
    }
    
    return $default;
}

$system_name = getSystemConfig('system_name', 'Sistema de Gestão');
$system_favicon = getSystemConfig('system_favicon', '');
?>
<title><?php echo htmlspecialchars($system_name); ?></title>
<?php if (!empty($system_favicon)): ?>
<link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($system_favicon); ?>">
<?php endif; ?>
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<!-- Bootstrap 5 JS Bundle (inclui Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
