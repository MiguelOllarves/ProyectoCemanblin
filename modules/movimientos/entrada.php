<?php
/**
 * CEMANBLIND - Registrar Entrada de Inventario
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_USUARIO);

$db = getDB();
$productos = $db->query("SELECT id, codigo, nombre, stock_actual, unidad_medida FROM productos WHERE activo = 1 ORDER BY nombre")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de seguridad inválido.');
        redirect(BASE_URL . '/modules/movimientos/entrada.php');
    }

    $productoId = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (!$productoId || $cantidad <= 0) {
        setFlash('error', 'Debe seleccionar un producto y una cantidad válida.');
        redirect(BASE_URL . '/modules/movimientos/entrada.php');
    }

    try {
        $db->beginTransaction();

        // 1. Registrar el movimiento
        $stmt = $db->prepare("
            INSERT INTO movimientos (tipo_movimiento, producto_id, cantidad, observaciones, usuario_id)
            VALUES ('entrada', :prod, :cant, :obs, :user)
        ");
        $stmt->execute([
            ':prod' => $productoId, ':cant' => $cantidad,
            ':obs'  => $observaciones, ':user' => $_SESSION['user_id']
        ]);
        $movId = 0;
        try {
            $seq = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'movimientos_id_seq' : null;
            $movId = (int) $db->lastInsertId($seq);
        } catch (Exception $e) {}

        // 2. Actualizar stock del producto
        $db->prepare("UPDATE productos SET stock_actual = stock_actual + :cant, updated_at = " . dbNow() . " WHERE id = :id")
           ->execute([':cant' => $cantidad, ':id' => $productoId]);

        $db->commit();

        // 3. Auditoría
        $prodInfo = $db->prepare("SELECT codigo, nombre FROM productos WHERE id = :id");
        $prodInfo->execute([':id' => $productoId]);
        $prod = $prodInfo->fetch();
        logAudit('CREATE', 'movimientos', $movId, [
            'tipo' => 'entrada', 'producto' => $prod['codigo'] . ' - ' . $prod['nombre'],
            'cantidad' => $cantidad
        ]);

        setFlash('success', "Entrada registrada: {$cantidad} unidades de {$prod['nombre']}");
        redirect(BASE_URL . '/modules/movimientos/index.php');
    } catch (PDOException $e) {
        $db->rollBack();
        setFlash('error', 'Error al registrar entrada: ' . $e->getMessage());
        redirect(BASE_URL . '/modules/movimientos/entrada.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php" class="text-slate-500 dark:text-gray-400 transition-colors hover:text-slate-800 dark:text-gray-200 transition-colors transition">← Volver</a>
        <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100 transition-colors">📥 Registrar Entrada</h1>
    </div>

    <form method="POST" class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 space-y-4">
        <?= csrfField() ?>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Producto *</label>
            <select name="producto_id" required class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                <option value="">-- Seleccionar producto --</option>
                <?php foreach ($productos as $p): ?>
                <option value="<?= $p['id'] ?>">[<?= sanitize($p['codigo']) ?>] <?= sanitize($p['nombre']) ?> (Stock: <?= $p['stock_actual'] ?> <?= $p['unidad_medida'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Cantidad *</label>
            <input type="number" name="cantidad" required min="1" placeholder="Cantidad a ingresar"
                class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Observaciones</label>
            <textarea name="observaciones" rows="2" placeholder="Ej: Recepción de proveedor X, factura #123"
                class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-green-700 hover:bg-green-600 text-white rounded-lg text-sm font-medium transition">Registrar Entrada</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
