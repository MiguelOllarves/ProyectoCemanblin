<?php
/**
 * CEMABLN - Editar Usuario
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMINISTRADOR);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { redirect(BASE_URL . '/modules/usuarios/index.php'); }

// No permitir editarse a sí mismo aquí
if ($id === $_SESSION['user_id']) {
    setFlash('warning', 'Para editar tu propio perfil, usa la opción de configuración de cuenta.');
    redirect(BASE_URL . '/modules/usuarios/index.php');
}

$stmt = $db->prepare("SELECT * FROM usuarios WHERE id = :id");
$stmt->execute([':id' => $id]);
$usuario = $stmt->fetch();

if (!$usuario) { setFlash('error', 'Usuario no encontrado.'); redirect(BASE_URL . '/modules/usuarios/index.php'); }

// Seguridad: Un Admin no puede editar a un Superadmin
if (!hasRole(ROLE_SUPERADMIN) && $usuario['rol'] >= ROLE_SUPERADMIN) {
    setFlash('error', 'No tienes permisos para editar a este usuario.');
    redirect(BASE_URL . '/modules/usuarios/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de seguridad inválido.');
        redirect(BASE_URL . "/modules/usuarios/editar.php?id={$id}");
    }

    $nombre = trim($_POST['nombre_completo'] ?? '');
    $grado = trim($_POST['grado'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $rol = (int)($_POST['rol'] ?? $usuario['rol']);
    $activo = isset($_POST['activo']) ? 1 : 0;
    $password = $_POST['password'] ?? '';

    if (!hasRole(ROLE_SUPERADMIN) && $rol >= ROLE_SUPERADMIN) {
        $rol = ROLE_ADMINISTRADOR;
    }

    try {
        $updateQuery = "UPDATE usuarios SET nombre_completo = :nom, grado = :gra, cargo = :car, rol = :rol, activo = :act, updated_at = datetime('now', 'localtime')";
        $params = [
            ':nom' => $nombre, ':gra' => $grado, ':car' => $cargo,
            ':rol' => $rol, ':act' => $activo, ':id' => $id
        ];

        if (!empty($password)) {
            $updateQuery .= ", password_hash = :hash";
            $params[':hash'] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        }
        $updateQuery .= " WHERE id = :id";

        $db->prepare($updateQuery)->execute($params);

        logAudit('UPDATE', 'usuarios', $id, ['nombre' => $nombre, 'rol' => ROLES_MAP[$rol], 'activo' => $activo]);
        setFlash('success', 'Usuario actualizado correctamente.');
        redirect(BASE_URL . '/modules/usuarios/index.php');
    } catch (PDOException $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
        redirect(BASE_URL . "/modules/usuarios/editar.php?id={$id}");
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php" class="text-slate-500 dark:text-gray-400 transition-colors hover:text-slate-800 dark:text-gray-200 transition-colors transition">← Volver</a>
        <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Editar Usuario: <?= sanitize($usuario['cedula']) ?></h1>
    </div>

    <form method="POST" class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 space-y-4">
        <?= csrfField() ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Cédula</label>
                <input type="text" disabled value="<?= sanitize($usuario['cedula']) ?>" class="w-full bg-slate-50 dark:bg-steel-900 border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-500 dark:text-gray-500 transition-colors cursor-not-allowed">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Nombre Completo *</label>
                <input type="text" name="nombre_completo" required value="<?= sanitize($usuario['nombre_completo']) ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Grado Militar</label>
                <input type="text" name="grado" value="<?= sanitize($usuario['grado']) ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Cargo</label>
                <input type="text" name="cargo" value="<?= sanitize($usuario['cargo']) ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Rol en Sistema *</label>
                <select name="rol" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                    <option value="<?= ROLE_USUARIO ?>" <?= $usuario['rol'] == ROLE_USUARIO ? 'selected' : '' ?>><?= ROLES_MAP[ROLE_USUARIO] ?></option>
                    <option value="<?= ROLE_SUPERVISOR ?>" <?= $usuario['rol'] == ROLE_SUPERVISOR ? 'selected' : '' ?>><?= ROLES_MAP[ROLE_SUPERVISOR] ?></option>
                    <option value="<?= ROLE_ADMINISTRADOR ?>" <?= $usuario['rol'] == ROLE_ADMINISTRADOR ? 'selected' : '' ?>><?= ROLES_MAP[ROLE_ADMINISTRADOR] ?></option>
                    <?php if (hasRole(ROLE_SUPERADMIN)): ?>
                    <option value="<?= ROLE_SUPERADMIN ?>" <?= $usuario['rol'] == ROLE_SUPERADMIN ? 'selected' : '' ?>><?= ROLES_MAP[ROLE_SUPERADMIN] ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Nueva Contraseña</label>
                <input type="password" name="password" placeholder="Dejar en blanco para no cambiar" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="flex items-center gap-2 mt-4 bg-slate-50 dark:bg-steel-800/50 p-3 rounded-lg border border-slate-200 dark:border-steel-700/50">
            <input type="checkbox" name="activo" id="activo" <?= $usuario['activo'] ? 'checked' : '' ?> class="w-4 h-4 text-military-600 bg-slate-50 dark:bg-steel-900 border-slate-300 dark:border-steel-700 rounded focus:ring-military-500 focus:ring-2">
            <label for="activo" class="text-sm font-medium text-slate-700 dark:text-gray-300 transition-colors">Usuario Activo (Puede iniciar sesión)</label>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-blue-700 hover:bg-blue-600 text-white rounded-lg text-sm font-medium transition">Guardar Cambios</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
