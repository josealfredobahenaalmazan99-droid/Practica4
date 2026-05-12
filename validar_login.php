<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST['nombre_usuario'];
    $contrasena = $_POST['contrasena'];

    $sql = "SELECT * FROM usuarios WHERE nombre_usuario = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $fila = $resultado->fetch_assoc();
        
        // Compara contraseña (en texto plano o hash si se usa password_hash)
        if ($contrasena === $fila['contrasena']) {
            $_SESSION['usuario'] = $fila['nombre_usuario'];
            $_SESSION['rol'] = $fila['rol'];
            header("Location: panel.php");
            exit();
        } else {
            echo "<script>alert('Contraseña incorrecta.'); window.location='login.php';</script>";
        }
    } else {
        echo "<script>alert('Usuario no encontrado.'); window.location='login.php';</script>";
    }
}
?>
