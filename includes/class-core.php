<?php
/**
 * Core class file for Patchwing plugin.
 */

namespace Patchwing;

// Make sure file is not directly accessible
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

/**
 * Class Core
 */
class Core {

	/**
	 * Patchwing Core constructor
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Plugin initiation
	 */
	public function init() {

        add_filter( 'plugin_action_links_' . plugin_basename( PATCHWING_PLUGIN_FILE ), array( $this, 'patchwing_plugins_page_action' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'patchwing_enqueues' ) );

        add_action( 'admin_init', array( $this, 'patchwing_register_settings' ) );

		add_action( 'admin_menu', array( $this, 'patchwing_register_menu' ) );

        // Hook AJAX handler (for class)
        add_action( 'wp_ajax_patchwing_db_table_actions', [ __NAMESPACE__ . '\DB_Tables', 'patchwing_handle_db_table_actions' ] );
        add_action( 'wp_ajax_patchwing_refresh_system_info', [ __NAMESPACE__ . '\Dashboard', 'patchwing_ajax_refresh_system_info' ] );
        add_action( 'wp_ajax_patchwing_get_system_info_report', [ __NAMESPACE__ . '\Dashboard', 'patchwing_ajax_system_info_report' ] );
        add_action( 'wp_ajax_patchwing_export_system_info', [ __NAMESPACE__ . '\Dashboard', 'patchwing_export_system_info' ] );
        add_action( 'wp_ajax_patchwing_performance_analyzer_refresh', [ __NAMESPACE__ . '\Performance_Analyzer', 'patchwing_ajax_refresh_performance' ] );
        add_action( 'wp_ajax_patchwing_performance_analyzer_clear', [ __NAMESPACE__ . '\Performance_Analyzer', 'patchwing_ajax_clear_performance' ] );
        add_action( 'admin_init', [ __NAMESPACE__ . '\Debug_Log', 'patchwing_handle_actions' ] );

	}

    /**
	 * input sanitize
	 */
    public function patchwing_sanitize_settings( $input ) {
        $output = [];
        $output['phpinfo_option'] = in_array( $input['phpinfo_option'], [ 'phpinfo', 'custom' ], true ) ? $input['phpinfo_option'] : 'custom';
        $output['delete_data_option'] = ! empty( $input['delete_data_option'] ) ? true : false;
        return $output;
    }

