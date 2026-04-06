<?php
/**
 * Dashboard class file for Patchwing plugin.
 */

namespace Patchwing;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Dashboard {

    /**
     * Dashboard page output
     */
    public static function patchwing_render_dashboard_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'patchwing' ) );
        }

        $nonce    = wp_create_nonce( 'patchwing_system_info_nonce' );
        $features = self::patchwing_get_dashboard_features(); 
        ?>
        <div class="wrap patchwing-dashboard">
            <div class="page-title">
                <h1><?php esc_html_e( 'Dashboard', 'patchwing' ); ?></h1>
            </div>

            <div id="system-info-container" data-nonce="<?php echo esc_attr( $nonce ); ?>">
                <?php self::patchwing_render_system_info(); ?>
            </div>

            <hr class="wp-header-end">

            <div class="system-info-actions">
                <button type="button" class="button button-primary" id="refresh-system-info">
                    <?php esc_html_e( 'Refresh', 'patchwing' ); ?>
                </button>
                <button type="button" class="button" id="copy-system-info">
                    <?php esc_html_e( 'Copy Report', 'patchwing' ); ?>
                </button>
                <a class="button" href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=patchwing_export_system_info&type=txt&_wpnonce=' . $nonce ) ); ?>">
                    <?php esc_html_e( 'Export TXT', 'patchwing' ); ?>
                </a>
            </div>

            <hr class="wp-header-end">

            <div class="feature-grid">
                <?php foreach ( $features as $feature ) : ?>
                    <div class="feature-card">
                        <div class="card-body">
                            <span class="dashicons <?php echo esc_attr( $feature['icon'] ); ?>" 
                                  style="color: <?php echo esc_attr( $feature['color'] ); ?>;" 
                                  aria-hidden="true"></span>
                            <h3 class="card-title"><?php echo esc_html( $feature['title'] ); ?></h3>
                            <p class="card-description"><?php echo esc_html( $feature['desc'] ); ?></p>
                            <a href="<?php echo esc_url( $feature['link'] ); ?>" class="btn button-primary btn-go">
                                <?php esc_html_e( 'Go Options', 'patchwing' ); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Returns the configuration for the dashboard feature cards.
     */
    public static function patchwing_get_dashboard_features() {
        return [
            'php_info' => [
                'title' => __( 'PHP Info', 'patchwing' ),
                'desc'  => __( 'Provides detailed information about the server’s PHP setup and configuration. A diagnostic tool to quickly see your site’s environment.', 'patchwing' ),
                'link'  => admin_url( 'admin.php?page=patchwing-php-info' ),
                'icon'  => 'dashicons-editor-code',
                'color' => '#2271b1',
            ],
            'debug_log' => [
                'title' => __( 'Debug Log', 'patchwing' ),
                'desc'  => __( 'Monitor, filter and manage PHP errors and system events. Designed to help developers keep the site healthy and error free.', 'patchwing' ),
                'link'  => admin_url( 'admin.php?page=patchwing-debug-log' ),
                'icon'  => 'dashicons-visibility',
                'color' => '#d63638',
            ],
            'database' => [
                'title' => __( 'Database', 'patchwing' ),
                'desc'  => __( 'Audit your database storage engines and migrate tables to InnoDB for better performance and reliability.', 'patchwing' ),
                'link'  => admin_url( 'admin.php?page=patchwing-database' ),
                'icon'  => 'dashicons-database',
                'color' => '#996214',
            ],
            'performance' => [
                'title' => __( 'Performance', 'patchwing' ),
                'desc'  => __( 'High level overview of resource consumption. See how core, themes and plugins are affecting your site’s speed.', 'patchwing' ),
                'link'  => admin_url( 'admin.php?page=patchwing-performance' ),
                'icon'  => 'dashicons-performance',
                'color' => '#46b450',
            ],
        ];
    }

    /**
     * Render system info grid
     */
    private static function patchwing_render_system_info() {
        global $wpdb;

        // Data Gathering
        $wp_version    = get_bloginfo( 'version' );
        $php_version   = PHP_VERSION;
        $mysql_version = $wpdb->db_version();
        $jquery_ver    = wp_scripts()->registered['jquery']->ver ?? 'N/A';
        $curl_version  = function_exists( 'curl_version' ) ? curl_version()['version'] : __( 'Not Installed', 'patchwing' );
        $gd_version    = function_exists( 'gd_info' ) ? gd_info()['GD Version'] : __( 'Not Installed', 'patchwing' );

        $server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : (isset($_SERVER['LOCAL_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['LOCAL_ADDR'])) : 'N/A');
        $server_sw = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'N/A';


        // CPU Metrics
        $cpu_cores = self::patchwing_getRealCores();
        if ($cpu_cores > 0) {
            $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
            $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
            // echo "Cores Detected: $cores\n";
        } else {
            $cpu_cores = 2; // fallback assumption
            $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
            $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
            // echo "Cores could not be detected. Assuming 2 core.\n";
        }
        $cpu_status = $cpu_pct === null ? 'moderate' : ( $cpu_pct < 50 ? 'excellent' : ( $cpu_pct < 80 ? 'moderate' : 'high' ) );

        // Memory Metrics
        $memory_usage       = memory_get_usage( true );
        $memory_limit       = ini_get( 'memory_limit' );
        $memory_limit_bytes = wp_convert_hr_to_bytes( $memory_limit );
        $memory_pct         = $memory_limit_bytes > 0 ? round( ( $memory_usage / $memory_limit_bytes ) * 100, 2 ) : null;
        $mem_status         = $memory_pct === null ? 'moderate' : ( $memory_pct < 60 ? 'excellent' : ( $memory_pct < 80 ? 'moderate' : 'high' ) );

        // Plugin & Theme Data
        $active_plugins   = count( get_option( 'active_plugins', [] ) );
        $mu_plugins       = count( get_mu_plugins() );
        $total_plugins    = count( get_plugins() );
        $inactive_plugins = max( 0, $total_plugins - $active_plugins );

        $theme        = wp_get_theme();
        $timezone     = wp_timezone_string();
        $wp_mem_limit = defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : __( 'Not defined', 'patchwing' );
        $opcache      = function_exists( 'opcache_get_status' ) ? __( 'Enabled', 'patchwing' ) : __( 'Disabled', 'patchwing' );
        ?>

        <div class="system-info-grid">
            <div class="card">
                <h2><?php esc_html_e( 'Versions', 'patchwing' ); ?></h2>
                <p><strong><?php esc_html_e( 'WordPress:', 'patchwing' ); ?></strong> <?php echo esc_html( $wp_version ); ?></p>
                <p><strong><?php esc_html_e( 'PHP:', 'patchwing' ); ?></strong> <?php echo esc_html( $php_version ); ?></p>
                <p><strong><?php esc_html_e( 'MySQL:', 'patchwing' ); ?></strong> <?php echo esc_html( $mysql_version ); ?></p>
                <p><strong><?php esc_html_e( 'jQuery:', 'patchwing' ); ?></strong> <?php echo esc_html( $jquery_ver ); ?></p>
                <p><strong><?php esc_html_e( 'cURL:', 'patchwing' ); ?></strong> <?php echo esc_html( $curl_version ); ?></p>
                <p><strong><?php esc_html_e( 'GD Library:', 'patchwing' ); ?></strong> <?php echo esc_html( $gd_version ); ?></p>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'Server Metrics', 'patchwing' ); ?></h2>
                <p><strong><?php esc_html_e( 'IP Address:', 'patchwing' ); ?></strong> <?php echo esc_html( $server_ip ); ?></p>
                <p><strong><?php esc_html_e( 'Web Server:', 'patchwing' ); ?></strong> <?php echo esc_html( $server_sw ); ?></p>
                <p><strong><?php esc_html_e( 'CPU Load:', 'patchwing' ); ?></strong>
                <?php
                if ( $cpu_load !== null ) {
                    /* translators: 1: load average, 2: number of cores, 3: percentage */
                    echo esc_html( sprintf( __( '%1$s (%2$d cores, %3$s%%)', 'patchwing' ), round($cpu_load,2), $cpu_cores, $cpu_pct ) ); 
                } else {
                    esc_html_e( 'Not Available', 'patchwing' );
                }
                ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'CPU Load Status:', 'patchwing' ); ?></strong> 
                    <span class="badge <?php echo esc_attr( $cpu_status ); ?>">
                        <?php echo esc_html( ucfirst( $cpu_status ) ); ?>
                    </span>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Memory Usage:', 'patchwing' ); ?></strong> 
                    <?php
                    // translators: %s is the percentage of the PHP memory limit used.
                    echo $memory_pct !== null ? esc_html( sprintf( __( '%s%% of PHP limit', 'patchwing' ), $memory_pct ) ) : esc_html__( 'Not Available', 'patchwing' );
                    ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Memory Usage Status:', 'patchwing' ); ?></strong> 
                    <span class="badge <?php echo esc_attr( $mem_status ); ?>">
                        <?php echo esc_html( ucfirst( $mem_status ) ); ?>
                    </span>
                </p>
            </div>

            <div class="card">
                <h2><?php esc_html_e( 'Environment', 'patchwing' ); ?></h2>
                <p>
                    <strong><?php esc_html_e( 'Multisite:', 'patchwing' ); ?></strong> <?php echo is_multisite() ? esc_html__( 'Yes', 'patchwing' ) : esc_html__( 'No', 'patchwing' ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Active Theme:', 'patchwing' ); ?></strong> <?php echo esc_html( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Active Plugins:', 'patchwing' ); ?></strong> <?php echo esc_html( $active_plugins ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Must-Use Plugins:', 'patchwing' ); ?></strong> <?php echo esc_html( $mu_plugins ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Inactive Plugins:', 'patchwing' ); ?></strong> <?php echo esc_html( $inactive_plugins ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'Timezone (WP):', 'patchwing' ); ?></strong> <?php echo esc_html( $timezone ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'WP Memory Limit:', 'patchwing' ); ?></strong> <?php echo esc_html( $wp_mem_limit ); ?>
                </p>
                <p>
                    <strong><?php esc_html_e( 'OPcache:', 'patchwing' ); ?></strong> <?php echo esc_html( $opcache ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Refreshes system info via AJAX.
     */
    public static function patchwing_ajax_refresh_system_info() {
        // Verify the nonce (Security: prevents CSRF)
        check_ajax_referer( 'patchwing_system_info_nonce', '_wpnonce' );

        // Verify user capabilities (Security: Authorization)
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized access.', 'patchwing' ), 403 );
        }

        // Render the content
        self::patchwing_render_system_info();

        wp_die();
    }



    /**
     * Exports system information as a TXT file.
     */
    public static function patchwing_export_system_info() {
        // Security Check: Verify Nonce (Prevents CSRF)
        check_admin_referer( 'patchwing_system_info_nonce', '_wpnonce' );

        // Authorization Check: Verify Permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'patchwing' ) );
        }

        global $wpdb;

        // --- Data Gathering (Following same logic as render function) ---
        $versions = [
            __( 'WordPress', 'patchwing' )  => get_bloginfo( 'version' ),
            __( 'PHP', 'patchwing' )        => PHP_VERSION,
            __( 'MySQL', 'patchwing' )      => $wpdb->db_version(),
            __( 'jQuery', 'patchwing' )     => wp_scripts()->registered['jquery']->ver ?? 'N/A',
            __( 'cURL', 'patchwing' )       => function_exists( 'curl_version' ) ? curl_version()['version'] : 'Not Installed',
            __( 'GD Library', 'patchwing' ) => function_exists( 'gd_info' ) ? gd_info()['GD Version'] : 'Not Installed',
        ];

        // CPU Metrics
        $cpu_cores = self::patchwing_getRealCores();
        if ($cpu_cores > 0) {
            $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
            $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
            // echo "Cores Detected: $cores\n";
        } else {
            $cpu_cores = 2; // fallback assumption
            $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
            $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
            // echo "Cores could not be detected. Assuming 2 core.\n";
        }
        $cpu_status = $cpu_pct === null ? 'moderate' : ( $cpu_pct < 50 ? 'excellent' : ( $cpu_pct < 80 ? 'moderate' : 'high' ) );

        $memory_usage   = memory_get_usage( true );
        $php_mem_limit  = ini_get( 'memory_limit' );
        $php_mem_bytes  = wp_convert_hr_to_bytes( $php_mem_limit );

        $memory_pct = $php_mem_bytes > 0 ? round( ( $memory_usage / $php_mem_bytes ) * 100, 2 ) : 'N/A';

        $mem_status = is_numeric( $memory_pct ) ? ( $memory_pct < 60 ? 'Excellent' : ( $memory_pct < 80 ? 'Moderate' : 'High' ) ) : 'Unknown';

        $server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : (isset($_SERVER['LOCAL_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['LOCAL_ADDR'])) : 'N/A');
        $server_sw = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'N/A';

        $server_metrics = [
            __( 'IP Address', 'patchwing' )    => $server_ip,
            __( 'Web Server', 'patchwing' )   => $server_sw,
            __( 'CPU Load', 'patchwing' )     => $cpu_load !== null ? sprintf( '%s (%d cores, %s%%)', round($cpu_load,2), $cpu_cores, $cpu_pct ) : 'N/A',
            __( 'CPU Load Status', 'patchwing' )   => ucfirst( $cpu_status ),
            __( 'Memory Usage', 'patchwing' ) => $memory_pct !== null ? $memory_pct . '%' : 'Not Available',
            __( 'Memory Usage Status', 'patchwing' ) => ucfirst( $mem_status ),
        ];

        $theme = wp_get_theme();
        $active_plugins   = count( get_option( 'active_plugins', [] ) );
        $mu_plugins       = count( get_mu_plugins() );
        $total_plugins    = count( get_plugins() );
        $environment = [
            __( 'Multisite', 'patchwing' )        => is_multisite() ? 'Yes' : 'No',
            __( 'Active Theme', 'patchwing' )     => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
            __( 'Active Plugins', 'patchwing' )   => $active_plugins,
            __( 'Must Use Plugins', 'patchwing' ) => $mu_plugins,
            __( 'Inactive Plugins', 'patchwing' )   => max( 0, $total_plugins - $active_plugins ),
            __( 'Timezone (WP)', 'patchwing' ) => wp_timezone_string(),
            __( 'WP Memory Limit', 'patchwing' )  => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'Not Defined',
            __( 'OPcache', 'patchwing' )  => function_exists( 'opcache_get_status' ) ? 'Enabled' : 'Disabled',
        ];

        // =========================
        // TXT EXPORT (WP Way)
        // =========================
    
        // Clear buffer to prevent any extraneous output from leaking into the file
        if ( ob_get_level() ) {
            ob_end_clean();
        }

        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=system-info-' . gmdate( 'Y-m-d' ) . '.txt' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );

        echo "==== " . esc_html__( 'Versions', 'patchwing' ) . " ====\n";
        foreach ( $versions as $k => $v ) {
            echo esc_html( $k ) . ': ' . esc_html( $v ) . "\n";
        }

        echo "\n==== " . esc_html__( 'Server Metrics', 'patchwing' ) . " ====\n";
        foreach ( $server_metrics as $k => $v ) {
            echo esc_html( $k ) . ': ' . esc_html( $v ) . "\n";
        }

        echo "\n==== " . esc_html__( 'Environment', 'patchwing' ) . " ====\n";
        foreach ( $environment as $k => $v ) {
            echo esc_html( $k ) . ': ' . esc_html( $v ) . "\n";
        }

        exit;
    }

    /**
     * AJAX system info report
     */
    public static function patchwing_ajax_system_info_report() {
        // Security: Verify Nonce (Prevents CSRF)
        check_ajax_referer( 'patchwing_system_info_nonce', '_wpnonce' );

        // Security: Verify User Capability (Authorization)
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized access.', 'patchwing' ), 403 );
        }

        global $wpdb;

        // Prevent "leaking" output from other plugins
        if ( ob_get_length() ) {
            ob_clean();
        }

        // --- Data Gathering (Localized) ---
        $versions = [
            __( 'WordPress', 'patchwing' )  => get_bloginfo( 'version' ),
            __( 'PHP', 'patchwing' )        => PHP_VERSION,
            __( 'MySQL', 'patchwing' )      => $wpdb->db_version(),
            __( 'jQuery', 'patchwing' )     => wp_scripts()->registered['jquery']->ver ?? 'N/A',
            __( 'cURL', 'patchwing' )       => function_exists( 'curl_version' ) ? curl_version()['version'] : __( 'Not Installed', 'patchwing' ),
            __( 'GD Library', 'patchwing' ) => function_exists( 'gd_info' ) ? gd_info()['GD Version'] : __( 'Not Installed', 'patchwing' ),
        ];

        // CPU Metrics
        $cpu_cores = self::patchwing_getRealCores();
        if ($cpu_cores > 0) {
            $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
            $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
            // echo "Cores Detected: $cores\n";
        } else {
            $cpu_cores = 2; // fallback assumption
            $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
            $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
            // echo "Cores could not be detected. Assuming 2 core.\n";
        }
        $cpu_status = $cpu_pct === null ? 'moderate' : ( $cpu_pct < 50 ? 'excellent' : ( $cpu_pct < 80 ? 'moderate' : 'high' ) );

        $memory_usage   = memory_get_usage( true );
        $php_mem_limit  = ini_get( 'memory_limit' );
        $php_mem_bytes  = wp_convert_hr_to_bytes( $php_mem_limit );
        $memory_pct     = $php_mem_bytes > 0 ? round( ( $memory_usage / $php_mem_bytes ) * 100, 2 ) : 'N/A';
        $mem_status     = is_numeric( $memory_pct ) ? ( $memory_pct < 60 ? 'Excellent' : ( $memory_pct < 80 ? 'Moderate' : 'High' ) ) : 'Unknown';

        $server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : (isset($_SERVER['LOCAL_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['LOCAL_ADDR'])) : 'N/A');
        $server_sw = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'N/A';

        $server_metrics = [
            __( 'IP Address', 'patchwing' )    => $server_ip,
            __( 'Web Server', 'patchwing' )   => $server_sw,
            __( 'CPU Load', 'patchwing' )     => $cpu_load !== null ? sprintf( '%s (%d cores, %s%%)', round($cpu_load,2), $cpu_cores, $cpu_pct ) : 'N/A',
            __( 'CPU Load Status', 'patchwing' )   => ucfirst( $cpu_status ),
            __( 'Memory Usage', 'patchwing' ) => $memory_pct !== null ? $memory_pct . '%' : 'Not Available',
            __( 'Memory Usage Status', 'patchwing' ) => ucfirst( $mem_status ),
        ];

        $theme = wp_get_theme();
        $active_plugins   = count( get_option( 'active_plugins', [] ) );
        $mu_plugins       = count( get_mu_plugins() );
        $total_plugins    = count( get_plugins() );
        $environment = [
            __( 'Multisite', 'patchwing' )        => is_multisite() ? 'Yes' : 'No',
            __( 'Active Theme', 'patchwing' )     => $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ),
            __( 'Active Plugins', 'patchwing' )   => $active_plugins,
            __( 'Must Use Plugins', 'patchwing' ) => $mu_plugins,
            __( 'Inactive Plugins', 'patchwing' )   => max( 0, $total_plugins - $active_plugins ),
            __( 'Timezone (WP)', 'patchwing' ) => wp_timezone_string(),
            __( 'WP Memory Limit', 'patchwing' )  => defined( 'WP_MEMORY_LIMIT' ) ? WP_MEMORY_LIMIT : 'Not Defined',
            __( 'OPcache', 'patchwing' )  => function_exists( 'opcache_get_status' ) ? 'Enabled' : 'Disabled',
        ];

        // --- Output (Sanitized) ---
        header( 'Content-Type: text/plain; charset=utf-8' );

        echo "==== " . esc_html__( 'Versions', 'patchwing' ) . " ====\n";
        foreach ( $versions as $label => $value ) {
            echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
        }

        echo "\n==== " . esc_html__( 'Server Metrics', 'patchwing' ) . " ====\n";
        foreach ( $server_metrics as $label => $value ) {
            echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
        }

        echo "\n==== " . esc_html__( 'Environment', 'patchwing' ) . " ====\n";
        foreach ( $environment as $label => $value ) {
            echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
        }

        wp_die();
    }

    public static function patchwing_getRealCores() {
        // Try to get cached value first
        $cached = get_transient( 'patchwing_real_cores' );
        if ( $cached !== false ) {
            return (int) $cached;
        }

        $cores = 1; // Default fallback

        // Strategy 1: Environment variables
        $vars = [ 'NUMBER_OF_PROCESSORS', '_NPROCESSORS_ONLN', 'OMP_NUM_THREADS' ];
        foreach ( $vars as $var ) {
            $val = getenv( $var );
            if ( $val && (int) $val > 0 ) {
                $cores = (int) $val;
                break;
            }
        }

        // Strategy 2: sys_getloadavg() approximation
        if ( $cores === 1 && function_exists( 'sys_getloadavg' ) ) {
            $load = sys_getloadavg();
            if ( is_array( $load ) && ! empty( $load ) ) {
                $cores = max( 1, (int) round( $load[0] ) );
            }
        }

        // Cache result for 24 hours
        set_transient( 'patchwing_real_cores', $cores, DAY_IN_SECONDS );

        return $cores;
    }

}