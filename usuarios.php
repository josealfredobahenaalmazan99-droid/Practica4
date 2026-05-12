<?php
session_start();
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: panel.php");
    exit();
}

include "db.php";

// Obtener usuarios
$result = $conn->query("SELECT * FROM usuarios ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #faf6f0;
            padding: 20px;
        }
        h2 {
            text-align: center;
            color: #b8860b;
        }
        table {
            width: 80%;
            margin: 20px auto;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #b8860b;
            color: white;
        }
        .btn {
            padding: 8px 12px;
            background: #b8860b;
            color: white;
            border-radius: 5px;
            text-decoration: none;
        }
        .btn:hover {
            background: #8b6508;
        }
        .btn-danger {
            background: #b02a37;
        }
        .btn-danger:hover {
            background: #7a1f27;
        }
        .btn-back {
            display: block;
            width: 200px;
            text-align: center;
            margin: 20px auto;
            background: #444;
        }
    </style>
</head>
<body>

<h2>Gestión de Usuarios</h2>

<table>
    <tr>
        <th>ID</th>
        <th>Usuario</th>
        <th>Rol</th>
        <th>Acciones</th>
    </tr>

    <?php while($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['id'] ?></td>
        <td><?= htmlspecialchars($row['username']) ?></td>
        <td><?= htmlspecialchars($row['rol']) ?></td>
        <td>
            <a class="btn" href="editar_usuario.php?id=<?= $row['id'] ?>">Editar</a>
            <a class="btn btn-danger" href="eliminar_usuario.php?id=<?= $row['id'] ?>"
               onclick="return confirm('¿Seguro que deseas eliminar este usuario?');">
               Eliminar
            </a>
        </td>
    </tr>
    <?php endwhile; ?>

</table>

<a href="panel.php" class="btn btn-back">⬅ Regresar</a>

</body>
</html>
