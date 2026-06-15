<?php
/**
 * CEMABLN - Cierre de Sesión
 */
require_once __DIR__ . '/includes/auth.php';
logoutUser();
header('Location: ' . BASE_URL . '/index.php');
exit;
