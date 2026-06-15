<?php
/**
 * ============================================================================
 * CEMABLN - Sistema de Auditoría Inmutable
 * ============================================================================
 * 
 * Registra cada acción del sistema de forma inmutable.
 * Los registros incluyen: quién, qué, cuándo, IP.
 * 
 * @author   CEMABLN Dev Team
 * @version  1.0.0
 */

require_once __DIR__ . '/../database/db.php';

/**
 * Registra una acción en el log de auditoría.
 * 
 * @param string      $accion         Tipo de acción
 * @param string|null $tablaAfectada  Tabla afectada
 * @param int|null    $registroId     ID del registro
 * @param array|null  $datos          Datos adicionales (JSON)
 */
function logAudit(string $accion, ?string $tablaAfectada = null, ?int $registroId = null, ?array $datos = null): void
{
    try {
        $db = getDB();
        $usuarioId = $_SESSION['user_id'] ?? null;
        $usuarioNombre = $_SESSION['user_nombre'] ?? 'Sistema';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);
        $datosJson = $datos ? json_encode($datos, JSON_UNESCAPED_UNICODE) : null;

        $stmt = $db->prepare("
            INSERT INTO logs_auditoria 
            (usuario_id, usuario_nombre, accion, tabla_afectada, registro_id, datos, ip_cliente, user_agent) 
            VALUES (:uid, :uname, :accion, :tabla, :rid, :datos, :ip, :ua)
        ");
        $stmt->execute([
            ':uid'    => $usuarioId,
            ':uname'  => $usuarioNombre,
            ':accion' => strtoupper($accion),
            ':tabla'  => $tablaAfectada,
            ':rid'    => $registroId,
            ':datos'  => $datosJson,
            ':ip'     => $ip,
            ':ua'     => $ua
        ]);
    } catch (PDOException $e) {
        error_log("Error en auditoría: " . $e->getMessage());
    }
}

/**
 * Obtiene logs de auditoría con paginación y filtros.
 */
function getAuditLogs(array $filters = [], int $page = 1, int $perPage = 20): array
{
    $db = getDB();
    $where = [];
    $params = [];

    if (!empty($filters['usuario_id'])) {
        $where[] = "usuario_id = :uid";
        $params[':uid'] = $filters['usuario_id'];
    }
    if (!empty($filters['accion'])) {
        $where[] = "accion = :accion";
        $params[':accion'] = strtoupper($filters['accion']);
    }
    if (!empty($filters['fecha_desde'])) {
        $where[] = "created_at >= :desde";
        $params[':desde'] = $filters['fecha_desde'] . ' 00:00:00';
    }
    if (!empty($filters['fecha_hasta'])) {
        $where[] = "created_at <= :hasta";
        $params[':hasta'] = $filters['fecha_hasta'] . ' 23:59:59';
    }

    $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $countStmt = $db->prepare("SELECT COUNT(*) FROM logs_auditoria $whereClause");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $stmt = $db->prepare("SELECT * FROM logs_auditoria $whereClause ORDER BY created_at DESC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
    $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return ['data' => $stmt->fetchAll(), 'total' => $total, 'pages' => (int) ceil($total / $perPage)];
}
