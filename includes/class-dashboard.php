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
        // $cpu_cores = self::patchwing_getRealCores();
        // if ($cpu_cores > 0) {
        //     $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
        //     $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
        //     // echo "Cores Detected: $cores\n";
        // } else {
        //     $cpu_cores = 2; // fallback assumption
        //     $cpu_load = function_exists('sys_getloadavg') ? sys_getloadavg()[0] : 0;
        //     $cpu_pct = round(($cpu_load / $cpu_cores) * 100, 2);
        //     // echo "Cores could not be detected. Assuming 2 core.\n";
        // }
        // $cpu_status = $cpu_pct === null ? 'moderate' : ( $cpu_pct < 50 ? 'excellent' : ( $cpu_pct < 80 ? 'moderate' : 'high' ) );
        $cpu_cores     = self::patchwing_getRealCores();
        $load_averages = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cpu_load      = $load_averages[0];
        if ( $cpu_cores && $cpu_cores > 0 ) {
            $cpu_pct = round( ( $cpu_load / $cpu_cores ) * 100, 2 );
        } else {
            $cpu_cores = null;
            $cpu_pct   = null;
        }
        $cpu_status = ( null === $cpu_cores ) ? 'Not Available' : ( $cpu_pct < 50 ? 'excellent' : ( $cpu_pct < 85 ? 'moderate' : 'high' ) );

        // Memory Metrics
        // $memory_usage       = memory_get_usage( true );
        // $memory_limit       = ini_get( 'memory_limit' );
        // $memory_limit_bytes = wp_convert_hr_to_bytes( $memory_limit );
        // $memory_pct         = $memory_limit_bytes > 0 ? round( ( $memory_usage / $memory_limit_bytes ) * 100, 2 ) : null;
        // $mem_status         = $memory_pct === null ? 'moderate' : ( $memory_pct < 60 ? 'excellent' : ( $memory_pct < 80 ? 'moderate' : 'high' ) );
        $mem = self::getUsage();
        $memory_usage       = $mem['used'];
        $memory_limit       = $mem['total'];
        $memory_pct         = $mem['pct'];
        $mem_status = ( $memory_pct === null || $memory_pct == 0 ) ? 'Not Available' : ( $memory_pct < 60 ? 'excellent' : ( $memory_pct < 80 ? 'moderate' : 'high' ) );

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
                if ( $cpu_cores != null ) {
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
                    //$memory_limit = 0.12;
                    if ( $memory_limit > 0 ) {
                        /* translators: 1: Memory usage (e.g. 0.5), 2: Total memory limit (e.g. 2), 3: Percentage used (e.g. 25). */
                        echo esc_html( sprintf( __( '%1$s of %2$s GB (%3$s%%)', 'patchwing' ), $memory_usage, $memory_limit, $memory_pct ) );
                    } else {
                        echo esc_html__( 'Not Available', 'patchwing' );
                    }
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
        $cpu_cores     = self::patchwing_getRealCores();
        $load_averages = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cpu_load      = $load_averages[0];
        if ( $cpu_cores && $cpu_cores > 0 ) {
            $cpu_pct = round( ( $cpu_load / $cpu_cores ) * 100, 2 );
        } else {
            $cpu_cores = null;
            $cpu_pct   = null;
        }
        $cpu_status = ( null === $cpu_cores ) ? 'Not Available' : ( $cpu_pct < 50 ? 'excellent' : ( $cpu_pct < 85 ? 'moderate' : 'high' ) );

        // Memory Metrics
        $mem = self::getUsage();
        $memory_usage       = $mem['used'];
        $memory_limit       = $mem['total'];
        $memory_pct         = $mem['pct'];
        $mem_status = ( $memory_pct === null || $memory_pct == 0 ) ? 'Not Available' : ( $memory_pct < 60 ? 'excellent' : ( $memory_pct < 80 ? 'moderate' : 'high' ) );

        $server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : (isset($_SERVER['LOCAL_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['LOCAL_ADDR'])) : 'N/A');
        $server_sw = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'N/A';

        $server_metrics = [
            __( 'IP Address', 'patchwing' )    => $server_ip,
            __( 'Web Server', 'patchwing' )   => $server_sw,
            __( 'CPU Load', 'patchwing' )     => $cpu_cores !== null ? sprintf( '%s (%d cores, %s%%)', round($cpu_load,2), $cpu_cores, $cpu_pct ) : 'Not Available',
            __( 'CPU Load Status', 'patchwing' )   => ucfirst( $cpu_status ),
            __( 'Memory Usage', 'patchwing' ) => ( $memory_limit > 0 && null !== $memory_pct ) ? sprintf( 
                /* translators: 1: Memory usage, 2: Memory limit, 3: Percentage used. */
                __( '%1$s of %2$s GB (%3$s%%)', 'patchwing' ), $memory_usage, $memory_limit, $memory_pct ) : __( 'Not Available', 'patchwing' ),
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
        $cpu_cores     = self::patchwing_getRealCores();
        $load_averages = function_exists('sys_getloadavg') ? sys_getloadavg() : [0, 0, 0];
        $cpu_load      = $load_averages[0];
        if ( $cpu_cores && $cpu_cores > 0 ) {
            $cpu_pct = round( ( $cpu_load / $cpu_cores ) * 100, 2 );
        } else {
            $cpu_cores = null;
            $cpu_pct   = null;
        }
        $cpu_status = ( null === $cpu_cores ) ? 'Not Available' : ( $cpu_pct < 50 ? 'excellent' : ( $cpu_pct < 85 ? 'moderate' : 'high' ) );

        // Memory Metrics
        $mem = self::getUsage();
        $memory_usage       = $mem['used'];
        $memory_limit       = $mem['total'];
        $memory_pct         = $mem['pct'];
        $mem_status = ( $memory_pct === null || $memory_pct == 0 ) ? 'Not Available' : ( $memory_pct < 60 ? 'excellent' : ( $memory_pct < 80 ? 'moderate' : 'high' ) );

        $server_ip = isset($_SERVER['SERVER_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_ADDR'])) : (isset($_SERVER['LOCAL_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['LOCAL_ADDR'])) : 'N/A');
        $server_sw = isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field(wp_unslash($_SERVER['SERVER_SOFTWARE'])) : 'N/A';

        $server_metrics = [
            __( 'IP Address', 'patchwing' )    => $server_ip,
            __( 'Web Server', 'patchwing' )   => $server_sw,
            __( 'CPU Load', 'patchwing' )     => $cpu_cores !== null ? sprintf( '%s (%d cores, %s%%)', round($cpu_load,2), $cpu_cores, $cpu_pct ) : 'Not Available',
            __( 'CPU Load Status', 'patchwing' )   => ucfirst( $cpu_status ),
            __( 'Memory Usage', 'patchwing' ) => ( $memory_limit > 0 && null !== $memory_pct ) ? sprintf( 
                /* translators: 1: Memory usage, 2: Memory limit, 3: Percentage used. */
                __( '%1$s of %2$s GB (%3$s%%)', 'patchwing' ), $memory_usage, $memory_limit, $memory_pct ) : __( 'Not Available', 'patchwing' ),
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

    /**
     * Returns the number of logical CPU cores.
     * Uses a Singleton pattern to cache the result for the current request.
     */
    public static function patchwing_getRealCores()
    {
        // Try to get cached value first
        $cached = get_transient( 'patchwing_real_cores' );
        if ( $cached !== false ) {
            return (int) $cached;
        }

        $coreCount = null;

        switch (PHP_OS_FAMILY) {
            case 'Darwin': // macOS
                $coreCount = self::execute('/usr/sbin/sysctl -n hw.ncpu') ? : self::execute('/usr/bin/getconf _NPROCESSORS_ONLN');
                break;

            case 'Linux':
                // Attempt to read the file directly (fastest, no shell required)
                if (is_readable('/proc/cpuinfo')) {
                    $cpuinfo = file_get_contents('/proc/cpuinfo');
                    $coreCount = substr_count($cpuinfo, 'processor');
                }
                
                // Fallback to nproc if file read failed
                if (!$coreCount) {
                    $coreCount = self::execute('nproc');
                }
                break;

            case 'Windows':
                // Use environment variable first
                $envCores = getenv('NUMBER_OF_PROCESSORS');
                if ($envCores !== false) {
                    $coreCount = (int)$envCores;
                } else {
                    // Fallback to WMIC
                    $wmic = self::execute('wmic cpu get NumberOfLogicalProcessors /value');
                    if (preg_match('/(\d+)/', (string)$wmic, $matches)) {
                        $coreCount = (int)$matches[1];
                    }
                }
                break;

            case 'BSD':
                $coreCount = self::execute('/sbin/sysctl -n hw.ncpu');
                break;
        }

        // Final fallback to 1 if all detection methods failed
        if (!$coreCount || $coreCount <= 0) {
            $coreCount = 0;
        }

        // Cache result for 24 hours
        set_transient( 'patchwing_real_cores', $coreCount, DAY_IN_SECONDS );

        return $coreCount;
    }

    /**
     * Internal helper to execute shell commands safely.
     */
    private static function execute(string $command): ?int
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        $result = shell_exec($command . ' 2>/dev/null');
        return $result !== null ? (int)trim($result) : null;
    }


    public static function getUsage(): array
    {
        $total = 0;
        $free = 0;

        // Check if shell_exec is even allowed on this server
        $can_exec = function_exists('shell_exec') && !in_array('shell_exec', array_map('trim', explode(',', ini_get('disable_functions'))));

        switch (PHP_OS_FAMILY) {
            case 'Windows':
                if ($can_exec) {
                    // Try PowerShell first (Modern Windows 10/11)
                    $psRes = shell_exec('powershell -Command "Get-CimInstance Win32_OperatingSystem | Select-Object TotalVisibleMemorySize, FreePhysicalMemory"');
                    if ($psRes && preg_match('/(\d+)\s+(\d+)/', $psRes, $matches)) {
                        $total = $matches[1] * 1024;
                        $free  = $matches[2] * 1024;
                    } else {
                        // Fallback to WMIC (Legacy Windows)
                        $totalStr = shell_exec('wmic OS get TotalVisibleMemorySize /Value');
                        $freeStr  = shell_exec('wmic OS get FreePhysicalMemory /Value');
                        preg_match('/(\d+)/', (string)$totalStr, $tMatches);
                        preg_match('/(\d+)/', (string)$freeStr, $fMatches);
                        $total = ($tMatches[1] ?? 0) * 1024;
                        $free  = ($fMatches[1] ?? 0) * 1024;
                    }
                }
                break;

            case 'Linux':
                // Try /proc/meminfo first (most efficient)
                if (is_readable('/proc/meminfo')) {
                    $meminfo = file_get_contents('/proc/meminfo');
                    preg_match('/MemTotal:\s+(\d+)/', $meminfo, $tMatches);
                    preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $fMatches);
                    // If MemAvailable isn't there (older kernels), use MemFree + Buffers + Cached
                    if (empty($fMatches)) {
                        preg_match('/MemFree:\s+(\d+)/', $meminfo, $freeM);
                        preg_match('/Buffers:\s+(\d+)/', $meminfo, $bufM);
                        preg_match('/^Cached:\s+(\d+)/m', $meminfo, $cacheM);
                        $total = ($tMatches[1] ?? 0) * 1024;
                        $free = (($freeM[1] ?? 0) + ($bufM[1] ?? 0) + ($cacheM[1] ?? 0)) * 1024;
                    } else {
                        $total = ($tMatches[1] ?? 0) * 1024;
                        $free = ($fMatches[1] ?? 0) * 1024;
                    }
                }

                // Fallback: If proc failed or returned 0, try the 'free' command
                if ($total === 0 && $can_exec) {
                    $freeOut = shell_exec('free -b'); // -b for bytes
                    if ($freeOut) {
                        $lines = explode("\n", trim($freeOut));
                        if (isset($lines[1])) {
                            // Standard 'free' output: Mem: total used free shared buff/cache available
                            $stats = preg_split('/\s+/', $lines[1]);
                            $total = (int)($stats[1] ?? 0);
                            // Use 'available' (last column) if it exists, otherwise use 'free'
                            $free = (int)($stats[6] ?? $stats[3]); 
                        }
                    }
                }
                break;

            case 'Darwin':
                // macOS
                if ($can_exec) {
                    $total = (int)shell_exec('/usr/sbin/sysctl -n hw.memsize');
                    $vmStat = shell_exec('/usr/bin/vm_stat');
                    if (preg_match('/page size of (\d+) bytes/', (string)$vmStat, $pMatch)) {
                        $pageSize = $pMatch[1];
                        preg_match('/Pages free:\s+(\d+)/', $vmStat, $fMatch);
                        preg_match('/Pages purgeable:\s+(\d+)/', $vmStat, $purMatch);
                        $free = (($fMatch[1] ?? 0) + ($purMatch[1] ?? 0)) * $pageSize;
                    }
                }
                break;
        }

        if ($total <= 0) {
            return ['total' => 0, 'used' => 0, 'free' => 0, 'pct' => 0];
        }

        $used = $total - $free;
        return [
            'total' => round($total / 1024 / 1024 / 1024, 2),
            'used'  => round($used / 1024 / 1024 / 1024, 2),
            'free'  => $free,
            'pct'   => round(($used / $total) * 100, 2)
        ];
    }

}