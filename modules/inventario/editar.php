<?php
/**
 * CEMABLN - Editar Producto
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_ADMINISTRADOR);

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) { setFlash('error', 'Producto no encontrado.'); redirect(BASE_URL . '/modules/inventario/index.php'); }

$stmt = $db->prepare("SELECT * FROM productos WHERE id = :id AND activo = 1");
$stmt->execute([':id' => $id]);
$producto = $stmt->fetch();

if (!$producto) { setFlash('error', 'Producto no encontrado.'); redirect(BASE_URL . '/modules/inventario/index.php'); }

$categorias = $db->query("SELECT * FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de seguridad inválido.');
        redirect(BASE_URL . '/modules/inventario/editar.php?id=' . $id);
    }

    $datosAntes = json_encode($producto);

    try {
        $stmt = $db->prepare("
            UPDATE productos SET 
                codigo = :codigo, nombre = :nombre, descripcion = :desc, categoria_id = :cat,
                unidad_medida = :unidad, stock_minimo = :minimo, precio_unitario = :precio,
                ubicacion_almacen = :ubic, fecha_vencimiento = :venc, numero_parte = :parte,
                updated_at = " . dbNow() . "
            WHERE id = :id
        ");
        $stmt->execute([
            ':codigo' => trim($_POST['codigo']), ':nombre' => trim($_POST['nombre']),
            ':desc' => trim($_POST['descripcion'] ?? ''), ':cat' => $_POST['categoria_id'] ?: null,
            ':unidad' => trim($_POST['unidad_medida'] ?? 'UND'), ':minimo' => (int)$_POST['stock_minimo'],
            ':precio' => (float)$_POST['precio_unitario'], ':ubic' => trim($_POST['ubicacion_almacen'] ?? ''),
            ':venc' => $_POST['fecha_vencimiento'] ?: null, ':parte' => trim($_POST['numero_parte'] ?? ''),
            ':id' => $id
        ]);

        logAudit('UPDATE', 'productos', $id, ['antes' => $datosAntes, 'nombre' => trim($_POST['nombre'])]);
        setFlash('success', 'Producto actualizado correctamente.');
        redirect(BASE_URL . '/modules/inventario/index.php');
    } catch (PDOException $e) {
        setFlash('error', 'Error: ' . $e->getMessage());
        redirect(BASE_URL . '/modules/inventario/editar.php?id=' . $id);
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-3xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php" class="text-slate-500 dark:text-gray-400 transition-colors hover:text-slate-800 dark:text-gray-200 transition-colors transition">← Volver</a>
        <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100 transition-colors">Editar: <?= sanitize($producto['nombre']) ?></h1>
    </div>

    <form method="POST" class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 space-y-4">
        <?= csrfField() ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Código *</label>
                <input type="text" name="codigo" required value="<?= sanitize($producto['codigo']) ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Nombre *</label>
                <input type="text" name="nombre" required value="<?= sanitize($producto['nombre']) ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Descripción</label>
            <textarea name="descripcion" rows="2" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500"><?= sanitize($producto['descripcion']) ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Categoría</label>
                <select name="categoria_id" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                    <option value="">Sin categoría</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $producto['categoria_id'] == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Unidad de Medida</label>
                <select name="unidad_medida" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                    <?php foreach (['UND','LT','KG','MT','GAL','JGO'] as $u): ?>
                    <option value="<?= $u ?>" <?= $producto['unidad_medida'] === $u ? 'selected' : '' ?>><?= $u ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">N° de Parte</label>
                <input type="text" name="numero_parte" value="<?= sanitize($producto['numero_parte']) ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Stock Actual</label>
                <input type="number" value="<?= $producto['stock_actual'] ?>" disabled class="w-full bg-slate-50 dark:bg-steel-900 border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-500 dark:text-gray-500 transition-colors cursor-not-allowed">
                <p class="text-[10px] text-slate-400 dark:text-gray-600 transition-colors mt-1">Se modifica vía movimientos</p>
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Stock Mínimo</label>
                <input type="number" name="stock_minimo" value="<?= $producto['stock_minimo'] ?>" min="0" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Precio Unit.</label>
                <input type="number" name="precio_unitario" value="<?= $producto['precio_unitario'] ?>" min="0" step="0.01" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
            <div>
                <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Vencimiento</label>
                <input type="date" name="fecha_vencimiento" value="<?= $producto['fecha_vencimiento'] ?? '' ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            </div>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Ubicación en Almacén</label>
            <input type="text" name="ubicacion_almacen" value="<?= sanitize($producto['ubicacion_almacen']) ?>" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-military-700 hover:bg-military-600 text-white rounded-lg text-sm font-medium transition">Guardar Cambios</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
