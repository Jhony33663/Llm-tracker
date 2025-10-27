<?php
/**
 * Dashboard page for LLM Tracker
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.'));
}

global $wpdb;
$table_name = $wpdb->prefix . 'llm_tracker_visits';

// Verificar si la tabla existe
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
if (!$table_exists) {
    echo '<div class="notice notice-error"><p>La tabla de tracking no existe. Por favor, desactiva y vuelve a activar el plugin.</p></div>';
    return;
}

// Obtener estadísticas
$total_visits = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
$bot_visits = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE bot_detected = 1");
$human_visits = $total_visits - $bot_visits;

// Visitas de las últimas 24 horas
$last_24h = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_name WHERE visit_time >= %s",
    date('Y-m-d H:i:s', strtotime('-24 hours'))
));

// Visitas de los últimos 7 días
$last_7d = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_name WHERE visit_time >= %s",
    date('Y-m-d H:i:s', strtotime('-7 days'))
));

// Top bots
$top_bots = $wpdb->get_results($wpdb->prepare(
    "SELECT bot_name, COUNT(*) as count FROM $table_name WHERE bot_detected = 1 AND bot_name != '' GROUP BY bot_name ORDER BY count DESC LIMIT 10"
));

// Visitas más recientes
$recent_visits = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table_name ORDER BY visit_time DESC LIMIT 10"
));
?>

<div class="wrap">
    <h1>LLM Tracker Dashboard</h1>
    
    <div class="llm-tracker-dashboard">
        <!-- Tarjetas de estadísticas -->
        <div class="llm-tracker-stats-grid">
            <div class="llm-tracker-stat-card">
                <h3>Total Visitas</h3>
                <div class="stat-number"><?php echo number_format($total_visits); ?></div>
                <div class="stat-label">Todas las visitas</div>
            </div>
            
            <div class="llm-tracker-stat-card bot">
                <h3>Visitas de Bots/LLMs</h3>
                <div class="stat-number"><?php echo number_format($bot_visits); ?></div>
                <div class="stat-label"><?php echo $total_visits > 0 ? round(($bot_visits / $total_visits) * 100, 1) : 0; ?>% del total</div>
            </div>
            
            <div class="llm-tracker-stat-card human">
                <h3>Visitas Humanas</h3>
                <div class="stat-number"><?php echo number_format($human_visits); ?></div>
                <div class="stat-label"><?php echo $total_visits > 0 ? round(($human_visits / $total_visits) * 100, 1) : 0; ?>% del total</div>
            </div>
            
            <div class="llm-tracker-stat-card">
                <h3>Últimas 24h</h3>
                <div class="stat-number"><?php echo number_format($last_24h); ?></div>
                <div class="stat-label">Visitas recientes</div>
            </div>
        </div>
        
        <!-- Gráficos y tablas -->
        <div class="llm-tracker-charts-grid">
            <!-- Top Bots -->
            <div class="llm-tracker-chart-container">
                <h3>Top Bots/LLMs Detectados</h3>
                <div class="chart-wrapper">
                    <canvas id="topBotsChart"></canvas>
                </div>
                <div class="top-bots-list">
                    <?php if (!empty($top_bots)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Bot/LLM</th>
                                    <th>Visitas</th>
                                    <th>Porcentaje</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($top_bots as $bot): ?>
                                    <tr>
                                        <td><?php echo esc_html($bot->bot_name); ?></td>
                                        <td><?php echo number_format($bot->count); ?></td>
                                        <td><?php echo $bot_visits > 0 ? round(($bot->count / $bot_visits) * 100, 1) : 0; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No se han detectado bots/LLMs aún.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Visitas Recientes -->
            <div class="llm-tracker-recent-visits">
                <h3>Visitas Recientes</h3>
                <div class="recent-visits-list">
                    <?php if (!empty($recent_visits)): ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Fecha/Hora</th>
                                    <th>IP</th>
                                    <th>Tipo</th>
                                    <th>Bot/LLM</th>
                                    <th>Origen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_visits as $visit): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i:s', strtotime($visit->visit_time)); ?></td>
                                        <td><?php echo esc_html($visit->ip_address); ?></td>
                                        <td>
                                            <?php if ($visit->bot_detected): ?>
                                                <span class="bot-badge">Bot</span>
                                            <?php else: ?>
                                                <span class="human-badge">Humano</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $visit->bot_name ? esc_html($visit->bot_name) : '-'; ?></td>
                                        <td>
                                            <?php 
                                            $referer = parse_url($visit->referer, PHP_URL_HOST);
                                            echo $referer ? esc_html($referer) : 'Directo';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No hay visitas registradas aún.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Información del archivo llms.txt -->
        <div class="llm-tracker-info-box">
            <h3>Información del Archivo llms.txt</h3>
            <p>Tu archivo <code>llms.txt</code> está disponible en: <strong><?php echo site_url('/llms.txt'); ?></strong></p>
            <p>Los LLMs pueden acceder a este archivo para obtener información sobre tu sitio y las directrices de uso.</p>
            <p><a href="<?php echo admin_url('admin.php?page=llm-tracker-visits'); ?>" class="button button-primary">Ver Historial Completo</a></p>
        </div>
    </div>
</div>

<style>
.llm-tracker-dashboard {
    margin-top: 20px;
}

.llm-tracker-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.llm-tracker-stat-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.llm-tracker-stat-card.bot {
    border-left: 4px solid #d63638;
}

.llm-tracker-stat-card.human {
    border-left: 4px solid #00a32a;
}

.llm-tracker-stat-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #50575e;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-number {
    font-size: 32px;
    font-weight: bold;
    color: #1d2327;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #646970;
}

.llm-tracker-charts-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
}

@media (max-width: 1200px) {
    .llm-tracker-charts-grid {
        grid-template-columns: 1fr;
    }
}

.llm-tracker-chart-container,
.llm-tracker-recent-visits {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.llm-tracker-chart-container h3,
.llm-tracker-recent-visits h3 {
    margin: 0 0 20px 0;
    font-size: 18px;
    color: #1d2327;
}

.chart-wrapper {
    height: 300px;
    margin-bottom: 20px;
}

.top-bots-list table,
.recent-visits-list table {
    margin-top: 0;
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

.llm-tracker-info-box {
    background: #f6f7f7;
    border: 1px solid #c3c4c7;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.llm-tracker-info-box h3 {
    margin: 0 0 15px 0;
    color: #1d2327;
}

.llm-tracker-info-box code {
    background: #d63638;
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Datos para el gráfico de top bots
    <?php if (!empty($top_bots)): ?>
    var topBotsData = {
        labels: [<?php echo implode(',', array_map(function($bot) { return "'".esc_js($bot->bot_name)."'"; }, $top_bots)); ?>],
        datasets: [{
            label: 'Visitas',
            data: [<?php echo implode(',', array_map(function($bot) { return $bot->count; }, $top_bots)); ?>],
            backgroundColor: [
                '#d63638', '#00a32a', '#3858e9', '#f0b849', '#9b51e0',
                '#569bdd', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24'
            ],
            borderWidth: 0
        }]
    };

    var ctx = document.getElementById('topBotsChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: topBotsData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 15,
                        font: {
                            size: 11
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>