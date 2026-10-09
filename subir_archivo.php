<?php
session_start();
require 'conexion.php';

$actividad_id = $_POST['actividad_id'] ?? 0;
$cliente_id = $_POST['cliente_id'] ?? 0;

if ($actividad_id <= 0) {
    die("Actividad no válida.");
}

$permitidos = ['pdf', 'jpg', 'jpeg', 'png'];
$carpeta = "uploads/actividad_$actividad_id";

// Comprobar que se han enviado archivos
if (isset($_FILES['archivos']) && !empty($_FILES['archivos']['name'][0])) {

    // Crear la carpeta si no existe
    if (!is_dir($carpeta)) {
        mkdir($carpeta, 0777, true);
    }

    // Recorrer el array de archivos múltiples
    foreach ($_FILES['archivos']['name'] as $key => $nombre) {
        $tmp = $_FILES['archivos']['tmp_name'][$key];
        $error = $_FILES['archivos']['error'][$key];

        // Verificar que el archivo se haya subido correctamente sin errores
        if ($error === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

            // Validar extensión permitida
            if (in_array($ext, $permitidos)) {
                $ruta_final = $carpeta . "/" . uniqid() . "_" . $nombre;

                if (move_uploaded_file($tmp, $ruta_final)) {
                    // Guardar registro en la base de datos para cada archivo subido
                    $stmt = $pdo->prepare("
                        INSERT INTO actividad_archivos (actividad_id, nombre_archivo, ruta)
                        VALUES (:actividad_id, :nombre, :ruta)
                    ");

                    $stmt->execute([
                        ':actividad_id' => $actividad_id,
                        ':nombre'       => $nombre,
                        ':ruta'         => $ruta_final
                    ]);
                }
            }
        }
    }
}

header("Location: logs.php?actividad_id=$actividad_id&cliente_id=$cliente_id");
exit;