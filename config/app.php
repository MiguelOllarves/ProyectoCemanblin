<?php
/**
 * ============================================================================
 * CEMABLN - Centro de Mantenimiento de Blindados
 * Archivo de Configuración Global
 * ============================================================================
 * 
 * Este archivo centraliza todas las constantes y configuraciones del sistema.
 * Modifica estos valores según el entorno de despliegue (desarrollo/producción).
 * 
 * @author   CEMABLN Dev Team
 * @version  1.0.0
 * @since    2026-05-28
 */

// ── Modo de la aplicación ──────────────────────────────────────────────────
// Cambiar a false en producción para ocultar errores detallados
define('APP_DEBUG', true);

// ── Información de la aplicación ───────────────────────────────────────────
define('APP_NAME', 'CEMABLN');
define('APP_FULL_NAME', 'Centro de Mantenimiento de Blindados');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Sistema de Gestión de Inventario y Trazabilidad');

// ── Rutas del sistema ──────────────────────────────────────────────────────
// BASE_PATH: Ruta absoluta en el filesystem del servidor
define('BASE_PATH', dirname(__DIR__));

// BASE_URL: URL base para enlaces y redirecciones (ajustar según entorno)
// En servidor local PHP built-in: 'http://localhost:8080'
// En XAMPP: 'http://localhost/ProyectoNavas'
define('BASE_URL', 'http://localhost:8080');

// ── Configuración de Base de Datos ─────────────────────────────────────────
// Ruta al archivo SQLite. Se almacena en /database/ por convención
define('DB_PATH', BASE_PATH . '/database/cemabln.sqlite');

// ── Configuración de Sesiones ──────────────────────────────────────────────
// Tiempo máximo de inactividad antes de cerrar sesión automáticamente (segundos)
// 1800 = 30 minutos
define('SESSION_LIFETIME', 1800);

// Nombre de la cookie de sesión (evitar nombres genéricos por seguridad)
define('SESSION_NAME', 'CEMABLN_SESSID');

// ── Roles del Sistema (RBAC) ──────────────────────────────────────────────
// Definición jerárquica de roles. El nivel numérico determina el acceso:
// Mayor número = Mayor privilegio
define('ROLE_USUARIO', 1);       // Solo lectura y operaciones básicas
define('ROLE_SUPERVISOR', 2);    // Puede aprobar despachos y ver reportes
define('ROLE_ADMINISTRADOR', 3); // Gestión completa de inventario y usuarios
define('ROLE_SUPERADMIN', 4);    // Acceso total, incluye auditoría y configuración

// Mapa de roles para UI (nombre legible)
define('ROLES_MAP', [
    ROLE_USUARIO       => 'Usuario',
    ROLE_SUPERVISOR    => 'Supervisor',
    ROLE_ADMINISTRADOR => 'Administrador',
    ROLE_SUPERADMIN    => 'Superadmin',
]);

// ── Configuración de Inventario ────────────────────────────────────────────
// Umbral para alertas de stock bajo (cuando el stock cae por debajo de este valor)
define('STOCK_ALERT_THRESHOLD', 10);

// Días antes del vencimiento para generar alerta
define('EXPIRY_ALERT_DAYS', 30);

// ── Configuración de Paginación ────────────────────────────────────────────
define('ITEMS_PER_PAGE', 20);

// ── Zona Horaria ───────────────────────────────────────────────────────────
date_default_timezone_set('America/Caracas');

// ── Configuración de Errores (basada en APP_DEBUG) ─────────────────────────
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', BASE_PATH . '/database/error.log');
}
