<?php
/**
 * Extended PHP Info class file for Patchwing plugin.
 */

namespace Patchwing;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Extended_PHP_Info {

    /**
     * Render PHP Info page
     */
    public static function patchwing_render_phpinfo_page() {
        // Security: Ensure only authorized users see this information
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'patchwing' ) );
        }

        // Get plugin settings
        $options = get_option( 'patchwing_settings', [
            'phpinfo_option'     => 'custom',
            'delete_data_option' => false,
        ] );

        $choice = isset( $options['phpinfo_option'] ) ? $options['phpinfo_option'] : 'custom';

        ?>
        <div class="wrap">
            <div class="page-title">
                <h1><?php esc_html_e( 'PHP Information', 'patchwing' ); ?></h1>
            </div>

            <?php if ( 'phpinfo' === $choice ) : ?>
                <?php if ( function_exists( 'phpinfo' ) ) : ?>
                    <div class="phpinfo-output">
                        <?php
                        // Capture phpinfo output
                        ob_start();
                        // phpcs:ignore WordPress.PHP.DevelopmentFunctions.prevent_path_disclosure_phpinfo -- Intentional: this page is a PHP Info viewer tool for administrators only.
                        phpinfo();
                        $phpinfo_raw = ob_get_clean();

                        // Extract the body and styles
                        preg_match_all( '/<body[^>]*>(.*)<\/body>/siU', $phpinfo_raw, $phpinfo_matches );
                        preg_match_all( '/<style[^>]*>(.*)<\/style>/siU', $phpinfo_raw, $style_matches );

                        // Clean up styles to prevent breaking wp-admin layout
                        if ( isset( $style_matches[1][0] ) ) {
                            $remove_patterns = array(
                                "/a:.+?\n/si",
                                "/body.+?\n/si",
                                "/.+?{.+?max-width:.+?}/i", // Prevents width issues in WP admin
                            );

                            // Sanitize the CSS output
                            $styles = preg_replace( $remove_patterns, '', $style_matches[1][0] );
        
                            // Enqueue inline styles via WordPress API instead of direct echo
                            wp_register_style( 'patchwing-phpinfo-inline', false, array(), PATCHWING_PLUGIN_VERSION );
                            wp_enqueue_style( 'patchwing-phpinfo-inline' );
                            wp_add_inline_style( 'patchwing-phpinfo-inline', wp_strip_all_tags( $styles ) );
                        }

                        // Output the body content with basic sanitization
                        if ( isset( $phpinfo_matches[1][0] ) ) {
                            $phpinfo_matches[1][0] = preg_replace( '/<img[^>]*>/i', '', $phpinfo_matches[1][0] );
                            echo wp_kses_post( $phpinfo_matches[1][0] );
                        }
                        ?>
                    </div>
                <?php else : ?>
                    <div class="notice notice-error">
                        <p><?php esc_html_e( 'The phpinfo() function is disabled on this server. Please use the custom info view instead.', 'patchwing' ); ?></p>
                    </div>
                <?php endif; ?>
                <?php else : ?>
                    <div class="php-dashboard">
                        <h2><?php esc_html_e( 'Basic Information', 'patchwing' ); ?></h2>
                        <table class="widefat striped">
                            <tbody>
                                <tr>
                                    <th><?php esc_html_e( 'PHP Version', 'patchwing' ); ?></th>
                                    <td><?php echo esc_html( phpversion() ); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Zend Version', 'patchwing' ); ?></th>
                                    <td><?php echo esc_html( zend_version() ); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Operating System', 'patchwing' ); ?></th>
                                    <td><?php echo esc_html( PHP_OS ); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Server API', 'patchwing' ); ?></th>
                                    <td><?php echo esc_html( php_sapi_name() ); ?></td>
                                </tr>
                                <tr>
                                    <th><?php esc_html_e( 'Loaded php.ini', 'patchwing' ); ?></th>
                                    <td><?php echo esc_html( php_ini_loaded_file() ?: __( 'None', 'patchwing' ) ); ?></td>
                                </tr>
                            </tbody>
                        </table>

                        <h2><?php esc_html_e( 'Key Configuration Settings', 'patchwing' ); ?></h2>
                        <table class="widefat striped">
                            <tbody>
                                <?php
                                $settings = [
                                    'memory_limit',
                                    'upload_max_filesize',
                                    'post_max_size',
                                    'max_execution_time',
                                    'max_input_time',
                                    'default_socket_timeout',
                                    'display_errors',
                                    'log_errors',
                                    'error_log',
                                    'date.timezone',
                                    'session.save_path',
                                    'allow_url_fopen'
                                ];
                                foreach ( $settings as $s ) :
                                    $val = ini_get( $s );
                                    if ( in_array( $s, [ 'display_errors', 'log_errors', 'allow_url_fopen' ], true ) ) {
                                        $val = $val ? __( 'On', 'patchwing' ) : __( 'Off', 'patchwing' );
                                    }
                                    ?>
                                    <tr>
                                        <th><?php echo esc_html( $s ); ?></th>
                                        <td><?php echo esc_html( $val ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <h2><?php esc_html_e( 'Loaded Extensions', 'patchwing' ); ?></h2>
                        <table class="widefat striped">
                            <tbody>
                                <?php
                                $exts = get_loaded_extensions();
                                sort( $exts );
                                foreach ( $exts as $ext ) :
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html( $ext ); ?></td>
                                        <td><?php esc_html_e( 'Enabled', 'patchwing' ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
        </div>
        <?php
    }

}