<?php
/**
 * CEMABLN - Listado de Movimientos (Historial de trazabilidad)
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_USUARIO);

$db = getDB();

$tipo = $_GET['tipo'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['1=1'];
$params = [];

if ($tipo) {
    $where[] = "m.tipo_movimiento = :tipo";
    $params[':tipo'] = $tipo;
}
if ($search) {
    $where[] = "(p.nombre LIKE :s OR p.codigo LIKE :s OR v.codigo LIKE :s OR m.numero_vale LIKE :s)";
    $params[':s'] = "%{$search}%";
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

$countStmt = $db->prepare("SELECT COUNT(*) FROM movimientos m JOIN productos p ON m.producto_id = p.id LEFT JOIN vehiculos v ON m.vehiculo_id = v.id $whereClause");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

$stmt = $db->prepare("
    SELECT m.*, p.nombre AS prod_nombre, p.codigo AS prod_codigo,
           v.codigo AS veh_codigo, v.tipo AS veh_tipo, v.modelo AS veh_modelo,
           u.nombre_completo AS usuario_nombre, u.grado AS usuario_grado
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    LEFT JOIN vehiculos v ON m.vehiculo_id = v.id
    JOIN usuarios u ON m.usuario_id = u.id
    $whereClause
    ORDER BY m.created_at DESC LIMIT :lim OFFSET :off
");
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':lim', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$movimientos = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Movimientos</h1>
        <p class="text-sm text-slate-500 dark:text-gray-500 transition-colors">Historial completo de entradas y salidas</p>
    </div>
    <div class="flex gap-2">
        <a href="entrada.php" class="px-4 py-2 bg-green-700 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition">📥 Entrada</a>
        <a href="salida.php" class="px-4 py-2 bg-orange-700 hover:bg-orange-600 text-white rounded-lg text-sm font-medium transition">📤 Salida</a>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Buscar producto, vehículo o N° vale..."
            class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        <select name="tipo" class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            <option value="">Todos los tipos</option>
            <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>📥 Entradas</option>
            <option value="salida" <?= $tipo === 'salida' ? 'selected' : '' ?>>📤 Salidas</option>
        </select>
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-military-700 hover:bg-military-600 text-white rounded-lg px-4 py-2 text-sm transition">Buscar</button>
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Limpiar</a>
        </div>
    </form>
</div>

<!-- Tabla -->
<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-300 dark:border-steel-700 bg-slate-100 dark:bg-steel-900/80">
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Tipo</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Producto</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Cant.</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Vehículo</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Destino</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Vale</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Responsable</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Fecha</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movimientos as $mov): ?>
                <tr class="border-b border-slate-100 dark:border-steel-800/50 hover:bg-slate-50/50 dark:bg-steel-800/30 transition">
                    <td class="py-3 px-4">
                        <?php if ($mov['tipo_movimiento'] === 'entrada'): ?>
                            <span class="px-2 py-0.5 bg-emerald-100 dark:bg-green-900/40 text-emerald-600 dark:text-green-400 rounded text-xs">📥 Entrada</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 rounded text-xs">📤 Salida</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4">
                        <span class="text-slate-500 dark:text-gray-400 transition-colors text-xs"><?= sanitize($mov['prod_codigo']) ?></span>
                        <span class="text-slate-800 dark:text-gray-200 transition-colors ml-1"><?= sanitize($mov['prod_nombre']) ?></span>
                    </td>
                    <td class="py-3 px-4 text-center font-bold text-slate-800 dark:text-gray-200 transition-colors"><?= $mov['cantidad'] ?></td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs"><?= $mov['veh_codigo'] ? sanitize($mov['veh_codigo'] . ' (' . $mov['veh_tipo'] . ')') : '—' ?></td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs"><?= sanitize($mov['destino'] ?: '—') ?></td>
                    <td class="py-3 px-4 font-mono text-xs text-military-600 dark:text-military-400"><?= sanitize($mov['numero_vale'] ?: '—') ?></td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs"><?= sanitize($mov['usuario_grado'] . ' ' . $mov['usuario_nombre']) ?></td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-500 transition-colors text-xs"><?= formatDate($mov['fecha_movimiento']) ?></td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($mov['tipo_movimiento'] === 'salida' && $mov['numero_vale']): ?>
                        <a href="vale.php?id=<?= $mov['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-300 text-xs" target="_blank">Ver Vale</a>
                        <?php else: ?>
                        <span class="text-slate-400 dark:text-gray-600 transition-colors text-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($movimientos)): ?>
                <tr><td colspan="9" class="py-8 text-center text-slate-400 dark:text-gray-600 transition-colors">No hay movimientos registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $totalPages, 'index.php?tipo=' . urlencode($tipo) . '&search=' . urlencode($search)) ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
