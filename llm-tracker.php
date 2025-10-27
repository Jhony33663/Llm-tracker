<?php
/**
 * Plugin Name: LLM Tracker for WordPress
 * Plugin URI: https://uide.edu.ec
 * Description: Plugin para rastrear las visitas de LLMs al archivo llms.txt con registro detallado de fecha, hora y origen.
 * Version: 1.0.0
 * Author: Jonathan Mata
 * Author URI: https://ec.linkedin.com/in/jonathan-david-mata-rodriguez-62a925203
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: llm-tracker
 * Domain Path: /languages
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('LLM_TRACKER_VERSION', '1.0.0');
define('LLM_TRACKER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LLM_TRACKER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Clase principal del plugin LLM Tracker
 */
class LLM_Tracker {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Interceptar solicitudes a llms.txt en el init hook
        add_action('init', array($this, 'handle_llms_txt_request'));
    }
    
    /**
     * Inicialización del plugin
     */
    public function init() {
        // Crear tabla de tracking al activar
        $this->create_tracking_table();
        
        // Agregar menu de administración
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Registrar shortcode para mostrar llms.txt
        add_shortcode('llms_txt', array($this, 'display_llms_txt'));
        
        // Interceptar TODAS las solicitudes para tracking global
        add_action('template_redirect', array($this, 'handle_global_tracking'));
        
        // Agregar script de tracking en el footer (detección cliente)
        add_action('wp_footer', array($this, 'add_tracking_script'));
        add_action('admin_footer', array($this, 'add_tracking_script'));
        
        // Agregar script en el head para detección temprana
        add_action('wp_head', array($this, 'add_early_detection_script'));
        
        // Cargar estilos y scripts de administración
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers para diferentes tipos de tracking
        add_action('wp_ajax_llm_tracker_client_detection', array($this, 'handle_client_detection'));
        add_action('wp_ajax_nopriv_llm_tracker_client_detection', array($this, 'handle_client_detection'));
        
        add_action('wp_ajax_llm_tracker_behavior_tracking', array($this, 'handle_behavior_tracking'));
        add_action('wp_ajax_nopriv_llm_tracker_behavior_tracking', array($this, 'handle_behavior_tracking'));
        
        // Hook para tracking de API REST
        add_action('rest_api_init', array($this, 'init_rest_tracking'));
    }
    
    /**
     * Activación del plugin
     */
    public function activate() {
        $this->create_tracking_table();
        $this->create_llms_txt_file();
        flush_rewrite_rules();
    }
    
    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Crear tabla de tracking en la base de datos
     */
    private function create_tracking_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'llm_tracker_visits';
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            visit_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            referer varchar(500) DEFAULT '' NOT NULL,
            request_method varchar(10) NOT NULL,
            request_uri varchar(500) NOT NULL,
            query_params text DEFAULT '' NOT NULL,
            bot_detected tinyint(1) DEFAULT 0 NOT NULL,
            bot_name varchar(100) DEFAULT '' NOT NULL,
            country varchar(100) DEFAULT '' NOT NULL,
            city varchar(100) DEFAULT '' NOT NULL,
            headers text DEFAULT '' NOT NULL,
            detection_method varchar(50) DEFAULT 'server' NOT NULL,
            page_type varchar(50) DEFAULT 'unknown' NOT NULL,
            session_id varchar(100) DEFAULT '' NOT NULL,
            fingerprint varchar(100) DEFAULT '' NOT NULL,
            behavior_data text DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY visit_time (visit_time),
            KEY bot_detected (bot_detected),
            KEY ip_address (ip_address),
            KEY detection_method (detection_method),
            KEY session_id (session_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Crear archivo llms.txt
     */
    private function create_llms_txt_file() {
        $llms_content = $this->generate_llms_content();
        file_put_contents(LLM_TRACKER_PLUGIN_DIR . 'llms.txt', $llms_content);
    }
    
    /**
     * Generar contenido para llms.txt
     */
    private function generate_llms_content() {
        $site_url = get_site_url();
        $site_name = get_bloginfo('name');
        $admin_email = get_option('admin_email');
        
        $content = "# LLMs Configuration File\n";
        $content .= "# This file provides configuration for Language Learning Models visiting this site\n\n";
        $content .= "# Site Information\n";
        $content .= "name: {$site_name}\n";
        $content .= "url: {$site_url}\n";
        $content .= "description: WordPress site with LLM tracking capabilities\n";
        $content .= "version: 1.0.0\n";
        $content .= "last_updated: " . date('Y-m-d H:i:s') . "\n";
        $content .= "admin_email: {$admin_email}\n\n";
        $content .= "# Available Endpoints for LLMs\n";
        $content .= "endpoints:\n";
        $content .= "  - name: API Health Check\n";
        $content .= "    url: {$site_url}/wp-json/wp/v2/\n";
        $content .= "    method: GET\n";
        $content .= "    description: WordPress REST API endpoint\n\n";
        $content .= "# Guidelines for LLMs\n";
        $content .= "guidelines:\n";
        $content .= "  - Please identify yourself when accessing this site\n";
        $content .= "  - Respect rate limits and fair usage policies\n";
        $content .= "  - Use appropriate endpoints for your needs\n";
        $content .= "  - Report any issues or bugs encountered\n\n";
        $content .= "# Contact Information\n";
        $content .= "contact:\n";
        $content .= "  email: {$admin_email}\n";
        $content .= "  website: {$site_url}\n";
        $content .= "  tracking: This site tracks LLM visits for analytics purposes\n";
        
        return $content;
    }
    
    /**
     * Manejar solicitudes a llms.txt
     */
    public function handle_llms_txt_request() {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Verificar si es una solicitud a llms.txt
        if (preg_match('/\/llms\.txt(\?.*)?$/', $request_uri)) {
            $this->track_visit('llms_txt');
            
            // Servir el archivo llms.txt
            $llms_file = LLM_TRACKER_PLUGIN_DIR . 'llms.txt';
            if (file_exists($llms_file)) {
                header('Content-Type: text/plain');
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                readfile($llms_file);
                exit;
            }
        }
    }
    
    /**
     * Manejar tracking global para todo el sitio
     */
    public function handle_global_tracking() {
        // Evitar tracking en admin (excepto páginas específicas)
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Evitar tracking de archivos estáticos
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $excluded_patterns = array(
            '\.css$', '\.js$', '\.png$', '\.jpg$', '\.jpeg$', '\.gif$', '\.svg$',
            '\.ico$', '\.pdf$', '\.doc$', '\.xls$', '\.zip$', '\.tar$'
        );
        
        foreach ($excluded_patterns as $pattern) {
            if (preg_match('/' . $pattern . '/i', $request_uri)) {
                return;
            }
        }
        
        // Determinar el tipo de página
        $page_type = $this->get_page_type();
        
        // Trackear la visita
        $this->track_visit($page_type);
    }
    
    /**
     * Determinar el tipo de página actual
     */
    private function get_page_type() {
        if (is_front_page() || is_home()) {
            return 'home';
        } elseif (is_single()) {
            return 'single';
        } elseif (is_page()) {
            return 'page';
        } elseif (is_category()) {
            return 'category';
        } elseif (is_tag()) {
            return 'tag';
        } elseif (is_search()) {
            return 'search';
        } elseif (is_404()) {
            return '404';
        } elseif (is_archive()) {
            return 'archive';
        } else {
            return 'unknown';
        }
    }
    
    /**
     * Inicializar tracking para API REST
     */
    public function init_rest_tracking() {
        // Agregar tracking para solicitudes a la API REST
        add_filter('rest_post_dispatch', array($this, 'track_rest_request'), 10, 3);
    }
    
    /**
     * Trackear solicitudes a la API REST
     */
    public function track_rest_request($response, $server, $request) {
        if ($request->get_method() === 'GET') {
            $this->track_visit('rest_api');
        }
        return $response;
    }
    
    /**
     * Registrar visita en la base de datos
     */
    private function track_visit($page_type = 'unknown', $detection_method = 'server') {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'llm_tracker_visits';
        
        // Obtener información de la solicitud
        $ip_address = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $request_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $query_params = json_encode($_GET);
        
        // Detectar si es un bot/LLM
        $bot_info = $this->detect_bot($user_agent);
        
        // Obtener información de geolocalización (básica)
        $geo_info = $this->get_geo_info($ip_address);
        
        // Obtener headers relevantes
        $headers = $this->get_relevant_headers();
        
        // Generar sesión y fingerprint
        $session_id = $this->generate_session_id();
        $fingerprint = $this->generate_fingerprint($user_agent, $headers);
        
        // Verificar si ya trackeamos esta sesión recientemente (evitar duplicados)
        if ($this->is_recent_session($session_id, $ip_address)) {
            return;
        }
        
        // Insertar en la base de datos
        $wpdb->insert(
            $table_name,
            array(
                'visit_time' => current_time('mysql'),
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'referer' => $referer,
                'request_method' => $request_method,
                'request_uri' => $request_uri,
                'query_params' => $query_params,
                'bot_detected' => $bot_info['detected'] ? 1 : 0,
                'bot_name' => $bot_info['name'],
                'country' => $geo_info['country'],
                'city' => $geo_info['city'],
                'headers' => json_encode($headers),
                'detection_method' => $detection_method,
                'page_type' => $page_type,
                'session_id' => $session_id,
                'fingerprint' => $fingerprint,
                'behavior_data' => '',
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
    
    /**
     * Generar ID de sesión único
     */
    private function generate_session_id() {
        $ip = $this->get_client_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $date = date('Y-m-d');
        return md5($ip . $ua . $date);
    }
    
    /**
     * Generar fingerprint del visitante
     */
    private function generate_fingerprint($user_agent, $headers) {
        $data = array(
            $user_agent,
            $headers['ACCEPT_LANGUAGE'] ?? '',
            $headers['ACCEPT_ENCODING'] ?? '',
            $headers['ACCEPT'] ?? ''
        );
        return md5(implode('|', $data));
    }
    
    /**
     * Verificar si la sesión es reciente (evitar duplicados)
     */
    private function is_recent_session($session_id, $ip_address) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'llm_tracker_visits';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE session_id = %s AND ip_address = %s 
             AND visit_time > DATE_SUB(NOW(), INTERVAL 5 MINUTE)",
            $session_id, $ip_address
        ));
        
        return $count > 0;
    }
    
    /**
     * Obtener IP del cliente
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Detectar tipo de bot/LLM
     */
    private function detect_bot($user_agent) {
        $user_agent = strtolower($user_agent);
        
        $bot_patterns = array(
            'gpt' => 'ChatGPT/OpenAI',
            'claude' => 'Claude/Anthropic',
            'gemini' => 'Gemini/Google',
            'llama' => 'LLaMA',
            'mistral' => 'Mistral',
            'anthropic' => 'Anthropic',
            'openai' => 'OpenAI',
            'googlebot' => 'Google Bot',
            'bingbot' => 'Bing Bot',
            'slurp' => 'Yahoo Bot',
            'duckduckbot' => 'DuckDuck Bot',
            'baiduspider' => 'Baidu Spider',
            'yandexbot' => 'Yandex Bot',
            'facebookexternalhit' => 'Facebook Bot',
            'twitterbot' => 'Twitter Bot',
            'linkedinbot' => 'LinkedIn Bot',
            'whatsapp' => 'WhatsApp Bot'
        );
        
        foreach ($bot_patterns as $pattern => $name) {
            if (strpos($user_agent, $pattern) !== false) {
                return array('detected' => true, 'name' => $name);
            }
        }
        
        // Patrones adicionales para detectar LLMs
        if (preg_match('/(curl|wget|python|java|node|http|request|fetch|bot|crawler|spider|scraper)/i', $user_agent)) {
            return array('detected' => true, 'name' => 'Unknown Bot/Crawler');
        }
        
        return array('detected' => false, 'name' => '');
    }
    
    /**
     * Obtener información geográfica básica
     */
    private function get_geo_info($ip) {
        // Aquí podrías integrar una API de geolocalización
        // Por ahora, devolvemos valores vacíos
        return array(
            'country' => '',
            'city' => ''
        );
    }
    
    /**
     * Obtener headers relevantes
     */
    private function get_relevant_headers() {
        $relevant_headers = array();
        $header_keys = array(
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_CONNECTION',
            'HTTP_UPGRADE_INSECURE_REQUESTS',
            'HTTP_SEC_FETCH_DEST',
            'HTTP_SEC_FETCH_MODE',
            'HTTP_SEC_FETCH_SITE',
            'HTTP_SEC_FETCH_USER',
            'HTTP_CACHE_CONTROL'
        );
        
        foreach ($header_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $relevant_headers[str_replace('HTTP_', '', $key)] = $_SERVER[$key];
            }
        }
        
        return $relevant_headers;
    }
    
    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            'LLM Tracker',
            'LLM Tracker',
            'manage_options',
            'llm-tracker',
            array($this, 'admin_page'),
            'dashicons-visibility',
            25
        );
        
        add_submenu_page(
            'llm-tracker',
            'Visits History',
            'Visits History',
            'manage_options',
            'llm-tracker-visits',
            array($this, 'visits_page')
        );
        
        add_submenu_page(
            'llm-tracker',
            'Settings',
            'Settings',
            'manage_options',
            'llm-tracker-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Página principal de administración
     */
    public function admin_page() {
        include_once(LLM_TRACKER_PLUGIN_DIR . 'admin/dashboard.php');
    }
    
    /**
     * Página de historial de visitas
     */
    public function visits_page() {
        include_once(LLM_TRACKER_PLUGIN_DIR . 'admin/visits.php');
    }
    
    /**
     * Página de configuración
     */
    public function settings_page() {
        include_once(LLM_TRACKER_PLUGIN_DIR . 'admin/settings.php');
    }
    
    /**
     * Cargar scripts y estilos de administración
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'llm-tracker') !== false) {
            wp_enqueue_style('llm-tracker-admin', LLM_TRACKER_PLUGIN_URL . 'assets/css/admin.css', array(), LLM_TRACKER_VERSION);
            wp_enqueue_script('llm-tracker-admin', LLM_TRACKER_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), LLM_TRACKER_VERSION, true);
            wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        }
    }
    
    /**
     * Agregar script de detección temprana en el head
     */
    public function add_early_detection_script() {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Obtener configuración
        $settings = get_option('llm_tracker_settings', array());
        $track_all_pages = isset($settings['track_all_pages']) ? $settings['track_all_pages'] : 0;
        
        if (!$track_all_pages && !is_front_page() && !is_single()) {
            return;
        }
        ?>
        <script>
        // Detección temprana de LLMs - se ejecuta inmediatamente
        (function() {
            'use strict';
            
            // Función para detectar características de LLM
            function detectLLMCharacteristics() {
                const tests = {
                    // Test 1: Verificar si navigator tiene propiedades inusuales
                    navigatorTest: function() {
                        const suspiciousProps = [
                            'webdriver', 'phantom', 'selenium', 'nightmare',
                            'callPhantom', '_phantom', '__nightmare'
                        ];
                        
                        for (let prop of suspiciousProps) {
                            if (window.navigator[prop] || window[prop]) {
                                return true;
                            }
                        }
                        return false;
                    },
                    
                    // Test 2: Verificar si hay ausencia de propiedades típicas de navegador
                    missingFeaturesTest: function() {
                        const requiredFeatures = [
                            'localStorage', 'sessionStorage', 'indexedDB',
                            'addEventListener', 'requestAnimationFrame'
                        ];
                        
                        let missingCount = 0;
                        for (let feature of requiredFeatures) {
                            if (!window[feature]) {
                                missingCount++;
                            }
                        }
                        
                        return missingCount > 2; // Si faltan más de 2 características
                    },
                    
                    // Test 3: Verificar timeouts y rendering
                    renderingTest: function() {
                        const start = performance.now();
                        let count = 0;
                        
                        for (let i = 0; i < 1000; i++) {
                            count += i;
                        }
                        
                        const end = performance.now();
                        const duration = end - start;
                        
                        // Si es demasiado rápido, podría ser un bot
                        return duration < 0.5;
                    },
                    
                    // Test 4: Verificar propiedades de pantalla
                    screenTest: function() {
                        if (!window.screen) return true;
                        
                        const suspiciousDimensions = [
                            screen.width === 0,
                            screen.height === 0,
                            screen.colorDepth === 0,
                            screen.pixelDepth === 0
                        ];
                        
                        return suspiciousDimensions.some(Boolean);
                    },
                    
                    // Test 5: Verificar plugins
                    pluginsTest: function() {
                        if (!navigator.plugins) return true;
                        
                        // Los LLMs usualmente no tienen plugins
                        return navigator.plugins.length === 0;
                    },
                    
                    // Test 6: Verificar timezone
                    timezoneTest: function() {
                        try {
                            const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                            return !timezone || timezone === 'UTC';
                        } catch (e) {
                            return true; // Si hay error, es sospechoso
                        }
                    },
                    
                    // Test 7: Verificar lenguaje
                    languageTest: function() {
                        const lang = navigator.language || navigator.userLanguage;
                        return !lang || lang.length < 2;
                    },
                    
                    // Test 8: Verificar Canvas
                    canvasTest: function() {
                        try {
                            const canvas = document.createElement('canvas');
                            const ctx = canvas.getContext('2d');
                            if (!ctx) return true;
                            
                            ctx.fillText('LLM Test', 10, 10);
                            const data = canvas.toDataURL();
                            return !data || data.length < 100;
                        } catch (e) {
                            return true;
                        }
                    }
                };
                
                const results = {};
                let totalScore = 0;
                
                // Ejecutar todos los tests
                for (let testName in tests) {
                    try {
                        results[testName] = tests[testName]();
                        if (results[testName]) totalScore++;
                    } catch (e) {
                        results[testName] = true; // Error = sospechoso
                        totalScore++;
                    }
                }
                
                return {
                    score: totalScore,
                    total: Object.keys(tests).length,
                    percentage: (totalScore / Object.keys(tests).length) * 100,
                    details: results,
                    isLikelyLLM: totalScore >= 4 // Si 4+ tests positivos, probablemente es LLM
                };
            }
            
            // Función para obtener fingerprint avanzado
            function getAdvancedFingerprint() {
                const fingerprint = {
                    userAgent: navigator.userAgent,
                    language: navigator.language,
                    languages: navigator.languages ? navigator.languages.join(',') : '',
                    platform: navigator.platform,
                    cookieEnabled: navigator.cookieEnabled,
                    doNotTrack: navigator.doNotTrack,
                    screen: {
                        width: screen.width,
                        height: screen.height,
                        colorDepth: screen.colorDepth,
                        pixelDepth: screen.pixelDepth
                    },
                    timezone: '',
                    canvas: '',
                    webgl: ''
                };
                
                // Obtener timezone
                try {
                    fingerprint.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                } catch (e) {}
                
                // Canvas fingerprint
                try {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    ctx.textBaseline = 'top';
                    ctx.font = '14px Arial';
                    ctx.fillText('LLM Tracker Fingerprint', 2, 2);
                    fingerprint.canvas = canvas.toDataURL().slice(-50);
                } catch (e) {}
                
                // WebGL fingerprint
                try {
                    const canvas = document.createElement('canvas');
                    const gl = canvas.getContext('webgl');
                    if (gl) {
                        fingerprint.webgl = gl.getParameter(gl.VENDOR) + '|' + gl.getParameter(gl.RENDERER);
                    }
                } catch (e) {}
                
                return fingerprint;
            }
            
            // Ejecutar detección inmediatamente
            const detection = detectLLMCharacteristics();
            const fingerprint = getAdvancedFingerprint();
            
            // Guardar resultados para enviar después
            window.llmTrackerData = {
                detection: detection,
                fingerprint: fingerprint,
                pageType: '<?php echo $this->get_page_type(); ?>',
                timestamp: Date.now()
            };
            
            // Si probablemente es un LLM, enviar inmediatamente
            if (detection.isLikelyLLM) {
                // Usar Image beacon para envío síncrono
                const img = new Image();
                img.src = '<?php echo admin_url("admin-ajax.php"); ?>?' + 
                    'action=llm_tracker_client_detection&' +
                    'detection=' + encodeURIComponent(JSON.stringify(detection)) + '&' +
                    'fingerprint=' + encodeURIComponent(JSON.stringify(fingerprint)) + '&' +
                    'page_type=<?php echo $this->get_page_type(); ?>&' +
                    'timestamp=' + Date.now() + '&' +
                    'user_agent=' + encodeURIComponent(navigator.userAgent) + '&' +
                    'referer=' + encodeURIComponent(document.referrer);
            }
        })();
        </script>
        <?php
    }
    
    /**
     * Agregar script de tracking mejorado
     */
    public function add_tracking_script() {
        if (is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Obtener configuración
        $settings = get_option('llm_tracker_settings', array());
        $track_all_pages = isset($settings['track_all_pages']) ? $settings['track_all_pages'] : 0;
        
        if (!$track_all_pages && !is_front_page() && !is_single()) {
            return;
        }
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Tracking de comportamiento del usuario
            let mouseMovements = 0;
            let keystrokes = 0;
            let scrollEvents = 0;
            let clicks = 0;
            let timeOnPage = 0;
            let startTime = Date.now();
            
            // Trackear eventos de mouse
            $(document).on('mousemove', function(e) {
                mouseMovements++;
            });
            
            // Trackear teclas presionadas
            $(document).on('keypress', function(e) {
                keystrokes++;
            });
            
            // Trackear scrolls
            $(window).on('scroll', function() {
                scrollEvents++;
            });
            
            // Trackear clics
            $(document).on('click', function(e) {
                clicks++;
            });
            
            // Trackear tiempo en la página
            setInterval(function() {
                timeOnPage = Math.floor((Date.now() - startTime) / 1000);
            }, 1000);
            
            // Función para enviar datos de comportamiento
            function sendBehaviorData() {
                const behaviorData = {
                    mouseMovements: mouseMovements,
                    keystrokes: keystrokes,
                    scrollEvents: scrollEvents,
                    clicks: clicks,
                    timeOnPage: timeOnPage,
                    pageHeight: $(document).height(),
                    viewportHeight: $(window).height(),
                    scrollPosition: $(window).scrollTop(),
                    hasScrolledToBottom: $(window).scrollTop() + $(window).height() >= $(document).height() - 100
                };
                
                // Enviar datos de comportamiento
                $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                    action: 'llm_tracker_behavior_tracking',
                    behavior_data: JSON.stringify(behaviorData),
                    page_type: '<?php echo $this->get_page_type(); ?>',
                    user_agent: navigator.userAgent,
                    referer: document.referrer
                });
            }
            
            // Enviar datos cuando el usuario sale de la página
            $(window).on('beforeunload', function() {
                // Usar beacon API si está disponible, o fallback a AJAX síncrono
                if (navigator.sendBeacon) {
                    const data = new FormData();
                    data.append('action', 'llm_tracker_behavior_tracking');
                    data.append('behavior_data', JSON.stringify({
                        mouseMovements: mouseMovements,
                        keystrokes: keystrokes,
                        scrollEvents: scrollEvents,
                        clicks: clicks,
                        timeOnPage: timeOnPage,
                        pageHeight: $(document).height(),
                        viewportHeight: $(window).height(),
                        scrollPosition: $(window).scrollTop(),
                        hasScrolledToBottom: $(window).scrollTop() + $(window).height() >= $(document).height() - 100
                    }));
                    data.append('page_type', '<?php echo $this->get_page_type(); ?>');
                    data.append('user_agent', navigator.userAgent);
                    data.append('referer', document.referrer);
                    
                    navigator.sendBeacon('<?php echo admin_url("admin-ajax.php"); ?>', data);
                } else {
                    // Fallback para navegadores antiguos
                    $.ajax({
                        url: '<?php echo admin_url("admin-ajax.php"); ?>',
                        type: 'POST',
                        async: false,
                        data: {
                            action: 'llm_tracker_behavior_tracking',
                            behavior_data: JSON.stringify({
                                mouseMovements: mouseMovements,
                                keystrokes: keystrokes,
                                scrollEvents: scrollEvents,
                                clicks: clicks,
                                timeOnPage: timeOnPage,
                                pageHeight: $(document).height(),
                                viewportHeight: $(window).height(),
                                scrollPosition: $(window).scrollTop(),
                                hasScrolledToBottom: $(window).scrollTop() + $(window).height() >= $(document).height() - 100
                            }),
                            page_type: '<?php echo $this->get_page_type(); ?>',
                            user_agent: navigator.userAgent,
                            referer: document.referrer
                        }
                    });
                }
            });
            
            // Enviar datos cada 30 segundos (para sesiones largas)
            setInterval(sendBehaviorData, 30000);
            
            // Si tenemos datos de detección previa, enviarlos
            if (window.llmTrackerData) {
                $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                    action: 'llm_tracker_client_detection',
                    detection: JSON.stringify(window.llmTrackerData.detection),
                    fingerprint: JSON.stringify(window.llmTrackerData.fingerprint),
                    page_type: window.llmTrackerData.pageType,
                    timestamp: window.llmTrackerData.timestamp,
                    user_agent: navigator.userAgent,
                    referer: document.referrer
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Manejar detección desde el cliente
     */
    public function handle_client_detection() {
        $detection = json_decode(stripslashes($_POST['detection']), true);
        $fingerprint = json_decode(stripslashes($_POST['fingerprint']), true);
        $page_type = sanitize_text_field($_POST['page_type']);
        $timestamp = intval($_POST['timestamp']);
        $user_agent = sanitize_text_field($_POST['user_agent']);
        $referer = sanitize_text_field($_POST['referer']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'llm_tracker_visits';
        
        // Detectar bot basado en detección del cliente
        $is_bot = $detection['isLikelyLLM'] || $this->detect_bot($user_agent)['detected'];
        $bot_name = '';
        
        if ($is_bot) {
            $bot_info = $this->detect_bot($user_agent);
            $bot_name = $bot_info['name'];
            
            // Si no se detectó por user agent pero sí por cliente
            if (!$bot_info['detected'] && $detection['isLikelyLLM']) {
                $bot_name = 'Client-Detected LLM';
            }
        }
        
        // Insertar registro de detección del cliente
        $wpdb->insert(
            $table_name,
            array(
                'visit_time' => date('Y-m-d H:i:s', $timestamp / 1000),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $user_agent,
                'referer' => $referer,
                'request_method' => 'GET',
                'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                'query_params' => '',
                'bot_detected' => $is_bot ? 1 : 0,
                'bot_name' => $bot_name,
                'country' => '',
                'city' => '',
                'headers' => json_encode($fingerprint),
                'detection_method' => 'client',
                'page_type' => $page_type,
                'session_id' => $this->generate_session_id(),
                'fingerprint' => md5(json_encode($fingerprint)),
                'behavior_data' => json_encode($detection),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        wp_die();
    }
    
    /**
     * Manejar tracking de comportamiento
     */
    public function handle_behavior_tracking() {
        $behavior_data = json_decode(stripslashes($_POST['behavior_data']), true);
        $page_type = sanitize_text_field($_POST['page_type']);
        $user_agent = sanitize_text_field($_POST['user_agent']);
        $referer = sanitize_text_field($_POST['referer']);
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'llm_tracker_visits';
        
        // Detectar bot basado en comportamiento
        $is_bot = false;
        $bot_name = '';
        
        // Patrones de comportamiento sospechosos
        if ($behavior_data['timeOnPage'] < 2 && $behavior_data['mouseMovements'] < 5) {
            $is_bot = true;
            $bot_name = 'Behavior Bot';
        } elseif ($behavior_data['timeOnPage'] > 0 && $behavior_data['mouseMovements'] === 0 && $behavior_data['scrollEvents'] === 0) {
            $is_bot = true;
            $bot_name = 'Static Bot';
        }
        
        // Si no es bot por comportamiento, verificar por user agent
        if (!$is_bot) {
            $bot_info = $this->detect_bot($user_agent);
            $is_bot = $bot_info['detected'];
            $bot_name = $bot_info['name'];
        }
        
        // Actualizar registro existente o crear nuevo
        $session_id = $this->generate_session_id();
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE session_id = %s ORDER BY visit_time DESC LIMIT 1",
            $session_id
        ));
        
        if ($existing) {
            // Actualizar registro existente con datos de comportamiento
            $wpdb->update(
                $table_name,
                array(
                    'behavior_data' => json_encode($behavior_data),
                    'bot_detected' => $is_bot ? 1 : 0,
                    'bot_name' => $bot_name,
                    'detection_method' => 'behavior'
                ),
                array('id' => $existing->id),
                array('%s', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Crear nuevo registro
            $wpdb->insert(
                $table_name,
                array(
                    'visit_time' => current_time('mysql'),
                    'ip_address' => $this->get_client_ip(),
                    'user_agent' => $user_agent,
                    'referer' => $referer,
                    'request_method' => 'GET',
                    'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
                    'query_params' => '',
                    'bot_detected' => $is_bot ? 1 : 0,
                    'bot_name' => $bot_name,
                    'country' => '',
                    'city' => '',
                    'headers' => '',
                    'detection_method' => 'behavior',
                    'page_type' => $page_type,
                    'session_id' => $session_id,
                    'fingerprint' => '',
                    'behavior_data' => json_encode($behavior_data),
                    'created_at' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
            );
        }
        
        wp_die();
    }
    
    /**
     * Mostrar llms.txt via shortcode
     */
    public function display_llms_txt() {
        $llms_file = LLM_TRACKER_PLUGIN_DIR . 'llms.txt';
        if (file_exists($llms_file)) {
            return '<pre>' . file_get_contents($llms_file) . '</pre>';
        }
        return '<p>llms.txt file not found.</p>';
    }
}

// Inicializar el plugin
new LLM_Tracker();

// AJAX handler para page visits
add_action('wp_ajax_llm_tracker_page_visit', 'llm_tracker_page_visit_handler');
add_action('wp_ajax_nopriv_llm_tracker_page_visit', 'llm_tracker_page_visit_handler');

// AJAX handler para exportar datos filtrados
add_action('wp_ajax_llm_tracker_export_filtered', 'llm_tracker_export_filtered_handler');
add_action('wp_ajax_nopriv_llm_tracker_export_filtered', 'llm_tracker_export_filtered_handler');

// AJAX handler para detalles de visita
add_action('wp_ajax_llm_tracker_visit_details', 'llm_tracker_visit_details_handler');
add_action('wp_ajax_nopriv_llm_tracker_visit_details', 'llm_tracker_visit_details_handler');

function llm_tracker_page_visit_handler() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'llm_tracker_visits';
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $user_agent = $_POST['user_agent'] ?? '';
    $referer = $_POST['referer'] ?? '';
    $page_url = $_POST['page_url'] ?? '';
    
    // Detectar bot
    $plugin = new LLM_Tracker();
    $bot_info = $plugin->detect_bot($user_agent);
    
    $wpdb->insert(
        $table_name,
        array(
            'visit_time' => current_time('mysql'),
            'ip_address' => $ip_address,
            'user_agent' => $user_agent,
            'referer' => $referer,
            'request_method' => 'GET',
            'request_uri' => $page_url,
            'query_params' => '',
            'bot_detected' => $bot_info['detected'] ? 1 : 0,
            'bot_name' => $bot_info['name'],
            'country' => '',
            'city' => '',
            'headers' => json_encode(array()),
            'created_at' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
    );
    
    wp_die();
}

function llm_tracker_export_filtered_handler() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'llm_tracker_visits';
    
    // Obtener parámetros de filtro
    $filter_bot = isset($_GET['filter_bot']) ? sanitize_text_field($_GET['filter_bot']) : '';
    $filter_date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $filter_date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $filter_ip = isset($_GET['filter_ip']) ? sanitize_text_field($_GET['filter_ip']) : '';
    
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
    
    // Obtener datos filtrados
    $query = "SELECT * FROM $table_name $where_clause ORDER BY visit_time DESC";
    if (!empty($where_params)) {
        $query = $wpdb->prepare($query, $where_params);
    }
    $visits = $wpdb->get_results($query);
    
    // Generar CSV
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="llm-tracker-export-' . date('Y-m-d-H-i-s') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Cabeceras
    fputcsv($output, array(
        'ID', 'Fecha/Hora', 'IP', 'User Agent', 'Referer', 'Método', 'URI', 
        'Bot Detectado', 'Nombre Bot', 'País', 'Ciudad', 'Método Detección', 
        'Tipo Página', 'Sesión', 'Creado'
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
            $visit->detection_method,
            $visit->page_type,
            $visit->session_id,
            $visit->created_at
        ));
    }
    
    fclose($output);
    exit;
}

function llm_tracker_visit_details_handler() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'llm_tracker_visits';
    $visit_id = intval($_POST['visit_id']);
    
    $visit = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $visit_id
    ));
    
    if ($visit) {
        $headers = json_decode($visit->headers, true) ?: array();
        $behavior_data = json_decode($visit->behavior_data, true) ?: array();
        
        $html = '<table class="widefat">';
        $html .= '<tr><td><strong>ID:</strong></td><td>' . $visit->id . '</td></tr>';
        $html .= '<tr><td><strong>Fecha/Hora:</strong></td><td>' . $visit->visit_time . '</td></tr>';
        $html .= '<tr><td><strong>IP Address:</strong></td><td>' . esc_html($visit->ip_address) . '</td></tr>';
        $html .= '<tr><td><strong>User Agent:</strong></td><td>' . esc_html($visit->user_agent) . '</td></tr>';
        $html .= '<tr><td><strong>Referer:</strong></td><td>' . ($visit->referer ? '<a href="' . esc_url($visit->referer) . '" target="_blank">' . esc_html($visit->referer) . '</a>' : 'Directo') . '</td></tr>';
        $html .= '<tr><td><strong>Método:</strong></td><td>' . $visit->request_method . '</td></tr>';
        $html .= '<tr><td><strong>URI:</strong></td><td>' . esc_html($visit->request_uri) . '</td></tr>';
        $html .= '<tr><td><strong>Bot Detectado:</strong></td><td>' . ($visit->bot_detected ? '<span class="bot-badge">Sí</span>' : '<span class="human-badge">No</span>') . '</td></tr>';
        $html .= '<tr><td><strong>Nombre Bot:</strong></td><td>' . ($visit->bot_name ? esc_html($visit->bot_name) : '-') . '</td></tr>';
        $html .= '<tr><td><strong>País:</strong></td><td>' . ($visit->country ? esc_html($visit->country) : '-') . '</td></tr>';
        $html .= '<tr><td><strong>Ciudad:</strong></td><td>' . ($visit->city ? esc_html($visit->city) : '-') . '</td></tr>';
        $html .= '<tr><td><strong>Método Detección:</strong></td><td>' . esc_html($visit->detection_method) . '</td></tr>';
        $html .= '<tr><td><strong>Tipo Página:</strong></td><td>' . esc_html($visit->page_type) . '</td></tr>';
        $html .= '<tr><td><strong>Sesión:</strong></td><td>' . esc_html($visit->session_id) . '</td></tr>';
        
        if (!empty($headers)) {
            $html .= '<tr><td><strong>Headers:</strong></td><td>';
            foreach ($headers as $key => $value) {
                $html .= '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '<br>';
            }
            $html .= '</td></tr>';
        }
        
        if (!empty($behavior_data)) {
            $html .= '<tr><td><strong>Datos Comportamiento:</strong></td><td>';
            if (is_array($behavior_data)) {
                foreach ($behavior_data as $key => $value) {
                    $html .= '<strong>' . esc_html($key) . ':</strong> ' . esc_html(print_r($value, true)) . '<br>';
                }
            } else {
                $html .= esc_html($behavior_data);
            }
            $html .= '</td></tr>';
        }
        
        $html .= '<tr><td><strong>Creado:</strong></td><td>' . $visit->created_at . '</td></tr>';
        $html .= '</table>';
        
        wp_send_json_success(array('html' => $html));
    } else {
        wp_send_json_error(array('message' => 'Visita no encontrada'));
    }
}
?>