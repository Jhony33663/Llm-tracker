<?php
/**
 * Visits history page for LLM Tracker
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'llm-tracker-pro'));
}

global $wpdb;
$table_name = $wpdb->prefix . 'llm_tracker_visits';

// Verificar si la tabla existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '" . esc_sql($table_name) . "'") == $table_name;
if (!$table_exists) {
    echo '<div class="notice notice-error"><p>La tabla de tracking no existe. Por favor, desactiva y vuelve a activar el plugin.</p></div>';
    return;
}

// Procesar filtros
$filter_bot = isset($_GET['filter_bot']) ? sanitize_text_field($_GET['filter_bot']) : '';
$filter_date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
$filter_ip = isset($_GET['filter_ip']) ? sanitize_text_field($_GET['filter_ip']) : '';

// Establecer fechas por defecto (últimos 30 días)
if (empty($filter_date_from) && empty($filter_date_to)) {
    $filter_date_from = date('Y-m-d', strtotime('-30 days'));
    $filter_date_to = date('Y-m-d');
}

// Paginación
$page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Construir consulta WHERE
$where_conditions = array();
$where_params = array();

if (!empty($filter_bot)) {
    if ($filter_bot === 'bots') {
        $where_conditions[] = "bot_detected = 1";
    } elseif ($filter_bot === 'humans') {
        $where_conditions[] = "bot_detected = 0";
    } elseif ($filter_bot === 'unknown') {
        $where_conditions[] = "bot_detected = 1 AND bot_name = ''";
    } else {
        $where_conditions[] = "bot_name = %s";
        $where_params[] = $filter_bot;
    }
}

if (!empty($filter_date_from)) {
    $where_conditions[] = "DATE(visit_time) >= %s";
    $where_params[] = $filter_date_from;
}

if (!empty($filter_date_to)) {
    $where_conditions[] = "DATE(visit_time) <= %s";
    $where_params[] = $filter_date_to;
}

if (!empty($filter_ip)) {
    $where_conditions[] = "ip_address LIKE %s";
    $where_params[] = '%' . $filter_ip . '%';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Obtener total de registros para paginación
$count_query = "SELECT COUNT(*) FROM {$table_name} {$where_clause}";
if (!empty($where_params)) {
    $count_query = $wpdb->prepare($count_query, $where_params);
}
$total_visits = intval($wpdb->get_var($count_query));
$total_pages = ceil($total_visits / $per_page);

// Obtener visitas
$query = "SELECT * FROM {$table_name} {$where_clause} ORDER BY visit_time DESC LIMIT %d OFFSET %d";
if (!empty($where_params)) {
    $query = $wpdb->prepare($query, array_merge($where_params, array($per_page, $offset)));
} else {
    $query = $wpdb->prepare($query, array($per_page, $offset));
}
$visits = $wpdb->get_results($query);

// Obtener lista de bots únicos para filtro
$bots_list = $wpdb->get_results("SELECT DISTINCT bot_name FROM {$table_name} WHERE bot_detected = 1 AND bot_name != '' ORDER BY bot_name");

// Obtener estadísticas para el período seleccionado
$stats_query = "SELECT 
    COUNT(*) as total_visits,
    SUM(CASE WHEN bot_detected = 1 THEN 1 ELSE 0 END) as bot_visits,
    SUM(CASE WHEN bot_detected = 0 THEN 1 ELSE 0 END) as human_visits,
    COUNT(DISTINCT ip_address) as unique_ips,
    COUNT(DISTINCT DATE(visit_time)) as unique_days
    FROM {$table_name} {$where_clause}";

if (!empty($where_params)) {
    $stats_query = $wpdb->prepare($stats_query, $where_params);
}
$period_stats = $wpdb->get_row($stats_query);

// Asegurar que tenemos valores por defecto
if (!$period_stats) {
    $period_stats = (object) array(
        'total_visits' => 0,
        'bot_visits' => 0,
        'human_visits' => 0,
        'unique_ips' => 0,
        'unique_days' => 0
    );
}

// Función helper para calcular tiempo relativo
function time_ago($timestamp) {
    $difference = time() - $timestamp;
    $periods = array("segundo", "minuto", "hora", "día", "semana", "mes", "año");
    $lengths = array("60", "60", "24", "7", "4.35", "12", "10");
    
    if ($difference < 10) {
        return "ahora mismo";
    }
    
    for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
        $difference /= $lengths[$j];
    }
    
    $difference = round($difference);
    if ($difference != 1) {
        $periods[$j] .= "s";
    }
    
    return "hace " . $difference . " " . $periods[$j];
}
?>

<div class="wrap">
    <h1>Historial de Visitas - LLM Tracker</h1>
    
    <!-- Estadísticas del período -->
    <div class="llm-tracker-period-stats">
        <h3>Estadísticas del Período Seleccionado</h3>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($period_stats->total_visits); ?></span>
                <span class="stat-label">Total Visitas</span>
            </div>
            <div class="stat-item bot">
                <span class="stat-number"><?php echo number_format($period_stats->bot_visits); ?></span>
                <span class="stat-label">Visitas Bots</span>
            </div>
            <div class="stat-item human">
                <span class="stat-number"><?php echo number_format($period_stats->human_visits); ?></span>
                <span class="stat-label">Visitas Humanas</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($period_stats->unique_ips); ?></span>
                <span class="stat-label">IPs Únicas</span>
            </div>
            <div class="stat-item">
                <span class="stat-number"><?php echo number_format($period_stats->unique_days); ?></span>
                <span class="stat-label">Días Únicos</span>
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="llm-tracker-filters">
        <form method="get" action="">
            <input type="hidden" name="page" value="llm-tracker-visits" />
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filter_bot">Tipo de visita:</label>
                    <select name="filter_bot" id="filter_bot">
                        <option value="">Todos</option>
                        <option value="bots" <?php selected($filter_bot, 'bots'); ?>>Solo Bots/LLMs</option>
                        <option value="humans" <?php selected($filter_bot, 'humans'); ?>>Solo Humanos</option>
                        <option value="unknown" <?php selected($filter_bot, 'unknown'); ?>>Bots no identificados</option>
                        <?php foreach ($bots_list as $bot): ?>
                            <option value="<?php echo esc_attr($bot->bot_name); ?>" <?php selected($filter_bot, $bot->bot_name); ?>>
                                <?php echo esc_html($bot->bot_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="filter_ip">Dirección IP:</label>
                    <input type="text" name="filter_ip" id="filter_ip" value="<?php echo esc_attr($filter_ip); ?>" placeholder="Buscar IP..." />
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label for="date_from">Fecha desde:</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo esc_attr($filter_date_from); ?>" />
                </div>
                
                <div class="filter-group">
                    <label for="date_to">Fecha hasta:</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo esc_attr($filter_date_to); ?>" />
                </div>
                
                <div class="filter-group">
                    <label>Atajos de fecha:</label>
                    <div class="date-shortcuts">
                        <button type="button" class="button button-small" onclick="setDateRange('today')">Hoy</button>
                        <button type="button" class="button button-small" onclick="setDateRange('7days')">7 días</button>
                        <button type="button" class="button button-small" onclick="setDateRange('30days')">30 días</button>
                        <button type="button" class="button button-small" onclick="setDateRange('thismonth')">Este mes</button>
                    </div>
                </div>
            </div>
            
            <div class="filter-row">
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <input type="submit" class="button button-primary" value="Aplicar Filtros" />
                    <a href="<?php echo esc_url(admin_url('admin.php?page=llm-tracker-visits')); ?>" class="button">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Estadísticas rápidas -->
    <div class="llm-tracker-quick-stats">
        <span class="stat-item">
            <strong>Total:</strong> <?php echo number_format($total_visits); ?> visitas
        </span>
        <?php if (!empty($filter_bot) || !empty($filter_date_from) || !empty($filter_date_to) || !empty($filter_ip)): ?>
            <span class="stat-item">
                <strong>Período:</strong> <?php echo !empty($filter_date_from) ? esc_html(date('d/m/Y', strtotime($filter_date_from))) : 'inicio'; ?> 
                al <?php echo !empty($filter_date_to) ? esc_html(date('d/m/Y', strtotime($filter_date_to))) : 'fin'; ?>
            </span>
        <?php endif; ?>
    </div>
    
    <!-- Tabla de visitas -->
    <div class="llm-tracker-visits-table">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">Fecha/Hora</th>
                    <th scope="col" class="manage-column">IP</th>
                    <th scope="col" class="manage-column">Tipo</th>
                    <th scope="col" class="manage-column">Bot/LLM</th>
                    <th scope="col" class="manage-column">User Agent</th>
                    <th scope="col" class="manage-column">Origen</th>
                    <th scope="col" class="manage-column">País/Ciudad</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($visits)): ?>
                    <?php foreach ($visits as $visit): ?>
                        <tr>
                            <td>
                                <?php echo esc_html(date('d/m/Y H:i:s', strtotime($visit->visit_time))); ?>
                                <br><small><?php echo esc_html(time_ago(strtotime($visit->visit_time))); ?></small>
                            </td>
                            <td>
                                <code><?php echo esc_html($visit->ip_address); ?></code>
                            </td>
                            <td>
                                <?php if ($visit->bot_detected): ?>
                                    <span class="bot-badge">Bot</span>
                                <?php else: ?>
                                    <span class="human-badge">Humano</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $visit->bot_name ? esc_html($visit->bot_name) : '-'; ?>
                            </td>
                            <td>
                                <div class="user-agent-cell" title="<?php echo esc_attr($visit->user_agent); ?>">
                                    <?php echo esc_html(substr($visit->user_agent, 0, 50)); ?>
                                    <?php if (strlen($visit->user_agent) > 50): ?>
                                        <span>...</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                if (!empty($visit->referer)) {
                                    $referer_host = wp_parse_url($visit->referer, PHP_URL_HOST);
                                    if ($referer_host) {
                                        echo '<a href="' . esc_url($visit->referer) . '" target="_blank">' . esc_html($referer_host) . '</a>';
                                    } else {
                                        echo esc_html($visit->referer);
                                    }
                                } else {
                                    echo '<em>Directo</em>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                $location = array();
                                if (!empty($visit->country)) $location[] = $visit->country;
                                if (!empty($visit->city)) $location[] = $visit->city;
                                echo !empty($location) ? esc_html(implode(', ', $location)) : '-';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7">
                            <p>No se encontraron visitas con los filtros seleccionados.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Paginación -->
    <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                $current_url = remove_query_arg('paged');
                echo paginate_links(array(
                    'base' => add_query_arg('paged', '%#%', $current_url),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $page,
                    'mid_size' => 5,
                ));
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.llm-tracker-filters {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.filter-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    align-items: end;
}

.filter-row:last-child {
    margin-bottom: 0;
}

.filter-group {
    display: flex;
    flex-direction: column;
    min-width: 200px;
}

.filter-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}

.filter-group input,
.filter-group select {
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
}

.llm-tracker-quick-stats {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 15px 20px;
    margin: 20px 0;
    display: flex;
    gap: 30px;
}

.stat-item {
    font-size: 14px;
}

.llm-tracker-visits-table {
    margin: 20px 0;
}

.user-agent-cell {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.bot-badge {
    background: #d63638;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.human-badge {
    background: #00a32a;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.llm-tracker-period-stats {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.llm-tracker-period-stats h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
    font-size: 18px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
}

.stat-item {
    text-align: center;
    padding: 15px;
    background: #f6f7f7;
    border-radius: 6px;
    border-left: 4px solid #8c8f94;
}

.stat-item.bot {
    border-left-color: #d63638;
}

.stat-item.human {
    border-left-color: #00a32a;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #1d2327;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.date-shortcuts {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .filter-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    }
    
    .llm-tracker-quick-stats {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-refresh cada 30 segundos
    setInterval(function() {
        location.reload();
    }, 30000);
});

// Funciones para atajos de fecha
function setDateRange(range) {
    const today = new Date();
    let fromDate = new Date();
    let toDate = new Date();
    
    switch(range) {
        case 'today':
            fromDate = today;
            toDate = today;
            break;
        case '7days':
            fromDate = new Date(today);
            fromDate.setDate(today.getDate() - 7);
            toDate = today;
            break;
        case '30days':
            fromDate = new Date(today);
            fromDate.setDate(today.getDate() - 30);
            toDate = today;
            break;
        case 'thismonth':
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1);
            toDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            break;
    }
    
    // Formatear fechas como YYYY-MM-DD
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };
    
    // Establecer valores en los campos
    jQuery('#date_from').val(formatDate(fromDate));
    jQuery('#date_to').val(formatDate(toDate));
    
    // Enviar formulario
    jQuery('.llm-tracker-filters form').submit();
}
</script>