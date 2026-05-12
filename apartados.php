<?php
session_start();
include 'db.php';

/* ----------------------------------------------------------
   AGREGAR APARTADO
---------------------------------------------------------- */
if (isset($_POST['agregar_apartado'])) {

    $cliente_id = $_POST['cliente_id'];
    $producto_id = $_POST['producto_id'];
    $fecha_apartado = $_POST['fecha_apartado'];
    $anticipo = $_POST['anticipo'];
    $saldo_restante = $_POST['saldo_restante'];
    $estatus = $_POST['estatus'];

    // Verificar si ya está apartado sin cancelar
    $verificar = $conn->prepare("SELECT id FROM apartados WHERE producto_id = ? AND estatus != 'Cancelado'");
    $verificar->bind_param("i", $producto_id);
    $verificar->execute();
    $verificar->store_result();

    if ($verificar->num_rows > 0) {
        $_SESSION['mensaje'] = "❌ El producto ya está apartado y no está disponible.";
        header("Location: apartados.php");
        exit();
    }

    // Crear fecha límite 30 días después
    $fecha_limite = date('Y-m-d', strtotime($fecha_apartado . ' +30 days'));

    $sql = "INSERT INTO apartados (cliente_id, producto_id, fecha_apartado, fecha_limite, anticipo, saldo_restante, estatus)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissdds", $cliente_id, $producto_id, $fecha_apartado, $fecha_limite, $anticipo, $saldo_restante, $estatus);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "✅ Apartado registrado correctamente.";
    } else {
        $_SESSION['mensaje'] = "❌ Error al registrar el apartado: " . $conn->error;
    }

    header("Location: apartados.php");
    exit();
}

/* ----------------------------------------------------------
   ELIMINAR APARTADO
---------------------------------------------------------- */
if (isset($_POST['eliminar_apartado'])) {

    $id = intval($_POST['apartado_id']);

    $stmt = $conn->prepare("DELETE FROM apartados WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['mensaje'] = "🗑️ Apartado eliminado correctamente.";
    } else {
        $_SESSION['mensaje'] = "❌ Error al eliminar el apartado.";
    }

    header("Location: apartados.php");
    exit();
}

/* ----------------------------------------------------------
   CONSULTAS PRINCIPALES
---------------------------------------------------------- */
$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");
$productos = $conn->query("SELECT id, nombre, precio FROM productos ORDER BY nombre ASC");

