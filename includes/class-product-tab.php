<?php
/**
 * Clase para gestionar la pestaña de programación en productos
 *
 * @package WC_Product_Scheduler
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase WC_Product_Scheduler_Tab
 */
class WC_Product_Scheduler_Tab {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Caché de timezone para evitar múltiples llamadas
     */
    private $timezone = null;

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
        // Añadir pestaña a los datos del producto
        add_filter('woocommerce_product_data_tabs', array($this, 'add_product_data_tab'));

        // Añadir contenido de la pestaña
        add_action('woocommerce_product_data_panels', array($this, 'add_product_data_panel'));

        // Guardar los campos personalizados
        add_action('woocommerce_process_product_meta', array($this, 'save_product_scheduler_fields'));

        // Mostrar avisos de error
        add_action('admin_notices', array($this, 'show_admin_notices'));

        // Añadir columna en el listado de productos
        add_filter('manage_edit-product_columns', array($this, 'add_product_column'));
        add_action('manage_product_posts_custom_column', array($this, 'render_product_column'), 10, 2);

        // OPTIMIZACIÓN: Pre-cargar metadatos para el listado de productos
        add_filter('posts_results', array($this, 'preload_scheduler_meta'), 10, 2);
    }

    /**
     * Mostrar avisos administrativos
     */
    public function show_admin_notices() {
        settings_errors('wc_product_scheduler');
    }

    /**
     * Añadir pestaña de programación
     */
    public function add_product_data_tab($tabs) {
        $tabs['scheduler'] = array(
            'label'    => __('Programación', 'wc-product-scheduler'),
            'target'   => 'scheduler_product_data',
            'class'    => array('show_if_simple', 'show_if_variable', 'scheduler_options', 'scheduler_tab'),
            'priority' => 80,
        );
        return $tabs;
    }

    /**
     * Añadir contenido de la pestaña
     */
    public function add_product_data_panel() {
        global $post;

        // Obtener valores guardados
        $unpublish_enabled = get_post_meta($post->ID, '_scheduler_unpublish_enabled', true);
        $unpublish_date = get_post_meta($post->ID, '_scheduler_unpublish_date', true);
        $unpublish_time = get_post_meta($post->ID, '_scheduler_unpublish_time', true);
        $republish_enabled = get_post_meta($post->ID, '_scheduler_republish_enabled', true);
        $republish_date = get_post_meta($post->ID, '_scheduler_republish_date', true);
        $republish_time = get_post_meta($post->ID, '_scheduler_republish_time', true);

        // Convertir fechas de YYYY-MM-DD a DD-MM-YYYY para mostrar
        $unpublish_date = $this->convert_date_to_display_format($unpublish_date);
        $republish_date = $this->convert_date_to_display_format($republish_date);

        // Valores por defecto
        if (empty($unpublish_time)) {
            $unpublish_time = '00:00';
        }
        if (empty($republish_time)) {
            $republish_time = '00:00';
        }

        ?>
        <div id="scheduler_product_data" class="panel woocommerce_options_panel">
            <!-- ZONA HORARIA - MEJORADA -->
            <div class="options_group">
                <div class="scheduler-info">
                    <span class="timezone-label"><?php esc_html_e('Zona horaria:', 'wc-product-scheduler'); ?></span>
                    <span class="timezone-value"><?php echo esc_html(wp_timezone_string()); ?></span>
                    <span class="timezone-note"><?php esc_html_e('Las fechas y horas se procesarán según la zona horaria configurada en WordPress.', 'wc-product-scheduler'); ?></span>
                </div>
            </div>

            <!-- DESPUBLICAR PRODUCTO -->
            <div class="options_group scheduler-section" id="unpublish-section">
                <!-- Header con toggle a la derecha -->
                <div class="scheduler-section-header">
                    <h3><?php esc_html_e('Despublicar producto', 'wc-product-scheduler'); ?></h3>
                    <label class="scheduler-toggle-wrapper">
                        <input type="checkbox"
                               name="_scheduler_unpublish_enabled"
                               id="_scheduler_unpublish_enabled"
                               value="yes"
                               class="scheduler-toggle"
                               <?php checked($unpublish_enabled, 'yes'); ?>>
                        <span class="scheduler-toggle-slider"></span>
                    </label>
                </div>

                <!-- Campos (se muestran cuando el toggle está activo) -->
                <div class="scheduler-fields-wrapper" id="unpublish-fields" <?php echo ($unpublish_enabled !== 'yes') ? 'style="display:none;"' : ''; ?>>
                    <p class="form-field">
                        <label for="_scheduler_unpublish_date"><?php esc_html_e('Fecha de despublicación', 'wc-product-scheduler'); ?></label>
                        <input type="text"
                               class="short scheduler-datepicker"
                               name="_scheduler_unpublish_date"
                               id="_scheduler_unpublish_date"
                               value="<?php echo esc_attr($unpublish_date); ?>"
                               placeholder="DD-MM-YYYY"
                               autocomplete="off">
                    </p>

                    <p class="form-field">
                        <label for="_scheduler_unpublish_time"><?php esc_html_e('Hora de despublicación', 'wc-product-scheduler'); ?></label>
                        <input type="time"
                               class="short"
                               name="_scheduler_unpublish_time"
                               id="_scheduler_unpublish_time"
                               value="<?php echo esc_attr($unpublish_time); ?>">
                        <span class="description"><?php esc_html_e('Formato: HH:MM (24 horas)', 'wc-product-scheduler'); ?></span>
                    </p>
                </div>
            </div>

            <!-- PUBLICAR PRODUCTO -->
            <div class="options_group scheduler-section" id="republish-section">
                <!-- Header con toggle a la derecha -->
                <div class="scheduler-section-header">
                    <h3><?php esc_html_e('Publicar producto', 'wc-product-scheduler'); ?></h3>
                    <label class="scheduler-toggle-wrapper">
                        <input type="checkbox"
                               name="_scheduler_republish_enabled"
                               id="_scheduler_republish_enabled"
                               value="yes"
                               class="scheduler-toggle"
                               <?php checked($republish_enabled, 'yes'); ?>>
                        <span class="scheduler-toggle-slider"></span>
                    </label>
                </div>

                <!-- Campos (se muestran cuando el toggle está activo) -->
                <div class="scheduler-fields-wrapper" id="republish-fields" <?php echo ($republish_enabled !== 'yes') ? 'style="display:none;"' : ''; ?>>
                    <p class="form-field">
                        <label for="_scheduler_republish_date"><?php esc_html_e('Fecha de publicación', 'wc-product-scheduler'); ?></label>
                        <input type="text"
                               class="short scheduler-datepicker"
                               name="_scheduler_republish_date"
                               id="_scheduler_republish_date"
                               value="<?php echo esc_attr($republish_date); ?>"
                               placeholder="DD-MM-YYYY"
                               autocomplete="off">
                    </p>

                    <p class="form-field">
                        <label for="_scheduler_republish_time"><?php esc_html_e('Hora de publicación', 'wc-product-scheduler'); ?></label>
                        <input type="time"
                               class="short"
                               name="_scheduler_republish_time"
                               id="_scheduler_republish_time"
                               value="<?php echo esc_attr($republish_time); ?>">
                        <span class="description"><?php esc_html_e('Formato: HH:MM (24 horas)', 'wc-product-scheduler'); ?></span>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Guardar campos personalizados
     */
    public function save_product_scheduler_fields($post_id) {
        // Verificar nonce de WooCommerce
        if (!isset($_POST['woocommerce_meta_nonce']) ||
            !wp_verify_nonce($_POST['woocommerce_meta_nonce'], 'woocommerce_save_data')) {
            return;
        }

        // Prevenir guardado durante autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Verificar que es un producto
        if (get_post_type($post_id) !== 'product') {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar estado de activación de despublicación
        $unpublish_enabled = isset($_POST['_scheduler_unpublish_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_scheduler_unpublish_enabled', $unpublish_enabled);

        // Solo procesar despublicación si está activada
        if ($unpublish_enabled === 'yes') {
            $unpublish_date = isset($_POST['_scheduler_unpublish_date']) ? sanitize_text_field($_POST['_scheduler_unpublish_date']) : '';
            $unpublish_time = isset($_POST['_scheduler_unpublish_time']) ? sanitize_text_field($_POST['_scheduler_unpublish_time']) : '';

            // Convertir DD-MM-YYYY a YYYY-MM-DD para guardar en BD
            if (!empty($unpublish_date)) {
                $unpublish_date = $this->convert_date_to_db_format($unpublish_date);
            }

            // Validar formato de fecha
            if (!empty($unpublish_date) && !$this->validate_date($unpublish_date)) {
                $unpublish_date = '';
                $unpublish_time = '';
            }

            // Validar que no sea una fecha pasada
            if (!empty($unpublish_date) && !$this->validate_future_date($unpublish_date, $unpublish_time)) {
                $unpublish_date = '';
                $unpublish_time = '';
                // Añadir aviso de error
                add_settings_error(
                    'wc_product_scheduler',
                    'past_unpublish_date',
                    __('La fecha de despublicación no puede estar en el pasado. Se ha eliminado la programación.', 'wc-product-scheduler'),
                    'error'
                );
            }

            update_post_meta($post_id, '_scheduler_unpublish_date', $unpublish_date);
            update_post_meta($post_id, '_scheduler_unpublish_time', $unpublish_time);

            // Actualizar timestamp para facilitar las consultas
            if (!empty($unpublish_date)) {
                $timestamp = $this->create_timestamp($unpublish_date, $unpublish_time);
                if ($timestamp !== false) {
                    update_post_meta($post_id, '_scheduler_unpublish_timestamp', $timestamp);
                    clean_post_cache($post_id);
                } else {
                    delete_post_meta($post_id, '_scheduler_unpublish_timestamp');
                }
            } else {
                delete_post_meta($post_id, '_scheduler_unpublish_timestamp');
            }
        } else {
            // Si está desactivado, limpiar todos los datos de despublicación
            delete_post_meta($post_id, '_scheduler_unpublish_date');
            delete_post_meta($post_id, '_scheduler_unpublish_time');
            delete_post_meta($post_id, '_scheduler_unpublish_timestamp');
        }

        // Guardar estado de activación de republicación
        $republish_enabled = isset($_POST['_scheduler_republish_enabled']) ? 'yes' : 'no';
        update_post_meta($post_id, '_scheduler_republish_enabled', $republish_enabled);

        // Solo procesar republicación si está activada
        if ($republish_enabled === 'yes') {
            $republish_date = isset($_POST['_scheduler_republish_date']) ? sanitize_text_field($_POST['_scheduler_republish_date']) : '';
            $republish_time = isset($_POST['_scheduler_republish_time']) ? sanitize_text_field($_POST['_scheduler_republish_time']) : '';

            // Convertir DD-MM-YYYY a YYYY-MM-DD para guardar en BD
            if (!empty($republish_date)) {
                $republish_date = $this->convert_date_to_db_format($republish_date);
            }

            // Validar formato de fecha
            if (!empty($republish_date) && !$this->validate_date($republish_date)) {
                $republish_date = '';
                $republish_time = '';
            }

            // Validar que no sea una fecha pasada
            if (!empty($republish_date) && !$this->validate_future_date($republish_date, $republish_time)) {
                $republish_date = '';
                $republish_time = '';
                // Añadir aviso de error
                add_settings_error(
                    'wc_product_scheduler',
                    'past_republish_date',
                    __('La fecha de republicación no puede estar en el pasado. Se ha eliminado la programación.', 'wc-product-scheduler'),
                    'error'
                );
            }

            update_post_meta($post_id, '_scheduler_republish_date', $republish_date);
            update_post_meta($post_id, '_scheduler_republish_time', $republish_time);

            // Actualizar timestamp para facilitar las consultas
            if (!empty($republish_date)) {
                $timestamp = $this->create_timestamp($republish_date, $republish_time);
                if ($timestamp !== false) {
                    update_post_meta($post_id, '_scheduler_republish_timestamp', $timestamp);
                    clean_post_cache($post_id);
                } else {
                    delete_post_meta($post_id, '_scheduler_republish_timestamp');
                }
            } else {
                delete_post_meta($post_id, '_scheduler_republish_timestamp');
            }
        } else {
            // Si está desactivado, limpiar todos los datos de republicación
            delete_post_meta($post_id, '_scheduler_republish_date');
            delete_post_meta($post_id, '_scheduler_republish_time');
            delete_post_meta($post_id, '_scheduler_republish_timestamp');
        }
    }

    /**
     * Convertir fecha de DD-MM-YYYY a YYYY-MM-DD
     */
    private function convert_date_to_db_format($date) {
        // Si ya está en formato YYYY-MM-DD, devolverla tal cual
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $date;
        }

        // Si está en formato DD-MM-YYYY, convertir
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1]; // YYYY-MM-DD
        }

        return $date; // Devolver sin cambios si no coincide con ningún formato
    }

    /**
     * Convertir fecha de YYYY-MM-DD a DD-MM-YYYY
     */
    private function convert_date_to_display_format($date) {
        if (empty($date)) {
            return '';
        }

        // Si está en formato YYYY-MM-DD, convertir a DD-MM-YYYY
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
            return $matches[3] . '-' . $matches[2] . '-' . $matches[1]; // DD-MM-YYYY
        }

        return $date; // Devolver sin cambios si ya está en otro formato
    }

    /**
     * Obtener timezone de WordPress (cacheada)
     */
    private function get_timezone() {
        if ($this->timezone === null) {
            $this->timezone = wp_timezone();
        }
        return $this->timezone;
    }

    /**
     * Validar formato de fecha
     */
    private function validate_date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    /**
     * Validar que la fecha y hora sean futuras
     *
     * @param string $date Fecha en formato Y-m-d
     * @param string $time Hora en formato H:i
     * @return bool True si es futura, false si es pasada
     */
    private function validate_future_date($date, $time) {
        try {
            // Obtener zona horaria de WordPress (cacheada)
            $timezone = $this->get_timezone();

            // Crear DateTime para la fecha programada
            $scheduled_datetime = new DateTime($date . ' ' . $time, $timezone);

            // Crear DateTime para ahora
            $now = new DateTime('now', $timezone);

            // Retornar true si la fecha programada es futura
            return $scheduled_datetime > $now;

        } catch (Exception $e) {
            // En caso de error, considerar inválida
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[WC Product Scheduler] Error validando fecha futura: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Crear timestamp con zona horaria de WordPress
     *
     * @param string $date Fecha en formato Y-m-d
     * @param string $time Hora en formato H:i
     * @return int|false Timestamp o false si hay error
     */
    private function create_timestamp($date, $time) {
        try {
            // Obtener zona horaria de WordPress (cacheada)
            $timezone = $this->get_timezone();

            // Crear string de fecha/hora
            $datetime_str = $date . ' ' . $time;

            // Crear objeto DateTime con la zona horaria correcta
            $datetime = new DateTime($datetime_str, $timezone);

            // Retornar timestamp
            return $datetime->getTimestamp();

        } catch (Exception $e) {
            // Log error si WP_DEBUG está activo
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[WC Product Scheduler] Error creando timestamp: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * OPTIMIZACIÓN: Pre-cargar metadatos de scheduler para evitar consultas individuales
     * Reduce de 80-200 consultas a solo 1 en listados de productos
     */
    public function preload_scheduler_meta($posts, $query) {
        // Solo pre-cargar en pantallas de admin de productos
        if (!is_admin() || !$query->is_main_query()) {
            return $posts;
        }

        // Verificar que sea listado de productos
        if (!isset($query->query['post_type']) || $query->query['post_type'] !== 'product') {
            return $posts;
        }

        if (empty($posts) || !is_array($posts)) {
            return $posts;
        }

        // Recopilar IDs de productos
        $post_ids = array();
        foreach ($posts as $post) {
            if (isset($post->ID)) {
                $post_ids[] = $post->ID;
            }
        }

        // Pre-cargar TODOS los metadatos de una vez (1 consulta SQL)
        if (!empty($post_ids)) {
            update_meta_cache('post', $post_ids);
        }

        return $posts;
    }

    /**
     * Añadir columna en el listado de productos
     */
    public function add_product_column($columns) {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Añadir después de la columna de precio
            if ($key === 'price') {
                $new_columns['scheduler'] = __('Programación', 'wc-product-scheduler');
            }
        }

        return $new_columns;
    }

    /**
     * Renderizar columna personalizada
     */
    public function render_product_column($column, $post_id) {
        if ($column === 'scheduler') {
            $unpublish_date = get_post_meta($post_id, '_scheduler_unpublish_date', true);
            $unpublish_time = get_post_meta($post_id, '_scheduler_unpublish_time', true);
            $republish_date = get_post_meta($post_id, '_scheduler_republish_date', true);
            $republish_time = get_post_meta($post_id, '_scheduler_republish_time', true);

            $output = array();

            if (!empty($unpublish_date)) {
                $output[] = '<strong>' . __('Despublicar:', 'wc-product-scheduler') . '</strong><br>'
                          . esc_html($unpublish_date) . ' ' . esc_html($unpublish_time);
            }

            if (!empty($republish_date)) {
                $output[] = '<strong>' . __('Publicar:', 'wc-product-scheduler') . '</strong><br>'
                          . esc_html($republish_date) . ' ' . esc_html($republish_time);
            }

            if (empty($output)) {
                echo '<span style="color: #999;">—</span>';
            } else {
                echo wp_kses_post('<small>' . implode('<br>', $output) . '</small>');
            }
        }
    }
}
