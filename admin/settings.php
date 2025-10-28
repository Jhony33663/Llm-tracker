<?php
/**
 * Settings page for LLM Tracker
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'llm-tracker-pro'));
}

// Procesar guardado de configuración
if (isset($_POST['save_settings'])) {
    check_admin_referer('llm_tracker_save_settings');
    
    $settings = array(
        'track_all_pages' => isset($_POST['track_all_pages']) ? 1 : 0,
        'track_logged_users' => isset($_POST['track_logged_users']) ? 1 : 0,
        'enable_geo_location' => isset($_POST['enable_geo_location']) ? 1 : 0,
        'geo_api_key' => isset($_POST['geo_api_key']) ? sanitize_text_field(wp_unslash($_POST['geo_api_key'])) : '',
        'exclude_ips' => isset($_POST['exclude_ips']) ? sanitize_textarea_field(wp_unslash($_POST['exclude_ips'])) : '',
        'auto_cleanup_days' => isset($_POST['auto_cleanup_days']) ? intval($_POST['auto_cleanup_days']) : 30,
        'email_notifications' => isset($_POST['email_notifications']) ? 1 : 0,
        'notification_email' => isset($_POST['notification_email']) ? sanitize_email(wp_unslash($_POST['notification_email'])) : get_option('admin_email'),
        'custom_llms_content' => isset($_POST['custom_llms_content']) ? wp_kses_post(wp_unslash($_POST['custom_llms_content'])) : '',
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
    $days = isset($_POST['cleanup_days']) ? intval($_POST['cleanup_days']) : 30;
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM $table_name WHERE visit_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
        $days
    ));
    
    echo '<div class="notice notice-success"><p>Se eliminaron ' . esc_html($deleted) . ' registros antiguos.</p></div>';
}

if (isset($_POST['export_visits'])) {
    check_admin_referer('llm_tracker_export_visits');
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'llm_tracker_visits';
    
    $visits = $wpdb->get_results("SELECT * FROM $table_name ORDER BY visit_time DESC");
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="llm-tracker-export-' . gmdate('Y-m-d') . '.csv"');
    
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
                <!-- Estado del archivo -->
                <div class="llms-file-status">
                    <h3>Estado del Archivo llms.txt</h3>
                    <div class="status-info">
                        <div class="status-item">
                            <strong>Ubicación:</strong> <code><?php echo esc_url(site_url('/llms.txt')); ?></code>
                        </div>
                        <div class="status-item">
                            <strong>Estado:</strong> <span id="llms-status" class="status-checking">Verificando...</span>
                        </div>
                        <div class="status-item">
                            <strong>Última modificación:</strong> <span id="llms-modified">-</span>
                        </div>
                        <div class="status-item">
                            <strong>Tamaño:</strong> <span id="llms-size">-</span>
                        </div>
                    </div>
                </div>
                
                <!-- Editor de contenido -->
                <div class="llms-editor">
                    <h3>Editor de Contenido</h3>
                    <div class="editor-toolbar">
                        <button type="button" id="load-llms-content" class="button">Cargar Contenido</button>
                        <button type="button" id="create-llms-file" class="button button-secondary">Crear Archivo en Raíz</button>
                        <button type="button" id="save-llms-content" class="button button-primary" disabled>Guardar Cambios</button>
                        <button type="button" id="regenerate-llms" class="button">Regenerar Automático</button>
                        <button type="button" id="format-yaml" class="button">Formatear YAML</button>
                    </div>
                    <div class="editor-container">
                        <textarea id="llms-content-editor" rows="20" class="large-text code-editor" placeholder="Carga el contenido del archivo para editar..."></textarea>
                    </div>
                    <div class="editor-info">
                        <p class="description">
                            <strong>Atención:</strong> Los cambios se guardarán directamente en el archivo llms.txt de la raíz del sitio. 
                            Se creará una copia de seguridad automáticamente antes de cada guardado.
                        </p>
                        <div class="markdown-guide" style="background: #f9f9f9; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 4px;">
                            <h4 style="margin: 0 0 10px 0; color: #23282d;">📝 Formato Markdown Optimizado para llms.txt</h4>
                            <p style="margin: 0 0 8px 0; font-size: 13px;">
                                <strong>El archivo llms.txt debe usar formato YAML con sintaxis Markdown para máxima compatibilidad con LLMs:</strong>
                            </p>
                            <ul style="margin: 0; padding-left: 20px; font-size: 12px; color: #555;">
                                <li><strong>Comentarios:</strong> Usar <code>#</code> para líneas informativas</li>
                                <li><strong>Estructura YAML:</strong> <code>clave: valor</code> con sangría de 2 espacios</li>
                                <li><strong>Arrays:</strong> Usar guiones <code>- item</code> con sangría consistente</li>
                                <li><strong>Texto multilinea:</strong> Usar <code>|</code> para preservar saltos de línea</li>
                                <li><strong>URLs:</strong> Incluir completas con <code>http://</code> o <code>https://</code></li>
                                <li><strong>Encoding:</strong> UTF-8 sin BOM para compatibilidad internacional</li>
                            </ul>
                            <div style="background: #fff; border: 1px solid #ccc; padding: 10px; margin: 10px 0; border-radius: 3px; font-family: monospace; font-size: 11px;">
                                <strong>Ejemplo optimizado:</strong><br>
                                # LLMs Configuration File<br>
                                # Site Information<br>
                                name: "Mi Sitio Web"<br>
                                url: "https://ejemplo.com"<br>
                                description: |<br>
                                &nbsp;&nbsp;Sitio web optimizado para LLMs<br>
                                &nbsp;&nbsp;con tracking y análisis integrado<br>
                                <br>
                                endpoints:<br>
                                &nbsp;&nbsp;- name: "API REST"<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;url: "https://ejemplo.com/wp-json/wp/v2/"<br>
                                &nbsp;&nbsp;&nbsp;&nbsp;method: "GET"<br>
                                <br>
                                guidelines:<br>
                                &nbsp;&nbsp;- "Identificar LLM al acceder"<br>
                                &nbsp;&nbsp;- "Respetar rate limits"<br>
                                &nbsp;&nbsp;- "Usar endpoints apropiados"
                            </div>
                            <p style="margin: 8px 0 0 0; font-size: 12px; color: #666;">
                                <strong>💡 Tip:</strong> Usa el botón "Formatear YAML" para validar y optimizar automáticamente tu contenido.
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- Sistema de backups -->
                <div class="llms-backups">
                    <h3>Sistema de Backups</h3>
                    <div class="backups-toolbar">
                        <button type="button" id="refresh-backups" class="button">Actualizar Lista</button>
                        <button type="button" id="create-backup" class="button">Crear Backup Ahora</button>
                    </div>
                    <div id="backups-list" class="backups-list">
                        <p class="description">Cargando lista de backups...</p>
                    </div>
                </div>
                
                <!-- Vista previa -->
                <div class="llms-preview">
                    <h3>Vista Previa</h3>
                    <div class="preview-toolbar">
                        <button type="button" id="refresh-preview" class="button">Actualizar Vista Previa</button>
                        <a href="<?php echo esc_url(site_url('/llms.txt')); ?>" target="_blank" class="button">Ver en Vivo</a>
                    </div>
                    <div class="preview-container">
                        <pre id="llms-preview-content">Carga el contenido para ver la vista previa...</pre>
                    </div>
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
                $total_records = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name}"));
                $table_size = $wpdb->get_var($wpdb->prepare("SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) FROM information_schema.TABLES WHERE table_schema = %s AND table_name = %s", DB_NAME, $table_name));
                ?>
                
                <h3>Información de la Base de Datos</h3>
                <table class="form-table">
                    <tr>
                        <th scope="row">Total de registros</th>
                        <td><?php echo number_format($total_records); ?> visitas</td>
                    </tr>
                    <tr>
                        <th scope="row">Tamaño de la tabla</th>
                        <td><?php echo esc_html($table_size); ?> MB</td>
                    </tr>
                    <tr>
                        <th scope="row">Nombre de la tabla</th>
                        <td><code><?php echo esc_html($table_name); ?></code></td>
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

/* Estilos para la interfaz de llms.txt */
.llms-file-status,
.llms-editor,
.llms-backups,
.llms-preview {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.llms-file-status h3,
.llms-editor h3,
.llms-backups h3,
.llms-preview h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    font-size: 16px;
}

