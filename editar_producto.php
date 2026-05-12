<?php
include 'db.php';
session_start();

// Verificar que se reciba el ID
if (!isset($_GET['id'])) {
    die("❌ ID de producto no especificado.");
}

$id = intval($_GET['id']);

// 🔹 Obtener datos actuales del producto
$stmt = $conn->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("❌ Producto no encontrado.");
}

$producto = $result->fetch_assoc();

// 🔹 Guardar cambios si el formulario se envía
if (isset($_POST['actualizar_producto'])) {
    $nombre = $_POST['nombre'];
    $tipo = $_POST['tipo'];
    $material = $_POST['material'];
    $color = $_POST['color'];
    $precio = $_POST['precio'];
    $cantidad = $_POST['cantidad'];
    $descripcion = $_POST['descripcion'];

    $update_stmt = $conn->prepare("UPDATE productos SET nombre = ?, tipo = ?, material = ?, color = ?, precio = ?, cantidad = ?, descripcion = ? WHERE id = ?");
    $update_stmt->bind_param("ssssdisi", $nombre, $tipo, $material, $color, $precio, $cantidad, $descripcion, $id);

    if ($update_stmt->execute()) {
        $_SESSION['mensaje'] = "✅ Producto actualizado correctamente.";
        header("Location: productos.php");
        exit();
    } else {
        echo "<script>alert('❌ Error al actualizar el producto.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>✏️ Editar Producto - Joyería Sahori</title>
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
        max-width: 700px;
        margin: 40px auto;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        padding: 25px;
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
    .btn-cancel {
        background-color: #888;
        margin-left: 10px;
    }
    .btn-cancel:hover {
        background-color: #666;
    }
</style>
</head>

<body>
<header>✏️ Editar Producto - Joyería Sahori</header>

<div class="container">
    <h2>Actualizar Datos del Producto</h2>

    <form method="POST" action="">
        <div class="form-group">
            <label>Nombre del producto:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label>Tipo:</label>
            <input type="text" name="tipo" value="<?= htmlspecialchars($producto['tipo']); ?>" placeholder="Ejemplo: Anillo, Pulsera, Collar">
        </div>

        <div class="form-group">
            <label>Material:</label>
            <input type="text" name="material" value="<?= htmlspecialchars($producto['material']); ?>" placeholder="Ejemplo: Oro, Plata, Acero">
        </div>

        <div class="form-group">
            <label>Color:</label>
            <input type="text" name="color" value="<?= htmlspecialchars($producto['color']); ?>">
        </div>

        <div class="form-group">
            <label>Precio:</label>
            <input type="number" step="0.01" name="precio" value="<?= htmlspecialchars($producto['precio']); ?>" required>
        </div>

        <div class="form-group">
            <label>Cantidad:</label>
            <input type="number" name="cantidad" value="<?= htmlspecialchars($producto['cantidad']); ?>" required>
        </div>

        <div class="form-group">
            <label>Descripción:</label>
            <textarea name="descripcion" rows="3"><?= htmlspecialchars($producto['descripcion']); ?></textarea>
        </div>

        <div style="text-align:center;">
            <button type="submit" name="actualizar_producto" class="btn">💾 Guardar Cambios</button>
            <a href="productos.php" class="btn btn-cancel">⬅ Cancelar</a>
        </div>
    </form>
</div>

</body>
</html>
