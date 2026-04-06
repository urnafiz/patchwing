<?php
/**
 * Debug Log class file for Patchwing plugin.
 */

namespace Patchwing;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class Debug_Log {

    public static function patchwing_handle_actions() {
        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_key(wp_unslash($_SERVER['REQUEST_METHOD'])) : '';

        if ( $method !== 'post' ) {
            return;
        }

        if ( ! isset( $_POST['patchwing_nonce'] ) ) {
            return;
        }

        $nonce = sanitize_text_field( wp_unslash( $_POST['patchwing_nonce'] ) );

        if ( ! wp_verify_nonce( $nonce, 'patchwing_log_action' ) ) {
            return;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }

        $wp_config = ABSPATH . 'wp-config.php';
        $action_performed = false;

        // wp_filesystem
        global $wp_filesystem;
        // Initialize WP Filesystem if not already done
        if ( ! $wp_filesystem ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

		
		// Toggle Debug
        if ( isset( $_POST['toggle_debug'] ) ) {
            if ( $wp_filesystem->is_writable( $wp_config ) ) {
                $config = $wp_filesystem->get_contents( $wp_config );
                // Remove existing debug definitions
                $config = preg_replace( "/^[ \t]*define\s*\(\s*'WP_DEBUG'.*?\);\s*$/mi", "", $config );
                $config = preg_replace( "/^[ \t]*define\s*\(\s*'WP_DEBUG_LOG'.*?\);\s*$/mi", "", $config );
                $config = preg_replace( "/^[ \t]*define\s*\(\s*'WP_DEBUG_DISPLAY'.*?\);\s*$/mi", "", $config );
                $config = preg_replace( "/^[ \t]*@ini_set\s*\(\s*'display_errors'.*?\);\s*$/mi", "", $config );

                // Build new debug block
                if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                    $debug_block = "define('WP_DEBUG', false);\n"
                                 . "define('WP_DEBUG_LOG', false);\n"
                                 . "define('WP_DEBUG_DISPLAY', false);\n";
                } else {
                    $debug_block = "define('WP_DEBUG', true);\n"
                                 . "define('WP_DEBUG_LOG', true);\n"
                                 . "define('WP_DEBUG_DISPLAY', false);\n"
                                 . "@ini_set('display_errors', 0);\n";
                }

                // Insert before the "stop editing" comment
                $config = preg_replace( "/(\/\* That's all, stop editing!.*)/", $debug_block . "\n$1", $config );

                // Save changes
                $wp_filesystem->put_contents( $wp_config, $config, FS_CHMOD_FILE );

                $action_performed = true;
            }
        }


        // Clear Log
        if ( isset( $_POST['clear_log'] ) ) {
            $log_file = self::patchwing_get_log_path();

            if ( $wp_filesystem->exists( $log_file ) && $wp_filesystem->is_writable( $log_file ) ) {
                $wp_filesystem->put_contents( $log_file, '', FS_CHMOD_FILE );
            }
            $action_performed = true;
        }
        
        if ( $action_performed ) {
            $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'patchwing-debug-log';
            $redirect_url = admin_url( 'admin.php?page=' . $page );
            wp_safe_redirect( $redirect_url );
            exit;
        }


    }

