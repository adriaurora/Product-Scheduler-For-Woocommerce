<?php
/**
 * Clase para gestionar notificaciones del plugin
 *
 * @package WC_Product_Scheduler
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase WC_Product_Scheduler_Notifications
 */
class WC_Product_Scheduler_Notifications {

    /**
     * Instancia única
     */
    private static $instance = null;

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
        // Hooks para enviar notificaciones
        add_action('wc_product_scheduler_unpublished', array($this, 'send_unpublish_notification'), 10, 2);
        add_action('wc_product_scheduler_republished', array($this, 'send_republish_notification'), 10, 2);
    }

    /**
     * Enviar notificación de despublicación
     */
    public function send_unpublish_notification($product_id, $product) {
        // Obtener email del administrador
        $admin_email = get_option('admin_email');

        // Preparar datos del producto
        $product_name = $product->get_name();
        $product_url = get_edit_post_link($product_id);
        $product_permalink = get_permalink($product_id);
        $timezone = wp_timezone_string();
        $current_time = current_time('d/m/Y H:i');

        // Asunto del email
        $subject = sprintf(
            __('[%s] Producto despublicado automáticamente', 'wc-product-scheduler'),
            get_bloginfo('name')
        );

        // Cuerpo del email
        $message = $this->get_email_template('unpublish', array(
            'product_name'      => $product_name,
            'product_id'        => $product_id,
            'product_url'       => $product_url,
            'product_permalink' => $product_permalink,
            'current_time'      => $current_time,
            'timezone'          => $timezone,
        ));

        // Cabeceras
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>'
        );

        // Enviar email
        wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Enviar notificación de publicación
     */
    public function send_republish_notification($product_id, $product) {
        // Obtener email del administrador
        $admin_email = get_option('admin_email');

        // Preparar datos del producto
        $product_name = $product->get_name();
        $product_url = get_edit_post_link($product_id);
        $product_permalink = get_permalink($product_id);
        $timezone = wp_timezone_string();
        $current_time = current_time('d/m/Y H:i');

        // Asunto del email
        $subject = sprintf(
            __('[%s] Producto publicado automáticamente', 'wc-product-scheduler'),
            get_bloginfo('name')
        );

        // Cuerpo del email
        $message = $this->get_email_template('republish', array(
            'product_name'      => $product_name,
            'product_id'        => $product_id,
            'product_url'       => $product_url,
            'product_permalink' => $product_permalink,
            'current_time'      => $current_time,
            'timezone'          => $timezone,
        ));

        // Cabeceras
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>'
        );

        // Enviar email
        wp_mail($admin_email, $subject, $message, $headers);
    }

    /**
     * Obtener plantilla de email
     */
    private function get_email_template($type, $data) {
        $site_name = get_bloginfo('name');
        $site_url = home_url('/');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($site_name); ?></title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: <?php echo $type === 'unpublish' ? '#d63638' : '#00a32a'; ?>; padding: 30px 40px; text-align: center;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px;">
                                        <?php
                                        if ($type === 'unpublish') {
                                            _e('Producto Despublicado', 'wc-product-scheduler');
                                        } else {
                                            _e('Producto Publicado', 'wc-product-scheduler');
                                        }
                                        ?>
                                    </h1>
                                </td>
                            </tr>

                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px;">
                                    <p style="margin: 0 0 20px 0; color: #333333; font-size: 16px; line-height: 1.5;">
                                        <?php
                                        if ($type === 'unpublish') {
                                            _e('Se ha despublicado automáticamente el siguiente producto según la programación establecida:', 'wc-product-scheduler');
                                        } else {
                                            _e('Se ha publicado automáticamente el siguiente producto según la programación establecida:', 'wc-product-scheduler');
                                        }
                                        ?>
                                    </p>

                                    <!-- Product Info Box -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f9f9f9; border-left: 4px solid <?php echo $type === 'unpublish' ? '#d63638' : '#00a32a'; ?>; margin: 20px 0;">
                                        <tr>
                                            <td style="padding: 20px;">
                                                <p style="margin: 0 0 10px 0; color: #666666; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;">
                                                    <strong><?php _e('Producto', 'wc-product-scheduler'); ?></strong>
                                                </p>
                                                <p style="margin: 0 0 15px 0; color: #333333; font-size: 18px; font-weight: bold;">
                                                    <?php echo esc_html($data['product_name']); ?>
                                                </p>
                                                <p style="margin: 0 0 5px 0; color: #666666; font-size: 14px;">
                                                    <strong><?php _e('ID:', 'wc-product-scheduler'); ?></strong> #<?php echo esc_html($data['product_id']); ?>
                                                </p>
                                                <p style="margin: 0 0 5px 0; color: #666666; font-size: 14px;">
                                                    <strong><?php _e('Fecha y hora:', 'wc-product-scheduler'); ?></strong> <?php echo esc_html($data['current_time']); ?>
                                                </p>
                                                <p style="margin: 0; color: #666666; font-size: 14px;">
                                                    <strong><?php _e('Zona horaria:', 'wc-product-scheduler'); ?></strong> <?php echo esc_html($data['timezone']); ?>
                                                </p>
                                            </td>
                                        </tr>
                                    </table>

                                    <!-- Action Buttons -->
                                    <table width="100%" cellpadding="0" cellspacing="0" style="margin: 30px 0;">
                                        <tr>
                                            <td align="center">
                                                <a href="<?php echo esc_url($data['product_url']); ?>" style="display: inline-block; padding: 12px 30px; background-color: #2271b1; color: #ffffff; text-decoration: none; border-radius: 4px; font-size: 16px; font-weight: bold;">
                                                    <?php _e('Editar Producto', 'wc-product-scheduler'); ?>
                                                </a>
                                            </td>
                                        </tr>
                                    </table>

                                    <?php if ($type === 'unpublish'): ?>
                                    <p style="margin: 20px 0 0 0; padding: 15px; background-color: #fff3cd; border-left: 4px solid #ffc107; color: #856404; font-size: 14px; line-height: 1.5;">
                                        <strong><?php _e('Nota:', 'wc-product-scheduler'); ?></strong>
                                        <?php _e('El producto ha cambiado a estado "Borrador" y ya no es visible para los clientes en tu tienda.', 'wc-product-scheduler'); ?>
                                    </p>
                                    <?php else: ?>
                                    <p style="margin: 20px 0 0 0; padding: 15px; background-color: #d1ecf1; border-left: 4px solid #00a32a; color: #0c5460; font-size: 14px; line-height: 1.5;">
                                        <strong><?php _e('Nota:', 'wc-product-scheduler'); ?></strong>
                                        <?php _e('El producto ha sido publicado y ya está visible para los clientes en tu tienda.', 'wc-product-scheduler'); ?>
                                    </p>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f9f9f9; padding: 30px 40px; text-align: center; border-top: 1px solid #e0e0e0;">
                                    <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px;">
                                        <?php _e('Esta es una notificación automática de', 'wc-product-scheduler'); ?>
                                        <a href="<?php echo esc_url($site_url); ?>" style="color: #2271b1; text-decoration: none;">
                                            <?php echo esc_html($site_name); ?>
                                        </a>
                                    </p>
                                    <p style="margin: 0; color: #999999; font-size: 12px;">
                                        WooCommerce Product Scheduler v<?php echo WC_PRODUCT_SCHEDULER_VERSION; ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}
