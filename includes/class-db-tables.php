<?php
/**
 * DB Tables class file for Patchwing plugin.
 */

namespace Patchwing;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

class DB_Tables {

    /**
     * Render function for add_menu_page
     */
    public static function patchwing_render_db_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'patchwing' ) );
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-only page render; metadata query for display purposes only.
        $tables = $wpdb->get_results( "SHOW TABLE STATUS" );
        $total_tables = count( $tables );
        $per_page     = 20;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Pagination parameter only; no state change or DB write occurs here.
        $current_page = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $total_pages  = ceil( $total_tables / $per_page );

        $offset      = ( $current_page - 1 ) * $per_page;
        $tables_page = array_slice( $tables, $offset, $per_page );

        $nonce = wp_create_nonce( 'patchwing_db_table_actions_nonce' );
        ?>
        <div class="wrap">
            <div class="db-table-container" 
                 data-nonce="<?php echo esc_attr( $nonce ); ?>" 
                 data-ajaxurl="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>">
                
                <div class="page-title">
                    <h1><?php esc_html_e( 'Database', 'patchwing' ); ?></h1>
                </div>

                <p>
                    <strong><?php esc_html_e( 'Safe Migration Plan:', 'patchwing' ); ?></strong> 
                    <?php esc_html_e( 'Always take a backup before converting tables. Use “Database Backup” button below.', 'patchwing' ); ?>
                </p>

                <button class="btn btn-backup" id="btn-backup"><?php esc_html_e( 'Database Backup', 'patchwing' ); ?></button>
                <button class="btn btn-convert-all" id="btn-convert-all"><?php esc_html_e( 'Convert All to InnoDB', 'patchwing' ); ?></button>
                
                <div id="spinner" class="spinner">
                    <img src="<?php echo esc_url( admin_url( 'images/spinner.gif' ) ); ?>" alt="<?php esc_attr_e( 'Loading...', 'patchwing' ); ?>">
                </div>
                
                <div id="successBox" class="success-message"></div>

                <table class="db-tables">
                    <thead>
                        <tr>
                            <th class="table-name"><?php esc_html_e( 'Table Name', 'patchwing' ); ?></th>
                            <th><?php esc_html_e( 'Engine', 'patchwing' ); ?></th>
                            <th><?php esc_html_e( 'Collation', 'patchwing' ); ?></th>
                            <th><?php esc_html_e( 'Data Length', 'patchwing' ); ?></th>
                            <th><?php esc_html_e( 'Index Length', 'patchwing' ); ?></th>
                            <th><?php esc_html_e( 'Action', 'patchwing' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $tables_page as $table ) : ?>
                            <tr>
                                <td class="table-name"><?php echo esc_html( $table->Name ); ?></td>
                                <td><?php echo esc_html( $table->Engine ); ?></td>
                                <td><?php echo esc_html( $table->Collation ); ?></td>
                                <td><?php echo esc_html( number_format( $table->Data_length ) ); ?></td>
                                <td><?php echo esc_html( number_format( $table->Index_length ) ); ?></td>
                                <td>
                                    <?php if ( 'MyISAM' === $table->Engine ) : ?>
                                        <button class="btn btn-convert convert-btn" data-table="<?php echo esc_attr( $table->Name ); ?>">
                                            <?php esc_html_e( 'Convert to InnoDB', 'patchwing' ); ?>
                                        </button>
                                    <?php else : ?>
                                        <span style="color:green;"><?php esc_html_e( 'Already InnoDB', 'patchwing' ); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="pagination">
                    <?php if ( $current_page > 1 ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page - 1 ) ); ?>"><?php esc_html_e( '← Prev', 'patchwing' ); ?></a>
                    <?php else : ?>
                        <span class="disabled"><?php esc_html_e( '← Prev', 'patchwing' ); ?></span>
                    <?php endif; ?>

                    <span class="current">
                        <?php // translators: %1$d is the current page number, %2$d is the total number of pages.
                        printf( esc_html__( 'Page %1$d of %2$d', 'patchwing' ), (int) $current_page, (int) $total_pages );
                        ?>
                    </span>

                    <?php if ( $current_page < $total_pages ) : ?>
                        <a href="<?php echo esc_url( add_query_arg( 'paged', $current_page + 1 ) ); ?>"><?php esc_html_e( 'Next →', 'patchwing' ); ?></a>
                    <?php else : ?>
                        <span class="disabled"><?php esc_html_e( 'Next →', 'patchwing' ); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler
     */
    public static function patchwing_handle_db_table_actions() {
        // Security check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Unauthorized.', 'patchwing' ) );
        }

        check_ajax_referer( 'patchwing_db_table_actions_nonce' );

        global $wpdb;
        $action = isset( $_POST['action_type'] ) ? sanitize_text_field( wp_unslash( $_POST['action_type'] ) ) : '';
		$table  = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';


        // Convert Single Table
        if ( 'convert_innodb' === $action && ! empty( $table ) ) {
            $safe_table = esc_sql( $table );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Admin-only AJAX handler; nonce verified via check_ajax_referer(). Table name escaped with esc_sql().
            $wpdb->query( "ALTER TABLE `{$safe_table}` ENGINE=InnoDB" );
            // translators: %s is the name of the database table.
            wp_send_json_success( sprintf( __( 'Table %s converted to InnoDB.', 'patchwing' ), $table ) );
        }

        // Convert All Tables
        if ( 'convert_all' === $action ) {
            $tables = $wpdb->get_results( "SHOW TABLE STATUS" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin-only AJAX; SHOW TABLE STATUS is a read-only metadata query.
            foreach ( $tables as $t ) {
                if ( 'MyISAM' === $t->Engine ) {
                    $safe_name = esc_sql( $t->Name );
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Admin-only AJAX; nonce verified. Table name from DB metadata, escaped with esc_sql().
                    $wpdb->query( "ALTER TABLE `{$safe_name}` ENGINE=InnoDB" );
                }
            }
            wp_send_json_success( __( 'All MyISAM tables converted to InnoDB.', 'patchwing' ) );
        }

        // Export Backup
        if ( 'export_backup' === $action ) {
            self::patchwing_generate_sql_backup();
        }

        wp_send_json_error( __( 'Invalid action.', 'patchwing' ) );
    }

    /**
     * Logic for SQL Generation
     */
    private static function patchwing_generate_sql_backup() {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Backup export; caching would return stale schema data.
        $tables = $wpdb->get_col( "SHOW TABLES" );
        $sql = "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET sql_mode='';\nSTART TRANSACTION;\n\n";

        foreach ( $tables as $table ) {
            $safe_table = esc_sql( $table );
            $sql .= "DROP TABLE IF EXISTS `{$safe_table}`;\n";
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names sourced from SHOW TABLES (DB metadata); escaped with esc_sql(). Schema query required for backup.
            $create = $wpdb->get_row( "SHOW CREATE TABLE `{$safe_table}`", ARRAY_N );
            $create_sql = $create[1];

            // Fix invalid zero-date defaults for newer MySQL versions
            $create_sql = str_replace( "DEFAULT '0000-00-00 00:00:00'", "DEFAULT CURRENT_TIMESTAMP", $create_sql );
            $sql .= $create_sql . ";\n\n";

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names from DB metadata; escaped with esc_sql(). Full table read required for backup.
            $rows = $wpdb->get_results( "SELECT * FROM `{$safe_table}`", ARRAY_A );
            if ( ! empty( $rows ) ) {
                $columns = array_keys( $rows[0] );
                $colList = "`" . implode( "`,`", $columns ) . "`";
                $sql .= "LOCK TABLES `$table` WRITE;\n";

                foreach ( $rows as $row ) {
                    $values = array();
                    foreach ( $row as $val ) {
                        if ( is_null( $val ) ) {
                            $values[] = "NULL";
                        } elseif ( is_numeric( $val ) ) {
                            $values[] = $val;
                        } else {
                            $values[] = "'" . esc_sql( $val ) . "'";
                        }
                    }
                    $sql .= "INSERT INTO `{$safe_table}` ($colList) VALUES (" . implode( ",", $values ) . ");\n";
                }
                $sql .= "UNLOCK TABLES;\n\n";
            }
        }

        $sql .= "COMMIT;\nSET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'db-backup-' . date_i18n( 'Y-m-d_h-i-A', current_time( 'timestamp' ) ) . '.sql';

        // Clear output buffer to avoid corrupting the SQL file
        if ( ob_get_length() ) {
            ob_clean();
        }

        header( 'Content-Type: application/sql' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        //echo $sql;
		echo esc_html( $sql );
        exit;
    }
    
}