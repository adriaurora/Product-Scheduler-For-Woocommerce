<?php
/**
 * Clase para gestionar la programación de productos
 *
 * @package WC_Product_Scheduler
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase WC_Product_Scheduler_Cron
 */
class WC_Product_Scheduler_Cron {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Caché de cron key para evitar múltiples consultas
     */
    private static $cron_key = null;

    /**
     * Obtener instancia
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
        // Hook para el cron de WordPress/Servidor
        add_action('wc_product_scheduler_check', array($this, 'check_scheduled_products'));

        // Hook alternativo para ejecutar mediante WP-CLI o cron del servidor
        // Solo registrar si NO estamos en admin
        if (!is_admin() || defined('DOING_CRON')) {
            add_action('init', array($this, 'maybe_check_via_request'));
        }

        // Sistema de fallback: SOLO en frontend, NUNCA en admin
        if (!is_admin() || defined('DOING_CRON')) {
            add_action('wp_loaded', array($this, 'maybe_run_fallback_check'));
        }
    }

    /**
     * Sistema de fallback para webs con WP-Cron automático
     * OPTIMIZADO: Usa archivos en lugar de transients (0 consultas SQL)
     * Solo se ejecuta en frontend, nunca en admin
     * Se ejecuta cada 5 minutos incluso si el evento cron no se dispara
     */
    public function maybe_run_fallback_check() {
        // SALIDA RÁPIDA: No hacer NADA en admin
        if (is_admin()) {
            return;
        }

        // Usar archivos en lugar de transients (más rápido, sin SQL)
        $lock_file = WP_CONTENT_DIR . '/wc-scheduler-last-run.txt';
        $running_lock = WP_CONTENT_DIR . '/wc-scheduler-running.lock';

        // Verificar si el archivo existe y cuándo fue la última ejecución
        if (file_exists($lock_file)) {
            $last_run = (int) @file_get_contents($lock_file);
            if ((time() - $last_run) < 300) { // 5 minutos
                return;
            }
        }

        // Verificar lock de ejecución (evitar duplicados)
        if (file_exists($running_lock)) {
            $lock_time = @filemtime($running_lock);
            if ($lock_time && (time() - $lock_time) < 300) {
                return; // Otra instancia está ejecutándose
            }
            // Lock muy antiguo, eliminarlo
            @wp_delete_file($running_lock);
        }

        // Crear lock
        @file_put_contents($running_lock, time());

        try {
            // Ejecutar verificación
            $this->check_scheduled_products();

            // Actualizar timestamp de última ejecución
            @file_put_contents($lock_file, time());

            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[WC Scheduler] ✅ Fallback check ejecutado a las ' . gmdate('Y-m-d H:i:s'));
            }
        } catch (Exception $e) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[WC Scheduler] ERROR en fallback check: ' . $e->getMessage());
        } finally {
            @wp_delete_file($running_lock);
        }
    }

    /**
     * Permitir ejecución mediante petición GET con clave secreta
     * Solo para desarrollo o como alternativa al cron
     */
    public function maybe_check_via_request() {
        // IMPORTANTE: Solo ejecutar si se pasan los parámetros específicos
        // NO ejecutar en cada petición normal
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['wc_scheduler_cron']) || !isset($_GET['key'])) {
            return; // Salir inmediatamente si no es una petición de cron
        }

        // Obtener cron key (cacheada)
        $secret_key = self::get_cron_key();

        // Sanitizar y verificar clave (la clave secreta hace las veces de nonce)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $provided_key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        if ($provided_key === $secret_key) {
            // Prevenir ejecuciones múltiples usando archivo
            $running_lock = WP_CONTENT_DIR . '/wc-scheduler-running.lock';

            if (file_exists($running_lock)) {
                $lock_time = @filemtime($running_lock);
                if ($lock_time && (time() - $lock_time) < 300) {
                    wp_die('Cron ya está ejecutándose', 'WC Product Scheduler', array('response' => 423));
                }
            }

            // Marcar como ejecutándose
            @file_put_contents($running_lock, time());

            try {
                $this->check_scheduled_products();
                @wp_delete_file($running_lock);
                wp_die('Cron ejecutado correctamente', 'WC Product Scheduler', array('response' => 200));
            } catch (Exception $e) {
                @wp_delete_file($running_lock);
                wp_die('Error en ejecución: ' . esc_html($e->getMessage()), 'WC Product Scheduler', array('response' => 500));
            }
        } else {
            wp_die('Clave incorrecta', 'WC Product Scheduler', array('response' => 403));
        }
    }

    /**
     * Verificar y procesar productos programados
     */
    public function check_scheduled_products() {
        // Obtener timezone de WordPress
        $timezone = wp_timezone();
        $current_time = new DateTime('now', $timezone);
        $current_timestamp = $current_time->getTimestamp();

        // Buscar productos para despublicar
        $this->process_unpublish_products($current_timestamp);

        // Buscar productos para republicar
        $this->process_republish_products($current_timestamp);
    }

    /**
     * Procesar productos para despublicar
     */
    private function process_unpublish_products($current_timestamp) {
        global $wpdb;

        // Log: inicio del proceso (solo si WP_DEBUG)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log(sprintf('[WC Scheduler] Buscando productos para despublicar. Timestamp actual: %d (%s)',
                $current_timestamp,
                gmdate('Y-m-d H:i:s', $current_timestamp)
            ));
        }

        // Buscar productos con fecha de despublicación vencida Y toggle activado
        // OPTIMIZADO: Empezar por postmeta (tabla más pequeña) y usar CAST para comparación numérica
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, pm_timestamp.meta_value as unpublish_timestamp
            FROM {$wpdb->postmeta} pm_timestamp
            INNER JOIN {$wpdb->posts} p ON pm_timestamp.post_id = p.ID AND p.post_type = 'product' AND p.post_status = 'publish'
            INNER JOIN {$wpdb->postmeta} pm_enabled ON p.ID = pm_enabled.post_id AND pm_enabled.meta_key = '_scheduler_unpublish_enabled' AND pm_enabled.meta_value = 'yes'
            WHERE pm_timestamp.meta_key = '_scheduler_unpublish_timestamp'
            AND CAST(pm_timestamp.meta_value AS UNSIGNED) > 0
            AND CAST(pm_timestamp.meta_value AS UNSIGNED) <= %d
            LIMIT 50
        ", $current_timestamp));

        // Log: resultados de la consulta (solo si WP_DEBUG)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            if (empty($products)) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[WC Scheduler] No se encontraron productos para despublicar');
            } else {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WC Scheduler] Encontrados %d productos para despublicar', count($products)));
            }
        }

        if (empty($products)) {
            return;
        }

        foreach ($products as $product_data) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WC Scheduler] Procesando producto ID: %d, timestamp: %d',
                    $product_data->ID,
                    $product_data->unpublish_timestamp
                ));
            }
            $result = $this->unpublish_product($product_data->ID);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log(sprintf('[WC Scheduler] Resultado despublicación producto #%d: %s',
                    $product_data->ID,
                    $result ? 'ÉXITO' : 'FALLO'
                ));
            }
        }
    }

    /**
     * Procesar productos para publicar
     * Busca productos en CUALQUIER estado (no solo draft) para permitir publicación programada
     */
    private function process_republish_products($current_timestamp) {
        global $wpdb;

        // Buscar productos con fecha de publicación vencida Y toggle activado
        // OPTIMIZADO: Empezar por postmeta y usar CAST para comparación numérica
        // IMPORTANTE: Busca productos en cualquier estado excepto 'publish' (ya publicados)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $products = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, pm_timestamp.meta_value as republish_timestamp
            FROM {$wpdb->postmeta} pm_timestamp
            INNER JOIN {$wpdb->posts} p ON pm_timestamp.post_id = p.ID AND p.post_type = 'product' AND p.post_status != 'publish'
            INNER JOIN {$wpdb->postmeta} pm_enabled ON p.ID = pm_enabled.post_id AND pm_enabled.meta_key = '_scheduler_republish_enabled' AND pm_enabled.meta_value = 'yes'
            WHERE pm_timestamp.meta_key = '_scheduler_republish_timestamp'
            AND CAST(pm_timestamp.meta_value AS UNSIGNED) > 0
            AND CAST(pm_timestamp.meta_value AS UNSIGNED) <= %d
            LIMIT 50
        ", $current_timestamp));

        if (empty($products)) {
            return;
        }

        foreach ($products as $product_data) {
            $this->republish_product($product_data->ID);
        }
    }

    /**
     * Despublicar un producto
     */
    private function unpublish_product($product_id) {
        // Verificar que el producto existe y está publicado
        $product = wc_get_product($product_id);
        if (!$product || $product->get_status() !== 'publish') {
            return false;
        }

        // Verificar que el toggle sigue activado (por seguridad)
        $enabled = get_post_meta($product_id, '_scheduler_unpublish_enabled', true);
        if ($enabled !== 'yes') {
            return false;
        }

        // Prevenir ejecución múltiple del mismo producto
        $processing_key = 'wc_scheduler_processing_' . $product_id;
        if (get_transient($processing_key)) {
            return false;
        }
        set_transient($processing_key, true, 60); // 1 minuto

        try {
            // Guardar el estado anterior por si se necesita
            update_post_meta($product_id, '_scheduler_previous_status', 'publish');

            // Cambiar estado a borrador (sin hooks para evitar bucles)
            $result = wp_update_post(array(
                'ID'          => $product_id,
                'post_status' => 'draft'
            ), true);

            if (is_wp_error($result)) {
                delete_transient($processing_key);
                return false;
            }

            // Limpiar la programación de despublicación Y desactivar el toggle
            delete_post_meta($product_id, '_scheduler_unpublish_date');
            delete_post_meta($product_id, '_scheduler_unpublish_time');
            delete_post_meta($product_id, '_scheduler_unpublish_timestamp');
            update_post_meta($product_id, '_scheduler_unpublish_enabled', 'no');

            // Registrar la acción (pasar $product para evitar consulta redundante)
            $this->log_action($product_id, 'unpublish', $product);

            // Enviar notificación
            do_action('wc_product_scheduler_unpublished', $product_id, $product);

            // Limpiar caché de WooCommerce
            wc_delete_product_transients($product_id);

            delete_transient($processing_key);
            return true;

        } catch (Exception $e) {
            delete_transient($processing_key);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[WC Product Scheduler] Error despublicando producto #' . $product_id . ': ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Publicar un producto
     * Funciona independientemente del estado actual (draft, pending, private, etc.)
     */
    private function republish_product($product_id) {
        // Verificar que el producto existe
        $product = wc_get_product($product_id);
        if (!$product) {
            return false;
        }

        // Si ya está publicado, no hacer nada (evitar procesamiento innecesario)
        if ($product->get_status() === 'publish') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[WC Scheduler] Producto #' . $product_id . ' ya está publicado, omitiendo');
            }
            // Limpiar la programación aunque ya esté publicado
            delete_post_meta($product_id, '_scheduler_republish_date');
            delete_post_meta($product_id, '_scheduler_republish_time');
            delete_post_meta($product_id, '_scheduler_republish_timestamp');
            update_post_meta($product_id, '_scheduler_republish_enabled', 'no');
            return true;
        }

        // Verificar que el toggle sigue activado (por seguridad)
        $enabled = get_post_meta($product_id, '_scheduler_republish_enabled', true);
        if ($enabled !== 'yes') {
            return false;
        }

        // Prevenir ejecución múltiple del mismo producto
        $processing_key = 'wc_scheduler_processing_' . $product_id;
        if (get_transient($processing_key)) {
            return false;
        }
        set_transient($processing_key, true, 60); // 1 minuto

        try {
            // Cambiar estado a publicado (sin hooks para evitar bucles)
            $result = wp_update_post(array(
                'ID'          => $product_id,
                'post_status' => 'publish'
            ), true);

            if (is_wp_error($result)) {
                delete_transient($processing_key);
                return false;
            }

            // Limpiar la programación de republicación Y desactivar el toggle
            delete_post_meta($product_id, '_scheduler_republish_date');
            delete_post_meta($product_id, '_scheduler_republish_time');
            delete_post_meta($product_id, '_scheduler_republish_timestamp');
            update_post_meta($product_id, '_scheduler_republish_enabled', 'no');

            // Limpiar estado anterior
            delete_post_meta($product_id, '_scheduler_previous_status');

            // Registrar la acción (pasar $product para evitar consulta redundante)
            $this->log_action($product_id, 'republish', $product);

            // Enviar notificación
            do_action('wc_product_scheduler_republished', $product_id, $product);

            // Limpiar caché de WooCommerce
            wc_delete_product_transients($product_id);

            delete_transient($processing_key);
            return true;

        } catch (Exception $e) {
            delete_transient($processing_key);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                error_log('[WC Product Scheduler] Error republicando producto #' . $product_id . ': ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Registrar acción en log
     * OPTIMIZADO: Solo usa error_log, no guarda en options table
     */
    private function log_action($product_id, $action, $product = null) {
        // Si no se pasó el producto, obtenerlo
        if (!$product) {
            $product = wc_get_product($product_id);
        }

        if (!$product) {
            return;
        }

        // Log en archivo (siempre, no solo en WP_DEBUG)
        // Esto permite tener registro de acciones importantes sin ralentizar la DB
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        error_log(sprintf(
            '[WC Product Scheduler] %s: Product #%d "%s" at %s',
            ucfirst($action),
            $product_id,
            $product->get_name(),
            current_time('mysql')
        ));

        // Hook para que otros plugins puedan manejar el logging si lo necesitan
        do_action('wc_product_scheduler_log', $product_id, $action, $product);
    }

    /**
     * Obtener cron key (cacheada para evitar múltiples consultas)
     */
    private static function get_cron_key() {
        if (self::$cron_key === null) {
            self::$cron_key = get_option('wc_product_scheduler_cron_key', '');

            if (empty(self::$cron_key)) {
                self::$cron_key = wp_generate_password(32, false);
                update_option('wc_product_scheduler_cron_key', self::$cron_key);
            }
        }

        return self::$cron_key;
    }

    /**
     * Obtener URL del cron para ejecutar manualmente o desde servidor
     */
    public static function get_cron_url() {
        return add_query_arg(array(
            'wc_scheduler_cron' => '1',
            'key' => self::get_cron_key()
        ), home_url('/'));
    }

    /**
     * Obtener comando WP-CLI para ejecutar el cron
     */
    public static function get_wpcli_command() {
        return 'wp cron event run wc_product_scheduler_check';
    }
}
