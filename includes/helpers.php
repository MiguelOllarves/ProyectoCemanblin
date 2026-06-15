<?php
/**
 * ============================================================================
 * CEMABLN - Funciones Auxiliares (Helpers)
 * ============================================================================
 * 
 * Funciones utilitarias reutilizables en todo el sistema:
 * sanitización, formato, paginación, generación de códigos.
 * 
 * @author   CEMABLN Dev Team
 * @version  1.0.0
 */

/**
 * Sanitiza una cadena para prevenir XSS.
 */
function sanitize(string $input): string
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige a una URL y termina la ejecución.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Establece un mensaje flash en sesión (para mostrar tras redirección).
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Obtiene y elimina el mensaje flash de la sesión.
 */
function getFlash(): ?array
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Renderiza un mensaje flash como HTML.
 */
function renderFlash(): string
{
    $flash = getFlash();
    if (!$flash) return '';

    $colors = [
        'success' => 'bg-green-900/50 border-green-500 text-green-300',
        'error'   => 'bg-red-900/50 border-red-500 text-red-300',
        'warning' => 'bg-yellow-900/50 border-yellow-500 text-yellow-300',
        'info'    => 'bg-blue-900/50 border-blue-500 text-blue-300',
    ];
    $colorClass = $colors[$flash['type']] ?? $colors['info'];

    return '<div class="border-l-4 p-4 mb-4 rounded ' . $colorClass . '" role="alert">
        <p>' . sanitize($flash['message']) . '</p>
    </div>';
}

/**
 * Genera un número de vale único para despachos.
 * Formato: VALE-YYYYMMDD-XXXX (secuencial del día)
 */
function generateValeNumber(): string
{
    $db = getDB();
    $today = date('Ymd');
    $prefix = "VALE-{$today}-";

    $stmt = $db->prepare("SELECT COUNT(*) FROM movimientos WHERE numero_vale LIKE :prefix AND tipo_movimiento = 'salida'");
    $stmt->execute([':prefix' => $prefix . '%']);
    $count = (int) $stmt->fetchColumn();

    return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
}

/**
 * Formatea una fecha para mostrar en la UI.
 */
function formatDate(?string $date, string $format = 'd/m/Y H:i'): string
{
    if (!$date) return 'N/A';
    try {
        return (new DateTime($date))->format($format);
    } catch (Exception $e) {
        return $date;
    }
}

/**
 * Retorna la expresión SQL para la fecha/hora actual según el driver activo.
 * Uso: "UPDATE tabla SET updated_at = " . dbNow() . " WHERE id = :id"
 */
function dbNow(): string
{
    // Asegurarse de que getDB() haya sido llamado al menos una vez para definir DB_DRIVER
    getDB();
    return (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') ? 'NOW()' : "datetime('now', 'localtime')";
}

/**
 * Retorna la expresión SQL para la fecha actual (sin hora) según el driver activo.
 */
function dbDate(): string
{
    getDB();
    return (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') ? 'CURRENT_DATE' : "date('now')";
}

/**
 * Retorna la expresión SQL para formatear una columna como 'YYYY-MM'
 */
function dbMonthYear(string $column): string
{
    getDB();
    return (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') 
        ? "TO_CHAR({$column}, 'YYYY-MM')" 
        : "strftime('%Y-%m', {$column})";
}

/**
 * Retorna la expresión SQL para el mes y año actual 'YYYY-MM'
 */
function dbCurrentMonthYear(): string
{
    getDB();
    return (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') 
        ? "TO_CHAR(NOW(), 'YYYY-MM')" 
        : "strftime('%Y-%m', 'now', 'localtime')";
}

/**
 * Retorna la expresión SQL para calcular "X días atrás"
 */
function dbDaysAgo(int $days): string
{
    getDB();
    return (defined('DB_DRIVER') && DB_DRIVER === 'pgsql') 
        ? "CURRENT_DATE - INTERVAL '{$days} days'" 
        : "date('now', '-{$days} days', 'localtime')";
}

/**
 * Genera HTML de paginación.
 */
function renderPagination(int $currentPage, int $totalPages, string $baseUrl): string
{
    if ($totalPages <= 1) return '';

    $html = '<nav class="flex justify-center mt-6"><ul class="flex space-x-1">';

    // Anterior
    if ($currentPage > 1) {
        $html .= '<li><a href="' . $baseUrl . '&page=' . ($currentPage - 1) . '" class="px-3 py-2 bg-gray-700 text-gray-300 rounded hover:bg-gray-600">&laquo;</a></li>';
    }

    // Páginas
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? 'bg-green-700 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600';
        $html .= '<li><a href="' . $baseUrl . '&page=' . $i . '" class="px-3 py-2 rounded ' . $active . '">' . $i . '</a></li>';
    }

    // Siguiente
    if ($currentPage < $totalPages) {
        $html .= '<li><a href="' . $baseUrl . '&page=' . ($currentPage + 1) . '" class="px-3 py-2 bg-gray-700 text-gray-300 rounded hover:bg-gray-600">&raquo;</a></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Obtiene productos con stock bajo.
 */
function getProductosStockBajo(): array
{
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM productos WHERE activo = 1 AND stock_actual <= stock_minimo ORDER BY stock_actual ASC");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Obtiene productos próximos a vencer.
 */
function getProductosProximosVencer(): array
{
    $db = getDB();
    $fechaLimite = date('Y-m-d', strtotime('+' . EXPIRY_ALERT_DAYS . ' days'));
    $stmt = $db->prepare("
        SELECT * FROM productos 
        WHERE activo = 1 AND fecha_vencimiento IS NOT NULL AND fecha_vencimiento <= :fecha AND fecha_vencimiento >= CURRENT_DATE
        ORDER BY fecha_vencimiento ASC
    ");
    $stmt->execute([':fecha' => $fechaLimite]);
    return $stmt->fetchAll();
}

/**
 * Genera token CSRF y lo almacena en sesión.
 */
function generateCSRFToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida un token CSRF recibido en un formulario.
 */
function validateCSRFToken(string $token): bool
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Renderiza un campo CSRF hidden para formularios.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}
