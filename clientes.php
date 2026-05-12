<?php
session_start();
include 'db.php';

// Mostrar alertas
if (isset($_SESSION['mensaje'])) {
    echo "<script>alert('" . $_SESSION['mensaje'] . "');</script>";
    unset($_SESSION['mensaje']);
}

// 🔍 Buscar clientes
$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search) {
    $clientes_sql = "SELECT * FROM clientes WHERE nombre LIKE ? OR telefono LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($clientes_sql);
    $likeSearch = '%' . $search . '%';
    $stmt->bind_param("sss", $likeSearch, $likeSearch, $likeSearch);
    $stmt->execute();
    $clientes_result = $stmt->get_result();
} else {
    $clientes_sql = "SELECT * FROM clientes";
    $clientes_result = $conn->query($clientes_sql);
}

// ➕ Agregar cliente
if (isset($_POST['agregar_cliente'])) {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $observacion = $_POST['observacion'];

    // Verificar si ya existe
    $stmt_check = $conn->prepare("SELECT id FROM clientes WHERE telefono = ? OR email = ?");
$stmt_check->bind_param("ss", $telefono, $email);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows > 0) {
    $_SESSION['mensaje'] = "❌ Ya existe un cliente con este teléfono o correo.";
    header("Location: clientes.php");
    exit();
}


    $stmt = $conn->prepare("INSERT INTO clientes (nombre, telefono, email, direccion, observacion) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $nombre, $telefono, $email, $direccion, $observacion);

    if ($stmt->execute()) {
        header("Location: clientes.php?agregado=1");
        exit();
    } else {
        die("Error al agregar: " . $conn->error);
    }
}

// ❌ Eliminar cliente
if (isset($_POST['eliminar_cliente'])) {

    $id = intval($_POST['cliente_id']);

    // 1️⃣ Verificar si tiene apartados activos
    $checkApartados = $conn->prepare("SELECT COUNT(*) FROM apartados WHERE cliente_id = ?");
    $checkApartados->bind_param("i", $id);
    $checkApartados->execute();
    $checkApartados->bind_result($totalApartados);
    $checkApartados->fetch();
    $checkApartados->close();

    if ($totalApartados > 0) {
        $_SESSION['mensaje'] = "❌ No se puede eliminar este cliente porque tiene apartados registrados.";
        header("Location: clientes.php");
        exit();
    }

    // 2️⃣ Verificar si tiene pedidos especiales
    $checkPedidos = $conn->prepare("SELECT COUNT(*) FROM pedidos WHERE cliente_id = ?");
    $checkPedidos->bind_param("i", $id);
    $checkPedidos->execute();
    $checkPedidos->bind_result($totalPedidos);
    $checkPedidos->fetch();
    $checkPedidos->close();

    if ($totalPedidos > 0) {
        $_SESSION['mensaje'] = "❌ No se puede eliminar este cliente porque tiene pedidos especiales registrados.";
        header("Location: clientes.php");
        exit();
    }

    // 3️⃣ Si NO tiene apartados ni pedidos → eliminar cliente
    $stmt = $conn->prepare("DELETE FROM clientes WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $_SESSION['mensaje'] = "✅ Cliente eliminado correctamente.";
        } else {
            $_SESSION['mensaje'] = "❌ No se pudo eliminar el cliente.";
        }

        $stmt->close();
    }

    header("Location: clientes.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>💎 Joyería Sahori - Gestión de Clientes</title>
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
<header>💎 Gestión de Clientes - Joyería Sahori</header>

<div class="container">

    <h2>Agregar Nuevo Cliente</h2>
    <form method="POST" action="clientes.php">
        <div class="form-group">
            <label>Nombre del cliente:</label>
            <input type="text" name="nombre" required>
        </div>
        <div class="form-group">
            <label>Teléfono:</label>
            <input type="text" name="telefono" required onkeyup="verificarCliente()">
        </div>
        <div class="form-group">
            <label>Correo electrónico:</label>
            <input type="email" name="email" onkeyup="verificarCliente()">
        </div>
        <p id="aviso-duplicado" style="font-weight: bold;"></p>
        <div class="form-group">
            <label>Dirección:</label>
            <textarea name="direccion" rows="2"></textarea>
        </div>
        <div class="form-group">
            <label>Observación:</label>
            <textarea name="observacion" rows="2" placeholder="Ejemplo: cliente frecuente, pedido especial, etc."></textarea>
        </div>
        <button type="submit" name="agregar_cliente" class="btn">➕ Agregar Cliente</button>
    </form>

    <hr style="margin: 30px 0;">

    <h2>Buscar Clientes</h2>
    <div class="search-box">
        <form method="GET" action="clientes.php">
            <input type="text" name="search" placeholder="Buscar por nombre, teléfono o email..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn">🔍 Buscar</button>
        </form>
    </div>

    <h2>Listado de Clientes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Dirección</th>
            <th>Observación</th>
            <th>Acciones</th>
        </tr>

        <?php if ($clientes_result->num_rows > 0): ?>
            <?php while($row = $clientes_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']); ?></td>
                    <td><?= htmlspecialchars($row['nombre']); ?></td>
                    <td><?= htmlspecialchars($row['telefono']); ?></td>
                    <td><?= htmlspecialchars($row['email']); ?></td>
                    <td><?= htmlspecialchars($row['direccion']); ?></td>
                    <td><?= htmlspecialchars($row['observacion']); ?></td>
                    <td class="actions">
                        <a href="editar_cliente.php?id=<?= $row['id']; ?>"><button class="edit">✏️</button></a>
                        <form method="POST" action="clientes.php" style="display:inline;">
                            <input type="hidden" name="cliente_id" value="<?= $row['id']; ?>">
                            <button type="submit" name="eliminar_cliente" class="delete" onclick="return confirm('¿Eliminar este cliente?');">🗑️</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center; color:#777;">No se encontraron clientes.</td></tr>
        <?php endif; ?>
    </table>

    <br>
    <div style="text-align:center;">
        <a href="panel.php" class="btn">⬅ Volver al Panel Principal</a>
    </div>
</div>

<script>
function verificarCliente() {
    let tel = document.querySelector("input[name='telefono']").value;
    let email = document.querySelector("input[name='email']").value;

    if (tel === "" && email === "") return;

    fetch("verificar_cliente.php?telefono=" + tel + "&email=" + email)
    .then(res => res.text())
    .then(data => {
        let aviso = document.getElementById("aviso-duplicado");

        if (data === "existe") {
            aviso.style.color = "red";
            aviso.innerText = "❌ Ya existe un cliente con este teléfono o correo.";
        } else {
            aviso.style.color = "green";
            aviso.innerText = "✔ Disponible";
        }
    });
}
</script>

</body>
</html>
