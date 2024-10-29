<?php
/**
 * Be always aware of your website status. AdminLabs provides powerful monitoring and communication services for you and your organization.
 *
 * @package AdminLabs
 *
 * @wordpress-plugin
 * Plugin Name:       AdminLabs
 * Description:       Be always aware of your website status. AdminLabs provides powerful monitoring and communication services for you and your organization.
 * Version:           1.0.1
 * Author:            AdminLabs
 * Author URI:        https://www.adminlabs.com/
 * License:           GPLv3
 * License URI:       https://www.gnu.org/licenses/gpl.html
 * Text Domain:       adminlabs
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AdminLabs' ) ) :

	define( 'ADMINLABS_PLUGIN_VERSION', '1.0.0' );

	/**
	 *  Our main class for this plugin
	 */
	class AdminLabs {
		public static $instance;

		/**
		 *  Start the magic
		 *
		 *  @since  1.0.0
		 *  @return object  Resource to use this plugin
		 */
		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new AdminLabs();
			}

			return self::$instance;
		} // end init

		/**
		 *  Load our thingies.
		 *
		 *  @since 0.0.1-alpha
		 */
		private function __construct() {
			require_once 'classes/class-adminlabs-tools.php';
			require_once 'classes/class-adminlabs-admin.php';
			require_once 'classes/class-adminlabs-settings.php';
			require_once 'classes/class-adminlabs-api.php';
			require_once 'classes/class-adminlabs-widget.php';

			AdminLabs_Admin::init();
			AdminLabs_Settings::init();
			AdminLabs_API::init();
			AdminLabs_Widget::init();

			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		}

		/**
		 *  Load plugin textdomain
		 *
		 *  @since  1.0.0
		 */
		public static function load_textdomain() {
			$loaded = load_plugin_textdomain( 'adminlabs', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
			if ( ! $loaded ) {
				$loaded = load_muplugin_textdomain( 'adminlabs', dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
			}
		}
	}

endif;

/**
 *  Init the plugin itself, but only when user is in dashboard
 */
if ( is_admin() ) {
	AdminLabs::init();
}

require_once 'classes/class-adminlabs-rest.php';
AdminLabs_REST::init();
