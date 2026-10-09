<?php
session_start();
require 'conexion.php';

if (!isset($_GET['cliente_id'])) {
    die("Cliente no especificado.");
}

$cliente_id = intval($_GET['cliente_id']);

// Obtener actividades del cliente
$stmt = $pdo->prepare("SELECT id, actividad FROM actividades WHERE cliente_id = ?");
$stmt->execute([$cliente_id]);
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$actividades) {
    die("Este cliente no tiene actividades.");
}

$zipname = "cliente_" . $cliente_id . "_archivos.zip";
$zip = new ZipArchive;

if ($zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

    foreach ($actividades as $act) {
        $folder = "uploads/actividad_" . $act['id'];

        if (is_dir($folder)) {
            $files = scandir($folder);

            foreach ($files as $file) {
                if ($file !== "." && $file !== "..") {
                    // Guardar dentro del ZIP en carpeta propia
                    $zip->addFile(
                        $folder . "/" . $file,
                        $act['actividad'] . "/" . $file
                    );
                }
            }
        }
    }

    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipname . '"');
    header('Content-Length: ' . filesize($zipname));

    readfile($zipname);
    unlink($zipname);
    exit;

} else {
    die("No se pudo crear el archivo ZIP.");
}
