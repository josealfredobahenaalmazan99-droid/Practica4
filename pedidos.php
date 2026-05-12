<?php
include 'db.php';
session_start();

// Filtros de búsqueda
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';
$estatus_filtro = isset($_GET['estatus']) ? trim($_GET['estatus']) : '';


// Consulta con filtros
$sql = "SELECT p.*, c.nombre AS cliente_nombre 
        FROM pedidos p 
        INNER JOIN clientes c ON p.cliente_id = c.id 
        WHERE 1=1";

if ($busqueda != '') {
    $bus = $conn->real_escape_string($busqueda);
    $sql .= " AND (c.nombre LIKE '%$bus%' OR p.descripcion LIKE '%$bus%')";
}
if ($estatus_filtro != '') {
    $est = $conn->real_escape_string($estatus_filtro);
    $sql .= " AND p.estatus = '$est'";
}

$sql .= " ORDER BY p.fecha_pedido DESC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>📜 Pedidos Especiales - Joyería Sahori</title>

<style>
    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f4efe6;
        margin: 0;
    }
    header {
        background: linear-gradient(to right, #d4af37, #b8860b);
        color: white;
        text-align: center;
        padding: 18px;
        font-size: 24px;
        letter-spacing: 1px;
    }
    .container {
        width: 95%;
        margin: 25px auto;
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #8b6508;
        font-size: 26px;
    }

    /* Buscador */
    form {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: center;
        margin-bottom: 20px;
    }
    input, select {
        padding: 10px 12px;
        border-radius: 8px;
        border: 1px solid #ccc;
        font-size: 14px;
    }
    button, .btn-agregar {
        background: #b8860b;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
    }
    button:hover, .btn-agregar:hover {
        background: #8b6508;
    }

    /* Tabla */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    th {
        background-color: #d4af37;
        padding: 10px;
        color: white;
        text-align: center;
    }
    td {
        padding: 10px;
        border-bottom: 1px solid #ddd;
        text-align: center;
    }

    /* Hover fila */
    tr:hover {
        background-color: #fbf3dd;
    }

    /* Chips de estatus */
    .chip {
        padding: 6px 12px;
        border-radius: 20px;
        color: white;
        font-weight: bold;
        font-size: 13px;
    }
    .pendiente { background: #e67e22; }
    .proceso   { background: #3498db; }
    .completo  { background: #27ae60; }
    .cancelado { background: #c0392b; }

    /* Acciones */
    .acciones a {
        padding: 6px 10px;
        border-radius: 8px;
        color: white;
        text-decoration: none;
        font-weight: bold;
        display: inline-block;
        margin: 2px;
    }
    .editar   { background: #27ae60; }
    .eliminar { background: #c0392b; }
    .pago     { background: #8e44ad; }
    .ticket   { background: #2c3e50; }

    .volver {
        display: block;
        margin-top: 25px;
        width: fit-content;
        padding: 10px 18px;
        background: #d4af37;
        color: white;
        text-decoration: none;
        font-weight: bold;
        border-radius: 8px;
    }
    .volver:hover { background: #b8860b; }

    .alert {
    padding: 6px 10px;
    border-radius: 8px;
    font-weight: bold;
    font-size: 12px;
}

.alert.rojo { background:#e74c3c; color:white; }
.alert.naranja { background:#e67e22; color:white; }
.alert.amarillo { background:#f1c40f; color:black; }

</style>
</head>

<body>

<header>📜 Pedidos Especiales - Joyería Sahori</header>

<div class="container">
    <h2>Listado de Pedidos Especiales</h2>

    <!-- Buscador -->
    <form method="GET">
        <input type="text" name="busqueda" placeholder="🔎 Buscar pedido..." value="<?= htmlspecialchars($busqueda) ?>">

        <select name="estatus">
            <option value="">Todos los estatus</option>
            <option value="Pendiente"   <?= $estatus_filtro == 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
            <option value="En proceso"  <?= $estatus_filtro == 'En proceso' ? 'selected' : '' ?>>En proceso</option>
            <option value="Completado"  <?= $estatus_filtro == 'Completado' ? 'selected' : '' ?>>Completado</option>
            <option value="Cancelado"   <?= $estatus_filtro == 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
        </select>

        <button type="submit">Buscar</button>
        <a href="agregar_pedido.php" class="btn-agregar">➕ Nuevo Pedido</a>
    </form>

    <table>
        <tr>
            <th>ID</th>
            <th>Cliente</th>
            <th>Descripción</th>
            <th>Material</th>
            <th>Talla</th>
            <th>Monto Total</th>
            <th>Anticipo</th>
            <th>Saldo</th>
            <th>Estatus</th>
            <th>Fecha</th>
            <th>Acciones</th>
        </tr>

        <?php if ($resultado->num_rows > 0): ?>
            <?php while ($row = $resultado->fetch_assoc()): ?>
            <?php
                // Chip visual del estatus
                $chip = '';
                switch ($row['estatus']) {
                    case "Pendiente":  $chip = "<span class='chip pendiente'>Pendiente</span>"; break;
                    case "En proceso": $chip = "<span class='chip proceso'>En proceso</span>"; break;
                    case "Completado": $chip = "<span class='chip completo'>Completado</span>"; break;
                    case "Cancelado":  $chip = "<span class='chip cancelado'>Cancelado</span>"; break;
                }
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['cliente_nombre']) ?></td>
                <td><?= htmlspecialchars($row['descripcion']) ?></td>
                <td><?= htmlspecialchars($row['material']) ?></td>
                <td><?= htmlspecialchars($row['talla']) ?></td>
                <td>$<?= number_format($row['monto_total'], 2) ?></td>
                <td>$<?= number_format($row['anticipo'], 2) ?></td>
                <td>$<?= number_format($row['saldo_restante'], 2) ?></td>
                <td><?= $chip ?></td>
                <td><?= $row['fecha_pedido'] ?></td>

                <td class="acciones">
                    <a href="editar_pedido.php?id=<?= $row['id'] ?>" class="editar">✏️</a>
                    <a href="eliminar_pedido.php?id=<?= $row['id'] ?>" class="eliminar" onclick="return confirm('¿Eliminar pedido?')">🗑️</a>
                    <!-- Nuevo botón para ticket -->
    <a href="ticket_pedido.php?id=<?= $row['id'] ?>" class="ticket" target="_blank">🧾</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="11">❌ No se encontraron pedidos.</td></tr>
        <?php endif; ?>
    </table>

    <a href="panel.php" class="volver">⬅ Volver al Panel</a>
</div>

</body>
</html>
