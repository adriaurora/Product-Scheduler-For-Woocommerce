<?php
/**
 * Plugin Name: Product Scheduler for WooCommerce
 * Description: Programa la publicación y despublicación automática de productos de WooCommerce
 * Version: 1.4.0
 * Author: Disma Consultores
 * Author URI: https://dismaconsultores.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: product-scheduler-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 *
 * @package WC_Product_Scheduler
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WC_PRODUCT_SCHEDULER_VERSION', '1.4.0');
define('WC_PRODUCT_SCHEDULER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_PRODUCT_SCHEDULER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_PRODUCT_SCHEDULER_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Clase principal del plugin
 */
class WC_Product_Scheduler {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Obtener instancia del plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // IMPORTANTE: Añadir intervalo personalizado ANTES de cualquier otro hook
        // Esto debe ejecutarse lo más pronto posible
        add_filter('cron_schedules', array($this, 'add_cron_interval'));

        // Verificar que WooCommerce esté activo
        add_action('plugins_loaded', array($this, 'check_woocommerce'));

        // Inicializar el plugin
        add_action('init', array($this, 'init'));

        // Encolar scripts y estilos en el admin
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Verificar que WooCommerce esté instalado y activo
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
    }

    /**
     * Mostrar aviso si WooCommerce no está activo
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('WooCommerce Product Scheduler requiere que WooCommerce esté instalado y activo.', 'product-scheduler-for-woocommerce'); ?></p>
        </div>
        <?php
    }

    /**
     * Inicializar el plugin
     */
    public function init() {
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Cargar archivos necesarios
        $this->load_files();

        // Inicializar hooks
        $this->init_hooks();
    }

    /**
     * Cargar archivos del plugin
     */
    private function load_files() {
        require_once WC_PRODUCT_SCHEDULER_PLUGIN_DIR . 'includes/class-product-tab.php';
        require_once WC_PRODUCT_SCHEDULER_PLUGIN_DIR . 'includes/class-scheduler.php';
        require_once WC_PRODUCT_SCHEDULER_PLUGIN_DIR . 'includes/class-notifications.php';
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Inicializar componentes
        WC_Product_Scheduler_Tab::get_instance();
        WC_Product_Scheduler_Cron::get_instance();
        WC_Product_Scheduler_Notifications::get_instance();
    }

    /**
     * Añadir intervalo personalizado de 5 minutos
     */
    public function add_cron_interval($schedules) {
        $schedules['every_5_minutes'] = array(
            'interval' => 300, // 5 minutos en segundos
            'display'  => __('Cada 5 minutos', 'product-scheduler-for-woocommerce')
        );
        return $schedules;
    }

    /**
     * Encolar assets del admin
     */
    public function enqueue_admin_assets($hook) {
        global $post, $typenow;

        // Determinar si estamos en páginas de productos
        $is_product_page = false;

        // Página de listado de productos
        if ($hook === 'edit.php' && $typenow === 'product') {
            $is_product_page = true;
            // Solo cargar CSS en listado, no JS
            wp_enqueue_style(
                'product-scheduler-for-woocommerce-admin',
                WC_PRODUCT_SCHEDULER_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WC_PRODUCT_SCHEDULER_VERSION
            );
            return;
        }

        // Páginas de edición/creación de productos
        if (('post.php' === $hook || 'post-new.php' === $hook) &&
            $post && 'product' === $post->post_type) {
            $is_product_page = true;
        }

        if (!$is_product_page) {
            return;
        }

        // Encolar jQuery UI para el datepicker
        wp_enqueue_script('jquery-ui-datepicker');

        // Encolar estilos de WordPress UI (incluye jQuery UI)
        wp_enqueue_style('wp-jquery-ui-dialog');

        // Encolar estilos del plugin
        wp_enqueue_style(
            'product-scheduler-for-woocommerce-admin',
            WC_PRODUCT_SCHEDULER_PLUGIN_URL . 'assets/css/admin.css',
            array('wp-jquery-ui-dialog'),
            WC_PRODUCT_SCHEDULER_VERSION
        );

        // Encolar JavaScript
        wp_enqueue_script(
            'product-scheduler-for-woocommerce-admin',
            WC_PRODUCT_SCHEDULER_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'jquery-ui-datepicker'),
            WC_PRODUCT_SCHEDULER_VERSION,
            true
        );
    }
}

/**
 * Activación del plugin
 */
function wc_product_scheduler_activate() {
    // Verificar que WooCommerce esté activo
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(esc_html__('Este plugin requiere que WooCommerce esté instalado y activo.', 'product-scheduler-for-woocommerce'));
    }

    // Crear evento cron de WordPress
    // Funciona tanto con WP-Cron automático como con wp-cron.php ejecutado por servidor
    if (!wp_next_scheduled('wc_product_scheduler_check')) {
        wp_schedule_event(time(), 'every_5_minutes', 'wc_product_scheduler_check');
    }

    // Generar clave secreta para cron por URL/script si no existe
    if (empty(get_option('wc_product_scheduler_cron_key'))) {
        $secret_key = wp_generate_password(32, false);
        update_option('wc_product_scheduler_cron_key', $secret_key);
    }
}
register_activation_hook(__FILE__, 'wc_product_scheduler_activate');

/**
 * Desactivación del plugin
 */
function wc_product_scheduler_deactivate() {
    // Eliminar evento cron
    $timestamp = wp_next_scheduled('wc_product_scheduler_check');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wc_product_scheduler_check');
    }
}
register_deactivation_hook(__FILE__, 'wc_product_scheduler_deactivate');

// Inicializar el plugin
function wc_product_scheduler_init() {
    return WC_Product_Scheduler::get_instance();
}
add_action('plugins_loaded', 'wc_product_scheduler_init');
