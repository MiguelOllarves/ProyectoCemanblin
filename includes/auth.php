<?php
/**
 * ============================================================================
 * CEMABLN - Sistema de Autenticación y Control de Acceso (RBAC)
 * ============================================================================
 * 
 * Este archivo proporciona todas las funciones de autenticación:
 * - Inicio/cierre de sesión seguro
 * - Verificación de roles (middleware)
 * - Protección contra fijación de sesión
 * - Control de tiempo de inactividad
 * 
 * Uso en cada página protegida:
 *   require_once __DIR__ . '/../includes/auth.php';
 *   requireRole(ROLE_USUARIO); // Mínimo rol requerido
 * 
 * @author   CEMABLN Dev Team
 * @version  1.0.0
 */

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/audit.php';

/**
 * Inicia la sesión con configuraciones de seguridad.
 * Debe llamarse antes de cualquier output HTML.
 */
function initSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // Configuración segura de la cookie de sesión
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => false,   // Cambiar a true con HTTPS
            'httponly'  => true,    // Previene acceso desde JavaScript
            'samesite'  => 'Strict' // Protección CSRF básica
        ]);
        session_start();
    }
}

/**
 * Autentica un usuario con cédula y contraseña.
 * 
 * Proceso de seguridad:
 * 1. Busca usuario por cédula (prepared statement)
 * 2. Verifica que el usuario esté activo
 * 3. Compara password con hash bcrypt
 * 4. Regenera ID de sesión (anti session fixation)
 * 5. Almacena datos en sesión
 * 6. Registra en auditoría
 * 
 * @param string $cedula Número de cédula del usuario
 * @param string $password Contraseña en texto plano
 * @return array ['success' => bool, 'message' => string]
 */
