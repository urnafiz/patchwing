<?php
/**
 * Performance_Analyzer class file for Patchwing plugin.
 */

namespace Patchwing;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Performance_Analyzer {

    public static function init() {
        self::patchwing_create_table();
    }

    public static function patchwing_create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'patchwing_performance';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            timestamp DATETIME NOT NULL,
            baseline_time FLOAT NOT NULL,
            peak_memory FLOAT NOT NULL,
            theme_time FLOAT NOT NULL
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function patchwing_render_performance_analyzer_page() {
        // Security: Always verify user permissions before rendering the page
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'patchwing' ) );
        }

        ?>
        <div class="wrap">
            <div class="page-title">
                <h1><?php echo esc_html__( 'Performance Analyzer', 'patchwing' ); ?></h1>
            </div>
            
            <button id="pa-refresh" class="pa-btn pa-refresh">
                <?php echo esc_html__( 'Refresh', 'patchwing' ); ?>
            </button>

            <button id="pa-clear" class="pa-btn pa-clear">
                <?php echo esc_html__( 'Clear Data', 'patchwing' ); ?>
            </button>

            <div id="pa-results"></div>
            <canvas id="trendChart" height="120"></canvas>
        </div>
        <?php
    }

    public static function patchwing_ajax_refresh_performance() {
        // Verify the nonce for CSRF protection
        check_ajax_referer('patchwing_performance_analyzer_nonce', 'nonce');

        // Check for sufficient user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'patchwing')], 403);
        }

        global $wpdb;

        // Baseline
        $baseline_start = microtime(true);
        wp_remote_get(home_url());
        $baseline_time = microtime(true) - $baseline_start;

        // Peak memory
        $peak_memory = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $memory_unit = 'MB';
        if ($peak_memory > 1024) {
            $peak_memory = $peak_memory / 1024;
            $memory_unit = 'GB';
        }

        // Theme
        $theme = wp_get_theme();
        $theme_start = microtime(true);
        wp_remote_get(home_url());
        $theme_time = microtime(true) - $theme_start;

        // Plugins
        $active_plugins = get_option('active_plugins');
        $plugin_names = [];
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_names[] = $plugin_data['Name'];
        }

        $table = $wpdb->prefix . 'patchwing_performance';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Inserting a new performance snapshot; caching is not applicable for write operations.
        $wpdb->insert($table, [
            'timestamp'     => current_time( 'mysql' ),
            'baseline_time' => round( $baseline_time, 3 ),
            'peak_memory'   => round( $peak_memory, 2 ),
            'theme_time'    => round( $theme_time, 3 ),
            ],
            [ '%s', '%f', '%f', '%f' ] // format specifiers
        );


        // Fetch all data
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is a hardcoded plugin-owned table ($wpdb->prefix . 'patchwing_performance'); no user input involved.
        $trends = $wpdb->get_results( "SELECT * FROM " . esc_sql( $table ) . " ORDER BY id ASC" );

        // Format timestamps using WP settings
        foreach ($trends as &$trend) {
            $trend->timestamp = date_i18n(
                get_option('date_format') . ' ' . get_option('time_format'),
                strtotime($trend->timestamp)
            );
        }

        wp_send_json([
            'baseline_time' => round($baseline_time, 3),
            'peak_memory'   => round($peak_memory, 2) . ' ' . $memory_unit,
            'theme_name'    => $theme->get('Name'),
            'theme_time'    => round($theme_time, 3),
            'plugins'       => $plugin_names,
            'trends'        => $trends
        ]);
    }

    public static function patchwing_ajax_clear_performance() {
        // Verify the nonce for CSRF protection
        check_ajax_referer('patchwing_performance_analyzer_nonce', 'nonce');

        // Check for sufficient user permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('You do not have permission to perform this action.', 'patchwing')], 403);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'patchwing_performance';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is a hardcoded plugin-owned table ($wpdb->prefix . 'patchwing_performance'); no user input involved. Caching is inapplicable for a DELETE operation.
        $result = $wpdb->query( "DELETE FROM " . esc_sql( $table_name ) );

        // Handle potential DB errors and provide a proper JSON response
        if (false === $result) {
            wp_send_json_error(['success' => false]);
        }

        wp_send_json_success(['success' => true]);
    }
    
}

Performance_Analyzer::init();