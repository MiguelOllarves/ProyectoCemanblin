<?php
/**
 * ============================================================================
 * CEMABLN - Conexión a Base de Datos (PDO + SQLite)
 * ============================================================================
 * 
 * Este archivo gestiona la conexión singleton a la base de datos SQLite.
 * Utiliza PDO para consultas preparadas (protección contra SQL Injection).
 * 
 * Si la base de datos no existe, se crea automáticamente ejecutando schema.sql.
 * 
 * Uso:
 *   require_once __DIR__ . '/../database/db.php';
 *   $db = getDB(); // Retorna instancia PDO
 * 
 * @author   CEMABLN Dev Team
 * @version  1.0.0
 */

require_once __DIR__ . '/../config/app.php';

/**
 * Obtiene una conexión singleton a la base de datos SQLite.
 * 
 * Características de seguridad:
 * - PDO::ATTR_ERRMODE = EXCEPTION: Lanza excepciones en errores SQL
 * - PDO::ATTR_DEFAULT_FETCH_MODE = ASSOC: Retorna arrays asociativos
 * - PRAGMA foreign_keys = ON: Habilita integridad referencial
 * - PRAGMA journal_mode = WAL: Mejor rendimiento en escritura concurrente
 * 
 * @return PDO Instancia de conexión PDO
 * @throws PDOException Si la conexión falla
 */
