<?php
/**
 * CEMANBLIND - Listado de Inventario
 * CRUD: Vista principal con búsqueda, filtros y alertas de stock.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_USUARIO);

$db = getDB();

// ── Filtros y búsqueda ─────────────────────────────────────────────────
$search = $_GET['search'] ?? '';
$catFilter = $_GET['categoria'] ?? '';
$alertFilter = $_GET['filter'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

$where = ['p.activo = 1'];
$params = [];

if ($search) {
    $where[] = "(p.nombre LIKE :search OR p.codigo LIKE :search OR p.numero_parte LIKE :search)";
    $params[':search'] = "%{$search}%";
}
if ($catFilter) {
    $where[] = "p.categoria_id = :cat";
    $params[':cat'] = $catFilter;
}
if ($alertFilter === 'alerts') {
    $fechaLimite = date('Y-m-d', strtotime('+' . EXPIRY_ALERT_DAYS . ' days'));
    $where[] = "(p.stock_actual <= p.stock_minimo OR (p.fecha_vencimiento IS NOT NULL AND p.fecha_vencimiento <= '{$fechaLimite}'))";
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Contar total
$countStmt = $db->prepare("SELECT COUNT(*) FROM productos p $whereClause");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = (int) ceil($total / ITEMS_PER_PAGE);
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Obtener productos
$sql = "SELECT p.*, c.nombre AS categoria_nombre FROM productos p LEFT JOIN categorias c ON p.categoria_id = c.id $whereClause ORDER BY p.nombre ASC LIMIT :lim OFFSET :off";
$stmt = $db->prepare($sql);
foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
$stmt->bindValue(':lim', ITEMS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$productos = $stmt->fetchAll();

// Categorías para filtro
$categorias = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Inventario</h1>
        <p class="text-sm text-slate-500 dark:text-gray-500 transition-colors"><?= $total ?> productos registrados</p>
    </div>
    <?php if (hasRole(ROLE_ADMINISTRADOR)): ?>
    <a href="crear.php" class="px-4 py-2 bg-military-700 hover:bg-military-600 text-white rounded-lg text-sm font-medium transition">
        + Nuevo Producto
    </a>
    <?php endif; ?>
</div>

<!-- Filtros -->
<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Buscar por nombre, código o N° parte..."
            class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        
        <select name="categoria" class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            <option value="">Todas las categorías</option>
            <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $catFilter == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        
        <select name="filter" class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            <option value="">Sin filtro especial</option>
            <option value="alerts" <?= $alertFilter === 'alerts' ? 'selected' : '' ?>>⚠️ Solo alertas</option>
        </select>
        
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-military-700 hover:bg-military-600 text-white rounded-lg px-4 py-2 text-sm transition">Filtrar</button>
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Limpiar</a>
        </div>
    </form>
</div>

<!-- Tabla de productos -->
<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm" id="tablaInventario">
            <thead>
                <tr class="border-b border-slate-300 dark:border-steel-700 bg-slate-100 dark:bg-steel-900/80">
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Código</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Producto</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Categoría</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Stock</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Mínimo</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Ubicación</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Vencimiento</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Estado</th>
                    <?php if (hasRole(ROLE_ADMINISTRADOR)): ?>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $prod): 
                    $isLowStock = $prod['stock_actual'] <= $prod['stock_minimo'];
                    $isExpiring = $prod['fecha_vencimiento'] && strtotime($prod['fecha_vencimiento']) <= strtotime('+' . EXPIRY_ALERT_DAYS . ' days');
                ?>
                <tr class="border-b border-slate-100 dark:border-steel-800/50 hover:bg-slate-50/50 dark:bg-steel-800/30 transition">
                    <td class="py-3 px-4 font-mono text-xs text-military-600 dark:text-military-400"><?= sanitize($prod['codigo']) ?></td>
                    <td class="py-3 px-4">
                        <div class="text-slate-800 dark:text-gray-200 transition-colors"><?= sanitize($prod['nombre']) ?></div>
                        <?php if ($prod['numero_parte']): ?>
                        <div class="text-xs text-slate-500 dark:text-gray-500 transition-colors">N/P: <?= sanitize($prod['numero_parte']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs"><?= sanitize($prod['categoria_nombre'] ?? 'Sin categoría') ?></td>
                    <td class="py-3 px-4 text-center font-bold <?= $isLowStock ? 'text-red-600 dark:text-red-400' : 'text-slate-800 dark:text-gray-200 transition-colors' ?>">
                        <?= $prod['stock_actual'] ?> <?= $prod['unidad_medida'] ?>
                    </td>
                    <td class="py-3 px-4 text-center text-slate-500 dark:text-gray-500 transition-colors"><?= $prod['stock_minimo'] ?></td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs"><?= sanitize($prod['ubicacion_almacen'] ?: '—') ?></td>
                    <td class="py-3 px-4 text-xs <?= $isExpiring ? 'text-orange-600 dark:text-orange-400 font-semibold' : 'text-slate-500 dark:text-gray-500 transition-colors' ?>">
                        <?= $prod['fecha_vencimiento'] ? formatDate($prod['fecha_vencimiento'], 'd/m/Y') : '—' ?>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($isLowStock): ?>
                            <span class="px-2 py-0.5 bg-red-900/40 text-red-600 dark:text-red-400 rounded text-xs">Bajo</span>
                        <?php elseif ($isExpiring): ?>
                            <span class="px-2 py-0.5 bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 rounded text-xs">Vencer</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 bg-emerald-100 dark:bg-green-900/40 text-emerald-600 dark:text-green-400 rounded text-xs">OK</span>
                        <?php endif; ?>
                    </td>
                    <?php if (hasRole(ROLE_ADMINISTRADOR)): ?>
                    <td class="py-3 px-4 text-center">
                        <a href="editar.php?id=<?= $prod['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-300 text-xs mr-2">Editar</a>
                        <a href="eliminar.php?id=<?= $prod['id'] ?>" onclick="return confirmDelete('¿Eliminar <?= sanitize($prod['nombre']) ?>?')" class="text-red-600 dark:text-red-400 hover:text-red-300 text-xs">Eliminar</a>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($productos)): ?>
                <tr><td colspan="9" class="py-8 text-center text-slate-400 dark:text-gray-600 transition-colors">No se encontraron productos.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $totalPages, 'index.php?search=' . urlencode($search) . '&categoria=' . urlencode($catFilter) . '&filter=' . urlencode($alertFilter)) ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
