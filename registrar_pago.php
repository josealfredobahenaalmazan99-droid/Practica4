<?php
session_start();
include 'db.php';

// -----------------------------------------------------------
// 1️⃣ VALIDAR ID DEL APARTADO
// -----------------------------------------------------------
if (!isset($_GET['id'])) {
    $_SESSION['mensaje'] = "❌ No se especificó el apartado.";
    header("Location: apartados.php");
    exit();
}

$apartado_id = intval($_GET['id']);

// -----------------------------------------------------------
// 2️⃣ OBTENER LOS DATOS DEL APARTADO
// -----------------------------------------------------------
$sql = "SELECT a.*, c.nombre AS cliente, p.nombre AS producto, p.precio AS precio_producto
        FROM apartados a
        INNER JOIN clientes c ON a.cliente_id = c.id
        INNER JOIN productos p ON a.producto_id = p.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $apartado_id);
$stmt->execute();
$datos = $stmt->get_result()->fetch_assoc();

if (!$datos) {
    $_SESSION['mensaje'] = "❌ El apartado no existe.";
    header("Location: apartados.php");
    exit();
}

// -----------------------------------------------------------
// 3️⃣ PROCESAR REGISTRO DE PAGO
// -----------------------------------------------------------
if (isset($_POST['registrar_pago'])) {
    $monto = floatval($_POST['monto']);
    $metodo_pago = $_POST['metodo_pago'];
    $fecha_pago = date("Y-m-d");

    // Validar que el pago no exceda el saldo
    if ($monto <= 0) {
        $_SESSION['mensaje'] = "❌ El monto debe ser mayor a 0.";
        header("Location: registrar_pago.php?id=" . $apartado_id);
        exit();
    }

    if ($monto > $datos['saldo_restante']) {
        $_SESSION['mensaje'] = "❌ El pago no puede ser mayor al saldo restante.";
        header("Location: registrar_pago.php?id=" . $apartado_id);
        exit();
    }

    // Calcular nuevo saldo
    $nuevo_saldo = $datos['saldo_restante'] - $monto;

    // -----------------------------------------------------------
    // Registrar pago en historial
    // -----------------------------------------------------------
    $insert = "INSERT INTO pagos_apartado (apartado_id, fecha_pago, monto, saldo_restante, metodo_pago)
               VALUES (?, ?, ?, ?, ?)";

    $stmt2 = $conn->prepare($insert);
    $stmt2->bind_param("isdss", $apartado_id, $fecha_pago, $monto, $nuevo_saldo, $metodo_pago);
    $stmt2->execute();

    // -----------------------------------------------------------
    // Actualizar saldo del apartado
    // -----------------------------------------------------------
    $estatus = ($nuevo_saldo == 0) ? "Pagado" : "Pendiente";

    $update = "UPDATE apartados SET saldo_restante=?, estatus=? WHERE id=?";
    $stmt3 = $conn->prepare($update);
    $stmt3->bind_param("dsi", $nuevo_saldo, $estatus, $apartado_id);
    $stmt3->execute();

    $_SESSION['mensaje'] = ($nuevo_saldo == 0)
        ? "✅ Pago registrado. Apartado liquidado."
        : "✅ Pago registrado correctamente.";

    header("Location: historial_pagos.php?id=" . $apartado_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>💎 Registrar Pago - Joyería Sahori</title>

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
        max-width: 600px;
        margin: 35px auto;
        background: white;
        padding: 25px;
        border-radius: 15px;
        box-shadow: 0 4px 25px rgba(0,0,0,0.2);
    }
    h2 {
        color: #b8860b;
        text-align: center;
        margin-bottom: 20px;
    }
    .info-box {
        background: #fff4d3;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 5px solid #b8860b;
    }
    .form-group {
        margin-bottom: 15px;
    }
    label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    input, select {
        width: 100%;
        padding: 8px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }
    .btn {
        background-color: #b8860b;
        color: white;
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        display: inline-block;
        text-decoration: none;
    }
    .btn:hover {
        background-color: #8b6508;
    }
</style>
</head>

<body>

<header>💎 Registrar Pago</header>

<div class="container">

    <h2>Registro de Abono</h2>

    <div class="info-box">
        <p><b>Cliente:</b> <?= htmlspecialchars($datos['cliente']) ?></p>
        <p><b>Producto:</b> <?= htmlspecialchars($datos['producto']) ?></p>
        <p><b>Precio total:</b> $<?= number_format($datos['precio_producto'], 2) ?></p>
        <p><b>Saldo restante:</b> $<?= number_format($datos['saldo_restante'], 2) ?></p>
    </div>

    <form method="POST">

        <div class="form-group">
            <label>Monto a abonar:</label>
            <input type="number" name="monto" step="0.01" min="1" required>
        </div>

        <div class="form-group">
            <label>Método de pago:</label>
            <select name="metodo_pago" required>
                <option value="Efectivo">Efectivo</option>
                <option value="Tarjeta">Tarjeta</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Otro">Otro</option>
            </select>
        </div>

        <button type="submit" name="registrar_pago" class="btn">💾 Registrar Pago</button>
        <a href="apartados.php" class="btn">⬅ Cancelar</a>
    </form>
</div>

</body>
</html>
