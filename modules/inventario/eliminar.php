<?php
/**
 * CEMANBLIND - Eliminar Producto (Soft Delete)
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMINISTRADOR);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if ($id) {
    $stmt = $db->prepare("SELECT nombre FROM productos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $prod = $stmt->fetch();

    if ($prod) {
        $db->prepare("UPDATE productos SET activo = 0, updated_at = " . dbNow() . " WHERE id = :id")->execute([':id' => $id]);
        logAudit('DELETE', 'productos', $id, ['nombre' => $prod['nombre']]);
        setFlash('success', "Producto '{$prod['nombre']}' dado de baja.");
    }
}

redirect(BASE_URL . '/modules/inventario/index.php');
