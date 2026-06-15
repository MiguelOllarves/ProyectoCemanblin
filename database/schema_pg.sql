-- ============================================================================
-- CEMABLN - Centro de Mantenimiento de Blindados
-- Script de Creación de Base de Datos (PostgreSQL)
-- ============================================================================

-- ============================================================================
-- TABLA: usuarios
-- ============================================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id              SERIAL PRIMARY KEY,
    cedula          TEXT    NOT NULL UNIQUE,
    password_hash   TEXT    NOT NULL,
    nombre_completo TEXT    NOT NULL,
    grado           TEXT    DEFAULT '',
    cargo           TEXT    DEFAULT '',
    rol             INTEGER NOT NULL DEFAULT 1,
    activo          INTEGER NOT NULL DEFAULT 1,
    ultimo_acceso   TIMESTAMP DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- TABLA: categorias
-- ============================================================================
CREATE TABLE IF NOT EXISTS categorias (
    id          SERIAL PRIMARY KEY,
    nombre      TEXT    NOT NULL UNIQUE,
    descripcion TEXT    DEFAULT '',
    activo      INTEGER NOT NULL DEFAULT 1,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- TABLA: productos
-- ============================================================================
CREATE TABLE IF NOT EXISTS productos (
    id                 SERIAL PRIMARY KEY,
    codigo             TEXT    NOT NULL UNIQUE,
    nombre             TEXT    NOT NULL,
    descripcion        TEXT    DEFAULT '',
    categoria_id       INTEGER DEFAULT NULL,
    unidad_medida      TEXT    NOT NULL DEFAULT 'UND',
    stock_actual       INTEGER NOT NULL DEFAULT 0,
    stock_minimo       INTEGER NOT NULL DEFAULT 5,
    precio_unitario    REAL    DEFAULT 0.00,
    ubicacion_almacen  TEXT    DEFAULT '',
    fecha_vencimiento  DATE    DEFAULT NULL,
    numero_parte       TEXT    DEFAULT '',
    activo             INTEGER NOT NULL DEFAULT 1,
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL
);

-- ============================================================================
-- TABLA: vehiculos
-- ============================================================================
CREATE TABLE IF NOT EXISTS vehiculos (
    id              SERIAL PRIMARY KEY,
    codigo          TEXT    NOT NULL UNIQUE,
    tipo            TEXT    NOT NULL,
    modelo          TEXT    DEFAULT '',
    placa_militar   TEXT    DEFAULT '',
    unidad_asignada TEXT    DEFAULT '',
    estado          TEXT    NOT NULL DEFAULT 'operativo',
    observaciones   TEXT    DEFAULT '',
    activo          INTEGER NOT NULL DEFAULT 1,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================================
-- TABLA: movimientos
-- ============================================================================
CREATE TABLE IF NOT EXISTS movimientos (
    id                SERIAL PRIMARY KEY,
    tipo_movimiento   TEXT    NOT NULL CHECK(tipo_movimiento IN ('entrada', 'salida')),
    producto_id       INTEGER NOT NULL,
    cantidad          INTEGER NOT NULL CHECK(cantidad > 0),
    vehiculo_id       INTEGER DEFAULT NULL,
    destino           TEXT    DEFAULT NULL,
    numero_vale       TEXT    DEFAULT NULL,
    observaciones     TEXT    DEFAULT '',
    usuario_id        INTEGER NOT NULL,
    fecha_movimiento  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT,
    FOREIGN KEY (vehiculo_id) REFERENCES vehiculos(id) ON DELETE RESTRICT,
    FOREIGN KEY (usuario_id)  REFERENCES usuarios(id) ON DELETE RESTRICT
);

-- ============================================================================
-- TABLA: logs_auditoria
-- ============================================================================
CREATE TABLE IF NOT EXISTS logs_auditoria (
    id              SERIAL PRIMARY KEY,
    usuario_id      INTEGER DEFAULT NULL,
    usuario_nombre  TEXT    DEFAULT 'Sistema',
    accion          TEXT    NOT NULL,
    tabla_afectada  TEXT    DEFAULT NULL,
    registro_id     INTEGER DEFAULT NULL,
    datos           TEXT    DEFAULT NULL,
    ip_cliente      TEXT    DEFAULT NULL,
    user_agent      TEXT    DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
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
