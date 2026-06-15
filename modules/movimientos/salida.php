<?php
/**
 * ============================================================================
 * CEMABLN - Despacho de Salida (CORE de Trazabilidad)
 * ============================================================================
 * 
 * Este es el proceso más crítico del sistema. Cada salida DEBE registrar:
 * 1. Producto despachado
 * 2. Cantidad
 * 3. Vehículo blindado destino (obligatorio)
 * 4. Destino/propósito del repuesto (obligatorio)
 * 
 * El proceso:
 * 1. Valida stock disponible
 * 2. Genera número de vale único
 * 3. Registra movimiento en BD (transacción atómica)
 * 4. Descuenta stock del producto
 * 5. Registra en auditoría
 * 6. Redirige al vale imprimible
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

requireRole(ROLE_USUARIO);

$db = getDB();
$productos = $db->query("SELECT id, codigo, nombre, stock_actual, unidad_medida FROM productos WHERE activo = 1 AND stock_actual > 0 ORDER BY nombre")->fetchAll();
$vehiculos = $db->query("SELECT id, codigo, tipo, modelo, unidad_asignada FROM vehiculos WHERE activo = 1 ORDER BY codigo")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de seguridad inválido.');
        redirect(BASE_URL . '/modules/movimientos/salida.php');
    }

    $productoId = (int)$_POST['producto_id'];
    $cantidad = (int)$_POST['cantidad'];
    $vehiculoId = (int)$_POST['vehiculo_id'];
    $destino = trim($_POST['destino'] ?? '');
    $observaciones = trim($_POST['observaciones'] ?? '');

    // ── Validaciones estrictas ─────────────────────────────────────────
    $errors = [];
    if (!$productoId) $errors[] = 'Debe seleccionar un producto.';
    if ($cantidad <= 0) $errors[] = 'La cantidad debe ser mayor a 0.';
    if (!$vehiculoId) $errors[] = 'Debe seleccionar un vehículo blindado.';
    if (empty($destino)) $errors[] = 'El destino del repuesto es obligatorio.';

    // Verificar stock disponible
    if ($productoId && $cantidad > 0) {
        $stmtStock = $db->prepare("SELECT stock_actual, nombre, codigo FROM productos WHERE id = :id AND activo = 1");
        $stmtStock->execute([':id' => $productoId]);
        $prodCheck = $stmtStock->fetch();

        if (!$prodCheck) {
            $errors[] = 'Producto no encontrado.';
        } elseif ($prodCheck['stock_actual'] < $cantidad) {
            $errors[] = "Stock insuficiente. Disponible: {$prodCheck['stock_actual']} unidades.";
        }
    }

    if ($errors) {
        setFlash('error', implode(' ', $errors));
        redirect(BASE_URL . '/modules/movimientos/salida.php');
    }

    try {
        $db->beginTransaction();

        // 1. Generar número de vale
        $numVale = generateValeNumber();

        // 2. Registrar movimiento de salida
        $stmt = $db->prepare("
            INSERT INTO movimientos (tipo_movimiento, producto_id, cantidad, vehiculo_id, destino, numero_vale, observaciones, usuario_id)
            VALUES ('salida', :prod, :cant, :veh, :dest, :vale, :obs, :user)
        ");
        $stmt->execute([
            ':prod' => $productoId, ':cant' => $cantidad, ':veh' => $vehiculoId,
            ':dest' => $destino, ':vale' => $numVale, ':obs' => $observaciones,
            ':user' => $_SESSION['user_id']
        ]);
        $movId = 0;
        try {
            $seq = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? 'movimientos_id_seq' : null;
            $movId = (int) $db->lastInsertId($seq);
        } catch (Exception $e) {}

        // 3. Descontar stock
        $db->prepare("UPDATE productos SET stock_actual = stock_actual - :cant, updated_at = datetime('now', 'localtime') WHERE id = :id")
           ->execute([':cant' => $cantidad, ':id' => $productoId]);

        $db->commit();

        // 4. Obtener info para auditoría
        $vehInfo = $db->prepare("SELECT codigo, tipo FROM vehiculos WHERE id = :id");
        $vehInfo->execute([':id' => $vehiculoId]);
        $veh = $vehInfo->fetch();

        logAudit('CREATE', 'movimientos', $movId, [
            'tipo' => 'salida', 'vale' => $numVale,
            'producto' => $prodCheck['codigo'] . ' - ' . $prodCheck['nombre'],
            'cantidad' => $cantidad,
            'vehiculo' => $veh['codigo'] . ' (' . $veh['tipo'] . ')',
            'destino' => $destino
        ]);

        setFlash('success', "Despacho registrado. Vale: {$numVale}");
        redirect(BASE_URL . '/modules/movimientos/vale.php?id=' . $movId);
    } catch (PDOException $e) {
        $db->rollBack();
        setFlash('error', 'Error en despacho: ' . $e->getMessage());
        redirect(BASE_URL . '/modules/movimientos/salida.php');
    }
}

include __DIR__ . '/../../includes/header.php';
?>

<div class="max-w-2xl mx-auto">
    <div class="flex items-center gap-3 mb-6">
        <a href="index.php" class="text-slate-500 dark:text-gray-400 transition-colors hover:text-slate-800 dark:text-gray-200 transition-colors transition">← Volver</a>
        <h1 class="text-xl font-bold text-slate-800 dark:text-gray-100 transition-colors">📤 Despachar Salida</h1>
    </div>

    <div class="bg-orange-950/20 border border-orange-700/30 rounded-xl p-4 mb-6">
        <p class="text-xs text-orange-300">⚠️ <strong>Importante:</strong> Todos los campos marcados con * son obligatorios. Cada despacho genera un vale de salida imprimible y actualiza el stock automáticamente.</p>
    </div>

    <form method="POST" class="bg-white dark:bg-steel-900/50 transition-colors border border-slate-200 dark:border-steel-700/50 rounded-xl p-6 space-y-4">
        <?= csrfField() ?>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Producto a Despachar *</label>
            <select name="producto_id" required id="selectProducto" class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                <option value="">-- Seleccionar producto --</option>
                <?php foreach ($productos as $p): ?>
                <option value="<?= $p['id'] ?>" data-stock="<?= $p['stock_actual'] ?>">[<?= sanitize($p['codigo']) ?>] <?= sanitize($p['nombre']) ?> (Disp: <?= $p['stock_actual'] ?> <?= $p['unidad_medida'] ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Cantidad *</label>
            <input type="number" name="cantidad" required min="1" id="inputCantidad" placeholder="Cantidad a despachar"
                class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
            <p id="stockWarning" class="text-xs text-red-600 dark:text-red-400 mt-1 hidden">⚠️ La cantidad excede el stock disponible</p>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Vehículo Blindado Destino *</label>
            <select name="vehiculo_id" required class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
                <option value="">-- Seleccionar vehículo --</option>
                <?php foreach ($vehiculos as $v): ?>
                <option value="<?= $v['id'] ?>">[<?= sanitize($v['codigo']) ?>] <?= sanitize($v['tipo']) ?> <?= sanitize($v['modelo']) ?> — <?= sanitize($v['unidad_asignada']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Destino / Propósito del Repuesto *</label>
            <input type="text" name="destino" required placeholder="Ej: Reemplazo de filtro de aceite, motor principal"
                class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500">
        </div>

        <div>
            <label class="block text-xs text-slate-500 dark:text-gray-400 transition-colors uppercase mb-1">Observaciones</label>
            <textarea name="observaciones" rows="2" placeholder="Notas adicionales..."
                class="w-full bg-white dark:bg-steel-800 transition-colors border border-slate-300 dark:border-steel-700 rounded-lg px-3 py-2 text-sm text-slate-800 dark:text-gray-200 transition-colors focus:outline-none focus:border-military-500"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4">
            <a href="index.php" class="px-4 py-2 bg-slate-200 dark:bg-steel-700 hover:bg-slate-300 dark:bg-steel-600 text-slate-700 dark:text-gray-300 transition-colors rounded-lg text-sm transition">Cancelar</a>
            <button type="submit" class="px-6 py-2 bg-orange-700 hover:bg-orange-600 text-white rounded-lg text-sm font-medium transition">Despachar y Generar Vale</button>
        </div>
    </form>
</div>

<!-- Validación de stock en tiempo real -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectProd = document.getElementById('selectProducto');
    const inputCant = document.getElementById('inputCantidad');
    const warning = document.getElementById('stockWarning');

    function checkStock() {
        if (!selectProd || !inputCant) return;
        const opt = selectProd.selectedOptions[0];
        const stock = parseInt(opt?.dataset?.stock || 0);
        const cant = parseInt(inputCant.value || 0);
        if (cant > stock && stock > 0) {
            warning.classList.remove('hidden');
            inputCant.max = stock;
        } else {
            warning.classList.add('hidden');
        }
    }
    if (selectProd) selectProd.addEventListener('change', checkStock);
    if (inputCant) inputCant.addEventListener('input', checkStock);
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>