    /**
	 * Register settings
	 */
    public function patchwing_register_settings() {
        register_setting(
            'patchwing_settings_group',
            'patchwing_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [ $this, 'patchwing_sanitize_settings' ],
                'default' => [
                    'phpinfo_option'     => 'custom',
                    'delete_data_option' => false,
                ],
            ]
        );
    }

    /**
	 * Register menu
	 */
    public function patchwing_register_menu() {
        $svg_path = PATCHWING_PLUGIN_DIRECTORY . 'assets/logo.svg';
        if ( file_exists( $svg_path ) ) {
            $svg_content = file_get_contents( $svg_path );
            $icon_url    = 'data:image/svg+xml;base64,' . base64_encode( $svg_content );
        } else {
            $icon_url = 'dashicons-code-standards'; // fallback icon
        }

        // Main menu
        add_menu_page(
            __( 'Patchwing Dashboard', 'patchwing' ),
            __( 'Patchwing', 'patchwing' ),
            'manage_options',
            'patchwing',
            array( __NAMESPACE__ . '\Dashboard', 'patchwing_render_dashboard_page' ),
            $icon_url,
            75
        );

        // Re-registering the first submenu to match the parent slug
        add_submenu_page(
            'patchwing',
            __( 'Patchwing Dashboard', 'patchwing' ),
            __( 'Dashboard', 'patchwing' ),
            'manage_options',
            'patchwing',
            array( __NAMESPACE__ . '\Dashboard', 'patchwing_render_dashboard_page' )
        );

        // PHP Info
        add_submenu_page(
            'patchwing',
            __( 'PHP Info', 'patchwing' ),
            __( 'PHP Info', 'patchwing' ),
            'manage_options',
            'patchwing-php-info',
            array( __NAMESPACE__ . '\Extended_PHP_Info', 'patchwing_render_phpinfo_page' )
        );

        // Debug Log
        add_submenu_page(
            'patchwing',
            __( 'Debug Log', 'patchwing' ),
            __( 'Debug Log', 'patchwing' ),
            'manage_options',
            'patchwing-debug-log',
            array( __NAMESPACE__ . '\Debug_Log', 'patchwing_render_log_page' )
        );

        // Database Tables
        add_submenu_page(
            'patchwing',
            __( 'Database', 'patchwing' ),
            __( 'Database', 'patchwing' ),
            'manage_options',
            'patchwing-database',
            array( __NAMESPACE__ . '\DB_Tables', 'patchwing_render_db_page' )
        );

        // Performance Analyzer
        add_submenu_page(
            'patchwing',
            __( 'Performance Analyzer', 'patchwing' ),
            __( 'Performance', 'patchwing' ),
            'manage_options',
            'patchwing-performance',
            array( __NAMESPACE__ . '\Performance_Analyzer', 'patchwing_render_performance_analyzer_page' )
        );

        // Settings
        add_submenu_page(
            'patchwing',
            __( 'Settings Page', 'patchwing' ),
            __( 'Settings', 'patchwing' ),
            'manage_options',
            'patchwing-settings',
            array( $this, 'patchwing_render_settings_page' )
        );
    }

	/**
	 * Enqueue assets.
	 */
    public function patchwing_enqueues() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only asset enqueue decision; no state change occurs.
        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
        
        $patchwing_pages = [
            'patchwing',
            'patchwing-php-info',
            'patchwing-performance',
            'patchwing-debug-log',
            'patchwing-database',
            'patchwing-settings'
        ];

        // Only run logic if we are actually on one of our plugin pages
        if ( in_array( $current_page, $patchwing_pages, true ) ) {
            // Enqueue common CSS
            wp_enqueue_style(
                'patchwing-style',
                PATCHWING_PLUGIN_URL . 'assets/css/style.css',
                array(),
                PATCHWING_PLUGIN_VERSION
            );

            // Performance analyzer specific scripts
            if ( 'patchwing-performance' === $current_page ) {
                wp_enqueue_script(
                    'chartjs',
                    PATCHWING_PLUGIN_URL . 'assets/js/chart.min.js', 
                    array(),
                    '4.5.1',
                    true
                );

                wp_enqueue_script(
                    'patchwing-performance-analyzer-js',
                    PATCHWING_PLUGIN_URL . 'assets/js/performance-analyzer.js',
                    array( 'jquery', 'chartjs' ),
                    PATCHWING_PLUGIN_VERSION,
                    true
                );

                wp_localize_script( 'patchwing-performance-analyzer-js', 'patchwingPerformanceAnalyzer', [
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'patchwing_performance_analyzer_nonce' )
                ]);
            }

            // Database tables specific scripts
            if ( 'patchwing-database' === $current_page ) {
                wp_enqueue_script(
                    'patchwing-database-tables-js',
                    PATCHWING_PLUGIN_URL . 'assets/js/database-tables.js',
                    array( 'jquery' ),
                    PATCHWING_PLUGIN_VERSION,
                    true
                );
            }

            // Dashboard specific scripts
            if ( 'patchwing' === $current_page ) {
                wp_enqueue_script(
                    'patchwing-dashboard-js',
                    PATCHWING_PLUGIN_URL . 'assets/js/dashboard.js',
                    array( 'jquery' ),
                    PATCHWING_PLUGIN_VERSION,
                    true
                );
            }
        }

        // Admin Bar styling (Only if WP_DEBUG is on)
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_admin_bar_showing() ) {
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Inline style block with no external file; version parameter is intentionally false.
            // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
            wp_register_style( 'patchwing-adminbar', false, [], false );
            wp_enqueue_style( 'patchwing-adminbar' );

            $custom_css = "
                #wpadminbar {
                    backdrop-filter: blur(6px);
                    border-bottom: 1px solid rgba(255, 255, 255, 0.25);
                    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    background-image: repeating-linear-gradient(
                        135deg,
                        rgba(255, 255, 255, 0.08),
                        rgba(255, 255, 255, 0.08) 12px,
                        transparent 12px,
                        transparent 24px
                    );
                }
            ";
            wp_add_inline_style( 'patchwing-adminbar', $custom_css );
        }
    }

    /**
	 * Settings page
	 */
    public function patchwing_render_settings_page() {
        $options = get_option( 'patchwing_settings', [
            'phpinfo_option'     => 'custom',
            'delete_data_option' => false,
        ] );
        ?>
        <div class="wrap">
            <div class="page-title">
                <h1><?php esc_html_e( 'Settings', 'patchwing' ); ?></h1>
            </div>
            <?php
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified by WordPress core via options.php for settings forms; this is a read-only redirect flag set by WP itself.
            $settings_updated = isset( $_GET['settings-updated'] ) ? sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) : '';
            if ( $settings_updated ) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e( 'Settings saved successfully.', 'patchwing' ); ?></p>
            </div>
            <?php endif; ?>
            <form method="post" action="options.php">
                <?php
                settings_fields( 'patchwing_settings_group' );
                do_settings_sections( 'patchwing_settings_group' );
                ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'PHP info display method', 'patchwing' ); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="patchwing_settings[phpinfo_option]" value="phpinfo"
                                        <?php checked( $options['phpinfo_option'], 'phpinfo' ); ?>
                                        <?php disabled( ! function_exists( 'phpinfo' ) ); ?> />
                                    <?php esc_html_e( 'Default', 'patchwing' ); ?>
                                    <?php if ( ! function_exists( 'phpinfo' ) ) : ?>
                                        <span class="description"><?php esc_html_e( '(Not available on this server)', 'patchwing' ); ?></span>
                                    <?php endif; ?>
                                </label>
                                <br/>
                                <label>
                                    <input type="radio" name="patchwing_settings[phpinfo_option]" value="custom"
                                        <?php checked( $options['phpinfo_option'], 'custom' ); ?> />
                                    <?php esc_html_e( 'Custom', 'patchwing' ); ?>
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Data deletion', 'patchwing' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="patchwing_settings[delete_data_option]" value="1"
                                    <?php checked( $options['delete_data_option'], true ); ?> />
                                <?php esc_html_e( 'Yes, delete all associated data when plugin is deleted.', 'patchwing' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

	/**
	 * Add a quick access link to plugin page
	 */
	public function patchwing_plugins_page_action( $actions ) {
		$page_link = sprintf(
            '<a href="%s">%s</a>',
			admin_url( 'admin.php?page=patchwing' ),
			_x( 'Dashboard', 'Menu, Section and Page Title', 'patchwing' )
		);
		array_unshift( $actions, $page_link );
		return $actions;
	}

}