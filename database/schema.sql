-- ============================================================================
-- CEMANBLIND - Centro de Mantenimiento de Blindados
-- Script de Creación de Base de Datos (SQLite)
-- ============================================================================
--
-- Este script crea todas las tablas necesarias para el funcionamiento del
-- sistema. Las tablas están diseñadas con integridad referencial y campos
-- de auditoría incorporados.
--
-- Ejecución: Este script se ejecuta automáticamente al inicializar el sistema
-- a través de database/db.php si la base de datos no existe.
--
-- @version  1.0.0
-- @since    2026-05-28
-- ============================================================================

-- ── Habilitar claves foráneas en SQLite ────────────────────────────────────
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;

-- ============================================================================
-- TABLA: usuarios
-- Almacena las credenciales y datos de los usuarios del sistema.
-- El campo 'cedula' es el identificador único de login.
-- ============================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    cedula          TEXT    NOT NULL UNIQUE,              -- Número de cédula (login)
    password_hash   TEXT    NOT NULL,                      -- Hash bcrypt de la contraseña
    nombre_completo TEXT    NOT NULL,                      -- Nombre y apellido
    grado           TEXT    DEFAULT '',                    -- Grado militar (Tte, Cap, My, etc.)
    cargo           TEXT    DEFAULT '',                    -- Cargo en la unidad
    rol             INTEGER NOT NULL DEFAULT 1,            -- 1=Usuario, 2=Supervisor, 3=Admin, 4=Superadmin
    activo          INTEGER NOT NULL DEFAULT 1,            -- 1=Activo, 0=Inactivo (soft delete)
    ultimo_acceso   TEXT    DEFAULT NULL,                  -- Timestamp del último login exitoso
    created_at      TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- ============================================================================