.status-info {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.status-item {
    padding: 10px;
    background: #f6f7f7;
    border-radius: 4px;
    border-left: 4px solid #3858e9;
}

.status-item strong {
    color: #1d2327;
}

.status-checking {
    color: #dba617;
}

.status-exists {
    color: #00a32a;
}

.status-not-exists {
    color: #d63638;
}

.editor-toolbar,
.backups-toolbar,
.preview-toolbar {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.editor-container {
    margin-bottom: 15px;
}

.code-editor {
    font-family: 'Courier New', Courier, monospace;
    font-size: 13px;
    line-height: 1.4;
    background: #f8f9f9;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    padding: 10px;
    width: 100%;
    min-height: 400px;
    resize: vertical;
}

.code-editor:focus {
    border-color: #3858e9;
    box-shadow: 0 0 0 1px #3858e9;
    outline: none;
}

.editor-info {
    background: #fcf9e8;
    border: 1px solid #dba617;
    border-radius: 4px;
    padding: 15px;
}

.editor-info p {
    margin: 0;
    color: #4a4f1a;
}

.backups-list {
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.backup-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 15px;
    border-bottom: 1px solid #f0f0f0;
    background: #fff;
}

.backup-item:last-child {
    border-bottom: none;
}

.backup-item:hover {
    background: #f6f7f7;
}

.backup-info {
    flex: 1;
}

.backup-filename {
    font-weight: bold;
    color: #1d2327;
    margin-bottom: 3px;
}

.backup-details {
    font-size: 12px;
    color: #646970;
}

.backup-actions {
    display: flex;
    gap: 5px;
}

.backup-actions .button {
    padding: 4px 8px;
    font-size: 12px;
    line-height: 1.4;
}

.preview-container {
    background: #f8f9f9;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    max-height: 400px;
    overflow-y: auto;
}

#llms-preview-content {
    margin: 0;
    font-family: 'Courier New', Courier, monospace;
    font-size: 13px;
    line-height: 1.4;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3858e9;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-right: 8px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.success-message {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
    padding: 10px 15px;
    border-radius: 4px;
    margin: 10px 0;
}

.error-message {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
    padding: 10px 15px;
    border-radius: 4px;
    margin: 10px 0;
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
    // Variables globales
    let originalContent = '';
    let hasUnsavedChanges = false;
    
    // Tab navigation
    $('.tab-button').on('click', function() {
        var tabId = $(this).data('tab');
        
        // Update buttons
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update content
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
        
        // Si es el tab de llms.txt, inicializar la interfaz
        if (tabId === 'llms') {
            initializeLlmsInterface();
        }
    });
    
    // Inicializar interfaz de llms.txt
    function initializeLlmsInterface() {
        checkFileStatus();
        loadBackupsList();
        updatePreview();
    }
    
    // Verificar estado del archivo
    function checkFileStatus() {
        $.get(ajaxurl, {action: 'llm_tracker_get_llms_content'}, function(response) {
            if (response.success) {
                $('#llms-status').removeClass('status-checking status-not-exists').addClass('status-exists').text('Existe');
                $('#llms-content-editor').val(response.data.content);
                originalContent = response.data.content;
                updateFileInfo();
            } else {
                $('#llms-status').removeClass('status-checking status-exists').addClass('status-not-exists').text('No existe');
                $('#llms-content-editor').val('# El archivo no existe. Crea uno nuevo o en la raiz de tu wordpress crea el llms.txt');
            }
        }).fail(function() {
            $('#llms-status').removeClass('status-checking status-exists').addClass('status-not-exists').text('Error al verificar');
        });
    }
    
    // Actualizar información del archivo
    function updateFileInfo() {
        $.ajax({
            url: '<?php echo esc_url(site_url('/llms.txt')); ?>',
            type: 'HEAD',
            success: function(data, textStatus, jqXHR) {
                var lastModified = new Date(jqXHR.getResponseHeader('Last-Modified'));
                var fileSize = jqXHR.getResponseHeader('Content-Length');
                
                $('#llms-modified').text(lastModified.toLocaleString());
                $('#llms-size').text(formatFileSize(fileSize));
            }
        });
    }
    
    // Formatear tamaño de archivo
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Cargar contenido del archivo
    $('#load-llms-content').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).html('<span class="loading-spinner"></span> Cargando...');
        
        $.get(ajaxurl, {action: 'llm_tracker_get_llms_content'}, function(response) {
            if (response.success) {
                $('#llms-content-editor').val(response.data.content);
                originalContent = response.data.content;
                hasUnsavedChanges = false;
                $('#save-llms-content').prop('disabled', true);
                updatePreview();
                showMessage('Contenido cargado correctamente', 'success');
            } else {
                showMessage('Error al cargar el contenido: ' + response.data.message, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text('Cargar Contenido');
        });
    });
    
    // Guardar contenido del archivo
    $('#save-llms-content').on('click', function() {
        var content = $('#llms-content-editor').val();
        var $button = $(this);
        
        $button.prop('disabled', true).html('<span class="loading-spinner"></span> Guardando...');
        
        $.post(ajaxurl, {
            action: 'llm_tracker_save_llms_content',
            content: content,
            _ajax_nonce: '<?php echo esc_attr(wp_create_nonce('llm_tracker_save_content')); ?>'
        }, function(response) {
            if (response.success) {
                originalContent = content;
                hasUnsavedChanges = false;
                $('#save-llms-content').prop('disabled', true);
                updateFileInfo();
                updatePreview();
                loadBackupsList(); // Actualizar lista de backups
                showMessage('Archivo guardado correctamente', 'success');
            } else {
                showMessage('Error al guardar: ' + response.data.message, 'error');
            }
        }).fail(function() {
            showMessage('Error de conexión al guardar', 'error');
        }).always(function() {
            $button.prop('disabled', false).text('Guardar Cambios');
        });
    });
    
    // Crear archivo en raíz usando touch
    $('#create-llms-file').on('click', function() {
        if (!confirm('¿Estás seguro de crear el archivo llms.txt en la raíz de WordPress usando el comando touch?')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).html('<span class="loading-spinner"></span> Creando...');
        
        $.post(ajaxurl, {
            action: 'llm_tracker_create_llms_file',
            _ajax_nonce: '<?php echo esc_attr(wp_create_nonce('llm_tracker_create_file')); ?>'
        }, function(response) {
            if (response.success) {
                // Cargar el contenido recién creado en el editor
                $('#llms-content-editor').val(response.data.content);
                originalContent = response.data.content;
                
                // Actualizar estado y vista previa
                checkFileStatus();
                updatePreview();
                loadBackupsList();
                
                // Habilitar botón de guardar
                $('#save-llms-content').prop('disabled', false);
                
                showMessage(response.data.message, 'success');
            } else {
                showMessage('Error al crear archivo: ' + response.data.message, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text('Crear Archivo en Raíz');
        });
    });
    
    // Regenerar archivo automáticamente
    $('#regenerate-llms').on('click', function() {
        if (!confirm('¿Estás seguro de regenerar el archivo llms.txt? Se creará un backup del archivo actual.')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).html('<span class="loading-spinner"></span> Regenerando...');
        
        $.post(ajaxurl, {
            action: 'llm_tracker_regenerate_llms',
            _ajax_nonce: '<?php echo esc_attr(wp_create_nonce('llm_tracker_regenerate')); ?>'
        }, function(response) {
            if (response.success) {
                checkFileStatus();
                updatePreview();
                loadBackupsList();
                showMessage('Archivo regenerado correctamente', 'success');
            } else {
                showMessage('Error al regenerar: ' + response.data.message, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text('Regenerar Automático');
        });
    });
    
    // Formatear YAML
    $('#format-yaml').on('click', function() {
        var content = $('#llms-content-editor').val();
        
        // Validar primero
        var errors = validateYaml(content);
        if (errors.length > 0) {
            var errorMsg = 'Se encontraron errores de sintaxis YAML:\n\n' + errors.join('\n');
            if (confirm(errorMsg + '\n\n¿Desea formatear de todos modos?')) {
                var formatted = formatYaml(content);
                $('#llms-content-editor').val(formatted);
                checkForChanges();
                showMessage('YAML formateado con advertencias', 'warning');
            }
        } else {
            var formatted = formatYaml(content);
            $('#llms-content-editor').val(formatted);
            checkForChanges();
            showMessage('YAML formateado y validado correctamente', 'success');
        }
    });
    
    // Formateo avanzado de YAML con validación Markdown
    function formatYaml(content) {
        var lines = content.split('\n');
        var formatted = [];
        var indentLevel = 0;
        var inMultiline = false;
        var multilineIndent = 0;
        
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            var originalLine = line;
            
            // Detectar inicio de texto multilinea
            if (line.trim().match(/^[^#]*:\s*\|$/)) {
                inMultiline = true;
                multilineIndent = line.search(/\S/);
                formatted.push(line);
                continue;
            }
            
            // Detectar fin de texto multilinea
            if (inMultiline && line.search(/\S/) <= multilineIndent && line.trim() !== '') {
                inMultiline = false;
            }
            
            // Si estamos en texto multilinea, mantener formato
            if (inMultiline) {
                formatted.push(line);
                continue;
            }
            
            // Eliminar espacios extra al inicio y final
            line = line.trim();
            
            // Ignorar líneas vacías o comentarios
            if (line === '' || line.startsWith('#')) {
                formatted.push(line);
                continue;
            }
            
            // Procesar arrays (líneas que empiezan con -)
            if (line.startsWith('-')) {
                var arrayContent = line.substring(1).trim();
                // Si tiene subelementos con :, formatear adecuadamente
                if (arrayContent.includes(':')) {
                    var parts = arrayContent.split(':');
                    if (parts.length >= 2) {
                        var key = parts[0].trim();
                        var value = parts.slice(1).join(':').trim();
                        // Si el valor contiene espacios, poner entre comillas
                        if (value.includes(' ') && !value.startsWith('"') && !value.startsWith("'")) {
                            value = '"' + value + '"';
                        }
                        formatted.push('  - ' + key + ': ' + value);
                        continue;
                    }
                }
                formatted.push('  - ' + arrayContent);
                continue;
            }
            
            // Procesar claves con valores
            if (line.includes(':')) {
                var parts = line.split(':');
                if (parts.length >= 2) {
                    var key = parts[0].trim();
                    var value = parts.slice(1).join(':').trim();
                    
                    // Si el valor está vacío, solo dejar la clave
                    if (value === '') {
                        formatted.push(key + ':');
                        continue;
                    }
                    
                    // Si el valor contiene caracteres especiales o espacios, poner entre comillas
                    if (value.includes(' ') && !value.startsWith('"') && !value.startsWith("'") && !value.startsWith('|')) {
                        value = '"' + value + '"';
                    }
                    
                    formatted.push(key + ': ' + value);
                    continue;
                }
            }
            
            // Si no coincide con ningún patrón, dejar la línea como está
            formatted.push(line);
        }
        
        // Limpiar líneas vacías múltiples
        var result = formatted.join('\n');
        result = result.replace(/\n{3,}/g, '\n\n');
        
        return result;
    }
    
    // Validar sintaxis YAML básica
    function validateYaml(content) {
        var errors = [];
        var lines = content.split('\n');
        var indentStack = [];
        
        for (var i = 0; i < lines.length; i++) {
            var line = lines[i];
            var lineNumber = i + 1;
            var trimmed = line.trim();
            
            // Ignorar comentarios y líneas vacías
            if (trimmed === '' || trimmed.startsWith('#')) {
                continue;
            }
            
            // Detectar sangría inconsistente
            var indent = line.search(/\S/);
            if (indent > 0 && indentStack.length > 0) {
                var lastIndent = indentStack[indentStack.length - 1];
                if (indent > lastIndent && (indent - lastIndent) % 2 !== 0) {
                    errors.push('Línea ' + lineNumber + ': Sangría incorrecta. Use múltiplos de 2 espacios.');
                }
            }
            
            // Validar formato clave: valor
            if (trimmed.includes(':') && !trimmed.startsWith('-')) {
                var colonIndex = trimmed.indexOf(':');
                var key = trimmed.substring(0, colonIndex).trim();
                var value = trimmed.substring(colonIndex + 1).trim();
                
                if (key === '') {
                    errors.push('Línea ' + lineNumber + ': Clave vacía antes de los dos puntos.');
                }
                
                // Validar que las claves no tengan espacios no citados
                if (key.includes(' ') && !key.startsWith('"') && !key.startsWith("'")) {
                    errors.push('Línea ' + lineNumber + ': La clave contiene espacios. Use comillas o reemplace espacios con guiones bajos.');
                }
            }
            
            indentStack.push(indent);
        }
        
        return errors;
    }
    
    // Detectar cambios en el editor
    $('#llms-content-editor').on('input', function() {
        checkForChanges();
        updatePreview();
    });
    
    function checkForChanges() {
        var currentContent = $('#llms-content-editor').val();
        hasUnsavedChanges = currentContent !== originalContent;
        $('#save-llms-content').prop('disabled', !hasUnsavedChanges);
    }
    
    // Actualizar vista previa
    function updatePreview() {
        var content = $('#llms-content-editor').val();
        $('#llms-preview-content').text(content || 'Carga el contenido para ver la vista previa...');
    }
    
    // Cargar lista de backups
    function loadBackupsList() {
        $('#backups-list').html('<p class="description"><span class="loading-spinner"></span> Cargando backups...</p>');
        
        $.get(ajaxurl, {action: 'llm_tracker_get_backups'}, function(response) {
            if (response.success) {
                renderBackupsList(response.data.backups);
            } else {
                $('#backups-list').html('<p class="description">Error al cargar backups</p>');
            }
        });
    }
    
    // Renderizar lista de backups
    function renderBackupsList(backups) {
        if (backups.length === 0) {
            $('#backups-list').html('<p class="description">No hay backups disponibles</p>');
            return;
        }
        
        var html = '';
        backups.forEach(function(backup) {
            var date = new Date(backup.modified * 1000);
            var size = formatFileSize(backup.size);
            
            html += '<div class="backup-item">';
            html += '<div class="backup-info">';
            html += '<div class="backup-filename">' + backup.filename + '</div>';
            html += '<div class="backup-details">' + date.toLocaleString() + ' • ' + size + '</div>';
            html += '</div>';
            html += '<div class="backup-actions">';
            html += '<button type="button" class="button" onclick="downloadBackup(\'' + backup.url + '\')">Descargar</button>';
            html += '<button type="button" class="button" onclick="restoreBackup(\'' + backup.filename + '\')">Restaurar</button>';
            html += '</div>';
            html += '</div>';
        });
        
        $('#backups-list').html(html);
    }
    
    // Descargar backup
    window.downloadBackup = function(url) {
        window.open(url, '_blank');
    };
    
    // Restaurar backup
    window.restoreBackup = function(filename) {
        if (!confirm('¿Estás seguro de restaurar este backup? Se creará un backup del archivo actual.')) {
            return;
        }
        
        $.post(ajaxurl, {
            action: 'llm_tracker_restore_backup',
            backup_file: filename,
            _ajax_nonce: '<?php echo esc_attr(wp_create_nonce('llm_tracker_restore_backup')); ?>'
        }, function(response) {
            if (response.success) {
                checkFileStatus();
                updatePreview();
                showMessage('Backup restaurado correctamente', 'success');
            } else {
                showMessage('Error al restaurar: ' + response.data.message, 'error');
            }
        });
    };
    
    // Actualizar lista de backups
    $('#refresh-backups').on('click', function() {
        loadBackupsList();
    });
    
    // Crear backup manual
    $('#create-backup').on('click', function() {
        var $button = $(this);
        $button.prop('disabled', true).html('<span class="loading-spinner"></span> Creando...');
        
        $.post(ajaxurl, {
            action: 'llm_tracker_save_llms_content',
            content: $('#llms-content-editor').val(),
            _ajax_nonce: '<?php echo esc_attr(wp_create_nonce('llm_tracker_save_content')); ?>'
        }, function(response) {
            if (response.success) {
                loadBackupsList();
                showMessage('Backup creado correctamente', 'success');
            } else {
                showMessage('Error al crear backup: ' + response.data.message, 'error');
            }
        }).always(function() {
            $button.prop('disabled', false).text('Crear Backup Ahora');
        });
    });
    
    // Actualizar vista previa
    $('#refresh-preview').on('click', function() {
        updatePreview();
    });
    
    // Mostrar mensajes
    function showMessage(message, type) {
        var className = type === 'success' ? 'success-message' : 'error-message';
        var $message = $('<div class="' + className + '">' + message + '</div>');
        
        // Insertar después del tab content activo
        $('.tab-content.active').prepend($message);
        
        // Auto-eliminar después de 3 segundos
        setTimeout(function() {
            $message.fadeOut(function() {
                $message.remove();
            });
        }, 3000);
    }
    
    // Advertencia al salir si hay cambios no guardados
    $(window).on('beforeunload', function() {
        if (hasUnsavedChanges) {
            return 'Tienes cambios no guardados en el archivo llms.txt. ¿Estás seguro de salir?';
        }
    });
});
</script>