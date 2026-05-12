<?php
$servername = "localhost";
$username = "u599276734_Alfredito";
$password = "SMAA1234a";
$dbname = "u599276734_JoyeriaM";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>
