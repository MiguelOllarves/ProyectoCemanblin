<?php
/**
 * CEMANBLIND - Exportar Movimientos a CSV
 */
require_once __DIR__ . '/../../includes/auth.php';

requireRole(ROLE_SUPERVISOR);

$db = getDB();
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-t');
$tipo = $_GET['tipo'] ?? 'todos';

$where = ["DATE(m.fecha_movimiento) BETWEEN :desde AND :hasta"];
$params = [':desde' => $desde, ':hasta' => $hasta];

if ($tipo !== 'todos') {
    $where[] = "m.tipo_movimiento = :tipo";
    $params[':tipo'] = $tipo;
}

$whereClause = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT m.tipo_movimiento, m.fecha_movimiento, m.numero_vale,
           p.codigo AS prod_codigo, p.nombre AS prod_nombre, m.cantidad, p.unidad_medida,
           v.codigo AS veh_codigo, v.tipo AS veh_tipo, v.placa_militar,
           m.destino, m.observaciones, u.nombre_completo AS responsable
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    LEFT JOIN vehiculos v ON m.vehiculo_id = v.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE $whereClause
    ORDER BY m.fecha_movimiento DESC
");
$stmt->execute($params);
$movimientos = $stmt->fetchAll();

// Log audit
logAudit('REPORT', 'movimientos', null, ['tipo' => 'csv_movimientos', 'desde' => $desde, 'hasta' => $hasta, 'filtro_tipo' => $tipo]);

// Generar CSV
$filename = "movimientos_cemanblind_" . date('Ymd_His') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
// UTF-8 BOM para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeceras
fputcsv($output, ['Fecha', 'Tipo', 'N° Vale', 'Cód. Producto', 'Producto', 'Cant.', 'Unidad', 'ID Vehículo', 'Tipo Vehículo', 'Placa Militar', 'Destino', 'Responsable', 'Observaciones']);

foreach ($movimientos as $row) {
    fputcsv($output, [
        $row['fecha_movimiento'],
        strtoupper($row['tipo_movimiento']),
        $row['numero_vale'] ?? 'N/A',
        $row['prod_codigo'],
        $row['prod_nombre'],
        $row['cantidad'],
        $row['unidad_medida'],
        $row['veh_codigo'] ?? 'N/A',
        $row['veh_tipo'] ?? 'N/A',
        $row['placa_militar'] ?? 'N/A',
        $row['destino'] ?? 'N/A',
        $row['responsable'],
        $row['observaciones']
    ]);
}
fclose($output);
exit;