function getDB(): PDO
{
    // Patrón Singleton: reutiliza la misma conexión durante todo el request
    static $pdo = null;

    if ($pdo === null) {
        $dbUrl = getenv('DATABASE_URL');
        $isPgsql = false;

        // 1. Intentar conexión a PostgreSQL (Supabase/Vercel)
        if ($dbUrl && (strpos($dbUrl, 'postgres://') === 0 || strpos($dbUrl, 'postgresql://') === 0)) {
            $isPgsql = true;
            $parsedUrl = parse_url($dbUrl);
            $host = $parsedUrl['host'];
            $port = $parsedUrl['port'] ?? 5432;
            $user = $parsedUrl['user'] ?? '';
            $pass = $parsedUrl['pass'] ?? '';
            $dbname = ltrim($parsedUrl['path'], '/');
            
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

            try {
                $pdo = new PDO($dsn, $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

                // Verificar si la tabla usuarios existe (indicador de BD inicializada)
                $stmt = $pdo->query("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'usuarios'");
                $dbExists = (bool) $stmt->fetch();

                if (!$dbExists) {
                    initializeDatabase($pdo, 'pgsql');
                }
                if (!defined('DB_DRIVER')) define('DB_DRIVER', 'pgsql');
            } catch (PDOException $e) {
                handleConnectionError($e);
            }
        } 
        // 2. Fallback a SQLite local
        else {
            $dbExists = file_exists(DB_PATH);

            try {
                $pdo = new PDO('sqlite:' . DB_PATH);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

                // Pragmas de SQLite
                $pdo->exec('PRAGMA foreign_keys = ON');
                $pdo->exec('PRAGMA journal_mode = WAL');
                $pdo->exec('PRAGMA busy_timeout = 5000');

                if (!$dbExists) {
                    initializeDatabase($pdo, 'sqlite');
                }
                if (!defined('DB_DRIVER')) define('DB_DRIVER', 'sqlite');
            } catch (PDOException $e) {
                handleConnectionError($e);
            }
        }
    }

    return $pdo;
}

function handleConnectionError(PDOException $e) {
    if (APP_DEBUG) {
        die("Error de conexión a BD: " . $e->getMessage());
    } else {
        error_log("Error de conexión a BD: " . $e->getMessage());
        die("Error interno del sistema. Contacte al administrador.");
    }
}

/**
 * Inicializa la base de datos ejecutando el script correspondiente
 * y creando los datos por defecto.
 * 
 * @param PDO $pdo Instancia de conexión
 * @param string $driver 'pgsql' o 'sqlite'
 * @return void
 */
function initializeDatabase(PDO $pdo, string $driver): void
{
    // Ejecutar el script de esquema correspondiente
    $schemaFile = $driver === 'pgsql' ? 'schema_pg.sql' : 'schema.sql';
    $schemaPath = __DIR__ . '/' . $schemaFile;

    if (!file_exists($schemaPath)) {
        die("Error crítico: No se encontró el archivo {$schemaFile}");
    }

    $schema = file_get_contents($schemaPath);
    $pdo->exec($schema);

    // ── Crear usuario Superadmin por defecto ───────────────────────────
    $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);

    // Usamos ON CONFLICT DO NOTHING para Postgres, o IGNORE para SQLite
    $ignoreClause = $driver === 'pgsql' ? 'ON CONFLICT DO NOTHING' : 'OR IGNORE';
    $conflictClause = $driver === 'pgsql' ? 'ON CONFLICT (cedula) DO NOTHING' : '';

    $stmt = $pdo->prepare("
        INSERT " . ($driver === 'sqlite' ? "OR IGNORE" : "") . " INTO usuarios (cedula, password_hash, nombre_completo, grado, cargo, rol) 
        VALUES (:cedula, :password, :nombre, :grado, :cargo, :rol)
        " . ($driver === 'pgsql' ? "ON CONFLICT (cedula) DO NOTHING" : "") . "
    ");

    $stmt->execute([
        ':cedula'   => '00000000',
        ':password' => $defaultPassword,
        ':nombre'   => 'Administrador del Sistema',
        ':grado'    => 'N/A',
        ':cargo'    => 'Superadministrador',
        ':rol'      => ROLE_SUPERADMIN
    ]);

    // ── Crear categorías por defecto ───────────────────────────────────
    $categorias = [
        ['Repuestos de Motor', 'Piezas y componentes del motor'],
        ['Filtros', 'Filtros de aceite, aire, combustible'],
        ['Lubricantes', 'Aceites, grasas y fluidos hidráulicos'],
        ['Neumáticos y Orugas', 'Neumáticos, bandas de rodamiento y orugas'],
        ['Sistema Eléctrico', 'Baterías, alternadores, cableado'],
        ['Sistema de Frenos', 'Pastillas, discos, líquido de frenos'],
        ['Carrocería y Blindaje', 'Paneles, placas de blindaje, cristales'],
        ['Armamento', 'Componentes de sistemas de armas'],
        ['Herramientas', 'Herramientas de mantenimiento'],
        ['Varios', 'Otros insumos y materiales'],
    ];

    $stmtCat = $pdo->prepare("
        INSERT " . ($driver === 'sqlite' ? "OR IGNORE" : "") . " INTO categorias (nombre, descripcion) 
        VALUES (?, ?)
        " . ($driver === 'pgsql' ? "ON CONFLICT (nombre) DO NOTHING" : "") . "
    ");
    foreach ($categorias as $cat) {
        $stmtCat->execute($cat);
    }

    // ── Crear vehículos de ejemplo ─────────────────────────────────────
    $vehiculos = [
        ['BLD-001', 'APC',    'BTR-80',    'MIL-0001', '1er Batallón', 'operativo'],
        ['BLD-002', 'Tanque', 'T-72B',     'MIL-0002', '1er Batallón', 'mantenimiento'],
        ['BLD-003', 'IFV',    'BMP-3',     'MIL-0003', '2do Batallón', 'operativo'],
        ['BLD-004', 'APC',    'BTR-80A',   'MIL-0004', '2do Batallón', 'operativo'],
        ['BLD-005', 'Tanque', 'AMX-30',    'MIL-0005', '3er Batallón', 'inoperativo'],
    ];

    $stmtVeh = $pdo->prepare("
        INSERT " . ($driver === 'sqlite' ? "OR IGNORE" : "") . " INTO vehiculos (codigo, tipo, modelo, placa_militar, unidad_asignada, estado) 
        VALUES (?, ?, ?, ?, ?, ?)
        " . ($driver === 'pgsql' ? "ON CONFLICT (codigo) DO NOTHING" : "") . "
    ");
    foreach ($vehiculos as $veh) {
        $stmtVeh->execute($veh);
    }

    // Registrar en auditoría la inicialización
    $pdo->exec("
        INSERT INTO logs_auditoria (usuario_nombre, accion, tabla_afectada, datos, ip_cliente) 
        VALUES ('Sistema', 'INIT', 'todas', 'Base de datos inicializada con datos por defecto', '127.0.0.1')
    ");
}

