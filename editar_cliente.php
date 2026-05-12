<?php
include 'db.php';

// Obtener el ID del cliente
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("⚠️ ID de cliente no especificado.");
}

$id = intval($_GET['id']);

// Buscar datos del cliente
$stmt = $conn->prepare("SELECT * FROM clientes WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("⚠️ Cliente no encontrado.");
}

$cliente = $result->fetch_assoc();

// Guardar cambios al actualizar
if (isset($_POST['actualizar_cliente'])) {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $observacion = $_POST['observacion'];

    $update_stmt = $conn->prepare("UPDATE clientes SET nombre = ?, telefono = ?, email = ?, direccion = ?, observacion = ? WHERE id = ?");
    $update_stmt->bind_param("sssssi", $nombre, $telefono, $email, $direccion, $observacion, $id);

    if ($update_stmt->execute()) {
        header("Location: clientes.php?actualizado=1");
        exit();
    } else {
        echo "<script>alert('❌ Error al actualizar el cliente.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>✏️ Editar Cliente - Joyería Sahori</title>
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
        max-width: 700px;
        margin: 40px auto;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 25px rgba(0,0,0,0.2);
        padding: 30px;
    }
    h2 {
        text-align: center;
        color: #b8860b;
    }
    .form-group {
        margin-bottom: 15px;
    }
    label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
        color: #333;
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
    .btn-cancelar {
        background-color: #d9534f;
    }
    .btn-cancelar:hover {
        background-color: #b52b27;
    }
    .actions {
        text-align: center;
        margin-top: 25px;
    }
</style>
</head>

<body>
<header>✏️ Editar Cliente - Joyería Sahori</header>

<div class="container">
    <h2>Actualizar Datos del Cliente</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label>Nombre:</label>
            <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label>Teléfono:</label>
            <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono']); ?>">
        </div>

        <div class="form-group">
            <label>Correo electrónico:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($cliente['email']); ?>">
        </div>

        <div class="form-group">
            <label>Dirección:</label>
            <textarea name="direccion" rows="2"><?= htmlspecialchars($cliente['direccion']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Observación:</label>
            <textarea name="observacion" rows="2"><?= htmlspecialchars($cliente['observacion']); ?></textarea>
        </div>

        <div class="actions">
            <button type="submit" name="actualizar_cliente" class="btn">💾 Guardar Cambios</button>
            <a href="clientes.php" class="btn btn-cancelar">⬅ Cancelar</a>
        </div>
    </form>
</div>
</body>
</html>
