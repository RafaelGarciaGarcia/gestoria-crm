<?php
require 'conexion.php';

$q = $_GET['q'] ?? '';

$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.Nombre_y_apellidos,
        c.BSN,
        c.Tlf,

        -- Nº de urgentes
        (
            SELECT COUNT(*) 
            FROM actividades a1
            WHERE a1.cliente_id = c.id
            AND a1.urgente = 1
        ) AS urgencias,

        -- Nº de gestionando
        (
            SELECT COUNT(*)
            FROM actividades a2
            WHERE a2.cliente_id = c.id
            AND a2.estado = 'Gestionando'
        ) AS gestionando,

        -- Fecha límite más cercana
        (
            SELECT MIN(a4.fecha_limite)
            FROM actividades a4
            WHERE a4.cliente_id = c.id
            AND a4.fecha_limite IS NOT NULL
        ) AS fecha_mas_cercana

    FROM clientes c
    WHERE 
        c.Nombre_y_apellidos LIKE :q
        OR c.BSN LIKE :q
        OR c.Tlf LIKE :q
        OR c.Mail LIKE :q

    ORDER BY 
        urgencias DESC,
        gestionando DESC,
        fecha_mas_cercana IS NULL ASC,
        fecha_mas_cercana ASC,
        c.Nombre_y_apellidos ASC
");

$stmt->execute([':q' => "%$q%"]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $urgente = $row['urgencias'] > 0 ? "🔥" : "—";
    $pendiente = $row['gestionando'] > 0 ? "🟡" : "—";
    $fecha = $row['fecha_mas_cercana']
        ? date("d/m/Y", strtotime($row['fecha_mas_cercana']))
        : "-";

    echo "
    <tr onclick=\"abrirVentana('cliente.php?id={$row['id']}')\" style='cursor:pointer;'>
        <td>{$row['Nombre_y_apellidos']}</td>
        <td>{$row['BSN']}</td>
        <td>{$row['Tlf']}</td>
        <td>{$urgente}</td>
        <td>{$pendiente}</td>
        <td>{$fecha}</td>
    </tr>";
}