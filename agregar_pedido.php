<?php
include 'db.php';
session_start();

// ==============================
// 🔹 AGREGAR PEDIDO
// ==============================
if (isset($_POST['agregar_pedido'])) {
    $cliente_id       = $_POST['cliente_id'];
    $descripcion      = $_POST['descripcion'];
    $material         = $_POST['material'];
    $talla            = $_POST['talla'];
    $peso_aproximado  = $_POST['peso_aproximado'];
    $fecha_pedido     = $_POST['fecha_pedido'];
    $fecha_entrega    = $_POST['fecha_entrega'];
    $monto_total      = floatval($_POST['monto_total']);
    $anticipo         = floatval($_POST['anticipo']);

    // ⭐ CÁLCULO AUTOMÁTICO
    $saldo_restante = $monto_total - $anticipo;
    if ($saldo_restante < 0) {
        $saldo_restante = 0; // seguridad
    }

    $estatus          = $_POST['estatus'];

    $sql = "INSERT INTO pedidos 
            (cliente_id, descripcion, material, talla, peso_aproximado, fecha_pedido, fecha_entrega, monto_total, anticipo, saldo_restante, estatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssssssdds", 
        $cliente_id,
        $descripcion,
        $material,
        $talla,
        $peso_aproximado,
        $fecha_pedido,
        $fecha_entrega,
        $monto_total,
        $anticipo,
        $saldo_restante,
        $estatus
    );

    $stmt->execute();

    $_SESSION['mensaje'] = "✅ Pedido especial agregado correctamente.";
    header("Location: pedidos.php");
    exit();
}


// ==============================
// 🔹 ELIMINAR PEDIDO
// ==============================
if (isset($_GET['eliminar'])) {
    $id = intval($_GET['eliminar']);
    $conn->query("DELETE FROM pedidos WHERE id=$id");
    $_SESSION['mensaje'] = "🗑️ Pedido eliminado correctamente.";
    header("Location: pedidos.php");
    exit();
}

// ==============================
// 🔹 CARGAR CLIENTES Y PEDIDOS
// ==============================
$clientes = $conn->query("SELECT id, nombre FROM clientes ORDER BY nombre ASC");

