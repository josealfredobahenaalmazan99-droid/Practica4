<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    header("Location: pedidos.php");
    exit();
}

$id = $_GET['id'];

// 🔹 Obtener el pedido actual
$sql = "SELECT * FROM pedidos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['mensaje'] = "⚠️ Pedido no encontrado.";
    header("Location: pedidos.php");
    exit();
}

$pedido = $result->fetch_assoc();

// 🔹 Obtener lista de clientes
$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");

// 🔸 Actualizar pedido
if (isset($_POST['actualizar_pedido'])) {
    $cliente_id = $_POST['cliente_id'];
    $descripcion = $_POST['descripcion'];
    $material = $_POST['material'];
    $talla = $_POST['talla'];
    $peso_aproximado = $_POST['peso_aproximado'];
    $fecha_pedido = $_POST['fecha_pedido'];
    $fecha_entrega = $_POST['fecha_entrega'];
    $monto_total = $_POST['monto_total'];
    $anticipo = $_POST['anticipo'];
    $saldo_restante = $_POST['saldo_restante'];
    $estatus = $_POST['estatus'];

    $update_sql = "UPDATE pedidos SET cliente_id=?, descripcion=?, material=?, talla=?, peso_aproximado=?, fecha_pedido=?, fecha_entrega=?, monto_total=?, anticipo=?, saldo_restante=?, estatus=? WHERE id=?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("isssssssddsi", $cliente_id, $descripcion, $material, $talla, $peso_aproximado, $fecha_pedido, $fecha_entrega, $monto_total, $anticipo, $saldo_restante, $estatus, $id);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "✅ Pedido actualizado correctamente.";
    } else {
        $_SESSION['mensaje'] = "❌ Error al actualizar pedido: " . $conn->error;
    }

    header("Location: pedidos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>✏️ Editar Pedido Especial - Joyería Sahori</title>
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #faf6f0;
        margin: 0;
        padding: 0;
    }
    header {
        background: linear-gradient(to right, #d4af37, #b8860b);
        color: white;
        text-align: center;
        padding: 15px;
        font-size: 22px;
        font-weight: bold;
    }
    .container {
        width: 90%;
        max-width: 900px;
        margin: 30px auto;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        padding: 25px;
    }
    h2 {
        color: #b8860b;
        text-align: center;
        margin-bottom: 25px;
    }
    form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 15px;
    }
    label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    input, select, textarea {
        width: 100%;
        padding: 8px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }
    textarea { resize: none; height: 80px; }
    .btn {
        background-color: #b8860b;
        color: white;
        border: none;
        padding: 10px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
    }
    .btn:hover { background-color: #8b6508; }
    .volver {
        display: inline-block;
        margin-top: 20px;
        text-decoration: none;
        background: #d4af37;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: bold;
    }
</style>
</head>

<body>
<header>✏️ Editar Pedido Especial - Joyería Sahori</header>

<div class="container">
    <h2>Modificar Información del Pedido</h2>

    <form method="POST">
        <div>
            <label>Cliente:</label>
            <select name="cliente_id" required>
                <?php while($c = $clientes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $pedido['cliente_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div>
            <label>Descripción del pedido:</label>
            <textarea name="descripcion" required><?= htmlspecialchars($pedido['descripcion']) ?></textarea>
        </div>

        <div>
            <label>Material:</label>
            <input type="text" name="material" value="<?= htmlspecialchars($pedido['material']) ?>">
        </div>

        <div>
            <label>Talla:</label>
            <input type="text" name="talla" value="<?= htmlspecialchars($pedido['talla']) ?>">
        </div>

        <div>
            <label>Peso Aproximado (gr):</label>
            <input type="number" step="0.01" name="peso_aproximado" value="<?= htmlspecialchars($pedido['peso_aproximado']) ?>">
        </div>

        <div>
            <label>Fecha del Pedido:</label>
            <input type="date" name="fecha_pedido" value="<?= $pedido['fecha_pedido'] ?>">
        </div>

        <div>
            <label>Fecha de Entrega:</label>
            <input type="date" name="fecha_entrega" value="<?= $pedido['fecha_entrega'] ?>">
        </div>

        <div>
            <label>Monto Total ($):</label>
            <input type="number" step="0.01" name="monto_total" value="<?= htmlspecialchars($pedido['monto_total']) ?>">
        </div>

        <div>
            <label>Anticipo ($):</label>
            <input type="number" step="0.01" name="anticipo" value="<?= htmlspecialchars($pedido['anticipo']) ?>">
        </div>

        <div>
            <label>Saldo Restante ($):</label>
            <input type="number" step="0.01" name="saldo_restante" value="<?= htmlspecialchars($pedido['saldo_restante']) ?>">
        </div>

        <div>
            <label>Estatus:</label>
            <select name="estatus" required>
                <option value="Pendiente" <?= $pedido['estatus'] == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                <option value="En proceso" <?= $pedido['estatus'] == 'En proceso' ? 'selected' : '' ?>>En proceso</option>
                <option value="Completado" <?= $pedido['estatus'] == 'Completado' ? 'selected' : '' ?>>Completado</option>
                <option value="Cancelado" <?= $pedido['estatus'] == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>

        <div style="grid-column: span 2; text-align:center;">
            <button type="submit" name="actualizar_pedido" class="btn">💾 Guardar Cambios</button>
        </div>
    </form>

    <a href="pedidos.php" class="volver">⬅ Volver a Pedidos</a>
</div>

</body>
</html>
