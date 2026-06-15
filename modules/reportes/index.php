<?php
/**
 * CEMABLN - Menú de Reportes
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_SUPERVISOR); // Supervisor o superior

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Reportes del Sistema</h1>
        <p class="text-sm text-slate-500 dark:text-gray-500 transition-colors">Generación y exportación de datos</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Reporte de Inventario -->
    <div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 hover:border-military-600/50 transition">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-military-50 dark:bg-military-900/50 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">📦</div>
            <div>
                <h2 class="text-lg font-semibold text-slate-800 dark:text-gray-200 transition-colors mb-1">Inventario Actual</h2>
                <p class="text-sm text-slate-500 dark:text-gray-400 transition-colors mb-4">Exporta el estado actual de todo el inventario, incluyendo stock, ubicación y alertas.</p>
                
                <form action="inventario_csv.php" method="GET" class="space-y-3">
                    <select name="estado" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                        <option value="todos">Todos los productos</option>
                        <option value="bajo_stock">Solo bajo stock</option>
                        <option value="por_vencer">Próximos a vencer</option>
                    </select>
                    <button type="submit" class="w-full bg-military-700 hover:bg-military-600 text-white rounded-lg px-4 py-2 text-sm font-medium transition flex justify-center items-center gap-2">
                        <span>📥</span> Exportar a CSV
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Reporte de Movimientos -->
    <div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 hover:border-blue-600/50 transition">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-900/50 rounded-xl flex items-center justify-center text-2xl flex-shrink-0">🔄</div>
            <div>
                <h2 class="text-lg font-semibold text-slate-800 dark:text-gray-200 transition-colors mb-1">Historial de Movimientos</h2>
                <p class="text-sm text-slate-500 dark:text-gray-400 transition-colors mb-4">Exporta el registro de entradas y salidas (trazabilidad completa).</p>
                
                <form action="movimientos_csv.php" method="GET" class="space-y-3">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] text-slate-500 dark:text-gray-500 transition-colors uppercase mb-1">Desde</label>
                            <input type="date" name="desde" required value="<?= date('Y-m-01') ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-2 py-1.5 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] text-slate-500 dark:text-gray-500 transition-colors uppercase mb-1">Hasta</label>
                            <input type="date" name="hasta" required value="<?= date('Y-m-t') ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-2 py-1.5 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <select name="tipo" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-blue-500">
                        <option value="todos">Todos los movimientos</option>
                        <option value="entrada">Solo Entradas</option>
                        <option value="salida">Solo Salidas</option>
                    </select>
                    <button type="submit" class="w-full bg-blue-700 hover:bg-blue-600 text-white rounded-lg px-4 py-2 text-sm font-medium transition flex justify-center items-center gap-2">
                        <span>📥</span> Exportar a CSV
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
