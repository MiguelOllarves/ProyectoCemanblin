<?php
/**
 * CEMANBLIND - Gestión de Vehículos
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMINISTRADOR);

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    if (validateCSRFToken($_POST['csrf_token'])) {
        try {
            $stmt = $db->prepare("INSERT INTO vehiculos (codigo, tipo, modelo, placa_militar, unidad_asignada, estado) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                trim($_POST['codigo']), trim($_POST['tipo']), trim($_POST['modelo']), 
                trim($_POST['placa_militar']), trim($_POST['unidad_asignada']), $_POST['estado']
            ]);
            $newId = 0;
            try {
                $seq = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'vehiculos_id_seq' : null;
                $newId = (int) $db->lastInsertId($seq);
            } catch (Exception $e) {}
            logAudit('CREATE', 'vehiculos', $newId, ['codigo' => $_POST['codigo']]);
            setFlash('success', 'Vehículo registrado.');
        } catch (PDOException $e) {
            setFlash('error', 'Error al registrar vehículo (¿Código duplicado?).');
        }
    }
    redirect('index.php');
}

$vehiculos = $db->query("SELECT * FROM vehiculos WHERE activo = 1 ORDER BY codigo ASC")->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Vehículos Blindados</h1>
        <p class="text-sm text-slate-500 dark:text-gray-500 transition-colors">Gestión de unidades y estados</p>
    </div>
    <button onclick="document.getElementById('modalNew').classList.remove('hidden')" class="px-4 py-2 bg-military-700 hover:bg-military-600 text-white rounded-lg text-sm font-medium transition">
        + Nuevo Vehículo
    </button>
</div>

<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="border-b border-slate-300 dark:border-steel-700 bg-slate-100 dark:bg-steel-900/80">
                <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Código</th>
                <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Tipo/Modelo</th>
                <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Placa Militar</th>
                <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Unidad Asignada</th>
                <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vehiculos as $v): ?>
            <tr class="border-b border-slate-100 dark:border-steel-800/50 hover:bg-slate-50/50 dark:bg-steel-800/30 transition">
                <td class="py-3 px-4 font-mono font-bold text-slate-800 dark:text-gray-200 transition-colors"><?= sanitize($v['codigo']) ?></td>
                <td class="py-3 px-4 text-slate-700 dark:text-gray-300 transition-colors"><?= sanitize($v['tipo']) ?> <span class="text-slate-500 dark:text-gray-500 transition-colors text-xs ml-1"><?= sanitize($v['modelo']) ?></span></td>
                <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs font-mono"><?= sanitize($v['placa_militar']) ?></td>
                <td class="py-3 px-4 text-slate-500 dark:text-gray-400 transition-colors text-xs"><?= sanitize($v['unidad_asignada']) ?></td>
                <td class="py-3 px-4 text-center">
                    <?php if ($v['estado'] === 'operativo'): ?>
                        <span class="px-2 py-0.5 bg-emerald-100 dark:bg-green-900/40 text-emerald-600 dark:text-green-400 rounded text-xs">Operativo</span>
                    <?php elseif ($v['estado'] === 'mantenimiento'): ?>
                        <span class="px-2 py-0.5 bg-yellow-900/40 text-yellow-400 rounded text-xs">Mantenimiento</span>
                    <?php else: ?>
                        <span class="px-2 py-0.5 bg-red-900/40 text-red-600 dark:text-red-400 rounded text-xs">Inoperativo</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Nuevo Vehículo -->
<div id="modalNew" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white dark:bg-steel-950 transition-colors border border-slate-300 dark:border-steel-700 rounded-xl w-full max-w-md overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-steel-800 flex justify-between items-center bg-white dark:bg-steel-900/50 transition-colors">
            <h3 class="text-lg font-bold text-slate-800 dark:text-gray-200 transition-colors">Registrar Vehículo</h3>
            <button onclick="document.getElementById('modalNew').classList.add('hidden')" class="text-slate-500 dark:text-gray-500 transition-colors hover:text-white">&times;</button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="create">
            
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Código ID *</label>
                <input type="text" name="codigo" required placeholder="Ej: BLD-006" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Tipo *</label>
                    <input type="text" name="tipo" required placeholder="Ej: Tanque, APC" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                </div>
                <div>
                    <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Modelo</label>
                    <input type="text" name="modelo" placeholder="Ej: T-72B1" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                </div>
            </div>
            
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Placa Militar</label>
                <input type="text" name="placa_militar" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Unidad Asignada</label>
                <input type="text" name="unidad_asignada" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>

            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Estado</label>
                <select name="estado" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                    <option value="operativo">Operativo</option>
                    <option value="mantenimiento">En Mantenimiento</option>
                    <option value="inoperativo">Inoperativo</option>
                </select>
            </div>
            
            <div class="pt-4 flex justify-end">
                <button type="submit" class="px-6 py-2 bg-military-700 hover:bg-military-600 text-white rounded-lg text-sm font-medium transition">Guardar Vehículo</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
