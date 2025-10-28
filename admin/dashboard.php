<?php
/**
 * Dashboard page for LLM Tracker
 */

// Verificar permisos
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'llm-tracker-pro'));
}

global $wpdb;
$table_name = $wpdb->prefix . 'llm_tracker_visits';

// Verificar si la tabla existe
$table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) == $table_name;
if (!$table_exists) {
    echo '<div class="notice notice-error"><p>La tabla de tracking no existe. Por favor, desactiva y vuelve a activar el plugin.</p></div>';
    return;
}

// Obtener estadísticas
$total_visits = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name}")) ?: 0;
$bot_visits = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table_name} WHERE bot_detected = 1")) ?: 0;
$human_visits = $total_visits - $bot_visits;

// Visitas de las últimas 24 horas
$last_24h = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE visit_time >= %s",
    gmdate('Y-m-d H:i:s', strtotime('-24 hours'))
)) ?: 0;

// Visitas de los últimos 7 días
$last_7d = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE visit_time >= %s",
    gmdate('Y-m-d H:i:s', strtotime('-7 days'))
)) ?: 0;

// Visitas de los últimos 30 días
$last_30d = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$table_name} WHERE visit_time >= %s",
    gmdate('Y-m-d H:i:s', strtotime('-30 days'))
)) ?: 0;

// Top bots
$top_bots = $wpdb->get_results($wpdb->prepare(
    "SELECT bot_name, COUNT(*) as count FROM {$table_name} WHERE bot_detected = 1 AND bot_name != '' GROUP BY bot_name ORDER BY count DESC LIMIT 10"
)) ?: array();

// Visitas más recientes
$recent_visits = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$table_name} ORDER BY visit_time DESC LIMIT 10"
)) ?: array();
?>

<div class="wrap">
    <h1>LLM Tracker Dashboard</h1>
    
    <?php if ($total_visits == 0): ?>
        <div class="notice notice-info">
            <p><strong>🚀 Bienvenido a LLM Tracker Pro</strong></p>
            <p>Aún no hay visitas registradas. El plugin comenzará a rastrear las visitas a tu archivo <code>llms.txt</code> automáticamente.</p>
            <p>Para verificar que funciona, puedes acceder directamente a: <a href="<?php echo esc_url(site_url('/llms.txt')); ?>" target="_blank"><?php echo esc_url(site_url('/llms.txt')); ?></a></p>
        </div>
    <?php endif; ?>
    
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
                <div class="stat-label"><?php echo esc_html($total_visits > 0 ? round(($bot_visits / $total_visits) * 100, 1) : 0); ?>% del total</div>
            </div>
            
            <div class="llm-tracker-stat-card human">
                <h3>Visitas Humanas</h3>
                <div class="stat-number"><?php echo number_format($human_visits); ?></div>
                <div class="stat-label"><?php echo esc_html($total_visits > 0 ? round(($human_visits / $total_visits) * 100, 1) : 0); ?>% del total</div>
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
                <h3>Distribución de Bots/LLMs Detectados</h3>
                <div class="chart-wrapper">
                    <?php if (!empty($top_bots)): ?>
                        <div class="pie-chart-container">
                            <canvas id="botsPieChart" width="300" height="300"></canvas>
                            <div class="pie-legend">
                                <?php foreach ($top_bots as $index => $bot): ?>
                                    <div class="legend-item">
                                        <div class="legend-color" style="background-color: <?php echo esc_attr(['#d63638', '#00a32a', '#3858e9', '#f0b849', '#9b51e0', '#569bdd', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24'][$index % 10]); ?>;"></div>
                                        <div class="legend-label"><?php echo esc_html($bot->bot_name); ?></div>
                                        <div class="legend-value"><?php echo number_format($bot->count); ?> (<?php echo esc_html($bot_visits > 0 ? round(($bot->count / $bot_visits) * 100, 1) : 0); ?>%)</div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-data">No se han detectado bots/LLMs aún.</div>
                    <?php endif; ?>
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
                                        <td><?php echo esc_html($bot_visits > 0 ? round(($bot->count / $bot_visits) * 100, 1) : 0); ?>%</td>
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
                                        <td><?php echo esc_html(gmdate('d/m/Y H:i:s', strtotime($visit->visit_time))); ?></td>
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
                                            $referer = wp_parse_url($visit->referer, PHP_URL_HOST);
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
            <p>Tu archivo <code>llms.txt</code> está disponible en: <strong><?php echo esc_url(site_url('/llms.txt')); ?></strong></p>
            <p>
                <?php 
                $llms_file = ABSPATH . 'llms.txt';
                if (file_exists($llms_file)) {
                    echo '✅ El archivo existe y está siendo trackeado. ';
                    echo '<a href="' . esc_url(admin_url('admin.php?page=llm-tracker-settings&tab=llms')) . '" class="button button-primary">Editar Archivo</a>';
                } else {
                    echo '⚠️ El archivo no existe. ';
                    echo '<button id="create-llms-instructions" class="button button-primary">Crear Archivo</button>';
                }
                ?>
            </p>
            <p>Los LLMs pueden acceder a este archivo para obtener información sobre tu sitio y las directrices de uso.</p>
            <p><a href="<?php echo esc_url(admin_url('admin.php?page=llm-tracker-visits')); ?>" class="button">Ver Historial Completo</a></p>
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
    height: auto;
    margin-bottom: 20px;
}

