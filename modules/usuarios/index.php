<?php
/**
 * CEMANBLIND - Gestión de Usuarios
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMINISTRADOR);

$db = getDB();

$search = $_GET['search'] ?? '';
$where = "1=1";
$params = [];

if ($search) {
    $where .= " AND (cedula LIKE :s OR nombre_completo LIKE :s)";
    $params[':s'] = "%{$search}%";
}

$stmt = $db->prepare("SELECT * FROM usuarios WHERE $where ORDER BY activo DESC, rol DESC, nombre_completo ASC");
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

include __DIR__ . '/../../includes/header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Usuarios</h1>
        <p class="text-sm text-slate-500 dark:text-gray-500 transition-colors">Gestión de acceso y roles</p>
    </div>
    <a href="crear.php" class="px-4 py-2 bg-military-700 hover:bg-military-600 text-white rounded-lg text-sm font-medium transition">
        + Nuevo Usuario
    </a>
</div>

<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-4 mb-6">
    <form method="GET" class="flex gap-4 max-w-md">
        <input type="text" name="search" value="<?= sanitize($search) ?>" placeholder="Buscar por cédula o nombre..."
            class="flex-1 bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        <button type="submit" class="bg-military-700 hover:bg-military-600 text-white rounded-lg px-4 py-2 text-sm transition">Buscar</button>
    </form>
</div>

<div class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-300 dark:border-steel-700 bg-slate-100 dark:bg-steel-900/80">
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Cédula</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Nombre</th>
                    <th class="text-left py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Grado/Cargo</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Rol</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Estado</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Último Acceso</th>
                    <th class="text-center py-3 px-4 text-xs text-slate-500 dark:text-gray-500 transition-colors uppercase">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr class="border-b border-slate-100 dark:border-steel-800/50 hover:bg-slate-50/50 dark:bg-steel-800/30 transition <?= !$u['activo'] ? 'opacity-50' : '' ?>">
                    <td class="py-3 px-4 font-mono text-xs text-slate-700 dark:text-gray-300 transition-colors"><?= sanitize($u['cedula']) ?></td>
                    <td class="py-3 px-4 text-slate-800 dark:text-gray-200 transition-colors font-medium"><?= sanitize($u['nombre_completo']) ?></td>
                    <td class="py-3 px-4">
                        <div class="text-slate-700 dark:text-gray-300 transition-colors"><?= sanitize($u['grado']) ?></div>
                        <div class="text-xs text-slate-500 dark:text-gray-500 transition-colors"><?= sanitize($u['cargo']) ?></div>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <span class="px-2 py-1 bg-white dark:bg-steel-800 transition-colors text-slate-700 dark:text-gray-300 transition-colors rounded text-xs border border-slate-300 dark:border-steel-700">
                            <?= ROLES_MAP[$u['rol']] ?? 'Desc' ?>
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($u['activo']): ?>
                            <span class="px-2 py-0.5 bg-emerald-100 dark:bg-green-900/40 text-emerald-600 dark:text-green-400 rounded text-xs">Activo</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 bg-red-900/40 text-red-600 dark:text-red-400 rounded text-xs">Inactivo</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4 text-center text-xs text-slate-500 dark:text-gray-500 transition-colors">
                        <?= $u['ultimo_acceso'] ? formatDate($u['ultimo_acceso']) : 'Nunca' ?>
                    </td>
                    <td class="py-3 px-4 text-center">
                        <?php if ($u['id'] !== $_SESSION['user_id']): // Evitar editarse a sí mismo aquí para no perder acceso ?>
                            <?php if (hasRole(ROLE_SUPERADMIN) || $u['rol'] < ROLE_SUPERADMIN): ?>
                                <a href="editar.php?id=<?= $u['id'] ?>" class="text-blue-600 dark:text-blue-400 hover:text-blue-300 text-xs">Editar</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-xs text-slate-500 dark:text-gray-500 transition-colors">(Tú)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
