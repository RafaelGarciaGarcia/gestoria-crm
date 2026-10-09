<?php 
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$cliente_id = $_GET['id'] ?? ($_POST['cliente_id'] ?? 0);

if (!$cliente_id) {
    die("Cliente no encontrado");
}

// PETICIÓN AJAX PARA ACTUALIZAR LOS TOGGLES EN TIEMPO REAL
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_update'])) {
    $campo = $_POST['campo'] ?? '';
    $valor = $_POST['valor'] ?? 0;

    // Campos permitidos por seguridad
    $permitidos = ['publicidad', 'en_espana', 'urgente'];

    if (in_array($campo, $permitidos)) {
        $stmt = $pdo->prepare("UPDATE clientes SET `$campo` = ? WHERE id = ?");
        $stmt->execute([$valor, $cliente_id]);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Campo no permitido']);
    }
    exit;
}

// PROCESAR LA EDICIÓN GENERAL DEL MODAL (SI SE ENVÍA)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_cliente'])) {
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

    if (!isset($_POST['en_espana'])) $_POST['en_espana'] = 0;
    if (!isset($_POST['publicidad'])) $_POST['publicidad'] = 0;

    $set = [];
    $params = [];

    foreach ($_POST as $key => $value) {
        if ($key === "cliente_id" || $key === "actualizar_cliente") continue;
        if (!isset($map[$key])) continue;

        $columna = $map[$key];
        $set[] = "`$columna` = :$key";
        $params[":$key"] = $value;
    }

    $params[':id'] = $cliente_id;

    if (!empty($set)) {
        $sql = "UPDATE clientes SET " . implode(", ", $set) . " WHERE id = :id";
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmtUpdate = $pdo->prepare($sql);
        $stmtUpdate->execute($params);
    }

    header("Location: cliente.php?id=$cliente_id");
    exit;
}

// Cargar usuario para map logs
$usuarios = $pdo->query("SELECT id, nombre FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);

// Descargar todos los archivos del cliente
if (isset($_GET['descargar_todo'])) {
    $stmtCliente = $pdo->prepare("SELECT Nombre_y_apellidos FROM clientes WHERE id = ?");
    $stmtCliente->execute([$cliente_id]);
    $clienteZip = $stmtCliente->fetch(PDO::FETCH_ASSOC);

    $nombre_cliente = $clienteZip['Nombre_y_apellidos'] ?? 'cliente';
    $nombre_cliente = str_replace(' ', '_', $nombre_cliente);
    $nombre_cliente = iconv('UTF-8', 'ASCII//TRANSLIT', $nombre_cliente);
    $zipname = $nombre_cliente . ".zip";

    $stmt = $pdo->prepare("SELECT id, actividad FROM actividades WHERE cliente_id = ?");
    $stmt->execute([$cliente_id]);
    $actividadesZip = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$actividadesZip) {
        die("Este cliente no tiene actividades.");
    }

    $zip = new ZipArchive;
    if ($zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($actividadesZip as $act) {
            $folder = "uploads/actividad_" . $act['id'];
            if (is_dir($folder)) {
                $files = scandir($folder);
                foreach ($files as $file) {
                    if ($file !== "." && $file !== "..") {
                        $nombre_actividad = str_replace(' ', '_', $act['actividad']);
                        $nombre_actividad = iconv('UTF-8', 'ASCII//TRANSLIT', $nombre_actividad);
                        $zip->addFile($folder . "/" . $file, $nombre_actividad . "/" . $file);
                    }
                }
            }
        }
        $zip->close();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipname) . '"');
        header('Content-Length: ' . filesize($zipname));
        readfile($zipname);
        unlink($zipname);
        exit;
    } else {
        die("No se pudo crear el archivo ZIP.");
    }
}

// Obtener datos del cliente actualizados
$stmtCliente = $pdo->prepare("SELECT * FROM clientes WHERE id = :id");
$stmtCliente->execute([':id' => $cliente_id]);
$cliente = $stmtCliente->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente no encontrado");
}

