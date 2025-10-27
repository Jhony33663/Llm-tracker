<?php
/**
 * Settings page for LLM Tracker
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

// Procesar guardado de configuración
if (isset($_POST['save_settings'])) {
    check_admin_referer('llm_tracker_save_settings');
    
    $settings = array(
        'track_all_pages' => isset($_POST['track_all_pages']) ? 1 : 0,
        'track_logged_users' => isset($_POST['track_logged_users']) ? 1 : 0,
        'enable_geo_location' => isset($_POST['enable_geo_location']) ? 1 : 0,
        'geo_api_key' => sanitize_text_field($_POST['geo_api_key']),
        'exclude_ips' => sanitize_textarea_field($_POST['exclude_ips']),
        'auto_cleanup_days' => intval($_POST['auto_cleanup_days']),
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'notification_email' => sanitize_email($_POST['notification_email']),
        'custom_llms_content' => wp_kses_post($_POST['custom_llms_content'])
    );
    
    update_option('llm_tracker_settings', $settings);
    
    echo '<div class="notice notice-success"><p>Configuración guardada correctamente.</p></div>';
}

// Obtener configuración actual
$settings = get_option('llm_tracker_settings', array(
    'track_all_pages' => 0,
    'track_logged_users' => 0,
    'enable_geo_location' => 0,
    'geo_api_key' => '',
    'exclude_ips' => '',
    'auto_cleanup_days' => 30,
    'email_notifications' => 0,
    'notification_email' => get_option('admin_email'),
    'custom_llms_content' => ''
));

// Procesar acciones adicionales
if (isset($_POST['regenerate_llms'])) {
    check_admin_referer('llm_tracker_regenerate_llms');
    
    $plugin = new LLM_Tracker();
    $plugin->create_llms_txt_file();
    
    echo '<div class="notice notice-success"><p>Archivo llms.txt regenerado correctamente.</p></div>';
}

if (isset($_POST['cleanup_visits'])) {
    check_admin_referer('llm_tracker_cleanup_visits');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'llm_tracker_visits';
    $days = intval($_POST['cleanup_days']);
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE visit_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
        $days
    ));
    
    echo '<div class="notice notice-success"><p>Se eliminaron ' . $deleted . ' registros antiguos.</p></div>';
}

if (isset($_POST['export_visits'])) {
    check_admin_referer('llm_tracker_export_visits');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'llm_tracker_visits';
    
    $visits = $wpdb->get_results("SELECT * FROM $table_name ORDER BY visit_time DESC");
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="llm-tracker-export-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Cabeceras
    fputcsv($output, array(
        'ID', 'Fecha/Hora', 'IP', 'User Agent', 'Referer', 'Método', 'URI', 
        'Bot Detectado', 'Nombre Bot', 'País', 'Ciudad', 'Creado'
    ));
    
    // Datos
    foreach ($visits as $visit) {
        fputcsv($output, array(
            $visit->id,
            $visit->visit_time,
            $visit->ip_address,
            $visit->user_agent,
            $visit->referer,
            $visit->request_method,
            $visit->request_uri,
            $visit->bot_detected,
            $visit->bot_name,
            $visit->country,
            $visit->city,
            $visit->created_at
        ));
    }
    
    fclose($output);
    exit;
}
?>

<div class="wrap">
    <h1>Configuración - LLM Tracker</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('llm_tracker_save_settings'); ?>
        
        <div class="llm-tracker-settings-tabs">
            <div class="tab-nav">
                <button type="button" class="tab-button active" data-tab="general">General</button>
                <button type="button" class="tab-button" data-tab="tracking">Tracking</button>
                <button type="button" class="tab-button" data-tab="llms">llms.txt</button>
                <button type="button" class="tab-button" data-tab="advanced">Avanzado</button>
            </div>
            
            <!-- Tab General -->
            <div class="tab-content active" id="general">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="track_all_pages">Trackear todas las páginas</label>
                        </th>
                        <td>
                            <input type="checkbox" name="track_all_pages" id="track_all_pages" value="1" <?php checked($settings['track_all_pages'], 1); ?> />
                            <p class="description">Trackea visitas en todas las páginas del sitio, no solo en el archivo llms.txt</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="track_logged_users">Trackear usuarios logueados</label>
                        </th>
                        <td>
                            <input type="checkbox" name="track_logged_users" id="track_logged_users" value="1" <?php checked($settings['track_logged_users'], 1); ?> />
                            <p class="description">Incluir en el tracking a los usuarios que han iniciado sesión</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="email_notifications">Notificaciones por email</label>
                        </th>
                        <td>
                            <input type="checkbox" name="email_notifications" id="email_notifications" value="1" <?php checked($settings['email_notifications'], 1); ?> />
                            <p class="description">Enviar notificaciones por email cuando se detecten nuevos LLMs</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="notification_email">Email para notificaciones</label>
                        </th>
                        <td>
                            <input type="email" name="notification_email" id="notification_email" value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text" />
                            <p class="description">Dirección de email donde se enviarán las notificaciones</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Tab Tracking -->
            <div class="tab-content" id="tracking">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="enable_geo_location">Geolocalización</label>
                        </th>
                        <td>
                            <input type="checkbox" name="enable_geo_location" id="enable_geo_location" value="1" <?php checked($settings['enable_geo_location'], 1); ?> />
                            <p class="description">Obtener información geográfica de las IPs (requiere API key)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="geo_api_key">API Key de Geolocalización</label>
                        </th>
                        <td>
                            <input type="text" name="geo_api_key" id="geo_api_key" value="<?php echo esc_attr($settings['geo_api_key']); ?>" class="regular-text" />
                            <p class="description">API key para servicio de geolocalización (ej: ipstack, ipapi, etc.)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="exclude_ips">IPs a excluir</label>
                        </th>
                        <td>
                            <textarea name="exclude_ips" id="exclude_ips" rows="5" class="large-text"><?php echo esc_textarea($settings['exclude_ips']); ?></textarea>
                            <p class="description">Una IP por línea. Estas direcciones no serán trackeadas</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="auto_cleanup_days">Limpieza automática (días)</label>
                        </th>
                        <td>
                            <input type="number" name="auto_cleanup_days" id="auto_cleanup_days" value="<?php echo esc_attr($settings['auto_cleanup_days']); ?>" min="1" max="365" />
                            <p class="description">Eliminar automáticamente registros más antiguos que este número de días</p>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Tab llms.txt -->
            <div class="tab-content" id="llms">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="custom_llms_content">Contenido personalizado llms.txt</label>
                        </th>
                        <td>
                            <textarea name="custom_llms_content" id="custom_llms_content" rows="15" class="large-text"><?php echo esc_textarea($settings['custom_llms_content']); ?></textarea>
                            <p class="description">Deja vacío para usar el contenido generado automáticamente. Usa formato YAML.</p>
                        </td>
                    </tr>
                </table>
                
                <div class="llms-actions">
                    <button type="submit" name="regenerate_llms" class="button" onclick="return confirm('¿Estás seguro de regenerar el archivo llms.txt?')">Regenerar llms.txt</button>
                    <p class="description">Esto sobreescribirá el archivo llms.txt actual con la configuración por defecto</p>
                </div>
            </div>
            
            <!-- Tab Avanzado -->
            <div class="tab-content" id="advanced">
                <h3>Mantenimiento de Datos</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Limpiar visitas antiguas</th>
                        <td>
                            <div class="cleanup-form">
                                <input type="number" name="cleanup_days" value="30" min="1" max="365" />
                                <span>días</span>
                                <button type="submit" name="cleanup_visits" class="button" onclick="return confirm('¿Estás seguro de eliminar los registros antiguos? Esta acción no se puede deshacer.')">Limpiar</button>
                            </div>
                            <p class="description">Eliminar todos los registros de visitas más antiguos que el número de días especificado</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Exportar datos</th>
                        <td>
                            <button type="submit" name="export_visits" class="button">Exportar todas las visitas (CSV)</button>
                            <p class="description">Descargar todos los registros de visitas en formato CSV</p>
                        </td>
                    </tr>
                </table>
                
                <?php
                // Estadísticas de la base de datos
                global $wpdb;
                $table_name = $wpdb->prefix . 'llm_tracker_visits';
                $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
                $table_size = $wpdb->get_var("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) FROM information_schema.TABLES WHERE table_schema = '" . DB_NAME . "' AND table_name = '$table_name'");
                ?>
                
                <h3>Información de la Base de Datos</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Total de registros</th>
                        <td><?php echo number_format($total_records); ?> visitas</td>
                    </tr>
                    <tr>
                        <th scope="row">Tamaño de la tabla</th>
                        <td><?php echo $table_size; ?> MB</td>
                    </tr>
                    <tr>
                        <th scope="row">Nombre de la tabla</th>
                        <td><code><?php echo $table_name; ?></code></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="save_settings" class="button button-primary" value="Guardar Cambios" />
        </p>
    </form>
</div>

<style>
.llm-tracker-settings-tabs {
    margin-top: 20px;
}

.tab-nav {
    display: flex;
    border-bottom: 1px solid #ccd0d4;
    margin-bottom: 20px;
}

.tab-button {
    background: none;
    border: none;
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    font-size: 14px;
    color: #50575e;
    transition: all 0.2s ease;
}

.tab-button:hover {
    color: #1d2327;
    background: #f6f7f7;
}

.tab-button.active {
    color: #3858e9;
    border-bottom-color: #3858e9;
    background: #f6f7f7;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.cleanup-form {
    display: flex;
    align-items: center;
    gap: 10px;
}

.cleanup-form input[type="number"] {
    width: 80px;
}

.llms-actions {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.llms-actions p {
    margin: 10px 0 0 0;
    font-style: italic;
    color: #646970;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Update buttons
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update content
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
    });
});
</script>