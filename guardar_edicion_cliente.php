<?php
session_start();
require 'conexion.php';

$cliente_id =$_POST['cliente_id'] ?? 0;

if (!$cliente_id) {
    die("ID de cliente no válido.");
}

$map = [
    "nombre_y_apellidos"    => "Nombre_y_apellidos",
    "fecha_nacimiento"      => "fecha_nacimiento",
    "BSN"                   => "BSN",
    "Tlf"                   => "Tlf",
    "Mail"                  => "Mail",
    "IBAN"                  => "IBAN",
    "Padron_Hol"            => "Padron_Hol",
    "CP_Hol"                => "CP_Hol",
    "Calle_Hol"             => "Calle_Hol",
    "Num_Letra_Hol"         => "Num_Letra_Hol",
    "Ciudad_Hol"            => "Ciudad_Hol",
    "calle_sin_padron"      => "Calle_sin_padron",
    "Num_Let_sin_padron"    => "Num_Let_sin_padron",
    "cp_sin_padron"         => "CP_sin_padron",
    "localidad_sin_padron"  => "Localidad_sin_padron",
    "urgente"               => "urgente",
    "notas"                 => "notas",
    "Autorizacion"          => "Autorizacion",
    "publicidad"            => "publicidad",
    "en_espana"             => "en_espana"
];

// Como un checkbox desmarcado no se envía por POST, aseguramos que 'en_espana' sea 0 si no viene definido
if (!isset($_POST['en_espana'])) {$_POST['en_espana'] = 0;
}

$set = [];$params = [];

foreach ($_POST as $key =>$value) {
    if ($key === "cliente_id") continue;
    if (!isset($map[$key])) continue;

    $columna =$map[$key];$set[] = "`$columna` = :$key";
    $params[":$key"] = $value;
}

$params[':id'] =$cliente_id;

if (empty($set)) {
    die("No hay campos para actualizar");
}

$sql = "UPDATE clientes SET " . implode(", ", $set) . " WHERE id = :id";

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header("Location: cliente.php?id=$cliente_id");
exit;