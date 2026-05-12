<?php
include 'db.php';

if (!isset($_GET['codigo'])) {
    echo '0';
    exit;
}

$codigo = $_GET['codigo'];

$stmt = $conn->prepare("SELECT id FROM productos WHERE codigo = ?");
$stmt->bind_param("s", $codigo);
$stmt->execute();
$stmt->store_result();

echo ($stmt->num_rows > 0) ? '1' : '0';
