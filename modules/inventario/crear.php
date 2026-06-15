<?php
/**
 * CEMABLN - Crear Producto
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMINISTRADOR);

$db = getDB();
$categorias = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de seguridad inválido.');
        redirect(BASE_URL . '/modules/inventario/crear.php');
    }

    $codigo = trim($_POST['codigo'] ?? '');
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $categoriaId = $_POST['categoria_id'] ?: null;
    $unidad = trim($_POST['unidad_medida'] ?? 'UND');
    $stockActual = (int)($_POST['stock_actual'] ?? 0);
    $stockMinimo = (int)($_POST['stock_minimo'] ?? 5);
    $precio = (float)($_POST['precio_unitario'] ?? 0);
    $ubicacion = trim($_POST['ubicacion_almacen'] ?? '');
    $vencimiento = $_POST['fecha_vencimiento'] ?: null;
    $numeroParte = trim($_POST['numero_parte'] ?? '');

    if (empty($codigo) || empty($nombre)) {
        setFlash('error', 'Código y nombre son obligatorios.');
        redirect(BASE_URL . '/modules/inventario/crear.php');
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO productos (codigo, nombre, descripcion, categoria_id, unidad_medida, stock_actual, stock_minimo, precio_unitario, ubicacion_almacen, fecha_vencimiento, numero_parte)
            VALUES (:codigo, :nombre, :desc, :cat, :unidad, :stock, :minimo, :precio, :ubic, :venc, :parte)
        ");
        $stmt->execute([
            ':codigo' => $codigo, ':nombre' => $nombre, ':desc' => $descripcion,
            ':cat' => $categoriaId, ':unidad' => $unidad, ':stock' => $stockActual,
            ':minimo' => $stockMinimo, ':precio' => $precio, ':ubic' => $ubicacion,
            ':venc' => $vencimiento, ':parte' => $numeroParte
        ]);

        $newId = 0;
        try {
            $seq = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'productos_id_seq' : null;
            $newId = (int) $db->lastInsertId($seq);
        } catch (Exception $e) {}
        logAudit('CREATE', 'productos', $newId, ['codigo' => $codigo, 'nombre' => $nombre, 'stock' => $stockActual]);
        setFlash('success', "Producto '{$nombre}' creado exitosamente.");
        redirect(BASE_URL . '/modules/inventario/index.php');
    } catch (PDOException $e) {
        if (stripos($e->getMessage(), 'unique') !== false || stripos($e->getMessage(), 'duplicate') !== false) {
            setFlash('error', "El código '{$codigo}' ya está en uso.");
        } else {
            setFlash('error', 'Error al crear producto: ' . $e->getMessage());
        }
        redirect(BASE_URL . '/modules/inventario/crear.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php" class="text-slate-500 dark:text-gray-400 transition-colors hover:text-slate-800 dark:text-gray-200 transition-colors transition">← Volver</a>
        <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Nuevo Producto</h1>
    </div>

    <form method="POST" id="formProducto" class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 space-y-4">
        <?= csrfField() ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Código *</label>
                <input type="text" name="codigo" required placeholder="REP-001" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Nombre *</label>
                <input type="text" name="nombre" required placeholder="Filtro de aceite" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Descripción</label>
            <textarea name="descripcion" rows="2" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500"></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Categoría</label>
                <select name="categoria_id" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= sanitize($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Unidad de Medida</label>
                <select name="unidad_medida" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                    <option value="UND">Unidad (UND)</option>
                    <option value="LT">Litros (LT)</option>
                    <option value="KG">Kilogramos (KG)</option>
                    <option value="MT">Metros (MT)</option>
                    <option value="GAL">Galones (GAL)</option>
                    <option value="JGO">Juego (JGO)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">N° de Parte</label>
                <input type="text" name="numero_parte" placeholder="OEM-12345" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Stock Inicial</label>
                <input type="number" name="stock_actual" value="0" min="0" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Stock Mínimo</label>
                <input type="number" name="stock_minimo" value="5" min="0" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Precio Unit.</label>
                <input type="number" name="precio_unitario" value="0" min="0" step="0.01" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Vencimiento</label>
                <input type="date" name="fecha_vencimiento" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Ubicación en Almacén</label>
            <input type="text" name="ubicacion_almacen" placeholder="Estante A3, Rack 2" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-military-700 hover:bg-military-600 text-white rounded-lg text-sm font-medium transition">Guardar Producto</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
