<?php
// Arquivo temporário para debug de sessão
session_start();
header('Content-Type: application/json');

echo json_encode([
    'session_data' => $_SESSION,
    'session_id' => session_id(),
    'cookie_params' => session_get_cookie_params()
], JSON_PRETTY_PRINT);
