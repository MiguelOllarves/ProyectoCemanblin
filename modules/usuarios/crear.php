<?php
/**
 * CEMANBLIND - Crear Usuario
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMINISTRADOR);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de seguridad inválido.');
        redirect(BASE_URL . '/modules/usuarios/crear.php');
    }

    $cedula = trim($_POST['cedula'] ?? '');
    $nombre = trim($_POST['nombre_completo'] ?? '');
    $grado = trim($_POST['grado'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $rol = (int)($_POST['rol'] ?? ROLE_USUARIO);
    $password = $_POST['password'] ?? '';

    // Solo un SUPERADMIN puede crear otro SUPERADMIN
    if ($rol === ROLE_SUPERADMIN && !hasRole(ROLE_SUPERADMIN)) {
        $rol = ROLE_ADMINISTRADOR;
    }

    if (empty($cedula) || empty($nombre) || empty($password)) {
        setFlash('error', 'Cédula, nombre y contraseña son obligatorios.');
        redirect(BASE_URL . '/modules/usuarios/crear.php');
    }

    $db = getDB();
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    try {
        $stmt = $db->prepare("
            INSERT INTO usuarios (cedula, password_hash, nombre_completo, grado, cargo, rol)
            VALUES (:ced, :hash, :nom, :gra, :car, :rol)
        ");
        $stmt->execute([
            ':ced' => $cedula, ':hash' => $hash, ':nom' => $nombre,
            ':gra' => $grado, ':car' => $cargo, ':rol' => $rol
        ]);

        $newId = 0;
        try {
            $seq = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'usuarios_id_seq' : null;
            $newId = (int) $db->lastInsertId($seq);
        } catch (Exception $e) {}
        logAudit('CREATE', 'usuarios', $newId, ['cedula' => $cedula, 'rol' => ROLES_MAP[$rol]]);
        setFlash('success', 'Usuario creado exitosamente.');
        redirect(BASE_URL . '/modules/usuarios/index.php');
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'unique') !== false || stripos($e->getMessage(), 'duplicate') !== false) {
            setFlash('error', 'La cédula ya está registrada.');
        } else {
            setFlash('error', 'Error: ' . $e->getMessage());
        }
        redirect(BASE_URL . '/modules/usuarios/crear.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php" class="text-slate-500 dark:text-gray-400 transition-colors hover:text-slate-800 dark:text-gray-200 transition-colors transition">← Volver</a>
        <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Nuevo Usuario</h1>
    </div>

    <form method="POST" class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 space-y-4">
        <?= csrfField() ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Cédula *</label>
                <input type="text" name="cedula" required class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Nombre Completo *</label>
                <input type="text" name="nombre_completo" required class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Grado Militar</label>
                <input type="text" name="grado" placeholder="Ej: 1Tte, Cap, S1" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Cargo</label>
                <input type="text" name="cargo" placeholder="Ej: Jefe de Almacén" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Rol en Sistema *</label>
                <select name="rol" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                    <option value="<?= ROLE_USUARIO ?>"><?= ROLES_MAP[ROLE_USUARIO] ?></option>
                    <option value="<?= ROLE_SUPERVISOR ?>"><?= ROLES_MAP[ROLE_SUPERVISOR] ?></option>
                    <option value="<?= ROLE_ADMINISTRADOR ?>"><?= ROLES_MAP[ROLE_ADMINISTRADOR] ?></option>
                    <?php if (hasRole(ROLE_SUPERADMIN)): ?>
                    <option value="<?= ROLE_SUPERADMIN ?>"><?= ROLES_MAP[ROLE_SUPERADMIN] ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Contraseña *</label>
                <input type="password" name="password" required class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-military-700 hover:bg-military-600 text-white rounded-lg text-sm font-medium transition">Crear Usuario</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
