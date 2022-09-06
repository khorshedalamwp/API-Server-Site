<?php
/**
 * Plugin Name: Browsing Statistics - Server Site
 * Plugin URI: https://pluginbazar.com/plugin
 * Description: This plugin for count websites visitors.
 * Version: 1.0.0
 * Author: Pluginbazar
 * Text Domain: bs-server
 * Domain Path: /languages/
 * Author URI: https://pluginbazar.com/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


global $wpdb;

defined( 'ABSPATH' ) || exit;
defined( 'BS_SERVER_FILE_URL' ) || define( 'BS_SERVER_FILE_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' );
defined( 'DATA_TABLE' ) || define( 'DATA_TABLE', $wpdb->prefix . 'users_data' );


/**
 * All hooks.
 */
add_action( 'rest_api_init', 'bs_server_register_endpoints' );
add_action( 'init', 'bs_server_create_data_table' );
add_action( 'admin_menu', 'bs_server_admin_menu_page' );
add_action( 'admin_enqueue_scripts', 'bs_add_frontend_scripts' );

/**
 * Add frontends scripts.
 */
if(!function_exists('bs_add_frontend_scripts')) {
	/**
	 * @return void
	 */
	function bs_add_frontend_scripts() {
		wp_register_style( 'bs-server-front', BS_SERVER_FILE_URL . 'assets/front/css/style.css', array(), '', 'all' );
		wp_enqueue_style( 'bs-server-front' );
	}
}

if ( ! function_exists( 'bs_server_register_endpoints' ) ) {
	/**
	 * Register endpoint
	 *
	 * @return void
	 */
	function bs_server_register_endpoints() {
		register_rest_route( 'server/v1/', 'pluginbazar', array(
			'methods'             => 'POST',
			'callback'            => 'bs_server_requested_data',
			'permission_callback' => '__return_true',
		) );
	}
}


if ( ! function_exists( 'bs_server_requested_data' ) ) {
	/**
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 */
	function bs_server_requested_data( WP_REST_Request $request ) {

		global $wpdb;

		$all_data  = $request->get_body_params();
		$name      = $all_data['user_name'];
		$url       = $all_data['url'];
		$date_time = $all_data['date_time'];
		$response  = array( 'status' => esc_attr( 'failed' ) );

        if(empty($name)){
            return  $response['status']= 'Invalid user-name';
        }
		if(empty($url)){
			return $response['status']= 'Invalid url';
		}
		if(empty($date_time)){
			return date('U');
		}

		$args   = array(
			'user_name' => sanitize_text_field( $name ),
			'url'       => sanitize_url( $url ),
			'date_time' => sanitize_text_field( $date_time )
		);
		$insert = $wpdb->insert( DATA_TABLE, $args );

		if ( $insert ) {
			$response['status'] = esc_attr( 'success' );
		}

		return $response;
	}
}

if ( ! function_exists( 'bs_server_create_data_table' ) ) {
	/**
	 * Create data table.
	 *
	 * @return bool
	 */
	function bs_server_create_data_table() {

		global $wpdb;

		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE IF NOT EXISTS " . DATA_TABLE . " (
         id int(100) NOT NULL AUTO_INCREMENT,
         user_name VARCHAR(255) NOT NULL,
         url VARCHAR(255) NOT NULL,
         date_time VARCHAR(50) NOT NULL,
          PRIMARY KEY (id)
      ) $charset;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		dbDelta( $sql );

		return empty( $wpdb->last_error );
	}
}

if ( ! function_exists( 'bs_server_admin_menu_page' ) ) {
	/**
	 *  Register a Reports menu page.
	 *
	 * @return void
	 */
	function bs_server_admin_menu_page() {
		add_menu_page(
			esc_html__( 'Reports', 'bs-server' ),
			esc_html__( 'Reports', 'bs-server' ),
			'manage_options',
			'bs-server-reports',
			'bs_server_render_reports_page',
			'dashicons-chart-area',
			12
		);
	}
}

if ( ! function_exists( 'bs_server_render_reports_page' ) ) {
	/**
	 * Render reporting page for browser data
	 *
	 * @return void
	 */
	function bs_server_render_reports_page() {

		global $wpdb;

		$i             = 0;
		$results       = $wpdb->get_results( "SELECT date_time FROM " . DATA_TABLE . " GROUP BY date_time DESC", ARRAY_A );
		$final_results = array();

		foreach ( $results as $result ) {

			$date_time = isset( $result['date_time'] ) ? $result['date_time'] : '';

			if ( empty( $date_time ) ) {
				continue;
			}
			$date = date( 'd-m-y', $date_time );
			if ( isset( $final_results[ $date ] ) ) {
				$final_results[ $date ] ++;
			} else {
				$final_results[ $date ] = 1;
			}
		}
		?>

        <h1 id="report"><?php echo esc_html__( 'Reports', 'bs-server' ); ?></h1>
        <table class="bs-table">
            <thead>
            <tr>
                <th class="bs-reports"><?php echo esc_html__( 'Serial', 'bs-server' ); ?></th>
                <th class="bs-reports"><?php echo esc_html__( 'Date', 'bs-server' ); ?></th>
                <th class="bs-reports"><?php echo esc_html__( 'Websites Visited', 'bs-server' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $final_results as $date => $count ): $i ++ ?>
                <tr>
                    <td class="bs-data"><?php echo esc_html( $i ); ?></td>
                    <td class="bs-data"><?php echo esc_html( $date ); ?></td>
                    <td class="bs-data"><?php echo esc_html( $count ); ?> <span class="dashicons dashicons-visibility"></span></td>
                </tr>
			<?php endforeach; ?>
            </tbody>
        </table>

		<?php
	}
}