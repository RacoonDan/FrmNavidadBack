<?php

$host = getenv("MYSQLHOST");
$port = getenv("MYSQLPORT");
$user = getenv("MYSQLUSER");
$password = getenv("MYSQLPASSWORD");
$database = getenv("MYSQLDATABASE");

$conexion = mysqli_connect(
    $host,
    $user,
    $password,
    $database,
    (int)$port
);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}
?>