.simple-chart {
    padding: 10px 0;
}

.chart-bar {
    display: flex;
    align-items: center;
    margin-bottom: 12px;
    min-height: 30px;
}

.bar-label {
    flex: 0 0 120px;
    font-size: 12px;
    font-weight: 500;
    padding-right: 10px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.bar-container {
    flex: 1;
    height: 24px;
    background: #f0f0f1;
    border-radius: 12px;
    overflow: hidden;
    margin-right: 10px;
    position: relative;
}

.bar-fill {
    height: 100%;
    border-radius: 12px;
    transition: width 0.3s ease;
    min-width: 2px;
}

.bar-value {
    flex: 0 0 50px;
    text-align: right;
    font-size: 12px;
    font-weight: 600;
    color: #1d2327;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
    color: #646970;
    font-style: italic;
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

/* Estilos para el gráfico de pastel */
.pie-chart-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 20px;
    padding: 20px 0;
}

#botsPieChart {
    max-width: 300px;
    max-height: 300px;
}

.pie-legend {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
    max-width: 400px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
    border-bottom: 1px solid #f0f0f1;
}

.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    flex-shrink: 0;
}

.legend-label {
    flex: 1;
    font-size: 13px;
    font-weight: 500;
    color: #1d2327;
}

.legend-value {
    font-size: 12px;
    color: #646970;
    font-weight: 600;
}

@media (max-width: 768px) {
    .pie-chart-container {
        flex-direction: column;
    }
    
    .pie-legend {
        max-width: 100%;
    }
    
    #botsPieChart {
        width: 250px !important;
        height: 250px !important;
    }
}
</style>


