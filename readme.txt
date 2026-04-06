=== Patchwing – Essential Debug Tools ===
Contributors: urnafiz
Tags: debug, performance, database, developer, logs
Requires at least: 5.9
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A developer tool for WordPress that provides real time server metrics, PHP configuration insights, error logging and performance monitoring.

== Description ==

Patchwing is a lightweight, powerful tool designed to make WordPress debugging simple and effective for site administrators. Instead of wasting time digging through complicated configuration files, Patchwing provides clear debug data right when you need it. Whether you are fixing the infamous white screen of death or working to boost site performance, Patchwing helps you debug issues quickly and keep your WordPress site running smoothly.

The plugin provides a overview of your environment while allowing deep dives into specific areas like database engine and load times.

== Features ==

* **System Dashboard:** At a glance view of WordPress version, PHP version, MySQL, cURL, GD Library status, Multisite status, active/inactive plugin counts and WP Memory Limit.
* **Real time Server Metrics:** Monitor your system health with live tracking of CPU load and actual RAM usage, featuring visual status indicators alongside your IP address and web server type for complete transparency.
* **Advanced PHP Info:** Detailed breakdown of key configuration settings including `memory_limit`, `upload_max_filesize`, `max_execution_time` and active PHP extensions.
* **Integrated Debug Log:** Monitor, filter and manage your PHP error logs directly from WordPress admin. Includes one click "Clear Log" and "Enable/Disable Log" functionality.
* **Database Audit:** View all database tables, storage engines, collation and data/index lengths. Includes engine migration tool.
* **Performance Monitoring:** Track baseline load times, peak memory usage and specific load times for your active theme. Visualizes performance data through interactive charts.

== Installation ==

1. Upload the `patchwing` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Access the tools via the 'Patchwing' menu in your WordPress Dashboard.

== Screenshots ==

1. Dashboard: High-level overview of versions and server health.
2. PHP Info: Detailed server configuration and limits.
3. Debug Log: Live view of PHP notices and errors.
4. Database: Table audit and engine migration tool.
5. Performance: Memory usage and load time tracking.

== Changelog ==

= 1.0.1 =
* Enhanced plugin settings page.
* Improved accuracy for real time CPU and Memory usage reporting.
* Added support for config file from one directory above WordPress root.

= 1.0.0 =
* Initial release.
* Introduced essential tools including PHP Info, Debug Log, Database and Performance analyzer.