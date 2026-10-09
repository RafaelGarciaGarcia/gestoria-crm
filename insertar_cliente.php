<?php
session_start();
require 'conexion.php';

// Capturar parámetros de ticket y búsqueda
$ticket_id = isset($_POST['ticket_id']) ? intval($_POST['ticket_id']) : 0;
$search    = isset($_POST['q']) ? trim($_POST['q']) : '';

// Capturar campos del formulario de cliente
$autorizacion         = $_POST['autorizacion'] ?? '';
$nombre_y_apellidos   = $_POST['nombre_y_apellidos'] ?? '';
$fecha_nacimiento     = !empty($_POST['fecha_nacimiento']) ? $_POST['fecha_nacimiento'] : NULL;
$bsn                  = $_POST['bsn'] ?? '';
$tlf                  = $_POST['tlf'] ?? '';
$mail                 = $_POST['mail'] ?? '';
$iban                 = $_POST['iban'] ?? '';

// Capturar toggles de publicidad y en_espana (1 si están marcados, 0 si no)
$publicidad           = isset($_POST['publicidad']) ? 1 : 0;
$en_espana            = isset($_POST['en_espana']) ? 1 : 0;

$padron_hol           = !empty($_POST['padron_hol']) ? $_POST['padron_hol'] : NULL;
$cp_hol               = $_POST['cp_hol'] ?? '';
$calle_hol            = $_POST['calle_hol'] ?? '';
$num_letra_hol        = $_POST['num_letra_hol'] ?? '';
$ciudad_hol           = $_POST['ciudad_hol'] ?? '';
$calle_sin_padron     = $_POST['calle_sin_padron'] ?? ($_POST['calle_sin'] ?? '');
$num_let_sin_padron   = $_POST['Num_Let_sin_padron'] ?? ($_POST['num_let_sin_padron'] ?? '');
$cp_sin_padron        = $_POST['cp_sin_padron'] ?? '';
$localidad_sin_padron = $_POST['localidad_sin_padron'] ?? '';

// Consulta SQL directa incluyendo publicidad y en_espana
$sql = "INSERT INTO clientes (
    Autorizacion,
    Nombre_y_apellidos,
    fecha_nacimiento,
    BSN,
    Tlf,
    Mail,
    IBAN,
    publicidad,
    en_espana,
    Padron_Hol,
    CP_Hol,
    Calle_Hol,
    Num_Letra_Hol,
    Ciudad_Hol,
    Calle_sin_padron,
    Num_Let_sin_padron,
    CP_sin_padron,
    Localidad_sin_padron,
    urgente,
    notas
) VALUES (
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ''
)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $autorizacion,
    $nombre_y_apellidos,
    $fecha_nacimiento,
    $bsn,
    $tlf,
    $mail,
    $iban,
    $publicidad,
    $en_espana,
    $padron_hol,
    $cp_hol,
    $calle_hol,
    $num_letra_hol,
    $ciudad_hol,
    $calle_sin_padron,
    $num_let_sin_padron,
    $cp_sin_padron,
    $localidad_sin_padron
]);

// Redirección
if ($ticket_id > 0) {
    header("Location: ticket.php?id=" . $ticket_id . "&modal=actividad&q=" . urlencode($search));
} else {
    header("Location: index.php" . (!empty($search) ? "?search=" . urlencode($search) : ""));
}
exit;