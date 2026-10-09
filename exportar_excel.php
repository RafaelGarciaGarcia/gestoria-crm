<?php
session_start();
require 'conexion.php';

// Construcción dinámica del SQL idéntica al filtrador
$query = "SELECT 
            a.*, 
            a.id AS actividad_id,
            c.Nombre_y_apellidos AS cliente,
            c.id AS cliente_id,
            u.nombre AS asignado_nombre,
            (SELECT MAX(l.fecha) FROM actividad_logs l WHERE l.actividad_id = a.id) AS ultimo_log
          FROM actividades a
          INNER JOIN clientes c ON c.id = a.cliente_id
          LEFT JOIN usuarios u ON u.id = a.usuario_asignado_id
          WHERE 1=1";

$params = [];

// Tipo
if (!empty($_GET['tipo_id'])) {
    $query .= " AND a.tipo_id = :tipo_id";
    $params[':tipo_id'] = $_GET['tipo_id'];
}

// Estado
if (isset($_GET['estado']) && $_GET['estado'] !== '') {
    if ($_GET['estado'] === '__NULL__') {
        $query .= " AND (a.estado IS NULL OR a.estado = '')";
    } else {
        $query .= " AND a.estado = :estado";
        $params[':estado'] = $_GET['estado'];
    }
}

// Usuario asignado
if (!empty($_GET['usuario_id'])) {
    $query .= " AND a.usuario_asignado_id = :usuario_id";
    $params[':usuario_id'] = $_GET['usuario_id'];
}

// Urgente
if (isset($_GET['urgente']) && $_GET['urgente'] !== "") {
    $query .= " AND a.urgente = :urgente";
    $params[':urgente'] = $_GET['urgente'];
}

// Fecha desde
if (!empty($_GET['desde'])) {
    $query .= " AND a.fecha_limite >= :desde";
    $params[':desde'] = $_GET['desde'];
}

// Fecha hasta
if (!empty($_GET['hasta'])) {
    $query .= " AND a.fecha_limite <= :hasta";
    $params[':hasta'] = $_GET['hasta'];
}

// Ordenación principal
$query .= " ORDER BY ultimo_log DESC, a.fecha_limite ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==========================================
// CONSTRUCCIÓN DEL NOMBRE DEL ARCHIVO
// ==========================================

// 1. Tipo
$p_tipo = 'todos';
if (!empty($_GET['tipo_id'])) {
    foreach ($resultados as $r) {
        if (!empty($r['actividad'])) {
            $p_tipo = $r['actividad'];
            break;
        }
    }
    if ($p_tipo === 'todos') {
        $stmtTipo = $pdo->prepare("SELECT actividad FROM actividades WHERE tipo_id = ? LIMIT 1");
        $stmtTipo->execute([$_GET['tipo_id']]);
        $nombreEncontrado = $stmtTipo->fetchColumn();
        if ($nombreEncontrado) {
            $p_tipo = $nombreEncontrado;
        }
    }
}

// 2. Estado
$p_estado = 'todos';
if (isset($_GET['estado']) && $_GET['estado'] !== '') {
    $p_estado = ($_GET['estado'] === '__NULL__') ? 'null' : $_GET['estado'];
}

// 3. Urgente
$p_urgente = 'todos';
if (isset($_GET['urgente']) && $_GET['urgente'] !== '') {
    $p_urgente = ($_GET['urgente'] === '1') ? 'urgentesi' : 'urgenteno';
}

// 4. Asignado a
$p_asignado = 'todos';
if (!empty($_GET['usuario_id'])) {
    foreach ($resultados as $r) {
        if (!empty($r['asignado_nombre'])) {
            $p_asignado = $r['asignado_nombre'];
            break;
        }
    }
    if ($p_asignado === 'todos') {
        $stmtUser = $pdo->prepare("SELECT nombre FROM usuarios WHERE id = ?");
        $stmtUser->execute([$_GET['usuario_id']]);
        $userNombre = $stmtUser->fetchColumn();
        if ($userNombre) {
            $p_asignado = $userNombre;
        }
    }
}

// 5. Fecha límite
$p_fechalimite = 'todas';
if (!empty($_GET['desde']) && !empty($_GET['hasta'])) {
    $p_fechalimite = $_GET['desde'] . '_a_' . $_GET['hasta'];
} elseif (!empty($_GET['desde'])) {
    $p_fechalimite = 'desde_' . $_GET['desde'];
} elseif (!empty($_GET['hasta'])) {
    $p_fechalimite = 'hasta_' . $_GET['hasta'];
}

// 6. Fecha último log
$p_ultimolog = 'todos';

// Función para limpiar caracteres especiales del nombre del archivo
function limpiarNombreArchivo($texto) {
    $texto = mb_strtolower($texto, 'UTF-8');
    $texto = str_replace([' ', 'á', 'é', 'í', 'ó', 'ú', 'ñ', '—', '/'], ['_', 'a', 'e', 'i', 'o', 'u', 'n', '', '_'], $texto);
    return preg_replace('/[^a-z0-9_\-]/u', '', $texto);
}

// Fecha actual del sistema para añadir al final entre paréntesis
$fecha_descarga = date('Y-m-d');

$nombre_archivo = limpiarNombreArchivo($p_tipo) . "_" .
                  limpiarNombreArchivo($p_estado) . "_" .
                  limpiarNombreArchivo($p_urgente) . "_" .
                  limpiarNombreArchivo($p_asignado) . "_" .
                  limpiarNombreArchivo($p_fechalimite) . "_" .
                  limpiarNombreArchivo($p_ultimolog) . 
                  "_(" . $fecha_descarga . ").csv";

// Cabeceras para forzar la descarga como CSV/Excel
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');

$output = fopen('php://output', 'w');

// BOM de UTF-8 para Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Cabeceras de columnas exactamente en el orden visual de la interfaz
fputcsv($output, [
    'Cliente', 
    'Actividad', 
    'Estado', 
    'Urgente', 
    'Asignado a', 
    'Fecha límite', 
    'Último log'
], ';');

// Rellenar filas de datos con el mismo orden exacto
foreach ($resultados as $r) {
    fputcsv($output, [
        $r['cliente'],
        $r['actividad'],
        $r['estado'] ?: 'Null',
        $r['urgente'] ? 'Sí' : '—',
        $r['asignado_nombre'] ?: '—',
        (!empty($r['fecha_limite']) && $r['fecha_limite'] !== '0000-00-00') ? date('d/m/Y', strtotime($r['fecha_limite'])) : '',
        (!empty($r['ultimo_log']) && $r['ultimo_log'] !== '0000-00-00 00:00:00') ? date('d/m/Y H:i', strtotime($r['ultimo_log'])) : '—'
    ], ';');
}

fclose($output);
exit;