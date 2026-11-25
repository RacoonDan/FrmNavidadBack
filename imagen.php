<?php
// imagen.php?id=REGISTRO&n=1|2|3  -> muestra una imagen guardada en la base de datos
require __DIR__ . '/conexion.php';

$id = (int)($_GET['id'] ?? 0);
$n  = (int)($_GET['n'] ?? 0);
if ($id < 1 || $n < 1 || $n > 3) {
    http_response_code(400);
    exit;
}

$st = $conexion->prepare("SELECT imagen$n AS img, imagen{$n}_tipo AS tipo FROM registros WHERE id = ?");
$st->execute([$id]);
$fila = $st->get_result()->fetch_assoc();

if (!$fila || $fila['img'] === null) {
    http_response_code(404);
    exit;
}

header('Content-Type: ' . $fila['tipo']);
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . strlen($fila['img']));
echo $fila['img'];
