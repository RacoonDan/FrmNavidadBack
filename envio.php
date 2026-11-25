<?php
// envio.php - Recibe el formulario y guarda textos, enlaces e imágenes (BLOB) en MySQL.

const MAX_BYTES = 5 * 1024 * 1024; // 5 MB por imagen
const TIPOS_PERMITIDOS = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function pagina(bool $ok, string $titulo, array $mensajes): void
{
    http_response_code($ok ? 200 : 400);
    $icono = $ok ? '🎁' : '⚠️';
    $clase = $ok ? 'success' : 'danger';
    echo '<!doctype html><html lang="es"><head><meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width, initial-scale=1">'
       . '<title>' . htmlspecialchars($titulo) . '</title>'
       . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">'
       . '<link rel="stylesheet" href="css/estilos.css"></head><body>'
       . '<main class="container my-5"><div class="row justify-content-center"><div class="col-lg-6">'
       . '<div class="card shadow"><div class="card-body p-4 text-center">'
       . '<div style="font-size:3rem">' . $icono . '</div>'
       . '<h2 class="mb-3">' . htmlspecialchars($titulo) . '</h2>'
       . '<div class="alert alert-' . $clase . ' text-start"><ul class="mb-0">';
    foreach ($mensajes as $m) {
        echo '<li>' . htmlspecialchars($m) . '</li>';
    }
    echo '</ul></div><a href="formulario.html" class="btn btn-navidad w-100">'
       . ($ok ? 'Registrar otro' : 'Volver al formulario') . '</a>'
       . '</div></div></div></div></main></body></html>';
    exit;
}

// Solo se acepta POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: formulario.html');
    exit;
}

// Si el envío supera post_max_size, PHP vacía $_POST y $_FILES
if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    pagina(false, 'No se pudo guardar', ['El envío es demasiado grande. Reduce el tamaño de las imágenes (máx. 5 MB cada una).']);
}

// Conexión
try {
    require __DIR__ . '/conexion.php';
    mysqli_set_charset($conexion, 'utf8mb4');
} catch (Throwable $e) {
    error_log('Conexión: ' . $e->getMessage());
    pagina(false, 'No se pudo guardar', ['No hay conexión con la base de datos. Inténtalo más tarde.']);
}

$errores = [];

// Persona
$personaId = (int)($_POST['persona_id'] ?? 0);
$nombrePersona = null;
if ($personaId > 0) {
    $st = $conexion->prepare('SELECT nombre FROM personas WHERE id = ?');
    $st->execute([$personaId]);
    $fila = $st->get_result()->fetch_assoc();
    $nombrePersona = $fila['nombre'] ?? null;
}
if ($nombrePersona === null) {
    $errores[] = 'Selecciona una persona válida.';
}

// Las 3 opciones
$datos = [];
$hayContenido = false;
for ($n = 1; $n <= 3; $n++) {
    $descripcion = trim((string)($_POST["descripcion$n"] ?? ''));
    $enlace      = trim((string)($_POST["enlace$n"] ?? ''));
    $imagen = null;
    $tipo   = null;

    if (strlen($descripcion) > 8000) {
        $errores[] = "Opción $n: la descripción es demasiado larga.";
    }

    if ($enlace !== '') {
        $esquema = strtolower((string)parse_url($enlace, PHP_URL_SCHEME));
        if (!filter_var($enlace, FILTER_VALIDATE_URL) || !in_array($esquema, ['http', 'https'], true) || strlen($enlace) > 2048) {
            $errores[] = "Opción $n: el enlace no es una URL válida (debe empezar con http:// o https://).";
        }
    }

    $archivo = $_FILES["imagen$n"] ?? null;
    if ($archivo && $archivo['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($archivo['error'] === UPLOAD_ERR_INI_SIZE || $archivo['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errores[] = "Opción $n: la imagen supera el tamaño máximo (5 MB).";
        } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errores[] = "Opción $n: error al subir la imagen (código {$archivo['error']}).";
        } elseif ($archivo['size'] > MAX_BYTES) {
            $errores[] = "Opción $n: la imagen supera los 5 MB.";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($archivo['tmp_name']);
            if (!in_array($mime, TIPOS_PERMITIDOS, true) || @getimagesize($archivo['tmp_name']) === false) {
                $errores[] = "Opción $n: el archivo no es una imagen válida (JPG, PNG, GIF o WEBP).";
            } else {
                $imagen = file_get_contents($archivo['tmp_name']);
                $tipo   = $mime;
            }
        }
    }

    if ($descripcion !== '' || $enlace !== '' || $imagen !== null) {
        $hayContenido = true;
    }

    $datos[$n] = [
        'descripcion' => $descripcion !== '' ? $descripcion : null,
        'enlace'      => $enlace !== '' ? $enlace : null,
        'imagen'      => $imagen,
        'tipo'        => $tipo,
    ];
}

if (!$hayContenido && !$errores) {
    $errores[] = 'Completa al menos una opción (descripción, imagen o enlace).';
}

if ($errores) {
    pagina(false, 'Revisa el formulario', $errores);
}

// Inserción
$titulo  = 'Pedido de ' . $nombrePersona; // nombre <= 100 caracteres, cabe en VARCHAR(150)
$columnas = ['persona_id', 'titulo'];
$valores  = [$personaId, $titulo];
foreach ($datos as $n => $d) {
    array_push($columnas, "descripcion$n", "enlace$n", "imagen$n", "imagen{$n}_tipo");
    array_push($valores, $d['descripcion'], $d['enlace'], $d['imagen'], $d['tipo']);
}
$sql = 'INSERT INTO registros (' . implode(', ', $columnas) . ') VALUES ('
     . implode(', ', array_fill(0, count($columnas), '?')) . ')';

try {
    $st = $conexion->prepare($sql);
    $st->execute($valores);
} catch (Throwable $e) {
    error_log('Insert: ' . $e->getMessage());
    pagina(false, 'No se pudo guardar', ['Ocurrió un error al guardar en la base de datos. Inténtalo de nuevo.']);
}

pagina(true, '¡Registro guardado!', ["Gracias, $nombrePersona. Tu pedido #{$conexion->insert_id} fue registrado."]);
