
<?php
$EquipoVisitanteID = $_POST["EquipoVisitanteID"];
$EquipoLocalID = $_POST["EquipoLocalID"];
$Estadio41 = $_POST["Estadio41"];
$Fecha41 = $_POST["Fecha41"];
$Hora41 = $_POST["Hora41"];
$GolesVisitante41 = $_POST["GolesVisitante41"];
$GolesLocal41 = $_POST["GolesLocal41"];

$mysqli = new mysqli("localhost", "root", "", "partidos");

if ($mysqli->connect_error) {
    die("Error en la conexión: " . $mysqli->connect_error);
}

$sql = "INSERT INTO partidos (EquipoVisitanteID, EquipoLocalID, Estadio41, Fecha41, Hora41, GolesVisitante41, GolesLocal41) VALUES ('$EquipoVisitanteID', '$EquipoLocalID', '$Estadio41', '$Fecha41', '$Hora41', '$GolesVisitante41', '$GolesLocal41')";

if ($mysqli->query($sql) === TRUE) {
    echo "Partido registrado exitosamente.";
} else {
    echo "Error al registrar el partido: " . $mysqli->error;
}

$mysqli->close();
?>