    /**
     * Main entry point to render the page.
     */
    public static function patchwing_render_log_page() {
        if ( isset( $_POST['patchwing_nonce'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_POST['patchwing_nonce'] ) );
            if ( wp_verify_nonce( $nonce, 'patchwing_log_action' ) ) {
                self::patchwing_handle_actions();
            }
        }

        $log_file      = self::patchwing_get_log_path();
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $range = isset( $_GET['range'] ) ? sanitize_text_field( wp_unslash( $_GET['range'] ) ) : '1h';
        $page  = isset( $_GET['paged'] ) ? max( 1, intval( sanitize_text_field( wp_unslash( $_GET['paged'] ) ) ) ) : 1;

        $per_page      = 50;
        $file_exists   = file_exists( $log_file );

        $entries       = $file_exists ? self::patchwing_process_logs( $log_file, $range, $search ) : [];
        $total_entries = count( $entries );
        $pages         = max( 1, ceil( $total_entries / $per_page ) );
        $paged_entries = array_slice( $entries, ( $page - 1 ) * $per_page, $per_page );

        $file_size     = $file_exists ? self::patchwing_format_bytes( filesize( $log_file ) ) : '0 B';
        $last_modified = $file_exists ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), filemtime( $log_file ) ) : 'N/A';

        self::patchwing_display_template( $paged_entries, $log_file, $file_size, $last_modified, $range, $search, $page, $pages );
    }

    private static function patchwing_process_logs( $log_file, $range, $search ) {
        $entries = [];
        $lines   = file( $log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

        foreach ( $lines as $line ) {
            if ( ! preg_match( '/^\[(.*?)\]\s*(.*)$/', $line, $m ) ) continue;

            $datetime  = $m[1];
            $timestamp = strtotime( $datetime );
            $message   = $m[2];

            if ( $range !== 'all' ) {
                switch ( $range ) {
                    case '5m':
                        $cutoff = time() - 300;
                        break;
                    case '30m':
                        $cutoff = time() - 1800;
                        break;
                    case '1h':
                        $cutoff = time() - 3600;
                        break;
                    case '12h':
                        $cutoff = time() - 43200;
                        break;
                    default:
                        $cutoff = 0;
                }
                if ( $timestamp < $cutoff ) continue;
            }

            if ( $search && stripos( $message, $search ) === false ) continue;

            $type = 'Other';
            if ( stripos( $message, 'PHP Fatal' ) !== false )       $type = 'PHP Fatal';
            elseif ( stripos( $message, 'PHP Warning' ) !== false ) $type = 'PHP Warning';
            elseif ( stripos( $message, 'PHP Notice' ) !== false )  $type = 'PHP Notice';
            elseif ( stripos( $message, 'Deprecated' ) !== false )  $type = 'Deprecated';
            elseif ( stripos( $message, 'database' ) !== false )    $type = 'Database Error';

            $file = $line_no = '';
            if ( preg_match( '/ in (\/.*) on line (\d+)/', $message, $fm ) || preg_match( '/(\/.*):(\d+)/', $message, $fm ) ) {
                $file    = $fm[1];
                $line_no = $fm[2];
                $message = trim( str_replace( $fm[0], '', $message ) );
            }

            $source = 'Other';
            if ( false !== strpos( $file, '/wp-admin/' ) || false !== strpos( $file, '/wp-includes/' ) ) {
                $source = 'WordPress Core';
            } elseif ( false !== strpos( $file, '/wp-content/plugins/' ) ) {
                $source = 'Plugin';
            } elseif ( false !== strpos( $file, '/wp-content/themes/' ) ) {
                $source = 'Theme';
            }

            if ( $search ) {
                $message = preg_replace( '/' . preg_quote( $search, '/' ) . '/i', '<span style="background:yellow;">$0</span>', $message );
            }

            $key = md5( $type . $message . $file . $line_no );

            if ( ! isset( $entries[ $key ] ) ) {
                $entries[ $key ] = [
                    'last_seen' => $timestamp,
                    'datetime'  => $datetime,
                    'type'      => $type,
                    'source'    => $source,
                    'log'       => $message,
                    'file'      => '/' . ltrim( str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $file ) ), '/' ),
                    'line'      => $line_no,
                    'count'     => 1,
                ];
            } else {
                $entries[ $key ]['count']++;
                if ( $timestamp > $entries[ $key ]['last_seen'] ) {
                    $entries[ $key ]['last_seen'] = $timestamp;
                    $entries[ $key ]['datetime']  = $datetime;
                }
            }
        }

        usort( $entries, fn( $a, $b ) => $b['last_seen'] <=> $a['last_seen'] );
        return $entries;
    }

    private static function patchwing_get_log_path() {
        if ( defined( 'WP_DEBUG_LOG' ) && is_string( WP_DEBUG_LOG ) ) {
            return WP_DEBUG_LOG;
        }
        return WP_CONTENT_DIR . '/debug.log';
    }

    private static function patchwing_get_type_color( $type ) {
        switch ( $type ) {
            case 'PHP Fatal':
            case 'Database Error':
                return '#d63638';
            case 'PHP Warning':
                return '#dba617';
            case 'PHP Notice':
                return '#2271b1';
            case 'Deprecated':
                return '#646970';
            default:
                return '#2c3338';
        }
    }

    public static function patchwing_format_bytes( $bytes, $precision = 2 ) : string {
        $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
        $bytes = max( (int)$bytes, 0 );
        $power = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        return round( $bytes / ( 1024 ** $power ), $precision ) . ' ' . $units[$power];
    }

    private static function patchwing_display_template( $entries, $log_file, $file_size, $last_modified, $range, $search, $page, $pages ) {
        ?>
        <div class="wrap debug-wrap">
            <div class="top-bar">
                <form method="post" class="top-bar-form">
                    <?php wp_nonce_field( 'patchwing_log_action', 'patchwing_nonce' ); ?>
                    <button type="submit" name="toggle_debug" class="btn btn-primary">
                        <?php echo ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Disable Debug Log' : 'Enable Debug Log'; ?>
                    </button>
                    <button type="submit" name="clear_log" class="btn btn-danger" onclick="return confirm('Clear all log entries?');">
                        Clear Log
                    </button>
                </form>

                <form method="get" class="search-form">
                    <input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '' );// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display; pre-filling a GET form field. Nonce is verified in patchwing_render_log_page() before any state-changing action. ?>">
                    <input type="hidden" name="range" value="<?php echo esc_attr( $range ); ?>">
                    <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search in log..." class="search-input">
                </form>
            </div>

            <div class="info-box">
                <div class="info-text">
                    <strong class="highlight">Log Path:</strong> <?php echo esc_html( $log_file ); ?>
                </div>
            </div>

            <div class="info-box file-info">
                <div class="info-item"><strong class="highlight">File Size:</strong> <?php echo esc_html( $file_size ); ?></div>
                <div class="info-item"><strong class="highlight">Last Modified:</strong> <?php echo esc_html( $last_modified ); ?></div>
            </div>

            <div class="range-filter">
                <?php 
                foreach ( ['5m', '30m', '1h', '12h', 'all'] as $r ): 
                    $url = add_query_arg( [ 'range' => $r ], remove_query_arg( [ 's', 'paged' ] ) ); ?>
                    <a href="<?php echo esc_url( $url ); ?>" class="range-btn <?php echo $range === $r ? 'active' : ''; ?>">
                        <?php echo esc_html( strtoupper( $r ) ); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="table-container">
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>Last Seen</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Message</th>
                            <th>File</th>
                            <th>Line</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $entries as $e ): ?>
                        <tr>
                            <td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $e['datetime'] ) ) ); ?></td>
                            <td style="font-weight:bold; color: <?php echo esc_attr( self::patchwing_get_type_color( $e['type'] ) ); ?>;">
                                <?php echo esc_html( $e['type'] ); ?>
                            </td>
                            <td><?php echo esc_html( $e['source'] ); ?></td>
                            <td><?php echo wp_kses_post( $e['log'] ); ?></td>
                            <td><?php echo esc_html( $e['file'] ); ?></td>
                            <td><?php echo esc_html( $e['line'] ); ?></td>
                            <td><strong><?php echo esc_html( $e['count'] ); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if ( ! $entries ): ?>
                            <tr><td colspan="7">No log entries found!</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination">
                <?php
                $window = 2;
                $start  = max( 1, $page - $window );
                $end    = min( $pages, $page + $window );

                if ( $page > 1 ) {
                    echo '<a href="' . esc_url( add_query_arg( [ 'paged' => $page - 1 ] ) ) . '" class="page-btn">← Prev</a>';
                } else {
                    echo '<span class="page-btn disabled">← Prev</span>';
                }

                for ( $i = $start; $i <= $end; $i++ ) {
                    echo '<a href="' . esc_url( add_query_arg( [ 'paged' => $i ] ) ) . '" class="page-btn ' . ( $i == $page ? 'active' : '' ) . '">' . esc_html( $i ) . '</a>';
                }

                if ( $page < $pages ) {
                    echo '<a href="' . esc_url( add_query_arg( [ 'paged' => $page + 1 ] ) ) . '" class="page-btn">Next →</a>';
                } else {
                    echo '<span class="page-btn disabled">Next →</span>';
                }
                ?>
            </div>
        </div>
        <?php
    }
    
}