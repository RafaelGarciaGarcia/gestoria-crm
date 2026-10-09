<?php
session_start();
require 'conexion.php';
$archivo_control = sys_get_temp_dir() . '/ultimo_check_urgentes.txt';
if (!file_exists($archivo_control) || filemtime($archivo_control) < strtotime('today')) {
    try {
        $pdo->exec("
            UPDATE actividades
            SET urgente = 1
            WHERE urgente = 0 
            AND (
                (
                    LOWER(estado) = 'gestionando' 
                    AND (
                        SELECT MAX(l.fecha) 
                        FROM actividad_logs l 
                        WHERE l.actividad_id = actividades.id
                    ) <= DATE_SUB(NOW(), INTERVAL 14 DAY)
                )
                OR 
                (
                    fecha_limite IS NOT NULL 
                    AND fecha_limite = CURDATE()
                )
            )
        ");
        file_put_contents($archivo_control, date('Y-m-d H:i:s'));
    } catch (Exception $e) {
    }
}

$actividad_id = $_GET['actividad_id'] ?? 0;
$cliente_id   = $_GET['cliente_id'] ?? 0;

if (!$actividad_id) {
    die("Actividad no especificada.");
}

$stmtAct = $pdo->prepare("
    SELECT a.*, c.Nombre_y_apellidos AS cliente_nombre 
    FROM actividades a
    LEFT JOIN clientes c ON a.cliente_id = c.id
    WHERE a.id = :id
");
$stmtAct->execute([':id' => $actividad_id]);
$actividad = $stmtAct->fetch(PDO::FETCH_ASSOC);

if (!$actividad) {
    die("Actividad no encontrada.");
}

$stmtLogs = $pdo->prepare("
    SELECT * 
    FROM actividad_logs 
    WHERE actividad_id = :id 
      AND mensaje IS NOT NULL 
      AND TRIM(mensaje) != ''
    ORDER BY fecha DESC
");
$stmtLogs->execute([':id' => $actividad_id]);
$logs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC);

// Obtener archivos
$stmtArch = $pdo->prepare("
    SELECT * 
    FROM actividad_archivos
    WHERE actividad_id = :id
    ORDER BY fecha_subida DESC
");
$stmtArch->execute([':id' => $actividad_id]);
$archivos = $stmtArch->fetchAll(PDO::FETCH_ASSOC);

// Obtener usuarios
$stmtUsers = $pdo->query("SELECT id, nombre FROM usuarios ORDER BY nombre");
$usuarios = $stmtUsers->fetchAll(PDO::FETCH_ASSOC);

// Crear mapa id → nombre
$usuariosMap = [];
foreach ($usuarios as $u) {
    $usuariosMap[$u['id']] = $u['nombre'];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Logs - <?= htmlspecialchars($actividad['actividad']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">

<div class="sticky-top bg-light py-3 border-bottom shadow-sm mb-4">
    <div class="container">
        <?php
        $volver = "index.php"; 
        if (!empty($_GET['from'])) {
            if ($_GET['from'] === 'tareas')     $volver = "tareas_pendientes.php";
            if ($_GET['from'] === 'filtrador')  $volver = "filtrador.php";
            if ($_GET['from'] === 'cliente')    $volver = "cliente.php?id=" . $cliente_id;
        } else if ($cliente_id > 0) {
            $volver = "cliente.php?id=" . $cliente_id;
        }
        ?>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <a href="<?= $volver ?>" class="btn btn-secondary btn-sm">← Volver</a>
                <h3 class="mb-0 fs-4"><?= htmlspecialchars($actividad['actividad']) ?></h3>
                
                <?php if (!empty($actividad['cliente_nombre'])): ?>
                    <span class="text-muted">
                        — 
                        <a href="cliente.php?id=<?= $actividad['cliente_id'] ?>" class="text-decoration-none fw-semibold">
                            👤 <?= htmlspecialchars($actividad['cliente_nombre']) ?>
                        </a>
                    </span>
                <?php endif; ?>
            </div>

            <div class="d-flex align-items-center gap-3">
                <span class="text-muted small fw-bold">
                    (<?= $actividad['fecha_limite'] ? date('d/m/Y', strtotime($actividad['fecha_limite'])) : 'Sin fecha límite' ?>)
                </span>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditarNombreActividad">
                    ✏ Cambiar nombre
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container py-2">

    <a href="completar_tarea.php?id=<?= $actividad_id ?>&cliente_id=<?= $cliente_id ?>" 
       class="btn btn-success mb-4">Completar tarea</a>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Añadir log</h5>

            <h5 class="mt-4">Subir documentos</h5>
                <form action="subir_archivo.php" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="actividad_id" value="<?= $actividad_id ?>">
                    <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
                
                    <div class="mb-3">
                        <input type="file" name="archivos[]" class="form-control" 
                               accept=".pdf,.jpg,.jpeg,.png" multiple required>
                    </div>
                
                    <button class="btn btn-secondary btn-sm">Subir archivos</button>
                </form>
        </div>

        <div class="card-body">

            <h5 class="mt-2">Documentos subidos</h5>

            <?php if (empty($archivos)): ?>
                <p class="text-muted">No hay documentos subidos.</p>
            <?php else: ?>
                <ul class="list-group mb-4">
                    <?php foreach ($archivos as $a): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="<?= $a['ruta'] ?>" target="_blank">
                                <?= htmlspecialchars($a['nombre_archivo']) ?>
                            </a>

                            <a href="eliminar_archivo.php?id=<?= $a['id'] ?>&actividad_id=<?= $actividad_id ?>&cliente_id=<?= $cliente_id ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('¿Eliminar este archivo?')">
                                🗑
                            </a>
                        </li>
                    <?php endforeach; ?>

                    <a href="cliente.php?id=<?= $cliente_id ?>&descargar_todo=1"
                       class="btn btn-info mt-3">
                        Descargar todos los archivos del cliente
                    </a>
                </ul>
            <?php endif; ?>

        </div>

        <div class="card-body border-top">

            <form method="post" action="nuevo_log.php">

                <input type="hidden" name="actividad_id" value="<?= $actividad_id ?>">
                <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">

                <div class="mb-3">
                    <label class="form-label fw-bold">Nuevo mensaje de log</label>
                    <textarea name="mensaje" class="form-control" rows="3" placeholder="Escribe un mensaje o déjalo vacío para actualizar solo el estado..."></textarea>
                </div>

                <div class="row">

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="Null"    <?= $actividad['estado'] == 'Null' ? 'selected' : '' ?>>Null</option>
                            <option value="Interesado"  <?= $actividad['estado'] == 'Interesado' ? 'selected' : '' ?>>Interesado</option>
                            <option value="Gestionando" <?= $actividad['estado'] == 'Gestionando' ? 'selected' : '' ?>>Gestionando</option>
                            <option value="Terminada"   <?= $actividad['estado'] == 'Terminada' ? 'selected' : '' ?>>Terminada</option>
                            <option value="Al carajo"   <?= $actividad['estado'] == 'Al carajo' ? 'selected' : '' ?>>Al carajo</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Urgente</label>
                        <select name="urgente" class="form-select">
                            <option value="0" <?= $actividad['urgente'] == 0 ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= $actividad['urgente'] == 1 ? 'selected' : '' ?>>Sí</option>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Asignado a</label>
                        <select name="usuario_asignado" class="form-select">
                            <?php foreach ($usuarios as $u): ?>
                                <option value="<?= $u['id'] ?>"
                                    <?= ($actividad['usuario_asignado_id'] ?? 0) == $u['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3 mb-3">
                        <label class="form-label">Fecha límite</label>
                    
                        <input type="date" name="fecha_limite" class="form-control"
                               value="<?= $actividad['fecha_limite'] ? htmlspecialchars($actividad['fecha_limite']) : '' ?>">
                    
                        <?php if (!empty($actividad['fecha_limite'])): ?>
                            <a href="eliminar_fecha_limite.php?actividad_id=<?= $actividad_id ?>&cliente_id=<?= $cliente_id ?>"
                               class="btn btn-warning btn-sm mt-2"
                               onclick="return confirm('¿Eliminar la fecha límite?')">
                                Eliminar fecha límite
                            </a>
                        <?php endif; ?>
                    </div>

                </div>

                <button type="submit" class="btn btn-primary">Guardar log</button>

            </form>

        </div>
    </div>

    <!-- HISTORIAL DE LOGS -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h5 class="mb-0">Historial de logs</h5>
        </div>

        <div class="card-body">
        
            <?php if (empty($logs)): ?>
                <p class="text-muted">No hay logs todavía.</p>
        
            <?php else: ?>
        
                <div style="max-height: 450px; overflow-y: auto; padding-right: 10px;">
        
                    <?php foreach ($logs as $log): ?>
                        <div class="border rounded p-3 mb-3 bg-white" id="log-card-<?= $log['id'] ?>">
        
                            <div class="d-flex justify-content-between align-items-center">
        
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($log['fecha'])) ?>
                                    — <?= htmlspecialchars($usuariosMap[$log['usuario_id']] ?? 'Desconocido') ?>
                                </small>
        
                                <select class="form-select form-select-sm fw-bold border-2" style="width: auto;" 
                                        onchange="actualizarEstadoSinAnotacion(<?= $log['id'] ?>, <?= $actividad_id ?>, this.value)">
                                    <option value="Interesado"  <?= strtolower(trim($log['estado_en_log'])) == 'interesado' ? 'selected' : '' ?>>🔵 Interesado</option>
                                    <option value="Gestionando" <?= strtolower(trim($log['estado_en_log'])) == 'gestionando' ? 'selected' : '' ?>>🟡 Gestionando</option>
                                    <option value="Terminada"   <?= strtolower(trim($log['estado_en_log'])) == 'terminada' ? 'selected' : '' ?>>🟢 Terminada</option>
                                    <option value="Al carajo"   <?= strtolower(trim($log['estado_en_log'])) == 'al carajo' ? 'selected' : '' ?>>💀 Al carajo</option>
                                </select>
        
                            </div>
        
                            <p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($log['mensaje'])) ?></p>
                            
                            <div class="mt-3">
                                <a href="editar_log.php?id=<?= $log['id'] ?>&actividad_id=<?= $actividad_id ?>&cliente_id=<?= $cliente_id ?>"
                                   class="btn btn-warning btn-sm">
                                    ✏ Editar log
                                </a>
        
                                <a href="eliminar_log.php?id=<?= $log['id'] ?>&actividad_id=<?= $actividad_id ?>&cliente_id=<?= $cliente_id ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('¿Seguro que quieres eliminar este log?')">
                                    🗑 Eliminar
                                </a>
                            </div>
        
                        </div>
                    <?php endforeach; ?>
        
                </div>
        
            <?php endif; ?>
        
        </div>
    </div>

    <?php
    $stmtT = $pdo->prepare("SELECT * FROM tickets WHERE actividad_id = ?");
    $stmtT->execute([$actividad_id]);
    $ticketsActividad = $stmtT->fetchAll(PDO::FETCH_ASSOC);
    
    if ($ticketsActividad):
    ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Tickets asociados a esta actividad</h5>
            </div>
    
            <div class="card-body">
    
                <?php foreach ($ticketsActividad as $tk): ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <p><strong>De:</strong> <?= htmlspecialchars($tk['nombre']) ?> (<?= htmlspecialchars($tk['email']) ?>)</p>
                        <p><strong>Preferencia:</strong> <?= htmlspecialchars($tk['preferencia']) ?></p>
                        <p><strong>Mensaje:</strong><br><?= nl2br(htmlspecialchars($tk['mensaje'])) ?></p>
                        <span class="badge bg-primary"><?= htmlspecialchars($tk['estado']) ?></span>
                    </div>
                <?php endforeach; ?>
    
            </div>
        </div>
    <?php endif; ?>

    <a href="eliminar_actividad.php?id=<?= $actividad_id ?>&cliente_id=<?= $cliente_id ?>"
       class="btn btn-danger btn-sm"
       onclick="return confirm('¿Seguro que quieres eliminar esta actividad?')">
        🗑 Eliminar actividad
    </a>

</div>

<div class="modal fade" id="modalEditarNombreActividad" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="renombrar_actividad.php">
        <div class="modal-header">
          <h5 class="modal-title">Cambiar nombre de la actividad</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="actividad_id" value="<?= $actividad_id ?>">
          <input type="hidden" name="cliente_id" value="<?= $cliente_id ?>">
          
          <div class="mb-3">
            <label class="form-label fw-bold">Nombre de la actividad</label>
            <input type="text" name="nuevo_nombre" class="form-control" value="<?= htmlspecialchars($actividad['actividad']) ?>" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function actualizarEstadoSinAnotacion(logId, actividadId, nuevoEstado) {
    event.target.disabled = true;

    const datos = new URLSearchParams();
    datos.append('log_id', logId);
    datos.append('actividad_id', actividadId);
    datos.append('estado', nuevoEstado);

    fetch('cambiar_estado_log.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: datos.toString()
    })
    .then(r => r.text())
    .then(respuesta => {
        if (respuesta.trim() === "OK") {
            location.reload();
        } else {
            alert("Atención al guardar: " + respuesta);
            location.reload();
        }
    })
    .catch(err => {
        alert("Error de conexión con el servidor");
        console.error(err);
    });
}
</script>

</body>
</html>