function loginUser(string $cedula, string $password): array
{
    $db = getDB();

    // Buscar usuario por cédula (prepared statement = seguro contra SQLi)
    $stmt = $db->prepare("
        SELECT id, cedula, password_hash, nombre_completo, grado, cargo, rol, activo 
        FROM usuarios 
        WHERE cedula = :cedula
    ");
    $stmt->execute([':cedula' => trim($cedula)]);
    $user = $stmt->fetch();

    // ── Validaciones ───────────────────────────────────────────────────
    if (!$user) {
        // No revelar si el usuario existe o no (seguridad)
        logAudit('LOGIN_FAILED', 'usuarios', null, ['cedula' => $cedula, 'razon' => 'Usuario no encontrado']);
        return ['success' => false, 'message' => 'Credenciales inválidas.'];
    }

    if (!$user['activo']) {
        logAudit('LOGIN_FAILED', 'usuarios', $user['id'], ['cedula' => $cedula, 'razon' => 'Cuenta inactiva']);
        return ['success' => false, 'message' => 'Su cuenta ha sido desactivada. Contacte al administrador.'];
    }

    // Verificar contraseña con hash bcrypt
    if (!password_verify($password, $user['password_hash'])) {
        logAudit('LOGIN_FAILED', 'usuarios', $user['id'], ['cedula' => $cedula, 'razon' => 'Contraseña incorrecta']);
        return ['success' => false, 'message' => 'Credenciales inválidas.'];
    }

    // ── Login exitoso ──────────────────────────────────────────────────
    // Regenerar ID de sesión para prevenir Session Fixation
    session_regenerate_id(true);

    // Almacenar datos del usuario en sesión
    $_SESSION['user_id']        = $user['id'];
    $_SESSION['user_cedula']    = $user['cedula'];
    $_SESSION['user_nombre']    = $user['nombre_completo'];
    $_SESSION['user_grado']     = $user['grado'];
    $_SESSION['user_cargo']     = $user['cargo'];
    $_SESSION['user_rol']       = $user['rol'];
    $_SESSION['user_rol_name']  = ROLES_MAP[$user['rol']] ?? 'Desconocido';
    $_SESSION['last_activity']  = time();
    $_SESSION['ip_address']     = getClientIP();

    // Actualizar último acceso en BD
    $stmtUpdate = $db->prepare("UPDATE usuarios SET ultimo_acceso = " . dbNow() . " WHERE id = :id");
    $stmtUpdate->execute([':id' => $user['id']]);

    // Registrar login exitoso en auditoría
    logAudit('LOGIN_SUCCESS', 'usuarios', $user['id'], [
        'cedula' => $cedula,
        'rol'    => ROLES_MAP[$user['rol']]
    ]);

    return ['success' => true, 'message' => 'Bienvenido, ' . $user['grado'] . ' ' . $user['nombre_completo']];
}

/**
 * Cierra la sesión del usuario de forma segura.
 * Destruye completamente la sesión y la cookie.
 */
function logoutUser(): void
{
    initSession();

    if (isLoggedIn()) {
        logAudit('LOGOUT', 'usuarios', $_SESSION['user_id'] ?? null);
    }

    // Limpiar todas las variables de sesión
    $_SESSION = [];

    // Eliminar la cookie de sesión
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Destruir la sesión
    session_destroy();
}

/**
 * Verifica si hay un usuario autenticado en la sesión actual.
 * También valida el tiempo de inactividad.
 * 
 * @return bool True si el usuario está autenticado y activo
 */
function isLoggedIn(): bool
{
    initSession();

    // Verificar que existan las variables de sesión necesarias
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }

    // Verificar tiempo de inactividad
    if ((time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
        // Sesión expirada por inactividad
        logoutUser();
        return false;
    }

    // Actualizar timestamp de última actividad
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * MIDDLEWARE: Requiere que el usuario tenga al menos el rol especificado.
 * Si no cumple, redirige al login o muestra error 403.
 * 
 * Uso:
 *   requireRole(ROLE_ADMINISTRADOR); // Solo admin y superadmin
 *   requireRole(ROLE_USUARIO);       // Cualquier usuario autenticado
 * 
 * @param int $minimumRole Nivel mínimo de rol requerido
 * @return void Redirige si no cumple
 */
function requireRole(int $minimumRole = ROLE_USUARIO): void
{
    initSession();

    // Si no está autenticado, redirigir al login
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/index.php?error=session_expired');
        exit;
    }

    // Verificar que el rol del usuario sea suficiente
    if (($_SESSION['user_rol'] ?? 0) < $minimumRole) {
        // Registrar intento de acceso no autorizado
        logAudit('ACCESS_DENIED', null, null, [
            'rol_usuario'  => $_SESSION['user_rol'],
            'rol_requerido' => $minimumRole,
            'url'           => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ]);

        // Mostrar error 403
        http_response_code(403);
        include __DIR__ . '/../includes/header.php';
        echo '<div class="flex items-center justify-center min-h-[60vh]">';
        echo '<div class="text-center">';
        echo '<div class="text-6xl mb-4">🔒</div>';
        echo '<h1 class="text-2xl font-bold text-red-500 mb-2">Acceso Denegado</h1>';
        echo '<p class="text-gray-400">No tiene permisos suficientes para acceder a este recurso.</p>';
        echo '<p class="text-gray-500 text-sm mt-2">Rol requerido: ' . (ROLES_MAP[$minimumRole] ?? 'Desconocido') . '</p>';
        echo '<a href="' . BASE_URL . '/modules/dashboard/index.php" class="inline-block mt-4 px-4 py-2 bg-green-700 text-white rounded hover:bg-green-600 transition">Volver al Panel de control</a>';
        echo '</div></div>';
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

/**
 * Verifica si el usuario actual tiene al menos el rol indicado.
 * No redirige, solo retorna bool. Útil para mostrar/ocultar elementos en la UI.
 * 
 * @param int $role Rol a verificar
 * @return bool
 */
function hasRole(int $role): bool
{
    return isset($_SESSION['user_rol']) && $_SESSION['user_rol'] >= $role;
}

/**
 * Obtiene la IP real del cliente, considerando proxies.
 * 
 * @return string Dirección IP
 */
function getClientIP(): string
{
    // Orden de prioridad para detectar IP detrás de proxies
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];

    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            // X-Forwarded-For puede contener múltiples IPs separadas por coma
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return '0.0.0.0';
}

/**
 * Obtiene los datos del usuario actual desde la sesión.
 * 
 * @return array|null Datos del usuario o null si no autenticado
 */
function getCurrentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id'       => $_SESSION['user_id'],
        'cedula'   => $_SESSION['user_cedula'],
        'nombre'   => $_SESSION['user_nombre'],
        'grado'    => $_SESSION['user_grado'],
        'cargo'    => $_SESSION['user_cargo'],
        'rol'      => $_SESSION['user_rol'],
        'rol_name' => $_SESSION['user_rol_name'],
    ];
}