$stmtFecha = $pdo->prepare("
    SELECT id, fecha_limite
    FROM actividades
    WHERE cliente_id = :id
    AND fecha_limite IS NOT NULL
    ORDER BY fecha_limite ASC
    LIMIT 1
");
$stmtFecha->execute([':id' => $cliente_id]);
$actividad_fecha = $stmtFecha->fetch(PDO::FETCH_ASSOC);

$stmtAct = $pdo->prepare("
    SELECT a.*, 
           u.nombre AS asignado_nombre, 
           (SELECT fecha FROM actividad_logs WHERE actividad_id = a.id ORDER BY fecha DESC LIMIT 1) AS ultimo_log,
           (SELECT COUNT(*) FROM tickets WHERE actividad_id = a.id) AS num_tickets
    FROM actividades a 
    LEFT JOIN usuarios u ON u.id = a.usuario_asignado_id 
    WHERE a.cliente_id = :id 
    ORDER BY a.actividad ASC
");
$stmtAct->execute([':id' => $cliente_id]);
$actividades = $stmtAct->fetchAll(PDO::FETCH_ASSOC);

$stmtNotas =$pdo->prepare("SELECT * FROM notas_cliente WHERE cliente_id = :id ORDER BY fecha DESC");
$stmtNotas->execute([':id' =>$cliente_id]);
$notas = $stmtNotas->fetchAll(PDO::FETCH_ASSOC);

function bolaEstadoTicket($estado) {
    $estado_lower = strtolower(trim($estado));
    
    if (str_contains($estado_lower, 'asignado') || str_contains($estado_lower, 'asociado') || (str_contains($estado_lower, 'ticket') && !str_contains($estado_lower, 'respondido'))) {
        return '<span title="Ticket: Asociado" style="font-size: 1.2rem; cursor: default;" aria-label="Ticket">📨</span>';
    }

    return match(true) {
        str_contains($estado_lower, 'nuevo') => '<span title="Nuevo" style="font-size: 1.2rem; cursor: default;">🔵</span>',
        str_contains($estado_lower, 'respondido') => '<span title="Respondido" style="font-size: 1.2rem; cursor: default;">🟡</span>',
        str_contains($estado_lower, 'terminado') || str_contains($estado_lower, 'terminada') => '<span title="Terminado" style="font-size: 1.2rem; cursor: default;">🟢</span>',
        str_contains($estado_lower, 'interesado') => '<span title="Interesado" style="font-size: 1.2rem; cursor: default;">🔵</span>',
        str_contains($estado_lower, 'gestionando') => '<span title="Gestionando" style="font-size: 1.2rem; cursor: default;">🟡</span>',
        str_contains($estado_lower, 'al carajo') => '<span title="Al carajo" style="font-size: 1.2rem; cursor: default;">💀</span>',
        str_contains($estado_lower, 'imposibilitado') => '<span title="Imposibilitado" style="font-size: 1.2rem; cursor: default;">⚫</span>',
        default => '<span title="Estado: ' . htmlspecialchars($estado) . '" style="font-size: 1.2rem; cursor: default;">🔵</span>'
    };
}
?>

<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Cliente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function abrirVentana(url) {
            window.open(url, "_blank", "width=1200,height=800,top=100,left=100,resizable=yes,scrollbars=yes");
        }

        // Función JavaScript para actualizar toggles mediante AJAX en tiempo real
        function actualizarToggle(campo, checkbox) {
            let valor = checkbox.checked ? 1 : 0;
            let clienteId = <?= $cliente_id ?>;

            let formData = new FormData();
            formData.append('ajax_update', '1');
            formData.append('campo', campo);
            formData.append('valor', valor);
            formData.append('cliente_id', clienteId);

            fetch('cliente.php?id=' + clienteId, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    alert('Error al actualizar el campo');
                    checkbox.checked = !valor;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                checkbox.checked = !valor;
            });
        }
    </script>
</head>
<body class="bg-light">

<!-- CABECERA FIJA (STICKY) AL HACER SCROLL -->
<div class="sticky-top bg-light py-3 border-bottom shadow-sm mb-4">
    <div class="container d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3">
            <a href="index.php" class="btn btn-secondary btn-sm">← Volver</a>
            <h3 class="mb-0 fs-4">👤 <?= htmlspecialchars($cliente['Nombre_y_apellidos']) ?></h3>
        </div>
        <div>
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editarClienteModal">
                ✏ Editar datos
            </button>
        </div>
    </div>
</div>

<div class="container py-2">

    <!-- DATOS DEL CLIENTE -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0 fs-6">Información general</h5>
        </div>

        <div class="card-body">

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nombre:</strong> <?= htmlspecialchars($cliente['Nombre_y_apellidos']) ?></p>
                    <p><strong>Autorización:</strong> <?= htmlspecialchars($cliente['Autorizacion']) ?></p>
                    <p><strong>Fecha nacimiento:</strong>
                        <?= ($cliente['fecha_nacimiento'] &&$cliente['fecha_nacimiento'] !== '0000-00-00')
                            ? date("d/m/Y", strtotime($cliente['fecha_nacimiento']))
                            : '—' ?>
                    </p>
                    <p><strong>BSN:</strong> <?= htmlspecialchars($cliente['BSN']) ?></p>
                    <p><strong>Teléfono:</strong> <?= htmlspecialchars($cliente['Tlf']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($cliente['Mail']) ?></p>
                    <p><strong>IBAN:</strong> <?= htmlspecialchars($cliente['IBAN']) ?></p>

                    <!-- TOGGLE PUBLICIDAD DIRECTO EN VISTA -->
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="togglePublicidad" 
                            <?= (!isset($cliente['publicidad']) ||$cliente['publicidad'] == 1) ? 'checked' : '' ?>
                            onchange="actualizarToggle('publicidad', this)">
                        <label class="form-check-label" for="togglePublicidad"><strong>Publicidad</strong></label>
                    </div>

                    <!-- TOGGLE ESPAÑA DIRECTO EN VISTA -->
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="toggleEspana" 
                            <?= (isset($cliente['en_espana']) &&$cliente['en_espana'] == 1) ? 'checked' : '' ?>
                            onchange="actualizarToggle('en_espana', this)">
                        <label class="form-check-label" for="toggleEspana"><strong>España</strong></label>
                    </div>

                </div>

                <div class="col-md-6">
                    <p><strong>Padrón Origen:</strong>
                        <?= ($cliente['Padron_Hol'] &&$cliente['Padron_Hol'] !== '0000-00-00')
                            ? date("d/m/Y", strtotime($cliente['Padron_Hol']))
                            : '—' ?>
                    </p>
                    <p><strong>CP Origen:</strong> <?= htmlspecialchars($cliente['CP_Hol']) ?></p>
                    <p><strong>Calle Origen:</strong> <?= htmlspecialchars($cliente['Calle_Hol']) ?></p>
                    <p><strong>Num/Letra Origen:</strong> <?= htmlspecialchars($cliente['Num_Letra_Hol']) ?></p>
                    <p><strong>Ciudad Origen:</strong> <?= htmlspecialchars($cliente['Ciudad_Hol']) ?></p>
                </div>
            </div>

            <hr>

            <h6 class="text-muted">Dirección sin padrón</h6>

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Calle Origen:</strong> <?= htmlspecialchars($cliente['Calle_sin_padron']) ?></p>
                    <p><strong>Num/Let Origen:</strong> <?= htmlspecialchars($cliente['Num_Let_sin_padron']) ?></p>
                </div>

                <div class="col-md-6">
                    <p><strong>CP Origen:</strong> <?= htmlspecialchars($cliente['cp_sin_padron']) ?></p>
                    <p><strong>Localidad Origen:</strong> <?= htmlspecialchars($cliente['localidad_sin_padron']) ?></p>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <p><strong>Urgente:</strong> <?= $cliente['urgente'] ? "🔥 Sí" : "—" ?></p>
                    <p><strong>Fecha límite más cercana:</strong>
                        <?= ($actividad_fecha && $actividad_fecha['fecha_limite'] &&$actividad_fecha['fecha_limite'] !== '0000-00-00')
                            ? date("d/m/Y", strtotime($actividad_fecha['fecha_limite']))
                            : '—' ?>
                    </p>

                    <?php if ($actividad_fecha): ?>
                        <a href="logs.php?actividad_id=<?= $actividad_fecha['id'] ?>&cliente_id=<?=$cliente_id ?>"
                           class="btn btn-warning mb-3" target="_blank" rel="noopener noreferrer">
                            Ver actividad con fecha límite
                        </a>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- NOTAS DEL CLIENTE -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h4 class="mb-0">Notas del cliente</h4>
        </div>
        <div class="card-body">
            <form method="post" action="guardar_nota.php">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                <textarea name="nota" class="form-control" rows="3" required></textarea>
                <button type="submit" class="btn btn-primary mt-3">Añadir nota</button>
            </form>

            <hr>

            <?php if (empty($notas)): ?>
                <p class="text-muted">No hay notas todavía.</p>
            <?php else: ?>
                <?php foreach ($notas as $n): ?>
                    <div class="border rounded p-3 mb-3 bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($n['fecha'])) ?></small>
                            <div>
                                <a href="editar_nota.php?id=<?= $n['id'] ?>&cliente_id=<?=$cliente_id ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                                <a href="borrar_nota.php?id=<?= $n['id'] ?>&cliente_id=<?=$cliente_id ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro?')">Borrar</a>
                            </div>
                        </div>
                        <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($n['nota'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ACTIVIDADES -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Actividades del cliente</h4>
            <a href="nueva_actividad.php?cliente_id=<?= $cliente_id ?>" class="btn btn-success btn-sm" target="_blank">➕ Crear actividad</a>
        </div>
        <div class="card-body">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Actividad</th>
                        <th>Estado</th>
                        <th>Urgente</th>
                        <th>Asignado a</th>
                        <th>Fecha límite</th>
                        <th>Último log</th>
                        <th>Logs</th>
                        <th>Tickets</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($actividades as$act): ?>
                        <?php
                        $estado_texto =$act['estado'] ?? 'Null';
                        $urg =$act['urgente'] ? "🔥 Sí" : "—";
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($act['actividad']) ?></td>
                            <td><?= bolaEstadoTicket($estado_texto) . " " . htmlspecialchars($estado_texto) ?></td>
                            <td><?= $urg ?></td>
                            <td><?= htmlspecialchars($act['asignado_nombre'] ?: "—") ?></td>
                            <td><?= (!empty($act['fecha_limite']) && $act['fecha_limite'] !== '0000-00-00') ? date('d/m/Y', strtotime($act['fecha_limite'])) : '' ?></td>
                            <td><?= (!empty($act['ultimo_log']) &&$act['ultimo_log'] !== '0000-00-00 00:00:00') ? date('d/m/Y H:i', strtotime($act['ultimo_log'])) : '' ?></td>
                            <td><a href="logs.php?actividad_id=<?= $act['id'] ?>&cliente_id=<?=$cliente_id ?>" class="btn btn-primary btn-sm" target="_blank">Ver</a></td>
                            <td>
                                <a href="tickets_actividad.php?actividad_id=<?= $act['id'] ?>&cliente_id=<?=$cliente_id ?>" class="btn btn-outline-secondary btn-sm position-relative" target="_blank">
                                    Tickets
                                    <?php if ($act['num_tickets'] > 0): ?>
                                        <span class="badge bg-info text-dark ms-1"><?= $act['num_tickets'] ?></span>
                                    <?php endif; ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php
$stmtLogsGlobal =$pdo->prepare("
    SELECT l.id AS log_id, l.fecha, l.mensaje, l.urgente, l.estado_en_log,
        l.usuario_id, l.actividad_id, a.actividad AS nombre_actividad
    FROM actividad_logs l
    INNER JOIN actividades a ON a.id = l.actividad_id
    WHERE a.cliente_id = :cid
    ORDER BY l.fecha DESC
");
$stmtLogsGlobal->execute([':cid' =>$cliente_id]);
$logs_globales =$stmtLogsGlobal->fetchAll(PDO::FETCH_ASSOC);

$usuariosMap = [];
foreach ($usuarios as $u) {$usuariosMap[$u['id']] =$u['nombre'];
}
?>

<!-- HISTORIAL GLOBAL DE LOGS -->
<div class="card shadow-sm mb-4">
    <div class="card-header"><h4 class="mb-0">Historial global de logs</h4></div>
    <div class="card-body">
        <?php if (empty($logs_globales)): ?>
            <p class="text-muted">Este cliente no tiene logs todavía.</p>
        <?php else: ?>
            <div style="max-height: 550px; overflow-y: auto; padding-right: 10px;">
                <?php foreach ($logs_globales as$log): ?>
                    <?php
                        $usuario = $usuariosMap[$log['usuario_id']] ?? 'Desconocido';
                        
                        // Detectar si el log hace referencia a un ticket o viene de un ticket asociado
                        $es_ticket = preg_match('/Ticket #(\d+)/i', $log['mensaje'], $matches);$ticket_id_detectado = $es_ticket ? $matches[1] : null;

                        $ticket_info = null;
                        if ($ticket_id_detectado) {
                            $stmtT =$pdo->prepare("SELECT * FROM tickets WHERE id = ?");
                            $stmtT->execute([$ticket_id_detectado]);
                            $ticket_info =$stmtT->fetch(PDO::FETCH_ASSOC);
                        }

                        // Determinar el texto de estado efectivo para la bolita
                        $estado_log =$log['estado_en_log'] ?? '';
                        if (($ticket_info && !empty($ticket_info['id'])) || str_contains(strtolower($log['mensaje']), 'ticket #')) {
                            // Si es un ticket asociado, combinamos con el estado que tenga o lo marcamos como asociado/ticket
                            $estado_log = 'asociado'; 
                        }

                        $bolitas_unificadas = bolaEstadoTicket($estado_log);
                    ?>
                    <div class="border rounded p-3 mb-3 bg-white">
                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($log['fecha'])) ?> — <?= htmlspecialchars($usuario) ?></small>
                        <p class="mb-1 mt-2"><strong><?= $bolitas_unificadas ?> <?= htmlspecialchars($log['nombre_actividad']) ?></strong></p>
                        <p class="mb-2"><?= nl2br(htmlspecialchars($log['mensaje'])) ?></p>
                        <a href="logs.php?actividad_id=<?= $log['actividad_id'] ?>&cliente_id=<?=$cliente_id ?>" class="btn btn-primary btn-sm" target="_blank">Ir a actividad</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<a href="eliminar_cliente.php?id=<?= $cliente_id ?>" class="btn btn-danger mb-4" onclick="return confirm('¿Seguro que quieres eliminar este cliente?')">🗑 Eliminar cliente</a>

</div>

<!-- MODAL EDITAR OTROS DATOS -->
<div class="modal fade" id="editarClienteModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="cliente.php?id=<?= $cliente_id ?>">
        <div class="modal-header">
          <h5 class="modal-title">Editar datos del cliente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
          <input type="hidden" name="actualizar_cliente" value="1">

          <div class="mb-3">
              <label class="form-label">Autorización</label>
              <input type="text" name="Autorizacion" class="form-control" value="<?= htmlspecialchars($cliente['Autorizacion']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Nombre y apellidos</label>
              <input type="text" name="nombre_y_apellidos" class="form-control" value="<?= htmlspecialchars($cliente['Nombre_y_apellidos']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Fecha nacimiento</label>
              <input type="date" name="fecha_nacimiento" class="form-control" value="<?= ($cliente['fecha_nacimiento'] && $cliente['fecha_nacimiento'] !== '0000-00-00') ? htmlspecialchars($cliente['fecha_nacimiento']) : '' ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">BSN</label>
              <input type="text" name="BSN" class="form-control" value="<?= htmlspecialchars($cliente['BSN']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Teléfono</label>
              <input type="text" name="Tlf" class="form-control" value="<?= htmlspecialchars($cliente['Tlf']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Mail</label>
              <input type="email" name="Mail" class="form-control" value="<?= htmlspecialchars($cliente['Mail']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">IBAN</label>
              <input type="text" name="IBAN" class="form-control" value="<?= htmlspecialchars($cliente['IBAN']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Padrón Hol</label>
              <input type="date" name="Padron_Hol" class="form-control" value="<?= ($cliente['Padron_Hol'] && $cliente['Padron_Hol'] !== '0000-00-00') ? htmlspecialchars($cliente['Padron_Hol']) : '' ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">CP Hol</label>
              <input type="text" name="CP_Hol" class="form-control" value="<?= htmlspecialchars($cliente['CP_Hol']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Calle Hol</label>
              <input type="text" name="Calle_Hol" class="form-control" value="<?= htmlspecialchars($cliente['Calle_Hol']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Num/Letra Hol</label>
              <input type="text" name="Num_Letra_Hol" class="form-control" value="<?= htmlspecialchars($cliente['Num_Letra_Hol']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Ciudad Hol</label>
              <input type="text" name="Ciudad_Hol" class="form-control" value="<?= htmlspecialchars($cliente['Ciudad_Hol']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Calle sin padrón</label>
              <input type="text" name="calle_sin_padron" class="form-control" value="<?= htmlspecialchars($cliente['Calle_sin_padron']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Num/Let sin padrón</label>
              <input type="text" name="Num_Let_sin_padron" class="form-control" value="<?= htmlspecialchars($cliente['Num_Let_sin_padron']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">CP sin padrón</label>
              <input type="text" name="cp_sin_padron" class="form-control" value="<?= htmlspecialchars($cliente['cp_sin_padron']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Localidad sin padrón</label>
              <input type="text" name="localidad_sin_padron" class="form-control" value="<?= htmlspecialchars($cliente['Localidad_sin_padron']) ?>">
          </div>
          <div class="mb-3">
              <label class="form-label">Notas</label>
              <textarea name="notas" class="form-control" rows="3"><?= htmlspecialchars($cliente['notas']) ?></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>