<?php
session_start();
include 'db.php';

// Mostrar mensajes
if (isset($_SESSION['mensaje'])) {
    echo "<script>alert('" . $_SESSION['mensaje'] . "');</script>";
    unset($_SESSION['mensaje']);
}

// Mensaje si el producto ya existe
if (isset($_GET['error']) && $_GET['error'] === 'existe' && isset($_GET['codigo'])) {
    echo "<script>alert('El código de producto \"" . htmlspecialchars($_GET['codigo']) . "\" ya está registrado.');</script>";
}


// Mensaje si se agregó correctamente
if (isset($_GET['agregado']) && $_GET['agregado'] == 1) {
    echo "<script>alert('Producto agregado correctamente.');</script>";
}

// 🔍 Buscar productos
$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search) {
    $productos_sql = "SELECT * FROM productos WHERE nombre LIKE ? OR descripcion LIKE ? OR tipo LIKE ? OR material LIKE ?";
    $stmt = $conn->prepare($productos_sql);
    if ($stmt) {
        $likeSearch = '%' . $search . '%';
        $stmt->bind_param("ssss", $likeSearch, $likeSearch, $likeSearch, $likeSearch);
        $stmt->execute();
        $productos_result = $stmt->get_result();
    } else {
        die("Error en la consulta: " . $conn->error);
    }
} else {
    $productos_sql = "SELECT * FROM productos";
    $productos_result = $conn->query($productos_sql);
    if (!$productos_result) {
        die("Error al obtener productos: " . $conn->error);
    }
}

