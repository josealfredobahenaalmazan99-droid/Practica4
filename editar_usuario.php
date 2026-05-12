<?php
session_start();

// Solo ADMIN puede acceder
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: panel.php");
    exit();
}

include "db.php";

// Validar ID recibido
if (!isset($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}

$id = intval($_GET['id']);

// Obtener datos del usuario
$query = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$query->bind_param("i", $id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    header("Location: usuarios.php");
    exit();
}

$user = $result->fetch_assoc();

// Guardar cambios
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $rol = $_POST['rol'];
    $password = $_POST['password'];

    if (!empty($password)) {
        // Nueva contraseña
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE usuarios SET username=?, password=?, rol=? WHERE id=?");
        $update->bind_param("sssi", $username, $hashed_password, $rol, $id);
    } else {
        // Mantener contraseña existente
        $update = $conn->prepare("UPDATE usuarios SET username=?, rol=? WHERE id=?");
        $update->bind_param("ssi", $username, $rol, $id);
    }

    $update->execute();
    header("Location: usuarios.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Usuario</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    body {
        font-family: 'Poppins', sans-serif;
        background: #faf6f0;
        padding: 20px;
    }
    .form-container {
        width: 450px;
        margin: 40px auto;
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    h2 {
        text-align: center;
        color: #b8860b;
    }
    label {
        font-weight: bold;
        color: #555;
    }
    input, select {
        width: 100%;
        padding: 10px;
        margin: 12px 0;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 16px;
    }
    .btn {
        background-color: #b8860b;
        color: white;
        padding: 10px 18px;
        border-radius: 6px;
        text-decoration: none;
        display: inline-block;
        margin-top: 10px;
    }
    .btn:hover {
        background-color: #8b6508;
    }
    .btn-back {
        background: #444;
        margin-left: 10px;
    }
</style>
</head>
<body>

<div class="form-container">
    <h2>Editar Usuario</h2>

    <form method="POST">

        <label>Nombre de Usuario:</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>Rol:</label>
        <select name="rol">
            <option value="admin" <?= $user['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
            <option value="empleado" <?= $user['rol'] === 'empleado' ? 'selected' : '' ?>>Empleado</option>
        </select>

        <label>Nueva Contraseña (opcional):</label>
        <input type="password" name="password" placeholder="Dejar vacío si no se cambia">

        <button type="submit" class="btn">Guardar Cambios</button>
        <a href="usuarios.php" class="btn btn-back">Cancelar</a>

    </form>
</div>

</body>
</html>