<script>
// Función para dibujar gráfico de pastel
function drawPieChart() {
    const canvas = document.getElementById('botsPieChart');
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = Math.min(centerX, centerY) - 20;
    
    // Datos del gráfico desde PHP
    const chartData = [
        <?php 
        $chart_colors = ['#d63638', '#00a32a', '#3858e9', '#f0b849', '#9b51e0', '#569bdd', '#ff6b6b', '#4ecdc4', '#45b7d1', '#f9ca24'];
        foreach ($top_bots as $index => $bot): 
            $percentage = $bot_visits > 0 ? ($bot->count / $bot_visits) * 100 : 0;
        ?>
        {
            label: '<?php echo esc_js($bot->bot_name); ?>',
            value: <?php echo (float)$bot->count; ?>,
            percentage: <?php echo (float)$percentage; ?>,
            color: '<?php echo esc_js($chart_colors[$index % 10]); ?>'
        },
        <?php endforeach; ?>
    ];
    
    if (chartData.length === 0) return;
    
    // Calcular ángulos
    let currentAngle = -Math.PI / 2; // Empezar desde arriba
    const total = chartData.reduce((sum, item) => sum + item.value, 0);
    
    // Dibujar cada segmento
    chartData.forEach((segment, index) => {
        const sliceAngle = (segment.value / total) * 2 * Math.PI;
        
        // Dibujar segmento
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + sliceAngle);
        ctx.lineTo(centerX, centerY);
        ctx.fillStyle = segment.color;
        ctx.fill();
        
        // Dibujar borde
        ctx.strokeStyle = '#ffffff';
        ctx.lineWidth = 2;
        ctx.stroke();
        
        // Dibujar etiqueta si el segmento es lo suficientemente grande
        if (segment.percentage > 5) {
            const labelAngle = currentAngle + sliceAngle / 2;
            const labelX = centerX + Math.cos(labelAngle) * (radius * 0.7);
            const labelY = centerY + Math.sin(labelAngle) * (radius * 0.7);
            
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 12px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            // Añadir sombra para mejor legibilidad
            ctx.shadowColor = 'rgba(0,0,0,0.5)';
            ctx.shadowBlur = 3;
            ctx.fillText(segment.percentage.toFixed(1) + '%', labelX, labelY);
            ctx.shadowBlur = 0;
        }
        
        currentAngle += sliceAngle;
    });
    
    // Dibujar círculo central para efecto donut (opcional)
    ctx.beginPath();
    ctx.arc(centerX, centerY, radius * 0.3, 0, 2 * Math.PI);
    ctx.fillStyle = '#ffffff';
    ctx.fill();
}

// Función global para mostrar instrucciones de creación
function showCreateInstructions() {
    var instructions = '📋 INSTRUCCIONES PARA CREAR ARCHIVO llms.txt\n\n';
    instructions += 'El archivo debe crearse en la raíz de tu instalación de WordPress.\n\n';
    instructions += '📍 RUTA DEL ARCHIVO:\n';
    instructions += '<?php echo esc_js(ABSPATH); ?>llms.txt\n\n';
    instructions += '🔧 MÉTODO 1 - SSH/Terminal:\n';
    instructions += 'Conéctate por SSH a tu servidor y ejecuta:\n';
    instructions += 'cd <?php echo esc_js(ABSPATH); ?>\n';
    instructions += 'touch llms.txt\n';
    instructions += 'chmod 644 llms.txt\n\n';
    instructions += '🔧 MÉTODO 2 - cPanel/File Manager:\n';
    instructions += '1. Ingresa a cPanel → File Manager\n';
    instructions += '2. Navega a la carpeta raíz de WordPress\n';
    instructions += '3. Haz clic en "New File"\n';
    instructions += '4. Nombre: llms.txt\n';
    instructions += '5. Haz clic derecho → "Change Permissions"\n';
    instructions += '6. Establece permisos: 644\n\n';
    instructions += '🔧 MÉTODO 3 - FTP/SFTP:\n';
    instructions += '1. Conéctate con tu cliente FTP/SFTP\n';
    instructions += '2. Navega a la carpeta raíz de WordPress\n';
    instructions += '3. Crea nuevo archivo: llms.txt\n';
    instructions += '4. Establece permisos: 644\n\n';
    instructions += '✅ Después de crear el archivo:\n';
    instructions += '- Recarga esta página para verificar\n';
    instructions += '- Ve a la sección de Configuración para editar el contenido\n\n';
    instructions += '¿Necesitas ayuda? Contacta a tu administrador de hosting.';
    
    alert(instructions);
}

// Agregar event listeners
document.addEventListener('DOMContentLoaded', function() {
    var button = document.getElementById('create-llms-instructions');
    if (button) {
        button.addEventListener('click', showCreateInstructions);
    }
    
    // Dibujar el gráfico de pastel
    drawPieChart();
});
</script>