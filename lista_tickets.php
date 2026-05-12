<?php
require __DIR__ . "/db.php"; // Conexión a la BD

// Obtener todas las ventas
$sql = "SELECT v.id, v.fecha, v.total, c.nombre AS cliente 
        FROM ventas v
        LEFT JOIN clientes c ON v.cliente_id = c.id
        ORDER BY v.id DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Lista de Tickets</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f2f2f2;
    padding: 20px;
}
h2 {
    text-align: center;
}
table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}
th, td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
th {
    background: #333;
    color: white;
}
.btn {
    display: inline-block;
    padding: 6px 10px;
    background: #3498db;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 13px;
}
.btn:hover {
    background: #217dbb;
}

/* Botón regresar */
.btn-back {
    display: inline-block;
    padding: 8px 15px;
    background: #2ecc71;
    color: white;
    text-decoration: none;
    border-radius: 6px;
    font-weight: bold;
    margin-bottom: 15px;
}
.btn-back:hover {
    background: #239b56;
}
</style>
</head>
<body>

<!-- Botón para regresar -->
<a href="ventas.php" class="btn-back">← Regresar a Ventas</a>

<h2>Tickets Generados</h2>

<table>
<thead>
<tr>
    <th>ID</th>
    <th>Fecha</th>
    <th>Cliente</th>
    <th>Total</th>
    <th>Acciones</th>
</tr>
</thead>
<tbody>

<?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= $row['id'] ?></td>
    <td><?= $row['fecha'] ?></td>
    <td><?= htmlspecialchars($row['cliente']) ?></td>
    <td>$<?= number_format($row['total'], 2) ?></td>
    <td>
        <a class="btn" href="ticket.php?id=<?= $row['id'] ?>" target="_blank">Ver Ticket</a>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</body>
</html>
