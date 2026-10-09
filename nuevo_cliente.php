<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

// Capturar parámetros si se accede desde la búsqueda de un ticket
$ticket_id = $_GET['ticket_id'] ?? 0;
$q         = $_GET['q'] ?? '';

// Variables para prerellenar por defecto
$pre_nombre = '';
$pre_mail   = '';
$pre_tlf    = '';

// Si viene un ticket_id, podemos buscar directamente sus datos en la BD para autocompletar
if ($ticket_id > 0) {
    $stmtT = $pdo->prepare("SELECT nombre, email, telefono FROM tickets WHERE id = ?");
    $stmtT->execute([$ticket_id]);
    $ticket_info = $stmtT->fetch(PDO::FETCH_ASSOC);
    if ($ticket_info) {
        $pre_nombre = $ticket_info['nombre'] ?? '';
        $pre_mail   = $ticket_info['email'] ?? '';
        $pre_tlf    = $ticket_info['telefono'] ?? '';
    }
} else {
    // O si se pasan directamente por GET en la URL (ej: ?nombre=...&mail=...&tlf=...)
    $pre_nombre = $_GET['nombre'] ?? '';
    $pre_mail   = $_GET['mail'] ?? '';
    $pre_tlf    = $_GET['tlf'] ?? '';
}

// Definir enlace de volver
$volver_url = "index.php";
if ($ticket_id > 0) {
    $volver_url = "ticket.php?id=" . $ticket_id . "&modal=actividad" . (!empty($q) ? "&q=" . urlencode($q) : "");
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Nuevo cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">👤 Nuevo cliente</h1>
                <a href="<?= htmlspecialchars($volver_url) ?>" class="btn btn-outline-secondary btn-sm">← Volver</a>
            </div>

            <form action="insertar_cliente.php" method="post">
                <!-- Campos ocultos para mantener el contexto del ticket y de la búsqueda -->
                <input type="hidden" name="ticket_id" value="<?= htmlspecialchars($ticket_id) ?>">
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">

                <!-- SECCIÓN 1: DATOS PERSONALES Y CONTACTO -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0 fs-6">Información personal y contacto</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Autorización</label>
                                <input type="text" name="autorizacion" class="form-control" placeholder="Ej. Sí/No">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Nombre y apellidos <span class="text-danger">*</span></label>
                                <input type="text" name="nombre_y_apellidos" class="form-control" required placeholder="Nombre completo" value="<?= htmlspecialchars($pre_nombre) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Fecha Nacimiento</label>
                                <input type="date" name="fecha_nacimiento" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">BSN</label>
                                <input type="text" name="bsn" class="form-control" placeholder="Número BSN">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="tlf" class="form-control" placeholder="+31 ..." value="<?= htmlspecialchars($pre_tlf) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Mail</label>
                                <input type="email" name="mail" class="form-control" placeholder="correo@ejemplo.com" value="<?= htmlspecialchars($pre_mail) ?>">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">IBAN</label>
                                <input type="text" name="iban" class="form-control" placeholder="NL00BANK...">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 2: DIRECCIÓN EN HOLANDA (PADRÓN) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0 fs-6">Dirección en Holanda (Padrón)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Padrón Hol</label>
                                <input type="date" name="padron_hol" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">CP Hol</label>
                                <input type="text" name="cp_hol" class="form-control" placeholder="1234 AB">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Ciudad Hol</label>
                                <input type="text" name="ciudad_hol" class="form-control" placeholder="Ciudad">
                            </div>
                            <div class="col-md-9">
                                <label class="form-label">Calle Hol</label>
                                <input type="text" name="calle_hol" class="form-control" placeholder="Nombre de la calle">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Num/Letra Hol</label>
                                <input type="text" name="num_letra_hol" class="form-control" placeholder="Ej. 12 A">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 3: DIRECCIÓN DE ORIGEN / SIN PADRÓN -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0 fs-6">Dirección de Origen (Sin Padrón)</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Calle origen</label>
                                <input type="text" name="calle_sin_padron" class="form-control" placeholder="Calle origen">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Num/Let origen</label>
                                <input type="text" name="Num_Let_sin_padron" class="form-control" placeholder="Número/Letra">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">CP origen</label>
                                <input type="text" name="cp_sin_padron" class="form-control" placeholder="Código postal">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Localidad origen</label>
                                <input type="text" name="localidad_sin_padron" class="form-control" placeholder="Localidad">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN 4: CONFIGURACIÓN ADICIONAL (PUBLICIDAD Y ESPAÑA) -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0 fs-6">Opciones adicionales</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="publicidad" name="publicidad" value="1" checked>
                                    <label class="form-check-label" for="publicidad"><strong>Publicidad</strong></label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" id="en_espana" name="en_espana" value="1">
                                    <label class="form-check-label" for="en_espana"><strong>España</strong></label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOTONES DE ACCIÓN -->
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <a href="<?= htmlspecialchars($volver_url) ?>" class="btn btn-secondary px-4">Cancelar</a>
                    <button type="submit" class="btn btn-primary px-5">💾 Guardar cliente</button>
                </div>

            </form>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>