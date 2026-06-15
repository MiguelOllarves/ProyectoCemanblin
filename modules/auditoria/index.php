<?php
/**
 * CEMANBLIND - Registro de Auditoría
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/audit.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERADMIN);

$page = max(1, (int)($_GET['page'] ?? 1));
$filters = [
    'accion' => $_GET['accion'] ?? '',
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
];

$result = getAuditLogs($filters, $page, ITEMS_PER_PAGE);
$logs = $result['data'];
$totalPages = $result['pages'];

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Auditoría del Sistema</h1>
        <p class="text-sm text-slate-500 dark:text-gray-500 transition-colors">Registro inmutable de todas las acciones</p>
    </div>
</div>

<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-4 mb-6">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <select name="accion" class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            <option value="">Todas las acciones</option>
            <option value="LOGIN_SUCCESS" <?= $filters['accion'] === 'LOGIN_SUCCESS' ? 'selected' : '' ?>>Login Exitoso</option>
            <option value="LOGIN_FAILED" <?= $filters['accion'] === 'LOGIN_FAILED' ? 'selected' : '' ?>>Login Fallido</option>
            <option value="CREATE" <?= $filters['accion'] === 'CREATE' ? 'selected' : '' ?>>Creación (CREATE)</option>
            <option value="UPDATE" <?= $filters['accion'] === 'UPDATE' ? 'selected' : '' ?>>Modificación (UPDATE)</option>
            <option value="DELETE" <?= $filters['accion'] === 'DELETE' ? 'selected' : '' ?>>Eliminación (DELETE)</option>
        </select>
        <input type="date" name="fecha_desde" value="<?= sanitize($filters['fecha_desde']) ?>" class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        <input type="date" name="fecha_hasta" value="<?= sanitize($filters['fecha_hasta']) ?>" class="bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        <div class="flex gap-2">
            <button type="submit" class="flex-1 bg-military-700 hover:bg-military-600 text-white rounded-lg px-4 py-2 text-sm transition">Filtrar</button>
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition text-center">Limpiar</a>
        </div>
    </form>
</div>

<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-300 dark:border-steel-700 bg-slate-100 dark:bg-steel-900/80">
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Fecha</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Usuario</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Acción</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Tabla (ID)</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">IP Cliente</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Detalles</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr class="border-b border-slate-100 dark:border-steel-800/50 hover:bg-slate-50/50 dark:bg-steel-800/30 transition">
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs whitespace-nowrap"><?= $log['created_at'] ?></td>
                    <td class="py-3 px-4 text-slate-700 dark:text-gray-300 transition-colors text-xs"><?= sanitize($log['usuario_nombre']) ?></td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold tracking-wider uppercase 
                        <?= $log['accion'] === 'CREATE' ? 'bg-green-900/50 text-emerald-600 dark:text-green-400' : 
                           ($log['accion'] === 'UPDATE' ? 'bg-blue-900/50 text-blue-600 dark:text-blue-400' : 
                           ($log['accion'] === 'DELETE' ? 'bg-red-900/50 text-red-600 dark:text-red-400' : 'bg-gray-800 text-slate-500 dark:text-gray-400 transition-colors')) ?>">
                            <?= sanitize($log['accion']) ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs">
                        <?= $log['tabla_afectada'] ? sanitize($log['tabla_afectada']) : '—' ?>
                        <?= $log['registro_id'] ? "(#{$log['registro_id']})" : '' ?>
                    </td>
                    <td class="py-3 px-4 text-slate-500 dark:text-gray-500 transition-colors text-xs font-mono"><?= sanitize($log['ip_cliente']) ?></td>
                    <td class="py-3 px-4">
                        <?php if ($log['datos']): ?>
                            <details class="text-xs">
                                <summary class="cursor-pointer text-military-600 dark:text-military-400 hover:text-military-300">Ver JSON</summary>
                                <pre class="mt-2 p-2 bg-black/50 rounded border border-slate-200 dark:border-steel-800 overflow-x-auto text-slate-500 dark:text-gray-400 transition-colors"><?= sanitize($log['datos']) ?></pre>
                            </details>
                        <?php else: ?>
                            <span class="text-slate-400 dark:text-gray-600 transition-colors text-xs">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?>
                <tr><td colspan="6" class="py-8 text-center text-slate-400 dark:text-gray-600 transition-colors">No hay registros de auditoría.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?= renderPagination($page, $totalPages, 'index.php?accion=' . urlencode($filters['accion']) . '&fecha_desde=' . urlencode($filters['fecha_desde']) . '&fecha_hasta=' . urlencode($filters['fecha_hasta'])) ?>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
