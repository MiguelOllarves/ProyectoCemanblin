<?php
/**
 * CEMANBLIND - Exportar Inventario a CSV
 */
require_once __DIR__ . '/../../includes/auth.php';

requireRole(ROLE_SUPERVISOR);

$db = getDB();
$estado = $_GET['estado'] ?? 'todos';

$where = "p.activo = 1";
if ($estado === 'bajo_stock') {
    $where .= " AND p.stock_actual <= p.stock_minimo";
} elseif ($estado === 'por_vencer') {
    $fechaLimite = date('Y-m-d', strtotime('+' . EXPIRY_ALERT_DAYS . ' days'));
    $where .= " AND p.fecha_vencimiento IS NOT NULL AND p.fecha_vencimiento <= '{$fechaLimite}'";
}

$stmt = $db->prepare("
    SELECT p.codigo, p.nombre, p.numero_parte, c.nombre AS categoria, 
           p.stock_actual, p.stock_minimo, p.unidad_medida, 
           p.ubicacion_almacen, p.precio_unitario, p.fecha_vencimiento
    FROM productos p 
    LEFT JOIN categorias c ON p.categoria_id = c.id 
    WHERE $where 
    ORDER BY p.nombre ASC
");
$stmt->execute();
$productos = $stmt->fetchAll();

// Log audit
logAudit('REPORT', 'productos', null, ['tipo' => 'csv_inventario', 'estado' => $estado]);

// Generar CSV
$filename = "inventario_cemanblind_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
// UTF-8 BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeceras
fputcsv($output, ['Código', 'Nombre', 'N° Parte', 'Categoría', 'Stock Actual', 'Stock Mínimo', 'Unidad', 'Ubicación', 'Precio Ref.', 'Vencimiento']);

foreach ($productos as $row) {
    fputcsv($output, [
        $row['codigo'],
        $row['nombre'],
        $row['numero_parte'],
        $row['categoria'] ?? 'Sin categoría',
        $row['stock_actual'],
        $row['stock_minimo'],
        $row['unidad_medida'],
        $row['ubicacion_almacen'],
        $row['precio_unitario'],
        $row['fecha_vencimiento']
    ]);
}
fclose($output);
exit;