-- TABLA: categorias
-- Categorías para organizar los productos del inventario.
-- Ejemplo: "Repuestos Motor", "Filtros", "Lubricantes", "Neumáticos"
-- ============================================================================
CREATE TABLE IF NOT EXISTS categorias (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre      TEXT    NOT NULL UNIQUE,
    descripcion TEXT    DEFAULT '',
    activo      INTEGER NOT NULL DEFAULT 1,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- ============================================================================
-- TABLA: productos
-- Inventario maestro de productos/repuestos/insumos.
-- Cada producto tiene un stock actual que se actualiza con cada movimiento.
-- ============================================================================
CREATE TABLE IF NOT EXISTS productos (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo             TEXT    NOT NULL UNIQUE,             -- Código interno del producto (ej: REP-001)
    nombre             TEXT    NOT NULL,                    -- Nombre descriptivo
    descripcion        TEXT    DEFAULT '',                  -- Descripción detallada
    categoria_id       INTEGER DEFAULT NULL,               -- FK a categorias
    unidad_medida      TEXT    NOT NULL DEFAULT 'UND',     -- UND, LT, KG, MT, GAL, etc.
    stock_actual       INTEGER NOT NULL DEFAULT 0,         -- Cantidad actual en almacén
    stock_minimo       INTEGER NOT NULL DEFAULT 5,         -- Umbral para alerta de stock bajo
    precio_unitario    REAL    DEFAULT 0.00,               -- Precio referencial unitario
    ubicacion_almacen  TEXT    DEFAULT '',                  -- Ubicación física (Estante, Rack, etc.)
    fecha_vencimiento  TEXT    DEFAULT NULL,                -- Fecha de vencimiento (si aplica)
    numero_parte       TEXT    DEFAULT '',                  -- Número de parte del fabricante
    activo             INTEGER NOT NULL DEFAULT 1,         -- 1=Activo, 0=Dado de baja
    created_at         TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at         TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- ============================================================================
-- TABLA: vehiculos
-- Registro de vehículos blindados asignados al centro de mantenimiento.
-- Cada movimiento de salida debe asociarse a un vehículo específico.
-- ============================================================================
CREATE TABLE IF NOT EXISTS vehiculos (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo          TEXT    NOT NULL UNIQUE,                -- ID único del vehículo (ej: BLD-001)
    tipo            TEXT    NOT NULL,                       -- Tipo: Tanque, APC, IFV, etc.
    modelo          TEXT    DEFAULT '',                     -- Modelo específico
    placa_militar   TEXT    DEFAULT '',                     -- Placa militar asignada
    unidad_asignada TEXT    DEFAULT '',                     -- Unidad/Batallón asignado
    estado          TEXT    NOT NULL DEFAULT 'operativo',   -- operativo, mantenimiento, inoperativo
    observaciones   TEXT    DEFAULT '',
    activo          INTEGER NOT NULL DEFAULT 1,
    created_at      TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    updated_at      TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- ============================================================================
-- TABLA: movimientos
-- Registro de TODAS las entradas y salidas del inventario.
-- Esta es la tabla CORE de trazabilidad del sistema.
--
-- tipo_movimiento: 'entrada' | 'salida'
-- Para salidas: vehiculo_id y destino son OBLIGATORIOS
-- ============================================================================
CREATE TABLE IF NOT EXISTS movimientos (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo_movimiento   TEXT    NOT NULL CHECK(tipo_movimiento IN ('entrada', 'salida')),
    producto_id       INTEGER NOT NULL,                    -- FK al producto
    cantidad          INTEGER NOT NULL CHECK(cantidad > 0),-- Cantidad movida
    vehiculo_id       INTEGER DEFAULT NULL,                -- FK al vehículo (obligatorio en salidas)
    destino           TEXT    DEFAULT NULL,                 -- Destino del repuesto (obligatorio en salidas)
    numero_vale       TEXT    DEFAULT NULL,                 -- Número de vale generado (para salidas)
    observaciones     TEXT    DEFAULT '',                   -- Notas adicionales
    usuario_id        INTEGER NOT NULL,                    -- Quién registró el movimiento
    fecha_movimiento  TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    created_at        TEXT    NOT NULL DEFAULT (datetime('now', 'localtime')),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id) ON DELETE RESTRICT
);

-- ============================================================================
-- TABLA: logs_auditoria
-- Registro INMUTABLE de cada acción realizada en el sistema.
-- Esta tabla NO debe tener operaciones UPDATE ni DELETE en producción.
--
-- Campos obligatorios: usuario, acción, tabla afectada, IP del cliente.
-- El campo 'datos' almacena un JSON con los detalles de la operación.
-- ============================================================================
CREATE TABLE IF NOT EXISTS logs_auditoria (
    id              INTEGER PRIMARY KEY AUTOINCREMENT,
    usuario_id      INTEGER DEFAULT NULL,                  -- FK al usuario (NULL = sistema/anónimo)
    usuario_nombre  TEXT    DEFAULT 'Sistema',             -- Nombre capturado al momento del log
    accion          TEXT    NOT NULL,                       -- CREATE, READ, UPDATE, DELETE, LOGIN, LOGOUT, etc.
    tabla_afectada  TEXT    DEFAULT NULL,                   -- Tabla sobre la que se realizó la acción
    registro_id     INTEGER DEFAULT NULL,                  -- ID del registro afectado
    datos           TEXT    DEFAULT NULL,                   -- JSON con detalles (antes/después)
    ip_cliente      TEXT    DEFAULT NULL,                   -- Dirección IP del cliente
    user_agent      TEXT    DEFAULT NULL,                   -- User-Agent del navegador
    created_at      TEXT    NOT NULL DEFAULT (datetime('now', 'localtime'))
);

-- ============================================================================
-- ÍNDICES para optimizar consultas frecuentes
-- ============================================================================
CREATE INDEX IF NOT EXISTS idx_productos_codigo       ON productos(codigo);
CREATE INDEX IF NOT EXISTS idx_productos_categoria    ON productos(categoria_id);
CREATE INDEX IF NOT EXISTS idx_productos_stock        ON productos(stock_actual);
CREATE INDEX IF NOT EXISTS idx_movimientos_tipo       ON movimientos(tipo_movimiento);
CREATE INDEX IF NOT EXISTS idx_movimientos_producto   ON movimientos(producto_id);
CREATE INDEX IF NOT EXISTS idx_movimientos_vehiculo   ON movimientos(vehiculo_id);
CREATE INDEX IF NOT EXISTS idx_movimientos_fecha      ON movimientos(fecha_movimiento);
CREATE INDEX IF NOT EXISTS idx_movimientos_usuario    ON movimientos(usuario_id);
CREATE INDEX IF NOT EXISTS idx_logs_usuario           ON logs_auditoria(usuario_id);
CREATE INDEX IF NOT EXISTS idx_logs_accion            ON logs_auditoria(accion);
CREATE INDEX IF NOT EXISTS idx_logs_fecha             ON logs_auditoria(created_at);
CREATE INDEX IF NOT EXISTS idx_vehiculos_codigo       ON vehiculos(codigo);
CREATE INDEX IF NOT EXISTS idx_usuarios_cedula        ON usuarios(cedula);