$pedidos = $conn->query("
    SELECT p.*, c.nombre AS cliente 
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    ORDER BY p.id DESC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>💍 Pedidos Especiales - Joyería Sahori</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

<style>
/* -------------------------------------------
   🎨 ESTILO GENERAL
------------------------------------------- */
body {
    font-family: 'Poppins', sans-serif;
    background-color: #f8f4ec;
    margin: 0;
}

header {
    background: linear-gradient(to right, #d4af37, #b8860b);
    color: #fff;
    text-align: center;
    padding: 18px;
    font-size: 24px;
    font-weight: bold;
    letter-spacing: 1px;
}

.container {
    width: 92%;
    max-width: 1200px;
    margin: 30px auto;
    background: #fff;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 6px 25px rgba(0,0,0,0.2);
}

h2 {
    color: #b8860b;
    text-align: center;
    font-weight: 600;
    margin-bottom: 20px;
}

form {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 15px;
}

label {
    font-weight: bold;
}

input, select, textarea {
    width: 100%;
    padding: 9px;
    border-radius: 8px;
    border: 1px solid #ccc;
}

/* -------------------------------------------
   🎨 BOTONES
------------------------------------------- */
.btn {
    background-color: #b8860b;
    color: white;
    border: none;
    padding: 12px 18px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    text-align: center;
}

.btn:hover { background-color: #8b6508; }

/* -------------------------------------------
   📋 TABLA
------------------------------------------- */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}

th {
    background: #b8860b;
    color: white;
    padding: 10px;
    font-weight: bold;
}

td {
    border: 1px solid #ddd;
    padding: 9px;
    text-align: center;
}

tr:nth-child(even) { background: #f0f0f0; }

/* -------------------------------------------
   🟢 ESTATUS Y ALERTAS
------------------------------------------- */
.estado {
    padding: 5px 10px;
    border-radius: 6px;
    font-weight: 600;
    color: white;
}

.pendiente { background: #d9534f; }
.proceso   { background: #0275d8; }
.completado{ background: #5cb85c; }
.cancelado { background: #6c757d; }

.alerta-vencimiento {
    background: #ff5722;
    color: white;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    display: inline-block;
}

.vencido {
    background: #000;
    color: white;
    padding: 4px 10px;
    border-radius: 6px;
}

/* -------------------------------------------
   🔧 ACCIONES
------------------------------------------- */
.acciones a {
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    font-weight: bold;
}

.editar { background: #2e8b57; }
.eliminar { background: #b22222; }
.ticket { background: #8b6508; }

.volver {
    margin-top: 20px;
    display: inline-block;
    background: #d4af37;
    padding: 10px 16px;
    border-radius: 8px;
    color: white;
    font-weight: bold;
    text-decoration: none;
}
</style>
</head>

<body>

<header>💍 Pedidos Especiales - Joyería Sahori</header>

<div class="container">

<?php if (isset($_SESSION['mensaje'])): ?>
<div style="padding:10px; background:#d4af3770; border-radius:8px; text-align:center; margin-bottom:15px;">
    <?= $_SESSION['mensaje']; unset($_SESSION['mensaje']); ?>
</div>
<?php endif; ?>

<!-- ================================
     FORMULARIO AGREGAR PEDIDO
================================ -->
<h2>Agregar Pedido Especial</h2>

<form method="POST">
    
    <div>
        <label>Cliente:</label>
        <select name="cliente_id" required>
            <option value="">Seleccione un cliente</option>
            <?php while($c = $clientes->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div>
        <label>Descripción:</label>
        <textarea name="descripcion" required></textarea>
    </div>

    <div>
        <label>Material:</label>
        <input type="text" name="material">
    </div>

    <div>
        <label>Talla:</label>
        <input type="text" name="talla">
    </div>

    <div>
        <label>Peso aproximado (gr):</label>
        <input type="number" step="0.01" name="peso_aproximado">
    </div>

    <div>
        <label>Fecha del pedido:</label>
        <input type="date" name="fecha_pedido" required>
    </div>

    <div>
        <label>Fecha de entrega:</label>
        <input type="date" name="fecha_entrega">
    </div>

    <!-- ⭐ TOTAL -->
    <div>
        <label>Total ($):</label>
        <input type="number" step="0.01" name="monto_total" id="monto_total" required>
    </div>

    <!-- ⭐ ANTICIPO -->
    <div>
        <label>Anticipo ($):</label>
        <input type="number" step="0.01" name="anticipo" id="anticipo" required>
    </div>

    <!-- ⭐ SALDO AUTOMÁTICO -->
    <div>
        <label>Saldo restante ($):</label>
        <input type="number" step="0.01" name="saldo_restante" id="saldo_restante" readonly style="background:#eee;">
    </div>

    <div>
        <label>Estatus:</label>
        <select name="estatus">
            <option value="Pendiente">Pendiente</option>
            <option value="En proceso">En proceso</option>
            <option value="Completado">Completado</option>
            <option value="Cancelado">Cancelado</option>
        </select>
    </div>

    <div style="grid-column: span 2; text-align:center;">
        <button class="btn" name="agregar_pedido">💾 Guardar Pedido</button>
    </div>

</form>

<!-- ================================
     LISTA DE PEDIDOS
================================ -->
<h2>Listado de Pedidos Especiales</h2>

<table>
<tr>
    <th>ID</th>
    <th>Cliente</th>
    <th>Descripción</th>
    <th>Material</th>
    <th>Pedido</th>
    <th>Entrega</th>
    <th>Total</th>
    <th>Estatus</th>
    <th>Acciones</th>
</tr>

<?php while($p = $pedidos->fetch_assoc()): ?>
<?php
    // === Alerta vencimiento ===
    $dias = 999;
    if ($p['fecha_entrega']) {
        $dias = (strtotime($p['fecha_entrega']) - strtotime(date("Y-m-d"))) / 86400;
    }

    $alerta = "";
    if ($dias < 0) {
        $alerta = "<span class='vencido'>Vencido</span>";
    } elseif ($dias <= 3) {
        $alerta = "<span class='alerta-vencimiento'>Entrega en $dias días</span>";
    }

    // === Estatus visual ===
    $estadoClass = [
        "Pendiente" => "pendiente",
        "En proceso" => "proceso",
        "Completado" => "completado",
        "Cancelado" => "cancelado"
    ][$p['estatus']];
?>

<tr>
    <td><?= $p['id'] ?></td>
    <td><?= htmlspecialchars($p['cliente']) ?></td>
    <td><?= htmlspecialchars($p['descripcion']) ?></td>
    <td><?= htmlspecialchars($p['material']) ?></td>
    <td><?= $p['fecha_pedido'] ?></td>
    <td><?= $p['fecha_entrega'] ?> <br><?= $alerta ?></td>
    <td>$<?= number_format($p['monto_total'], 2) ?></td>
    <td><span class="estado <?= $estadoClass ?>"><?= $p['estatus'] ?></span></td>

    <td class="acciones">
        <a class="ticket" href="ticket_pedido.php?id=<?= $p['id'] ?>">🧾</a>
        <a class="editar" href="editar_pedido.php?id=<?= $p['id'] ?>">✏️</a>
        <a class="eliminar" href="pedidos.php?eliminar=<?= $p['id'] ?>" onclick="return confirm('¿Eliminar este pedido?')">🗑️</a>
    </td>
</tr>

<?php endwhile; ?>
</table>

<a href="panel.php" class="volver">⬅ Volver al Panel</a>

</div>

<script>
function calcularSaldo() {
    let total = parseFloat(document.getElementById("monto_total").value) || 0;
    let anticipo = parseFloat(document.getElementById("anticipo").value) || 0;
    let saldo = total - anticipo;

    if (saldo < 0) saldo = 0;
    document.getElementById("saldo_restante").value = saldo.toFixed(2);
}

document.getElementById("monto_total").addEventListener("input", calcularSaldo);
document.getElementById("anticipo").addEventListener("input", calcularSaldo);
</script>


</body>
</html>