// ➕ Agregar producto
if (isset($_POST['agregar_producto'])) {
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $material = $_POST['material'];
    $color = $_POST['color'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    $descripcion = $_POST['descripcion'];

    $codigo = $_POST['codigo'];

// Verificar existencia por ID o código único
$stmt_check = $conn->prepare("SELECT id FROM productos WHERE codigo = ?");
$stmt_check->bind_param("s", $codigo);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    header("Location: productos.php?error=existe&codigo=" . urlencode($codigo));
    exit();
}

$stmt_check->close();


    $stmt = $conn->prepare("INSERT INTO productos (codigo, nombre, tipo, material, color, precio, cantidad, descripcion) 
VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssdis", $codigo, $nombre, $tipo, $material, $color, $precio, $cantidad, $descripcion);


    if ($stmt->execute()) {
        header("Location: productos.php?agregado=1");
        exit();
    } else {
        die("Error al agregar: " . $conn->error);
    }
}

// ❌ Eliminar producto
if (isset($_POST['eliminar_producto'])) {
    $id = intval($_POST['producto_id']);

    // 1️⃣ Verificar si tiene apartados activos
    $check = $conn->prepare("SELECT COUNT(*) FROM apartados WHERE producto_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->bind_result($totalApartados);
    $check->fetch();
    $check->close();

    if ($totalApartados > 0) {
        $_SESSION['mensaje'] = "❌ Este producto tiene apartados activos. Elimínelos antes de borrar el producto.";
        header("Location: productos.php");
        exit();
    }

    // 2️⃣ Intentar eliminar y capturar errores de llave foránea
    try {
        $stmt = $conn->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['mensaje'] = "✅ Producto eliminado correctamente.";
        } else {
            $_SESSION['mensaje'] = "❌ No se pudo eliminar el producto.";
        }

        $stmt->close();

    } catch (mysqli_sql_exception $e) {

        // Detectar error por llave foránea (ventas registradas)
        if ($e->getCode() == 1451) {
            $_SESSION['mensaje'] = "❌ No se puede eliminar este producto porque ya forma parte de una venta. (Restricción de llave foránea)";
        } else {
            $_SESSION['mensaje'] = "❌ Error desconocido al eliminar: " . $e->getMessage();
        }
    }

    header("Location: productos.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>💎 Joyería Sahori - Gestión de Productos</title>
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
        max-width: 1000px;
        margin: 30px auto;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        padding: 30px;
    }
    h2 {
        color: #b8860b;
        text-align: center;
    }
    .form-group {
        margin-bottom: 15px;
    }
    label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
    }
    input, textarea {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 8px;
    }
    .btn {
        background-color: #b8860b;
        color: white;
        border: none;
        padding: 10px 18px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        text-decoration: none;
    }
    .btn:hover {
        background-color: #8b6508;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 25px;
    }
    th, td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: center;
    }
    th {
        background-color: #f3e5ab;
        color: #333;
    }
    .actions button {
        margin: 3px;
        border: none;
        padding: 8px 10px;
        border-radius: 6px;
        color: white;
        cursor: pointer;
    }
    .edit { background-color: #2196F3; }
    .delete { background-color: #d9534f; }
    .search-box {
        text-align: center;
        margin-bottom: 20px;
    }
    .search-box input {
        width: 60%;
        padding: 8px;
        border-radius: 8px;
        border: 1px solid #ccc;
    }
</style>
</head>

<body>
<header>💎 Gestión de Productos - Joyería Sahori</header>

<div class="container">

    <h2>Agregar Nuevo Producto</h2>
    <form method="POST" action="productos.php">
        <div class="form-group">
            <label>Nombre del producto:</label>
            <input type="text" name="nombre" required>
        </div>
        <div class="form-group">
            <label>Código o ID del producto:</label>
            <input type="text" name="codigo" id="codigo" required onkeyup="verificarCodigo()">
            <small id="codigo_msg" style="color:red; font-weight:bold;"></small>
        </div>
        <div class="form-group">
            <label>Tipo:</label>
            <input type="text" name="tipo" placeholder="Ejemplo: Anillo, Pulsera, Collar">
        </div>
        <div class="form-group">
            <label>Material:</label>
            <input type="text" name="material" placeholder="Ejemplo: Plata, Oro, Acero">
        </div>
        <div class="form-group">
            <label>Color:</label>
            <input type="text" name="color" placeholder="Ejemplo: Dorado, Plateado">
        </div>
        <div class="form-group">
            <label>Precio:</label>
            <input type="number" step="0.01" name="precio" required>
        </div>
        <div class="form-group">
            <label>Cantidad:</label>
            <input type="number" name="cantidad" required>
        </div>
        <div class="form-group">
            <label>Descripción:</label>
            <textarea name="descripcion" rows="3"></textarea>
        </div>
        <button type="submit" name="agregar_producto" class="btn">➕ Agregar Producto</button>
    </form>

    <hr style="margin: 30px 0;">

    <h2>Buscar Productos</h2>
    <div class="search-box">
        <form method="GET" action="productos.php">
            <input type="text" name="search" placeholder="Buscar por nombre, tipo o material..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn">🔍 Buscar</button>
        </form>
    </div>

    <h2>Listado de Productos</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Material</th>
            <th>Color</th>
            <th>Precio</th>
            <th>Cantidad</th>
            <th>Descripción</th>
            <th>Acciones</th>
        </tr>

        <?php if ($productos_result->num_rows > 0): ?>
            <?php while($row = $productos_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']); ?></td>
                    <td><?= htmlspecialchars($row['nombre']); ?></td>
                    <td><?= htmlspecialchars($row['tipo']); ?></td>
                    <td><?= htmlspecialchars($row['material']); ?></td>
                    <td><?= htmlspecialchars($row['color']); ?></td>
                    <td>$<?= htmlspecialchars($row['precio']); ?></td>
                    <td><?= htmlspecialchars($row['cantidad']); ?></td>
                    <td><?= htmlspecialchars($row['descripcion']); ?></td>
                    <td class="actions">
                        <a href="editar_producto.php?id=<?= $row['id']; ?>"><button class="edit">✏️</button></a>
                        <form method="POST" action="productos.php" style="display:inline;">
                            <input type="hidden" name="producto_id" value="<?= $row['id']; ?>">
                            <button type="submit" name="eliminar_producto" class="delete" onclick="return confirm('¿Eliminar este producto?');">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align:center; color:#777;">No se encontraron productos.</td></tr>
        <?php endif; ?>
    </table>

    <br>
    <div style="text-align:center;">
        <a href="panel.php" class="btn">⬅ Volver al Panel Principal</a>
    </div>
</div>

<script>
function verificarCodigo() {
    let codigo = document.getElementById("codigo").value;

    if (codigo.length === 0) {
        document.getElementById("codigo_msg").innerHTML = "";
        return;
    }

    let xhr = new XMLHttpRequest();
    xhr.open("GET", "validar_codigo.php?codigo=" + encodeURIComponent(codigo), true);

    xhr.onload = function () {
        if (this.responseText.trim() === "1") {
            document.getElementById("codigo_msg").innerHTML = "❌ Código ya registrado";
            document.querySelector("button[name='agregar_producto']").disabled = true;
            document.querySelector("button[name='agregar_producto']").style.background = "#999";
        } else {
            document.getElementById("codigo_msg").innerHTML = "✔ Código disponible";
            document.querySelector("button[name='agregar_producto']").disabled = false;
            document.querySelector("button[name='agregar_producto']").style.background = "#b8860b";
        }
    }

    xhr.send();
}
</script>

</body>
</html>
