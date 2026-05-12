<?php
session_start();
include 'db.php';

// --------------------------------------------------
//  VALIDAR ID RECIBIDO
// --------------------------------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = "❌ No se especificó un apartado válido.";
    header("Location: apartados.php");
    exit();
}

$id = intval($_GET['id']);

// --------------------------------------------------
//  CONSULTAR INFORMACIÓN DEL APARTADO
// --------------------------------------------------
$sql = "SELECT * FROM apartados WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$apartado = $result->fetch_assoc();

if (!$apartado) {
    $_SESSION['mensaje'] = "❌ El apartado no existe.";
    header("Location: apartados.php");
    exit();
}

// --------------------------------------------------
//  ACTUALIZAR DATOS
// --------------------------------------------------
if (isset($_POST['actualizar_apartado'])) {

    $cliente_id      = intval($_POST['cliente_id']);
    $producto_id     = intval($_POST['producto_id']);
    $fecha_apartado  = $_POST['fecha_apartado'];
    $anticipo        = floatval($_POST['anticipo']);
    $saldo_restante  = floatval($_POST['saldo_restante']);
    $estatus         = $_POST['estatus'];

    // ---------- Evitar que un cliente vuelva a apartar el mismo producto ----------
    $check = $conn->prepare("SELECT id FROM apartados 
                             WHERE producto_id = ? AND id != ?");
    $check->bind_param("ii", $producto_id, $id);
    $check->execute();
    $check_result = $check->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION['mensaje'] = "⚠️ *Este producto ya está apartado por otro cliente.* No se puede asignar de nuevo.";
        header("Location: editar_apartado.php?id=$id");
        exit();
    }

    // ---------- Actualizar ----------
    $update = "UPDATE apartados 
               SET cliente_id=?, producto_id=?, fecha_apartado=?, anticipo=?, saldo_restante=?, estatus=? 
               WHERE id=?";
    $stmt = $conn->prepare($update);
    $stmt->bind_param("iisddsi", $cliente_id, $producto_id, $fecha_apartado, $anticipo, $saldo_restante, $estatus, $id);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "✅ Apartado actualizado correctamente.";
    } else {
        $_SESSION['mensaje'] = "❌ Error al actualizar: " . $conn->error;
    }

    header("Location: apartados.php");
    exit();
}

// --------------------------------------------------
//  LISTA DE CLIENTES Y PRODUCTOS
// --------------------------------------------------
$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");
$productos = $conn->query("SELECT id, nombre FROM productos ORDER BY nombre ASC");

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>💎 Editar Apartado - Joyería Sahori</title>
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
        letter-spacing: 1px;
    }
    .container {
        max-width: 650px;
        margin: 40px auto;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        padding: 30px;
    }
    h2 {
        color: #b8860b;
        text-align: center;
        margin-bottom: 25px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
        color: #444;
    }
    input, select {
        width: 100%;
        padding: 10px;
        border: 1px solid #caa86b;
        border-radius: 8px;
        background: #fffdfa;
    }
    .btn {
        background-color: #b8860b;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
    }
    .btn:hover {
        background-color: #8b6508;
    }
    .actions {
        text-align: center;
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
    }
</style>
</head>

<body>
<header>💎 Editar Apartado - Joyería Sahori</header>

<div class="container">
    <h2>Modificar Datos del Apartado</h2>

    <form method="POST">
        
        <!-- CLIENTE -->
        <div class="form-group">
            <label>Cliente:</label>
            <select name="cliente_id" required>
                <?php while($c = $clientes->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>" <?= ($apartado['cliente_id'] == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- PRODUCTO -->
        <div class="form-group">
            <label>Producto:</label>
            <select name="producto_id" required>
                <?php while($p = $productos->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>" <?= ($apartado['producto_id'] == $p['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nombre']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <!-- FECHA -->
        <div class="form-group">
            <label>Fecha del Apartado:</label>
            <input type="date" name="fecha_apartado" value="<?= $apartado['fecha_apartado'] ?>" required>
        </div>

        <!-- ANTICIPO -->
        <div class="form-group">
            <label>Anticipo:</label>
            <input type="number" step="0.01" name="anticipo" value="<?= $apartado['anticipo'] ?>" required>
        </div>

        <!-- SALDO -->
        <div class="form-group">
            <label>Saldo Restante:</label>
            <input type="number" step="0.01" name="saldo_restante" value="<?= $apartado['saldo_restante'] ?>" required>
        </div>

        <!-- ESTATUS -->
        <div class="form-group">
            <label>Estatus:</label>
            <select name="estatus" required>
                <option value="Pendiente" <?= ($apartado['estatus'] == 'Pendiente') ? 'selected' : '' ?>>Pendiente</option>
                <option value="Pagado" <?= ($apartado['estatus'] == 'Pagado') ? 'selected' : '' ?>>Pagado</option>
                <option value="Cancelado" <?= ($apartado['estatus'] == 'Cancelado') ? 'selected' : '' ?>>Cancelado</option>
            </select>
        </div>

        <div class="actions">
            <button type="submit" name="actualizar_apartado" class="btn">💾 Guardar</button>
            <a href="apartados.php" class="btn">⬅ Regresar</a>
        </div>
    </form>
</div>

</body>
</html>
