<?php
/**
 * CEMANBLIND - Vale de Salida (Imprimible / PDF)
 * Genera un vale imprimible con print() para convertir a PDF.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_USUARIO);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { redirect(BASE_URL . '/modules/movimientos/index.php'); }

$stmt = $db->prepare("
    SELECT m.*, p.nombre AS prod_nombre, p.codigo AS prod_codigo, p.unidad_medida,
           v.codigo AS veh_codigo, v.tipo AS veh_tipo, v.modelo AS veh_modelo, v.placa_militar, v.unidad_asignada,
           u.nombre_completo AS usuario_nombre, u.grado AS usuario_grado, u.cedula AS usuario_cedula, u.cargo AS usuario_cargo
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    LEFT JOIN vehiculos v ON m.vehiculo_id = v.id
    JOIN usuarios u ON m.usuario_id = u.id
    WHERE m.id = :id AND m.tipo_movimiento = 'salida'
");
$stmt->execute([':id' => $id]);
$vale = $stmt->fetch();

if (!$vale) { setFlash('error', 'Vale no encontrado.'); redirect(BASE_URL . '/modules/movimientos/index.php'); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Vale <?= sanitize($vale['numero_vale']) ?> - <?= APP_NAME ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Courier New', monospace; }
        body { background: #fff; color: #000; padding: 20px; font-size: 12px; }
        .vale { max-width: 700px; margin: 0 auto; border: 2px solid #000; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 15px; }
        .header h1 { font-size: 16px; margin-bottom: 3px; }
        .header h2 { font-size: 13px; font-weight: normal; }
        .vale-number { font-size: 18px; font-weight: bold; background: #f0f0f0; padding: 5px 15px; display: inline-block; margin-top: 8px; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 3px; margin-bottom: 8px; text-transform: uppercase; font-size: 11px; }
        .row { display: flex; margin-bottom: 4px; }
        .label { font-weight: bold; width: 160px; flex-shrink: 0; }
        .value { flex: 1; border-bottom: 1px dotted #ccc; padding-left: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 6px 8px; text-align: left; }
        th { background: #e0e0e0; font-size: 11px; text-transform: uppercase; }
        .signatures { display: flex; justify-content: space-between; margin-top: 40px; }
        .signature-box { text-align: center; width: 200px; }
        .signature-line { border-top: 1px solid #000; margin-top: 40px; padding-top: 5px; }
        .footer { text-align: center; margin-top: 20px; font-size: 10px; color: #666; border-top: 1px solid #ccc; padding-top: 10px; }
        .no-print { text-align: center; margin: 20px 0; }
        @media print { .no-print { display: none; } body { padding: 0; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 30px; background: #2d6b2d; color: #fff; border: none; cursor: pointer; font-size: 14px; border-radius: 5px;">🖨️ Imprimir Vale / Guardar como PDF</button>
        <a href="index.php" style="margin-left: 10px; color: #333;">← Volver a movimientos</a>
    </div>

    <div class="vale">
        <div class="header">
            <h1>REPÚBLICA BOLIVARIANA DE VENEZUELA</h1>
            <h2>FUERZA ARMADA NACIONAL BOLIVARIANA</h2>
            <h2><?= APP_FULL_NAME ?> (<?= APP_NAME ?>)</h2>
            <div class="vale-number">VALE DE SALIDA: <?= sanitize($vale['numero_vale']) ?></div>
        </div>

        <div class="section">
            <div class="section-title">Datos del Movimiento</div>
            <div class="row"><span class="label">Fecha de Despacho:</span><span class="value"><?= formatDate($vale['fecha_movimiento'], 'd/m/Y H:i:s') ?></span></div>
            <div class="row"><span class="label">N° de Vale:</span><span class="value"><?= sanitize($vale['numero_vale']) ?></span></div>
        </div>

        <div class="section">
            <div class="section-title">Producto Despachado</div>
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Descripción</th>
                        <th>Cantidad</th>
                        <th>Unidad</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?= sanitize($vale['prod_codigo']) ?></td>
                        <td><?= sanitize($vale['prod_nombre']) ?></td>
                        <td style="text-align:center; font-weight:bold;"><?= $vale['cantidad'] ?></td>
                        <td><?= sanitize($vale['unidad_medida']) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Vehículo Destino</div>
            <div class="row"><span class="label">ID Vehículo:</span><span class="value"><?= sanitize($vale['veh_codigo'] ?? 'N/A') ?></span></div>
            <div class="row"><span class="label">Tipo / Modelo:</span><span class="value"><?= sanitize(($vale['veh_tipo'] ?? '') . ' ' . ($vale['veh_modelo'] ?? '')) ?></span></div>
            <div class="row"><span class="label">Placa Militar:</span><span class="value"><?= sanitize($vale['placa_militar'] ?? 'N/A') ?></span></div>
            <div class="row"><span class="label">Unidad Asignada:</span><span class="value"><?= sanitize($vale['unidad_asignada'] ?? 'N/A') ?></span></div>
        </div>

        <div class="section">
            <div class="section-title">Destino y Observaciones</div>
            <div class="row"><span class="label">Destino/Propósito:</span><span class="value"><?= sanitize($vale['destino'] ?? '') ?></span></div>
            <div class="row"><span class="label">Observaciones:</span><span class="value"><?= sanitize($vale['observaciones'] ?: 'Ninguna') ?></span></div>
        </div>

        <div class="section">
            <div class="section-title">Responsable del Despacho</div>
            <div class="row"><span class="label">Nombre:</span><span class="value"><?= sanitize($vale['usuario_grado'] . ' ' . $vale['usuario_nombre']) ?></span></div>
            <div class="row"><span class="label">Cédula:</span><span class="value"><?= sanitize($vale['usuario_cedula']) ?></span></div>
            <div class="row"><span class="label">Cargo:</span><span class="value"><?= sanitize($vale['usuario_cargo']) ?></span></div>
        </div>

        <div class="signatures">
            <div class="signature-box">
                <div class="signature-line">Entregado por</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Recibido por</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">Autorizado por</div>
            </div>
        </div>

        <div class="footer">
            Documento generado automáticamente por <?= APP_NAME ?> v<?= APP_VERSION ?> | <?= date('d/m/Y H:i:s') ?><br>
            Este vale es un documento oficial de control de inventario. Cualquier alteración invalida el documento.
        </div>
    </div>
</body>
</html>
