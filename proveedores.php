<?php
session_start();
include 'db.php';

if (isset($_SESSION['mensaje'])) {
    echo "<script>alert('" . $_SESSION['mensaje'] . "');</script>";
    unset($_SESSION['mensaje']);
}

// Agregar nuevo proveedor
if (isset($_POST['agregar_proveedor'])) {
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $producto_suministrado = $_POST['producto_suministrado'];

    // Manejo de carga de archivo
    $ticket = $_FILES['ticket'];
    $ticket_path = '';

    if ($ticket['error'] === UPLOAD_ERR_OK) {
        $ticket_name = basename($ticket['name']);
        $ticket_tmp = $ticket['tmp_name'];
        $ticket_path = 'uploads/' . $ticket_name;

        move_uploaded_file($ticket_tmp, $ticket_path);
    }

    // Verificar si ya existe un proveedor con ese nombre
$verificar_sql = "SELECT id FROM proveedores WHERE LOWER(nombre) = LOWER('$nombre')";
$resultado = $conn->query($verificar_sql);

if ($resultado->num_rows > 0) {
    $_SESSION['mensaje'] = "El proveedor con ese nombre ya existe.";
    header("Location: proveedores.php");
    exit();
}

    $sql = "INSERT INTO proveedores (nombre, telefono, email, direccion, producto_suministrado, ticket_path) 
            VALUES ('$nombre', '$telefono', '$email', '$direccion', '$producto_suministrado', '$ticket_path')";
    if ($conn->query($sql) === TRUE) {
        $_SESSION['mensaje'] = "Proveedor agregado exitosamente.";
        header("Location: proveedores.php");
        exit();
    } else {
        $_SESSION['mensaje'] = "Error al agregar proveedor: " . $conn->error;
        header("Location: proveedores.php");
        exit();
    }
}

// Eliminar proveedor
if (isset($_POST['eliminar_proveedor'])) {
    $id = intval($_POST['proveedor_id']);

    $stmt = $conn->prepare("DELETE FROM proveedores WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        try {
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $_SESSION['mensaje'] = "✅ Proveedor eliminado exitosamente.";
                $_SESSION['tipo_mensaje'] = "success";
            } else {
                $_SESSION['mensaje'] = "⚠️ No se encontró el proveedor.";
                $_SESSION['tipo_mensaje'] = "warning";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1451 || strpos($e->getMessage(), 'foreign key') !== false) {
                $_SESSION['mensaje'] = "❌ No se puede eliminar el proveedor porque está asociado a productos o entradas.";
                $_SESSION['tipo_mensaje'] = "danger";
            } else {
                $_SESSION['mensaje'] = "⚠️ Error al eliminar proveedor: " . $e->getMessage();
                $_SESSION['tipo_mensaje'] = "danger";
            }
        }
        $stmt->close();
    } else {
        $_SESSION['mensaje'] = "⚠️ Error de preparación al eliminar el proveedor.";
        $_SESSION['tipo_mensaje'] = "danger";
    }

    header("Location: proveedores.php");
    exit();
}


// Obtener la lista de proveedores con filtro de búsqueda
$search = isset($_POST['buscar']) ? $_POST['buscar'] : '';
$proveedores_sql = "SELECT * FROM proveedores WHERE nombre LIKE '%$search%'";
$proveedores_result = $conn->query($proveedores_sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ÓPTICA 'S PRISMAVISIÓN - Gestión de Proveedores</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 80%;
            margin: auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1, h2 {
            text-align: center;
            color: #007bff;
        }

        .form-section {
            background: #f2f2f2;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            font-weight: bold;
            display: block;
            color: #333;
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
            text-align: left;
        }

        th, td {
            padding: 10px;
        }

        th {
            background-color: #007bff;
            color: white;
        }

        .btn-action {
            padding: 10px 20px;
            background-color: #2196F3; /* Botón para editar */
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            margin: 5px;
        }

        .btn-delete {
            background-color: #f44336; /* Botón para eliminar */
        }

        img {
            width: 100px;
            height: auto;
            border-radius: 5px;
        }

        .acciones {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">

    <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="alert alert-<?= $_SESSION['tipo_mensaje'] ?? 'info' ?> alert-dismissible fade show mt-3" role="alert">
        <?= $_SESSION['mensaje'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
    <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
<?php endif; ?>

        <h1>ÓPTICA 'S PRISMAVISIÓN - Gestión de Proveedores</h1>

        <div class="form-section">
            <h2>Agregar Nuevo Proveedor</h2>
            <form method="post" action="proveedores.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="nombre">Nombre:</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="telefono">Teléfono:</label>
                    <input type="text" id="telefono" name="telefono" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email">
                </div>
                <div class="form-group">
                    <label for="direccion">Dirección:</label>
                    <textarea id="direccion" name="direccion"></textarea>
                </div>
                <div class="form-group">
                    <label for="producto_suministrado">Producto Suministrado:</label>
                    <input type="text" id="producto_suministrado" name="producto_suministrado">
                </div>
                <div class="form-group">
                    <label for="ticket">Foto del Ticket:</label>
                    <input type="file" id="ticket" name="ticket">
                </div>
                <button type="submit" name="agregar_proveedor" class="btn">Agregar Proveedor</button>
            </form>
        </div>

        <div class="form-section">
            <h2>Buscar Proveedor</h2>
            <form method="post" action="proveedores.php">
                <input type="text" name="buscar" placeholder="Ingrese nombre del proveedor" value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="btn">Buscar</button>
            </form>
        </div>

        <h2>Listado de Proveedores</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Dirección</th>
                    <th>Producto Suministrado</th>
                    <th>Foto del Ticket</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($proveedores_result->num_rows > 0): ?>
                <?php while($row = $proveedores_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td><?= $row['nombre'] ?></td>
                        <td><?= $row['telefono'] ?></td>
                        <td><?= $row['email'] ?></td>
                        <td><?= $row['direccion'] ?></td>
                        <td><?= $row['producto_suministrado'] ?></td>
                        <td>
                            <?php if ($row['ticket_path']): ?>
                                <a href="<?= $row['ticket_path'] ?>" target="_blank">
                                    <img src="<?= $row['ticket_path'] ?>" alt="Ticket" width="100">
                                </a>
                            <?php else: ?>
                                No disponible
                            <?php endif; ?>
                        </td>
                        <td>
                        <a href="editar_proveedor.php?id=<?= $row['id'] ?>" class="btn-action">Editar</a>
                        <form method="post" action="proveedores.php" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este proveedor?');">
                        <input type="hidden" name="proveedor_id" value="<?= $row['id'] ?>">
                        <button type="submit" name="eliminar_proveedor" class="btn-delete">Eliminar</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" style="text-align:center; color: #888;">No se encontraron proveedores con ese nombre.</td>
        </tr>
    <?php endif; ?>
</tbody>
        </table>

        <a href="dashboard.php" class="btn">Regresar al Panel de Control</a>
    </div>
</body>
</html>
