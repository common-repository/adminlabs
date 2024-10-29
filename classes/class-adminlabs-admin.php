<?php

/**
 * General tasks to perform on dashboard
 *
 * @Author: Timi Wahalahti
 * @Date:   2017-08-10 22:45:57
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-12-19 14:59:58
 *
 * @package AdminLabs
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AdminLabs_Admin' ) ) :

	/**
	 *  Class for all this
	 *
	 *  @since  1.0.0
	 */
	class AdminLabs_Admin {
		/**
		 *  Instance of this class
		 *
		 *  @var resource
		 */
		public static $instance;

		/**
		 *  Instance of AdminLabs_API class
		 *
		 *  @var resource
		 */
		private static $api_instance;

		/**
		 *  Start the magic
		 *
		 *  @since  1.0.0
		 *  @return object  Resource to use this class
		 */
		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new AdminLabs_Admin();
			}

			return self::$instance;
		} // end init

		/**
		 *  Set variables and place few hooks
		 *
		 *  @since 0.0.1-alpha
		 */
		public function __construct() {
			self::$api_instance =  new AdminLabs_API();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
			add_action( 'admin_init', array( $this, 'maybe_do_reset' ) );
			add_action( 'admin_init', array( $this, 'maybe_show_admin_notices' ) );
		} // end __construct

		/**
		 *  Enqueue our css and js if user is in dashboard
		 *
		 *  @since  1.0.0
		 */
		public function enqueue_admin() {
			$screen = get_current_screen();
			wp_enqueue_style( 'adminlabs', plugins_url( 'assets/admin/main.css', dirname( __FILE__ ) ), array(), ADMINLABS_PLUGIN_VERSION );

			if ( 'dashboard' === $screen->base && self::$api_instance->check_credentials_health() ) {
				wp_enqueue_script( 'adminlabs-widget', plugins_url( 'assets/admin/widget.js', dirname( __FILE__ ) ), ADMINLABS_PLUGIN_VERSION, true );
				wp_localize_script( 'adminlabs-widget', 'adminlabs', array(
					'root' 			=> esc_url_raw( rest_url() ),
					'nonce' 		=> wp_create_nonce( 'wp_rest' ),
					'messages'	=> array(
						'comment_add_confirm'			=> esc_attr__( 'Really remove comment?', 'adminlabs' ),
						'comment_add_failure'			=> esc_attr__( 'Adding comment failed. Try again and if problem presist, please send us a bug report.', 'adminlabs' ),
						'comment_delete_failure'	=> esc_attr__( 'Removing comment failed. Try again and if problem presist, please send us a bug report.', 'adminlabs' )
					)
				) );
			}

			if ( 'settings_page_adminlabs' === $screen->base ) {
				wp_enqueue_script( 'adminlabs-settings', plugins_url( 'assets/admin/settings.js', dirname( __FILE__ ) ), ADMINLABS_PLUGIN_VERSION, true );
				wp_localize_script( 'adminlabs-settings', 'adminlabs', array(
					'root' 			=> esc_url_raw( rest_url() ),
					'nonce' 		=> wp_create_nonce( 'wp_rest' ),
					'messages'	=> array(
						'maintenance_add_confirm'			=> esc_attr__( 'Really remove scheduled maintenance?', 'adminlabs' ),
						'maintenance_add_failure'			=> esc_attr__( 'Scheduling maintenance failed. Try again and if problem presist, please send us a bug report.', 'adminlabs' ),
						'maintenance_delete_failure'	=> esc_attr__( 'Removing maintenance failed. Try again and if problem presist, please send us a bug report.', 'adminlabs' ),
					)
				) );
			}
		} // end enqueue_admin

		/**
		 *  Check if things looks like we need to reset all adminlabs settings
		 *
		 *  @since  1.0.0
		 */
		public function maybe_do_reset() {
			if ( isset( $_GET['adminlabs_reset_credentials'] ) && current_user_can( 'manage_options' ) ) {
				AdminLabs_Tools::reset();
				wp_redirect( admin_url( 'options-general.php?page=adminlabs&message=adminlabs_reset' ) );
				exit;
			}
		} // end maybe_do_reset

		/**
		 *  Check if we need to show some admin notices
		 *
		 *  @since  1.0.0
		 */
		public function maybe_show_admin_notices() {
			$api_account_id = get_option( 'adminlabs_api_account_id' );
			$api_key = get_option( 'adminlabs_api_key' );

			// Maybe show general message.
			if ( isset( $_GET['message'] ) ) {
				if ( 'adminlabs_reset' === $_GET['message'] ) {
					add_action( 'admin_notices', array( $this, 'notice_reset_success' ) );
				}
			}

			// Maybe show onboarding process.
			if ( current_user_can( 'manage_options' ) && ! AdminLabs_Tools::check_api_settings_existance() ) {
				add_action( 'admin_notices', array( $this, 'notice_onboarding' ) );
				return;
			}

			// Maybe show API connectivity issue warning.
			if ( current_user_can( 'manage_options' ) && ! self::$api_instance->check_credentials_health() ) {
				add_action( 'admin_notices', array( $this, 'notice_no_api_connection' ) );
			}
		} // end maybe_show_admin_notices

		/**
		 *  Show onboarding process when there's no API settings saved
		 *
		 *  @since  1.0.0
		 */
		public function notice_onboarding() {
			$screen = get_current_screen();

			if ( 'settings_page_adminlabs' !== $screen->id ) : ?>
				<div class="notice adminlabs-onboarding is-dismissible">
					<div class="top-bar">
		    		<svg aria-labelledby="title" fill="#feffff" width="200" height="45" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 696 151"><title>AdminLabs</title><path d="M103 145.9H79.2L72 126.5H35l-7.2 19.4H4.1l39.2-97.1h20.5l39.2 97.1zM53.7 78.7h-.2L42.6 107h21.9L53.7 78.7zM132.4 83.7c8.1 0 14.8 2.7 19.8 7.5V44.6H173v101.3h-20.6l-.2-6.8c-4.5 4.9-11.5 8.6-19.8 8.6-17.7 0-30-14.2-30-31.9s12.4-32.1 30-32.1zm6.3 46.1c8.1 0 14-5.9 14-14.1 0-8.4-6.2-14.2-14-14.2-7.9 0-14.1 5.9-14.1 14.2 0 8.3 5.6 14.1 14.1 14.1zM187.2 85.5H208v6.4h.2c4.7-4.7 10.7-8.1 17.3-8.1 8 0 16.8 2.9 21.1 11.1 6.4-7.7 13.4-11.1 20-11.1 11.1 0 23.5 5.5 23.5 22.4V146h-20.8v-34.1c0-6-3.2-11.3-10.6-11.3-6.6 0-9.4 4.8-9.7 9.4v36h-20.8v-34.1c0-6-3.2-11.3-10.6-11.3-6.9 0-9.7 5.3-9.7 10.1V146h-20.8V85.5zM303.1 145.9H324V85.5h-20.8v60.4zM338.7 85.5h20.8v6.4h.2c4.7-4.7 10.7-8.1 17.3-8.1 11.1 0 26.5 5.5 26.5 22.4V146h-20.8v-34.1c0-6-4.1-11.8-10.8-11.8-6.2 0-12.4 5.8-12.4 11.8V146h-20.8V85.5zM438.6 126.4H472v19.4h-55.6V48.7h22.1v77.7zM508.7 83.7c8.1 0 14.8 2.7 19.8 7.5v-5.8h20.8v60.4h-20.6V139h-.2c-4.5 4.9-11.5 8.6-19.8 8.6-17.7 0-30-14.2-30-31.9s12.3-32 30-32zm6.2 46.1c8.1 0 14-5.9 14-14.1 0-8.4-6.2-14.2-14-14.2-7.9 0-14.1 5.9-14.1 14.2 0 8.3 5.6 14.1 14.1 14.1zM604 147.6c-8.2 0-15.3-3.6-19.8-8.6h-.2v6.8h-20.6V44.6h20.8v46.6c4.9-4.8 11.7-7.5 19.8-7.5 17.7 0 30 14.4 30 32s-12.3 31.9-30 31.9zm-6.2-46.1c-7.8 0-14 5.9-14 14.2 0 8.2 5.9 14.1 14 14.1 8.5 0 14.1-5.9 14.1-14.1 0-8.3-6.2-14.2-14.1-14.2zM689.7 88.4l-6.8 13.7s-7.1-3.8-13.4-3.8c-4.6 0-6.5.8-6.5 3.7 0 3.2 4.2 4.1 9.4 5.8 8 2.5 18.4 6.9 18.4 19.3 0 17.8-16.2 20.6-28.6 20.6-14.6 0-23.3-8.1-23.3-8.1l8.5-14.2s8.2 6.9 15 6.9c2.9 0 6.5-.4 6.5-4.4 0-4.4-6.6-4.7-13.4-8.2-6.1-3.2-12.2-7.4-12.2-16.5 0-12.4 10.5-19.4 26.4-19.4 11.4-.1 20 4.6 20 4.6z"/><circle cx="334.4" cy="34.2" r="11.3"/><circle cx="313.6" cy="8.7" r="6.1"/><circle cx="313.6" cy="63.6" r="10.3"/></svg>
		    		<p><?php esc_attr_e( 'Welcome to AdminLabs! Please follow the next steps to start using the plugin.', 'adminlabs' ) ?></p>
		    	</div>
		    	<div class="steps">
		    		<ol>
		    			<li>
		    				<div class="step">
		    					<div class="icon">
		    						<svg xmlns="http://www.w3.org/2000/svg" widht="90" height="50" fill="#04537b" viewBox="0 0 612 344"><path d="M204 133.8h-76.5V57.2h-51v76.5H0v51h76.5v76.5h51v-76.5H204v-50.9zm255 25.4c43.4 0 76.5-33.1 76.5-76.5S502.4 6.2 459 6.2c-7.6 0-15.3 2.6-23 2.6 15.3 22.9 23 45.9 23 73.9s-7.6 51-23 74c7.7 0 15.4 2.5 23 2.5zm-127.5 0c43.4 0 76.5-33.1 76.5-76.5S374.9 6.2 331.5 6.2 255 39.4 255 82.8s33.1 76.4 76.5 76.4zm168.3 56.2c20.4 17.9 35.7 43.4 35.7 71.4v51H612v-51c0-38.3-61.2-63.8-112.2-71.4zm-168.3-5.2c-51 0-153 25.5-153 76.5v51h306v-51c0-50.9-102-76.5-153-76.5z"/></svg>
		    					</div>
		    					<div class="content">
		    						<h3><?php esc_attr_e( 'Create account', 'adminlabs' ) ?></h3>
		    						<p><?php esc_attr_e( "You'll need AdminLabs account to use this plugin. If you already have account, you are good to go. Otherwise you should create account by clicking the button below.", 'adminlabs' ) ?></p>
		    						<p><a href="https://dashboard.adminlabs.com/auth/register/feature/website-monitoring" target="_blank" class="button"><?php esc_attr_e( 'Create account', 'adminlabs' ) ?></a></p>
		    					</div>
		    				</div>
		    			</li>
		    			<li>
		    				<div class="step">
		    					<div class="icon">
		    						<svg width="44" height="44" fill="#00517b" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 516.4 516.4"><path d="M353.8 0c-89.9 0-162.6 72.7-162.6 162.6 0 19.1 3.8 38.2 9.6 57.4L0 420.8v95.6h95.6V459H153v-57.4h57.4l86.1-86.1c17.2 5.7 36.3 9.6 57.4 9.6 89.9 0 162.6-72.7 162.6-162.6S443.7 0 353.8 0zm47.8 172.1c-32.5 0-57.4-24.9-57.4-57.4s24.9-57.4 57.4-57.4S459 82.2 459 114.8s-24.9 57.3-57.4 57.3z"/></svg>
		    					</div>
		    					<div class="content">
		    						<h3><?php esc_attr_e( 'Enter your API key', 'adminlabs' ) ?></h3>
		    						<p><?php echo wp_kses( 'AdminLabs WordPress plugin uses the official AdminLabs REST API. Generate and copy your API credentials on <a href="https://dashboard.adminlabs.com/settings/api" target="_blank">AdminLabs dashboard</a>', 'adminlabs' ) ?>.</p>
		    						<p><a href="options-general.php?page=adminlabs" class="button"><?php esc_attr_e( 'Enter API credentials', 'adminlabs' ) ?></a></p>
		    					</div>
		    				</div>
		    			</li>
		    			<li>
		    				<div class="step">
		    					<div class="icon">
		    						<svg width="44" height="44" fill="#00517b" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 932.2 932.2"><path d="M61.2 341.5c4.9 16.8 11.7 33 20.3 48.2L57 420.6c-8 10.1-7.1 24.5 1.9 33.6l42.2 42.2c9.1 9.1 23.5 9.9 33.6 1.9l30.7-24.3c15.8 9.1 32.6 16.2 50.1 21.2l4.6 39.5c1.5 12.8 12.3 22.4 25.1 22.4h59.7c12.8 0 23.6-9.6 25.1-22.4l4.4-38.1c18.8-4.9 36.8-12.2 53.7-21.7l29.7 23.5c10.1 8 24.5 7.1 33.6-1.9l42.2-42.2c9.1-9.1 9.9-23.5 1.9-33.6l-23.1-29.3c9.6-16.6 17.1-34.3 22.1-52.8l35.6-4.1c12.8-1.5 22.4-12.3 22.4-25.1v-59.7c0-12.8-9.6-23.6-22.4-25.1l-35.1-4.1c-4.8-18.3-12-35.8-21.2-52.2l21.6-27.3c8-10.1 7.1-24.5-1.9-33.6l-42.1-42.1c-9.1-9.1-23.5-9.9-33.6-1.9l-26.5 21c-17.2-10.1-35.6-17.8-54.9-23l-4-34.3c-1.5-12.8-12.3-22.4-25.1-22.4h-59.7c-12.8 0-23.6 9.6-25.1 22.4l-4 34.3c-19.8 5.3-38.7 13.3-56.3 23.8l-27.5-21.8c-10.1-8-24.5-7.1-33.6 1.9l-42.2 42.2c-9.1 9.1-9.9 23.5-1.9 33.6l23 29.1c-9.2 16.6-16.2 34.3-20.8 52.7l-36.8 4.2C9.6 228.6 0 239.4 0 252.2v59.7c0 12.8 9.6 23.6 22.4 25.1l38.8 4.5zM277.5 180c54.4 0 98.7 44.3 98.7 98.7s-44.3 98.7-98.7 98.7c-54.4 0-98.7-44.3-98.7-98.7s44.3-98.7 98.7-98.7z"/><path d="M867.7 356.2l-31.5-26.6c-9.7-8.2-24-7.8-33.2.9l-17.4 16.3c-14.7-7.1-30.3-12.1-46.4-15l-4.9-24c-2.5-12.4-14-21-26.6-20l-41.1 3.5c-12.6 1.1-22.5 11.4-22.9 24.1l-.8 24.4c-15.8 5.7-30.7 13.5-44.3 23.3l-20.8-13.8c-10.6-7-24.7-5-32.9 4.7l-26.6 31.7c-8.2 9.7-7.8 24 .9 33.2l18.2 19.4c-6.3 14.2-10.8 29.1-13.4 44.4l-26 5.3c-12.4 2.5-21 14-20 26.6l3.5 41.1c1.1 12.6 11.4 22.5 24.1 22.9l28.1.9c5.1 13.4 11.8 26.1 19.9 38l-15.7 23.7c-7 10.6-5 24.7 4.7 32.9l31.5 26.6c9.7 8.2 24 7.8 33.2-.9l20.6-19.3c13.5 6.3 27.7 11 42.3 13.8l5.7 28.2c2.5 12.4 14 21 26.6 20l41.1-3.5c12.6-1.1 22.5-11.4 22.9-24.1l.9-27.6c15-5.3 29.2-12.5 42.3-21.4l22.7 15c10.6 7 24.7 5 32.9-4.7l26.6-31.5c8.2-9.7 7.8-24-.9-33.2l-18.3-19.4c6.7-14.2 11.6-29.2 14.4-44.6l25-5.1c12.4-2.5 21-14 20-26.6l-3.5-41.1c-1.1-12.6-11.4-22.5-24.1-22.9l-25.1-.8c-5.2-14.6-12.2-28.4-20.9-41.2l13.7-20.6c7.2-10.6 5.2-24.8-4.5-33zM712.8 593.8c-44.4 3.8-83.6-29.3-87.3-73.7-3.8-44.4 29.3-83.6 73.7-87.3 44.4-3.8 83.6 29.3 87.3 73.7 3.8 44.4-29.3 83.6-73.7 87.3zM205 704.4c-12.6 1.3-22.3 11.9-22.4 24.6l-.3 25.3c-.2 12.7 9.2 23.5 21.8 25.1l18.6 2.4c3.1 11.3 7.5 22.1 13.2 32.3l-12 14.8c-8 9.9-7.4 24.1 1.5 33.2l17.7 18.1c8.9 9.1 23.1 10.1 33.2 2.3l14.9-11.5c10.5 6.2 21.6 11.1 33.2 14.5l2 19.2c1.3 12.6 11.9 22.3 24.6 22.4l25.3.3c12.7.2 23.5-9.2 25.1-21.8l2.3-18.2c12.6-3.1 24.6-7.8 36-14l14 11.3c9.9 8 24.1 7.4 33.2-1.5l18.1-17.7c9.1-8.9 10.1-23.1 2.3-33.2l-10.7-13.9c6.6-11 11.7-22.7 15.2-35l16.6-1.7c12.6-1.3 22.3-11.9 22.4-24.6l.3-25.3c.2-12.7-9.2-23.5-21.8-25.1l-16.2-2.1c-3.1-12.2-7.7-24-13.7-35l10.1-12.4c8-9.9 7.4-24.1-1.5-33.2l-17.7-18.1c-8.9-9.1-23.1-10.1-33.2-2.3l-12.1 9.3c-11.4-6.9-23.6-12.2-36.4-15.8l-1.6-15.7c-1.3-12.6-11.9-22.3-24.6-22.4l-25.3-.3c-12.7-.2-23.5 9.2-25.1 21.8l-2 15.6c-13.2 3.4-25.9 8.6-37.7 15.4l-12.5-10.2c-9.9-8-24.1-7.4-33.2 1.5l-18.2 17.8c-9.1 8.9-10.1 23.1-2.3 33.2l10.7 13.8c-6.2 11-11.1 22.7-14.3 35l-17.5 1.8zm163.3-28.6c36.3.4 65.4 30.3 65 66.6-.4 36.3-30.3 65.4-66.6 65-36.3-.4-65.4-30.3-65-66.6.4-36.3 30.3-65.4 66.6-65z"/></svg>
		    					</div>
		    					<div class="content">
		    						<h3><?php esc_attr_e( 'Configure', 'adminlabs' ) ?></h3>
		    						<p><?php esc_attr_e( 'AdminLabs WordPress plugin contains various settings, like managing maintenance and adding comments. Have fun!', 'adminlabs' ) ?></p>
		    						<p><a href="options-general.php?page=adminlabs" class="button"><?php esc_attr_e( 'Go to settings', 'adminlabs' ) ?></a></p>
		    					</div>
		    				</div>
		    			</li>
		    		<p>
		    	</p>
		    </div>
		  </div>
			<?php endif;
		} // end notice_onboarding

		/**
		 *  Show API connectivity issue warning
		 *
		 *  @since  1.0.0
		 */
		public function notice_no_api_connection() {
			?>
			<div class="notice notice-warning">
        <p>
        	<?php // Translators: %s is link to settings page.
		    	echo wp_sprintf( wp_kses( 'We can\'t connect to AdminLabs! There might be a temporary problem with our API or you might have added wrong <a href="%s">API credentials</a>. If this problem persist, please contact our support.', 'adminlabs' ), 'options-general.php?page=adminlabs' ); ?>
		    </p>
    	</div>
		<?php } // end notice_no_api_connection

		/**
		 *  Show info message when plugin is reseted succesfully
		 *
		 *  @since  1.0.0
		 */
		public function notice_reset_success() {
			?>
			<div class="notice notice-info is-dismissible">
        <p>
        	<?php // Translators: %s is link to settings page.
		    	esc_attr_e( 'Plugin settings reseted and cache purged!', 'adminlabs' ); ?>
		    </p>
    	</div>
		<?php } // end notice_reset_success
	}

endif;
