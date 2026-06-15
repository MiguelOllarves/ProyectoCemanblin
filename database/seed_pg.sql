-- ============================================================================
-- CEMABLN - Script de Seed (Datos de Prueba para PostgreSQL)
-- ============================================================================
-- Este script inserta datos ficticios para poder probar la aplicación en Supabase.
-- Incluye un superusuario, categorías, productos, vehículos y movimientos.
-- ============================================================================

-- 1. Insertar Usuarios (La contraseña para todos es: admin123)
INSERT INTO usuarios (cedula, password_hash, nombre_completo, grado, cargo, rol) 
VALUES 
('00000000', '$2y$12$4FSWta7reiKgt7bWj6N2N.CVFP9FQwi.xzvzEwX2HBrntjFts4bO.', 'Super Administrador', 'General', 'Director', 4),
('11111111', '$2y$12$4FSWta7reiKgt7bWj6N2N.CVFP9FQwi.xzvzEwX2HBrntjFts4bO.', 'Juan Pérez', 'Capitán', 'Jefe de Almacén', 3),
('22222222', '$2y$12$4FSWta7reiKgt7bWj6N2N.CVFP9FQwi.xzvzEwX2HBrntjFts4bO.', 'María Gómez', 'Sargento', 'Operador de Inventario', 1)
ON CONFLICT (cedula) DO NOTHING;

-- 2. Insertar Categorías
INSERT INTO categorias (nombre, descripcion)
VALUES 
('Repuestos de Motor', 'Piezas y componentes del motor'),
('Filtros', 'Filtros de aceite, aire, combustible'),
('Lubricantes', 'Aceites, grasas y fluidos hidráulicos'),
('Neumáticos y Orugas', 'Neumáticos, bandas de rodamiento y orugas'),
('Sistema Eléctrico', 'Baterías, alternadores, cableado')
ON CONFLICT (nombre) DO NOTHING;

-- 3. Insertar Productos (Asumiendo IDs de categoría del 1 al 5 según la inserción anterior)
INSERT INTO productos (codigo, nombre, descripcion, categoria_id, unidad_medida, stock_actual, stock_minimo, precio_unitario, ubicacion_almacen)
VALUES 
('REP-001', 'Filtro de Aceite XZ-500', 'Filtro de alto rendimiento para motores diésel', 2, 'UND', 50, 10, 15.50, 'Estante A-1'),
('REP-002', 'Aceite Sintético 15W40', 'Tambor de aceite de 208L', 3, 'LT', 416, 50, 4.20, 'Zona B-Pasillo 2'),
('REP-003', 'Batería 12V 100Ah', 'Batería de gel resistente a vibraciones extremas', 5, 'UND', 15, 5, 120.00, 'Rack C-1'),
('REP-004', 'Oruga Eslabón D8', 'Eslabón de acero reforzado', 4, 'UND', 120, 20, 85.00, 'Patio Exterior'),
('REP-005', 'Bomba de Inyección', 'Bomba mecánica de alta presión de repuesto', 1, 'UND', 3, 2, 450.00, 'Estante A-3')
ON CONFLICT (codigo) DO NOTHING;

-- 4. Insertar Vehículos Blindados
INSERT INTO vehiculos (codigo, tipo, modelo, placa_militar, unidad_asignada, estado)
VALUES 
('BLD-001', 'APC', 'BTR-80', 'MIL-0001', '1er Batallón Blindado', 'operativo'),
('BLD-002', 'Tanque', 'T-72B', 'MIL-0002', '1er Batallón Blindado', 'mantenimiento'),
('BLD-003', 'IFV', 'BMP-3', 'MIL-0003', '2do Batallón de Infantería', 'operativo'),
('BLD-004', 'APC', 'BTR-80A', 'MIL-0004', '2do Batallón de Infantería', 'operativo'),
('BLD-005', 'Tanque', 'AMX-30', 'MIL-0005', '3er Batallón Blindado', 'inoperativo')
ON CONFLICT (codigo) DO NOTHING;

-- 5. Insertar Movimientos (Entradas y Salidas)
-- Para esto utilizamos subconsultas para evitar problemas con los IDs autoincrementables
INSERT INTO movimientos (tipo_movimiento, producto_id, cantidad, vehiculo_id, destino, observaciones, usuario_id)
VALUES 
-- Entradas Iniciales (por el usuario Super Administrador - id 1)
('entrada', (SELECT id FROM productos WHERE codigo = 'REP-001'), 60, NULL, NULL, 'Lote inicial de compra', (SELECT id FROM usuarios WHERE cedula = '00000000')),
('entrada', (SELECT id FROM productos WHERE codigo = 'REP-002'), 500, NULL, NULL, 'Lote inicial de compra', (SELECT id FROM usuarios WHERE cedula = '00000000')),
('entrada', (SELECT id FROM productos WHERE codigo = 'REP-003'), 20, NULL, NULL, 'Lote inicial de compra', (SELECT id FROM usuarios WHERE cedula = '00000000')),

-- Salidas (Asignación a vehículos en mantenimiento)
('salida', (SELECT id FROM productos WHERE codigo = 'REP-001'), 10, (SELECT id FROM vehiculos WHERE codigo = 'BLD-002'), 'Taller 1', 'Mantenimiento preventivo', (SELECT id FROM usuarios WHERE cedula = '11111111')),
('salida', (SELECT id FROM productos WHERE codigo = 'REP-002'), 84, (SELECT id FROM vehiculos WHERE codigo = 'BLD-002'), 'Taller 1', 'Cambio de aceite', (SELECT id FROM usuarios WHERE cedula = '11111111')),
('salida', (SELECT id FROM productos WHERE codigo = 'REP-003'), 5, (SELECT id FROM vehiculos WHERE codigo = 'BLD-001'), 'Taller 2', 'Reemplazo por fin de vida útil', (SELECT id FROM usuarios WHERE cedula = '11111111'));

-- 6. Actualizar las secuencias para que los próximos INSERTs funcionen correctamente (Solo Postgres)
SELECT setval('usuarios_id_seq', (SELECT MAX(id) FROM usuarios));
SELECT setval('categorias_id_seq', (SELECT MAX(id) FROM categorias));
SELECT setval('productos_id_seq', (SELECT MAX(id) FROM productos));
SELECT setval('vehiculos_id_seq', (SELECT MAX(id) FROM vehiculos));
SELECT setval('movimientos_id_seq', (SELECT MAX(id) FROM movimientos));