$apartados = $conn->query("
    SELECT a.*, c.nombre AS cliente, p.nombre AS producto, p.precio AS precio_producto
    FROM apartados a
    LEFT JOIN clientes c ON a.cliente_id = c.id
    LEFT JOIN productos p ON a.producto_id = p.id
    ORDER BY a.fecha_apartado DESC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>💎 Joyería Sahori - Gestión de Apartados</title>

<style>
    body{font-family:Poppins;background:#faf6f0;margin:0}
    header{background:linear-gradient(to right,#d4af37,#b8860b);color:white;text-align:center;padding:15px;font-size:22px;font-weight:bold}
    .container{max-width:1000px;margin:30px auto;background:white;padding:30px;border-radius:15px;box-shadow:0 5px 25px rgba(0,0,0,.2)}
    h2{text-align:center;color:#b8860b}
    label{font-weight:bold}
    input,select{width:100%;padding:8px;border:1px solid #ccc;border-radius:8px}
    table{width:100%;border-collapse:collapse;margin-top:20px}
    th,td{padding:10px;border:1px solid #ddd;text-align:center}
    th{background:#f3e5ab}
    .btn{background:#b8860b;color:white;padding:10px 15px;border:none;border-radius:8px;text-decoration:none;font-weight:bold}
    .actions a,.actions button{padding:6px 10px;border-radius:6px;margin-right:4px;text-decoration:none;color:white;font-weight:bold;border:none;cursor:pointer}
    .edit{background:#3498db}.delete{background:#e74c3c}.pay{background:#f1c40f}.history{background:#16a085}.ticket{background:#2ecc71}
</style>

<script>
function actualizarPrecio() {
    let select = document.getElementById("producto_id");
    let precio = select.options[select.selectedIndex].getAttribute("data-precio");
    if (precio) {
        document.getElementById("precio_total").value = precio;
        calcularSaldo();
    }
}

function calcularSaldo() {
    let precio = parseFloat(document.getElementById("precio_total").value) || 0;
    let anticipo = parseFloat(document.getElementById("anticipo").value) || 0;
    let saldo = precio - anticipo;
    document.getElementById("saldo_restante").value = saldo < 0 ? 0 : saldo.toFixed(2);
}
</script>

</head>
<body>

<header>💎 Gestión de Apartados - Joyería Sahori</header>

<div class="container">

<?php if (isset($_SESSION['mensaje'])): ?>
    <div><?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?></div>
<?php endif; ?>

<h2>Agregar Nuevo Apartado</h2>

<form method="POST">

    <label>Cliente:</label>
    <select name="cliente_id" required>
        <option value="">Seleccione</option>
        <?php while($c = $clientes->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endwhile; ?>
    </select>

    <label>Producto:</label>
    <select name="producto_id" id="producto_id" required onchange="actualizarPrecio()">
        <option value="">Seleccione</option>
        <?php while($p = $productos->fetch_assoc()): ?>
            <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio'] ?>">
                <?= htmlspecialchars($p['nombre']) ?>
            </option>
        <?php endwhile; ?>
    </select>

    <label>Precio total:</label>
    <input type="number" id="precio_total" disabled>

    <label>Fecha apartado:</label>
    <input type="date" name="fecha_apartado" value="<?= date('Y-m-d') ?>" required>

    <label>Anticipo:</label>
    <input type="number" step="0.01" name="anticipo" id="anticipo" required oninput="calcularSaldo()">

    <label>Saldo restante:</label>
    <input type="number" step="0.01" name="saldo_restante" id="saldo_restante" required>

    <label>Estatus:</label>
    <select name="estatus">
        <option value="Pendiente">Pendiente</option>
        <option value="Pagado">Pagado</option>
        <option value="Cancelado">Cancelado</option>
    </select>

    <br><br>
    <button class="btn" name="agregar_apartado">➕ Agregar</button>

</form>

<hr>

<h2>Listado de Apartados</h2>

<table>
<tr>
    <th>ID</th><th>Cliente</th><th>Producto</th><th>Fecha</th><th>Anticipo</th>
    <th>Saldo</th><th>Estatus</th><th>Fecha Límite</th><th>Acciones</th>
</tr>

<?php if ($apartados->num_rows > 0): ?>
<?php while($a = $apartados->fetch_assoc()): ?>

<?php
/* ------------------------------------------
   AUTO-ACTUALIZA a PAGADO si saldo llega a 0
------------------------------------------- */
if ($a['saldo_restante'] <= 0 && $a['estatus'] != "Pagado") {
    $auto = $conn->prepare("UPDATE apartados SET estatus='Pagado' WHERE id=?");
    $auto->bind_param("i", $a['id']);
    $auto->execute();
    $a['estatus'] = "Pagado";
}

/* ------------------------------------------
   CÁLCULO DE ESTATUS POR FECHA
------------------------------------------- */
$hoy = date('Y-m-d');
if ($a['estatus'] == "Pagado") {
    $estatus_html = "<span style='color:green;font-weight:bold;'>PAGADO ✔</span>";
} else {
    if ($a['fecha_limite'] < $hoy) {
        $estatus_html = "<span style='color:red;font-weight:bold;'>VENCIDO</span>";
    } else {
        $d1 = new DateTime($a['fecha_limite']);
        $d2 = new DateTime($hoy);
        $dias_restantes = $d1->diff($d2)->days;

        if ($dias_restantes <= 5) {
            $estatus_html = "<span style='color:orange;font-weight:bold;'>POR VENCER ($dias_restantes días)</span>";
        } else {
            $estatus_html = "<span style='color:green;'>A tiempo ($dias_restantes días)</span>";
        }
    }
}
?>

<tr>
    <td><?= $a['id'] ?></td>
    <td><?= htmlspecialchars($a['cliente']) ?></td>
    <td><?= htmlspecialchars($a['producto']) ?></td>
    <td><?= $a['fecha_apartado'] ?></td>
    <td>$<?= number_format($a['anticipo'],2) ?></td>
    <td>$<?= number_format($a['saldo_restante'],2) ?></td>
    <td><?= $estatus_html ?></td>
    <td><?= $a['fecha_limite'] ?></td>

    <td class="actions">

        <form method="POST" style="display:inline;">
            <input type="hidden" name="apartado_id" value="<?= $a['id'] ?>">
            <button name="eliminar_apartado" class="delete">🗑️</button>
        </form>

        <a href="editar_apartado.php?id=<?= $a['id'] ?>" class="edit">✏️</a>
        <a href="registrar_pago.php?id=<?= $a['id'] ?>" class="pay">💰</a>
        <a href="historial_pagos.php?id=<?= $a['id'] ?>" class="history">📄</a>
        <a href="generar_ticket_apartado.php?id=<?= $a['id'] ?>" class="ticket" target="_blank">🧾</a>

    </td>
</tr>

<?php endwhile; ?>
<?php else: ?>
<tr><td colspan="9">No hay apartados registrados.</td></tr>
<?php endif; ?>

</table>

<br>
<div style="text-align:center;">
<a href="panel.php" class="btn">⬅ Volver al panel</a>
</div>

</div>
</body>
</html>
