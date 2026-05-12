<?php
require __DIR__ . "/db.php"; // conexión correcta

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Datos incompletos.");
}

$apartado_id = intval($_GET['id']);

// Obtener datos del apartado
$sql = "SELECT a.*, c.nombre AS cliente, p.nombre AS producto, p.precio
        FROM apartados a
        LEFT JOIN clientes c ON a.cliente_id = c.id
        LEFT JOIN productos p ON a.producto_id = p.id
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $apartado_id);
$stmt->execute();
$apartado = $stmt->get_result()->fetch_assoc();

if (!$apartado) {
    die("Apartado no encontrado.");
}

// Obtener historial de pagos
$pagos = $conn->prepare("SELECT * FROM pagos_apartado WHERE apartado_id = ? ORDER BY fecha_pago ASC");
$pagos->bind_param("i", $apartado_id);
$pagos->execute();
$lista_pagos = $pagos->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Historial de Pagos</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #faf6ee;
    padding: 20px;
}
.container {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0px 4px 20px rgba(0,0,0,0.1);
    width: 800px;
    margin: auto;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}
th, td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
}
th {
    background: #f3d18b;
}
.btn {
    background: #b8860b;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}
.btn:hover {
    background: #8b6508;
}
</style>
</head>
<body>

<div class="container">
    <h2>📄 Historial de Pagos del Apartado #<?= $apartado['id'] ?></h2>

    <p><strong>Cliente:</strong> <?= htmlspecialchars($apartado['cliente']) ?></p>
    <p><strong>Producto:</strong> <?= htmlspecialchars($apartado['producto']) ?></p>
    <p><strong>Precio Total:</strong> $<?= number_format($apartado['precio'], 2) ?></p>
    <p><strong>Anticipo:</strong> $<?= number_format($apartado['anticipo'], 2) ?></p>
    <p><strong>Saldo Restante:</strong> $<?= number_format($apartado['saldo_restante'], 2) ?></p>
    <hr>

    <h3>Pagos Registrados</h3>

    <?php if ($lista_pagos->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Fecha de Pago</th>
                <th>Monto</th>
            </tr>

            <?php while ($p = $lista_pagos->fetch_assoc()): ?>
                <tr>
                    <td><?= $p['id'] ?></td>
                    <td><?= $p['fecha_pago'] ?></td>
                    <td>$<?= number_format($p['monto'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p style="color:#777;">No hay pagos registrados aún.</p>
    <?php endif; ?>

    <br>
    <a href="apartados.php" class="btn">⬅ Volver a Apartados</a>
</div>

</body>
</html>
