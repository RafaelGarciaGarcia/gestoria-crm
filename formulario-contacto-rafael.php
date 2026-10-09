<?php
/*
Plugin Name: Formulario Contacto Rafael
Description: Formulario de contacto personalizado con envío directo a CRM y confirmación inline.
Version: 2.0
Author: Rafael
*/

if (!defined('ABSPATH')) {
    exit; // Salida de seguridad si se accede directamente
}

add_shortcode('formulario_contacto', 'fcr_formulario_contacto');

function fcr_formulario_contacto() {
    $mensaje_estado = '';

    if (isset($_POST['fcr_enviar'])) {
        $mensaje_estado = fcr_procesar_formulario();
    }

    ob_start();
    ?>

    <?php if ($mensaje_estado): ?>
        <div class="fcr-mensaje-exito" style="padding: 15px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; margin-bottom: 20px;">
            <?php echo esc_html($mensaje_estado); ?>
        </div>
    <?php endif; ?>

    <form method="post" action="">
        <label>Nombre completo</label>
        <input type="text" name="fcr_nombre" required>

        <label>Mensaje</label>
        <textarea name="fcr_mensaje" required></textarea>

        <label>Correo electrónico</label>
        <input type="email" name="fcr_email" required>

        <label>Teléfono</label>
        <input type="text" name="fcr_telefono" required>
        
        <label>¿Cómo prefieres que te contactemos?</label>
        <select name="fcr_metodo" id="fcr_metodo" onchange="fcr_mostrar_horario()" required>
            <option value="">Selecciona una opción</option>
            <option value="correo">Correo electrónico</option>
            <option value="telefono">Teléfono</option>
        </select>

        <div id="fcr_horario_div" style="display:none;">
            <label>Horario preferido para llamarte</label>
            <input type="text" name="fcr_horario" placeholder="Ej: 10:00 - 14:00">
        </div>

        <label>Descuento</label>
        <input type="text" name="fcr_descuentos" placeholder="Escribe aquí tu descuento si lo tienes">

        <button type="submit" name="fcr_enviar">Enviar ticket</button>
    </form>

    <script>
    function fcr_mostrar_horario() {
        const metodo = document.getElementById("fcr_metodo").value;
        const horarioDiv = document.getElementById("fcr_horario_div");

        if (metodo === "telefono") {
            horarioDiv.style.display = "block";
        } else {
            horarioDiv.style.display = "none";
        }
    }
    </script>

    <?php
    return ob_get_clean();
}

function fcr_procesar_formulario() {

    $nombre     = sanitize_text_field($_POST['fcr_nombre']);
    $mensaje    = sanitize_textarea_field($_POST['fcr_mensaje']);
    $metodo     = sanitize_text_field($_POST['fcr_metodo']);
    $email      = sanitize_email($_POST['fcr_email']);
    $telefono   = sanitize_text_field($_POST['fcr_telefono']);
    $horario    = sanitize_text_field($_POST['fcr_horario']);
    $descuentos = sanitize_text_field($_POST['fcr_descuentos']);

    $crm_url = "https://panel.taxvisionbc.com/crear_ticket.php"; 

    $data = array(
        'nombre'      => $nombre,
        'email'       => $email,
        'telefono'    => $telefono,
        'preferencia' => $horario,
        'mensaje'     => $mensaje,
        'descuentos'  => $descuentos
    );
    
    wp_remote_post($crm_url, array(
        'method'      => 'POST',
        'timeout'     => 5,
        'blocking'    => true,
        'body'        => $data
    ));

    $destinatario = get_option('rafagargar2000@gmail.com'); // O pon tu email directo: "tu@email.com"
    $contenido = "
Nuevo ticket enviado desde la web:

Nombre: $nombre
Método de contacto: $metodo
Email: $email
Teléfono: $telefono
Horario preferido: $horario
Descuento: $descuentos

Mensaje:
$mensaje
";

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    wp_mail($destinatario, "Nuevo ticket de contacto: $nombre", $contenido, $headers);

    return "¡Tu mensaje ha sido enviado correctamente! Hemos generado tu ticket de soporte.";
}