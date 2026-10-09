<?php
session_start();
require 'conexion_contabilidad.php';
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['usuario_id'])) {
    die("No tienes permiso.");
}

$id_usuario = $_SESSION['usuario_id'];
$cliente = $_POST['cliente'];

$archivo = $_FILES['excel']['tmp_name'];
$spreadsheet = IOFactory::load($archivo);
$hoja = $spreadsheet->getActiveSheet();
$filas = $hoja->toArray(null, true, true, true);

$primera = true;

foreach ($filas as $fila) {

    if ($primera) { 
        $primera = false; 
        continue; 
    }

    $fecha          = $fila['A'];
    $cuenta_debe    = $fila['B'];
    $importe_debe   = $fila['C'];
    $descripcion    = $fila['D'];
    $cuenta_haber   = $fila['E'];
    $importe_haber  = $fila['F'];

    $stmt = $pdo_conta->prepare("
        INSERT INTO libro_diario 
        (fecha, cuenta_debe, importe_debe, descripcion_debe, cuenta_haber, importe_haber, descripcion_haber, origen_archivo, id_usuario, cliente)
        VALUES
        (:fecha, :cuenta_debe, :importe_debe, :descripcion, :cuenta_haber, :importe_haber, :descripcion, 'Excel', :id_usuario, :cliente)
    ");

    $stmt->execute([
        ':fecha' => $fecha,
        ':cuenta_debe' => $cuenta_debe,
        ':importe_debe' => $importe_debe,
        ':descripcion' => $descripcion,
        ':cuenta_haber' => $cuenta_haber,
        ':importe_haber' => $importe_haber,
        ':id_usuario' => $id_usuario,
        ':cliente' => $cliente
    ]);
}

header("Location: libro_diario.php");
exit;