<?php
include 'db.php';

$telefono = $_GET['telefono'] ?? '';
$email = $_GET['email'] ?? '';

$stmt = $conn->prepare("SELECT id FROM clientes WHERE telefono = ? OR email = ?");
$stmt->bind_param("ss", $telefono, $email);
$stmt->execute();
$stmt->store_result();

echo ($stmt->num_rows > 0) ? "existe" : "no";
