<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

require 'conexion.php';

$ticket_id = intval($_GET['id']);

// Obtener ticket junto con los nombres de la actividad y del cliente asociados (si los hay)
$stmt = $pdo->prepare("
    SELECT t.*, 
           a.actividad AS actividad_nombre, 
           c.Nombre_y_apellidos AS cliente_nombre 
    FROM tickets t
    LEFT JOIN actividades a ON t.actividad_id = a.id
    LEFT JOIN clientes c ON t.cliente_id = c.id
    WHERE t.id = ?
");
$stmt->execute([$ticket_id]);
$t = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$t) {
    die("Ticket no encontrado");
}

$esta_asociado = !empty($t['actividad_id']);
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Ticket #<?= $ticket_id ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<style>
body { background:#f8f9fa; }
</style>
</head>

<body>

<div class="container py-4">

    <a href="javascript:history.back()" class="btn btn-secondary mb-3">Volver</a>

    <div class="card shadow-sm">
        <div class="card-body">

            <h3 class="mb-3">Ticket #<?= $ticket_id ?></h3>

            <p><strong>Nombre:</strong> <?= htmlspecialchars($t['nombre']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($t['email']) ?></p>
            <p><strong>Teléfono:</strong> <?= htmlspecialchars($t['telefono']) ?></p>
            <p><strong>Preferencia:</strong> <?= htmlspecialchars($t['preferencia']) ?></p>
            <p><strong>Mensaje:</strong><br><?= nl2br(htmlspecialchars($t['mensaje'])) ?></p>
            <p><strong>Estado:</strong> <?= htmlspecialchars($t['estado']) ?></p>
            <p><strong>Descuento:</strong> <?= htmlspecialchars($t['descuentos']) ?></p>
            <hr>

            <div class="btn-group">
                <!-- Solo se muestra si el ticket NO está asociado aún -->
                <?php if (!$esta_asociado): ?>
                    <button class="btn btn-secondary" onclick="abrirModalActividad()">Asociar actividad</button>
                <?php endif; ?>

                <button class="btn btn-success" onclick="abrirModalResponder()">Responder</button>
                <button class="btn btn-danger" onclick="eliminarTicket()">Eliminar</button>
            </div>

        </div>
    </div>
</div>

<!-- MODAL: ASOCIAR ACTIVIDAD -->
<?php if (!$esta_asociado): ?>
<div class="modal fade" id="modalActividad" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title">Asociar actividad</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <label class="fw-bold">Buscar cliente por nombre</label>
        <input type="text" id="buscar_cliente_actividad" class="form-control mb-3" placeholder="Nombre del cliente">

        <div id="resultado_actividades"></div>

      </div>

    </div>
  </div>
</div>
<?php endif; ?>

<!-- MODAL: RESPONDER -->
<div class="modal fade" id="modalResponder" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Responder ticket</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <textarea id="respuesta_mensaje" class="form-control" rows="5"></textarea>

        <button class="btn btn-success mt-3" onclick="enviarRespuesta()">Enviar respuesta</button>

      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {

    window.abrirModalActividad = function() {
        const modalEl = document.getElementById('modalActividad');
        if (modalEl) {
            new bootstrap.Modal(modalEl).show();
        }
    }

    window.abrirModalResponder = function() {
        new bootstrap.Modal(document.getElementById('modalResponder')).show();
    }

    const buscador = document.getElementById('buscar_cliente_actividad');

    window.realizarBusqueda = function(q) {
        if (!q) return;
        fetch('buscar_clientes_tickets.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'q=' + encodeURIComponent(q) + '&ticket_id=<?= $ticket_id ?>'
        })
        .then(r => r.text())
        .then(html => {
            const res = document.getElementById('resultado_actividades');
            if (res) res.innerHTML = html;
        });
    }

    if (buscador) {
        buscador.addEventListener('keyup', function () {
            realizarBusqueda(this.value);
        });
    }

    window.buscarActividadesCliente = function(clienteId, ticketId, q) {
        const tId = ticketId || <?= $ticket_id ?>;
        const busqueda = q || (buscador ? buscador.value : '');

        fetch('buscar_actividades_tickets.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'cliente_id=' + encodeURIComponent(clienteId) + 
                  '&ticket_id=' + encodeURIComponent(tId) + 
                  '&q=' + encodeURIComponent(busqueda)
        })
        .then(r => r.text())
        .then(html => {
            const res = document.getElementById('resultado_actividades');
            if (res) res.innerHTML = html;
        });
    }

    // RESTAURAR ESTADO SI VIENE DE CREAR
    const urlParams    = new URLSearchParams(window.location.search);
    const busquedaPrev = urlParams.get('q');
    const clientePrev  = urlParams.get('cliente_id');
    const abrirModal   = urlParams.get('modal');

    if ((abrirModal === 'actividad' || busquedaPrev || clientePrev) && <?= $esta_asociado ? 'false' : 'true' ?>) {
        abrirModalActividad();

        if (clientePrev) {
            buscarActividadesCliente(clientePrev, <?= $ticket_id ?>, busquedaPrev || '');
        } else if (busquedaPrev) {
            if (buscador) buscador.value = busquedaPrev;
            realizarBusqueda(busquedaPrev);
        }
    }

    window.asociarActividadFinal = function(actividadId) {
        if (!confirm("¿Asociar este ticket a esta actividad?")) return;

        fetch('asociar_ticket_actividad.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                ticket_id: <?= $ticket_id ?>,
                actividad_id: actividadId
            })
        })
        .then(r => r.text())
        .then(resp => {
            const respuestaLimpia = resp.trim();
            // Si la respuesta empieza por ticket.php, redirigimos ahí mismo
            if (respuestaLimpia.startsWith("ticket.php")) {
                alert("Ticket asociado correctamente.");
                window.location.href = respuestaLimpia;
                return;
            }
            alert("Error o respuesta inesperada: " + resp);
        });
    }

    window.enviarRespuesta = function() {
        const mensaje = document.getElementById('respuesta_mensaje').value;

        if (!mensaje.trim()) {
            alert("Escribe una respuesta");
            return;
        }

        if (!confirm("¿Enviar esta respuesta?")) return;

        fetch('responder_ticket.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ticket_id=<?= $ticket_id ?>&mensaje=' + encodeURIComponent(mensaje)
        })
        .then(r => r.text())
        .then(resp => {
            if (resp.trim() === "OK") {
                alert("Respuesta enviada correctamente");
                location.reload();
            } else {
                alert("Atención: " + resp);
            }
        });
    }

    window.eliminarTicket = function() {
        if (!confirm("¿Seguro que quieres eliminar este ticket?")) return;

        fetch('eliminar_ticket.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'ticket_id=<?= $ticket_id ?>'
        })
        .then(r => r.text())
        .then(resp => {
            if (resp.trim() === "OK") {
                alert("Ticket eliminado");
                window.location.href = "tickets.php";
            } else {
                alert("Error al eliminar el ticket");
            }
        });
    }

});
</script>

</body>
</html>