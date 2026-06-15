<?php
/**
 * CEMANBLIND - Dashboard Principal
 * Vista con indicadores de gestión, alertas críticas y movimientos recientes.
 * Usa Chart.js para gráficos.
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_USUARIO);

$db = getDB();

// ── Métricas principales ───────────────────────────────────────────────
$totalProductos = $db->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();
$totalStock = $db->query("SELECT COALESCE(SUM(stock_actual), 0) FROM productos WHERE activo = 1")->fetchColumn();
$totalVehiculos = $db->query("SELECT COUNT(*) FROM vehiculos WHERE activo = 1")->fetchColumn();
$vehiculosMantenimiento = $db->query("SELECT COUNT(*) FROM vehiculos WHERE estado = 'mantenimiento' AND activo = 1")->fetchColumn();

// Movimientos de hoy
$movHoy = $db->query("SELECT COUNT(*) FROM movimientos WHERE DATE(fecha_movimiento) = " . dbDate())->fetchColumn();

// Entradas y salidas del mes actual
$entradasMes = $db->query("SELECT COUNT(*) FROM movimientos WHERE tipo_movimiento = 'entrada' AND " . dbMonthYear('fecha_movimiento') . " = " . dbCurrentMonthYear())->fetchColumn();
$salidasMes = $db->query("SELECT COUNT(*) FROM movimientos WHERE tipo_movimiento = 'salida' AND " . dbMonthYear('fecha_movimiento') . " = " . dbCurrentMonthYear())->fetchColumn();

// ── Alertas ────────────────────────────────────────────────────────────
$stockBajo = getProductosStockBajo();
$proximosVencer = getProductosProximosVencer();

// ── Movimientos recientes (últimos 10) ─────────────────────────────────
$stmtRecientes = $db->query("
    SELECT m.*, p.nombre AS producto_nombre, p.codigo AS producto_codigo,
           v.codigo AS vehiculo_codigo, v.tipo AS vehiculo_tipo,
           u.nombre_completo AS usuario_nombre
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    LEFT JOIN vehiculos v ON m.vehiculo_id = v.id
    JOIN usuarios u ON m.usuario_id = u.id
    ORDER BY m.created_at DESC LIMIT 10
");
$movRecientes = $stmtRecientes->fetchAll();

// ── Datos para gráficos: Movimientos por día (últimos 7 días) ──────────
$stmtGrafico = $db->query("
    SELECT DATE(fecha_movimiento) as fecha, tipo_movimiento, COUNT(*) as total
    FROM movimientos
    WHERE fecha_movimiento >= " . dbDaysAgo(7) . "
    GROUP BY DATE(fecha_movimiento), tipo_movimiento
    ORDER BY fecha
");
$datosGrafico = $stmtGrafico->fetchAll();

$labels = [];
$entradas = [];
$salidas = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-{$i} days"));
    $labels[] = date('d/m', strtotime($fecha));
    $entradas[$fecha] = 0;
    $salidas[$fecha] = 0;
}
foreach ($datosGrafico as $row) {
    if ($row['tipo_movimiento'] === 'entrada') {
        $entradas[$row['fecha']] = $row['total'];
    } else {
        $salidas[$row['fecha']] = $row['total'];
    }
}

// ── Top 5 productos más despachados del mes ────────────────────────────
$stmtTop = $db->query("
    SELECT p.nombre, SUM(m.cantidad) as total_despachado
    FROM movimientos m
    JOIN productos p ON m.producto_id = p.id
    WHERE m.tipo_movimiento = 'salida'
    AND " . dbMonthYear('m.fecha_movimiento') . " = " . dbCurrentMonthYear() . "
    GROUP BY p.id
    ORDER BY total_despachado DESC
    LIMIT 5
");
$topProductos = $stmtTop->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<!-- Page Title -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Panel de control</h1>
        <p class="text-sm text-slate-500 dark:text-gray-500 transition-colors">Vista general del sistema de control de blindados</p>
    </div>
    <div class="text-xs text-slate-500 dark:text-gray-500 transition-colors">
        Última actualización: <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

<!-- ── KPI Cards ────────────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Productos -->
    <div class="bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700/50 rounded-xl p-5 hover:border-military-400 dark:hover:border-military-600/50 transition-all duration-300 shadow-sm hover:shadow-md group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider transition-colors">Productos Registrados</p>
                <p class="text-3xl font-bold text-slate-800 dark:text-gray-100 mt-1 transition-colors"><?= number_format($totalProductos) ?></p>
            </div>
            <div class="w-12 h-12 bg-military-50 dark:bg-military-900/50 text-military-600 dark:text-military-400 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">📦</div>
        </div>
        <div class="mt-3 text-xs text-slate-500 dark:text-gray-500 font-medium transition-colors">Stock total: <span class="text-military-600 dark:text-military-400 font-bold"><?= number_format($totalStock) ?></span> unidades</div>
    </div>
    
    <!-- Vehículos -->
    <div class="bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700/50 rounded-xl p-5 hover:border-blue-400 dark:hover:border-blue-600/50 transition-all duration-300 shadow-sm hover:shadow-md group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider transition-colors">Vehículos Blindados</p>
                <p class="text-3xl font-bold text-slate-800 dark:text-gray-100 mt-1 transition-colors"><?= $totalVehiculos ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/50 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">🚛</div>
        </div>
        <div class="mt-3 text-xs text-slate-500 dark:text-gray-500 font-medium transition-colors">En mantenimiento: <span class="text-amber-500 dark:text-yellow-400 font-bold"><?= $vehiculosMantenimiento ?></span></div>
    </div>
    
    <!-- Movimientos Hoy -->
    <div class="bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700/50 rounded-xl p-5 hover:border-purple-400 dark:hover:border-purple-600/50 transition-all duration-300 shadow-sm hover:shadow-md group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider transition-colors">Movimientos Hoy</p>
                <p class="text-3xl font-bold text-slate-800 dark:text-gray-100 mt-1 transition-colors"><?= $movHoy ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/50 text-purple-600 dark:text-purple-400 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">🔄</div>
        </div>
        <div class="mt-3 text-xs text-slate-500 dark:text-gray-500 font-medium transition-colors">
            Entradas mes: <span class="text-emerald-600 dark:text-green-400 font-bold"><?= $entradasMes ?></span> |
            Salidas mes: <span class="text-orange-600 dark:text-orange-400 font-bold"><?= $salidasMes ?></span>
        </div>
    </div>
    
    <!-- Alertas -->
    <div class="bg-white dark:bg-steel-900/50 border <?= (count($stockBajo) + count($proximosVencer)) > 0 ? 'border-red-300 dark:border-red-600/50 shadow-red-100 dark:shadow-none' : 'border-slate-200 dark:border-steel-700/50' ?> rounded-xl p-5 hover:border-red-400 dark:hover:border-red-600/50 transition-all duration-300 shadow-sm hover:shadow-md group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider transition-colors">Alertas Activas</p>
                <p class="text-3xl font-bold <?= (count($stockBajo) + count($proximosVencer)) > 0 ? 'text-red-500 dark:text-red-400' : 'text-slate-800 dark:text-gray-100' ?> mt-1 transition-colors">
                    <?= count($stockBajo) + count($proximosVencer) ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-red-50 dark:bg-red-900/50 text-red-500 dark:text-red-400 rounded-xl flex items-center justify-center text-2xl group-hover:scale-110 transition-transform shadow-inner">⚠️</div>
        </div>
        <div class="mt-3 text-xs text-slate-500 dark:text-gray-500 font-medium transition-colors">
            Stock bajo: <span class="text-red-500 dark:text-red-400 font-bold"><?= count($stockBajo) ?></span> |
            Por vencer: <span class="text-orange-500 dark:text-orange-400 font-bold"><?= count($proximosVencer) ?></span>
        </div>
    </div>
</div>

<!-- ── Gráficos ─────────────────────────────────────────────────────────── -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <!-- Movimientos últimos 7 días -->
    <div class="bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700/50 rounded-xl p-5 shadow-sm transition-colors duration-300">
        <h3 class="text-sm font-bold text-slate-700 dark:text-gray-300 mb-4 transition-colors">📊 Movimientos — Últimos 7 días</h3>
        <canvas id="chartMovimientos" height="200"></canvas>
    </div>
    
    <!-- Top productos despachados -->
    <div class="bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700/50 rounded-xl p-5 shadow-sm transition-colors duration-300">
        <h3 class="text-sm font-bold text-slate-700 dark:text-gray-300 mb-4 transition-colors">🏆 Top Productos Despachados (Mes)</h3>
        <?php if ($topProductos): ?>
        <canvas id="chartTopProductos" height="200"></canvas>
        <?php else: ?>
        <div class="flex items-center justify-center h-[200px] text-slate-400 dark:text-gray-600 font-medium transition-colors">Sin datos de despachos este mes</div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Alertas Críticas ─────────────────────────────────────────────────── -->
<?php if ($stockBajo || $proximosVencer): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
    <?php if ($stockBajo): ?>
    <div class="bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800/50 rounded-xl p-5 shadow-sm transition-colors duration-300">
        <h3 class="text-sm font-bold text-red-600 dark:text-red-400 mb-3 flex items-center gap-2">🚨 Stock Bajo Crítico</h3>
        <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
            <?php foreach (array_slice($stockBajo, 0, 8) as $prod): ?>
            <div class="flex items-center justify-between bg-white dark:bg-red-900/20 border border-red-100 dark:border-transparent rounded-lg px-3 py-2 shadow-sm transition-colors">
                <div>
                    <span class="text-xs font-bold text-slate-500 dark:text-gray-400"><?= sanitize($prod['codigo']) ?></span>
                    <span class="text-sm font-medium text-slate-700 dark:text-gray-200 ml-2"><?= sanitize($prod['nombre']) ?></span>
                </div>
                <span class="text-sm font-bold text-red-600 dark:text-red-400"><?= $prod['stock_actual'] ?> / <?= $prod['stock_minimo'] ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($proximosVencer): ?>
    <div class="bg-orange-50 dark:bg-orange-950/30 border border-orange-200 dark:border-orange-800/50 rounded-xl p-5 shadow-sm transition-colors duration-300">
        <h3 class="text-sm font-bold text-orange-600 dark:text-orange-400 mb-3 flex items-center gap-2">⏰ Próximos a Vencer</h3>
        <div class="space-y-2 max-h-48 overflow-y-auto pr-2">
            <?php foreach (array_slice($proximosVencer, 0, 8) as $prod): ?>
            <div class="flex items-center justify-between bg-white dark:bg-orange-900/20 border border-orange-100 dark:border-transparent rounded-lg px-3 py-2 shadow-sm transition-colors">
                <div>
                    <span class="text-xs font-bold text-slate-500 dark:text-gray-400"><?= sanitize($prod['codigo']) ?></span>
                    <span class="text-sm font-medium text-slate-700 dark:text-gray-200 ml-2"><?= sanitize($prod['nombre']) ?></span>
                </div>
                <span class="text-xs font-bold text-orange-600 dark:text-orange-400"><?= formatDate($prod['fecha_vencimiento'], 'd/m/Y') ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- ── Movimientos Recientes ────────────────────────────────────────────── -->
<div class="bg-white dark:bg-steel-900/50 border border-slate-200 dark:border-steel-700/50 rounded-xl p-5 shadow-sm transition-colors duration-300 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-bold text-slate-700 dark:text-gray-300 transition-colors">🔄 Movimientos Recientes</h3>
        <a href="<?= BASE_URL ?>/modules/movimientos/index.php" class="text-xs font-bold text-military-600 dark:text-military-400 hover:text-military-500 dark:hover:text-military-300 transition-colors">Ver todos →</a>
    </div>
    
    <?php if ($movRecientes): ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 dark:border-steel-700 transition-colors">
                    <th class="text-left py-3 px-3 text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="text-left py-3 px-3 text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider">Producto</th>
                    <th class="text-left py-3 px-3 text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider">Cantidad</th>
                    <th class="text-left py-3 px-3 text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider">Vehículo</th>
                    <th class="text-left py-3 px-3 text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider">Usuario</th>
                    <th class="text-left py-3 px-3 text-xs font-bold text-slate-500 dark:text-gray-500 uppercase tracking-wider">Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($movRecientes as $mov): ?>
                <tr class="border-b border-slate-100 dark:border-steel-800/50 hover:bg-slate-50 dark:hover:bg-steel-800/30 transition-colors">
                    <td class="py-3 px-3">
                        <?php if ($mov['tipo_movimiento'] === 'entrada'): ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 dark:bg-green-900/40 text-emerald-700 dark:text-green-400 font-bold rounded-md text-xs transition-colors">📥 Entrada</span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-orange-100 dark:bg-orange-900/40 text-orange-700 dark:text-orange-400 font-bold rounded-md text-xs transition-colors">📤 Salida</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-3">
                        <span class="text-slate-500 dark:text-gray-400 text-xs font-bold transition-colors"><?= sanitize($mov['producto_codigo']) ?></span>
                        <span class="text-slate-800 dark:text-gray-200 ml-2 font-medium transition-colors"><?= sanitize($mov['producto_nombre']) ?></span>
                    </td>
                    <td class="py-3 px-3 font-bold text-slate-800 dark:text-gray-200 transition-colors"><?= $mov['cantidad'] ?></td>
                    <td class="py-3 px-3 text-slate-600 dark:text-gray-400 font-medium transition-colors"><?= $mov['vehiculo_codigo'] ? sanitize($mov['vehiculo_codigo']) : '—' ?></td>
                    <td class="py-3 px-3 text-slate-500 dark:text-gray-400 text-xs font-medium transition-colors"><?= sanitize($mov['usuario_nombre']) ?></td>
                    <td class="py-3 px-3 text-slate-400 dark:text-gray-500 text-xs font-medium transition-colors"><?= formatDate($mov['fecha_movimiento']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-8 text-slate-500 dark:text-gray-600 font-medium transition-colors">No hay movimientos registrados aún.</div>
    <?php endif; ?>
</div>

<!-- ── Chart.js Scripts ─────────────────────────────────────────────────── -->
<script>
// Check current theme for chart text colors
const isDark = document.documentElement.classList.contains('dark');
const textColor = isDark ? '#9da5b3' : '#64748b';
const gridColor = isDark ? 'rgba(61,68,82,0.3)' : 'rgba(226,232,240,0.8)';

// Gráfico de Movimientos (Barras)
const ctxMov = document.getElementById('chartMovimientos');
if (ctxMov) {
    new Chart(ctxMov, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_values($labels)) ?>,
            datasets: [
                {
                    label: 'Entradas',
                    data: <?= json_encode(array_values($entradas)) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)', // emerald-500
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 1, borderRadius: 6
                },
                {
                    label: 'Salidas',
                    data: <?= json_encode(array_values($salidas)) ?>,
                    backgroundColor: 'rgba(249, 115, 22, 0.7)', // orange-500
                    borderColor: 'rgba(249, 115, 22, 1)',
                    borderWidth: 1, borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { labels: { color: textColor, font: {family: "'Inter', sans-serif", weight: '500'} } } },
            scales: {
                x: { ticks: { color: textColor, font: {family: "'Inter', sans-serif"} }, grid: { color: gridColor, drawBorder: false } },
                y: { beginAtZero: true, ticks: { color: textColor, stepSize: 1, font: {family: "'Inter', sans-serif"} }, grid: { color: gridColor, drawBorder: false } }
            }
        }
    });
}

// Gráfico Top Productos (Doughnut)
<?php if ($topProductos): ?>
const ctxTop = document.getElementById('chartTopProductos');
if (ctxTop) {
    new Chart(ctxTop, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($topProductos, 'nombre')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($topProductos, 'total_despachado')) ?>,
                backgroundColor: ['#10b981', '#f97316', '#3b82f6', '#a855f7', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: { legend: { position: 'bottom', labels: { color: textColor, padding: 12, font: { family: "'Inter', sans-serif", size: 11, weight: '500' } } } }
        }
    });
}
<?php endif; ?>

// Escuchar cambios de tema para recargar página o actualizar gráficos
// (Una forma sencilla de que los gráficos cambien su color de texto es recargar la gráfica, 
//  pero por simplicidad aquí dejamos que tomen el color al inicializar la página).
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
