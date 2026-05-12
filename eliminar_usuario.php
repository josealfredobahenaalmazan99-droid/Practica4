<?php
session_start();

// Solo admin puede eliminar
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    header("Location: panel.php");
    exit();
}

include "db.php";

// Validar ID
if (!isset($_GET['id'])) {
    header("Location: usuarios.php");
    exit();
}

$id = intval($_GET['id']);

// Evitar que el admin se elimine a sí mismo
if ($id == $_SESSION['user_id']) {
    echo "<script>alert('No puedes eliminar tu propio usuario.'); window.location='usuarios.php';</script>";
    exit();
}

$delete = $conn->prepare("DELETE FROM usuarios WHERE id=?");
$delete->bind_param("i", $id);
$delete->execute();

header("Location: usuarios.php");
exit();
