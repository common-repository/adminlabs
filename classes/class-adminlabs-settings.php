<?php
/**
 * Make setting pages for this plugin
 *
 * @Author: Timi Wahalahti
 * @Date:   2017-08-10 18:58:37
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-12-19 14:59:50
 *
 * @package AdminLabs
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AdminLabs_Settings' ) ) :

	/**
	 *  Class that makes the page
	 *
	 *  @since  1.0.0
	 */
	class AdminLabs_Settings {
		/**
		 *  Instance of this class
		 *
		 *  @var resource
		 */
		public static $instance;

		/**
		 *  Instance of AdminLabs_Admin class
		 *
		 *  @var resource
		 */
		private static $admin_instance;

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
				self::$instance = new AdminLabs_Settings();
			}

			return self::$instance;
		} // end init

		/**
		 *  Set variables and place few hooks
		 *
		 *  @since 0.0.1-alpha
		 */
		public function __construct() {
			self::$admin_instance = AdminLabs_Admin::$instance;
			self::$api_instance = new AdminLabs_API();

			// Add link to settings on plugin list.
			add_filter( 'plugin_action_links_adminlabs/adminlabs.php', array( $this, 'add_settings_link_to_plugin_list' ) );

			// Add our settings page to menu and make the actual page.
			add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
			add_action( 'admin_init', array( $this, 'add_setting_sections_and_fields' ) );
		} // end __construct

		/**
		 *  Add link to settings on plugin list
		 *
		 *  @since 0.0.1-alpha
		 *  @param array  $links links to show below plugin name
		 */
		public function add_settings_link_to_plugin_list( $links ) {
			if ( current_user_can( 'manage_options' ) ) {
				$links[] = '<a href="options-general.php?page=adminlabs">' . esc_attr__( 'Settings', 'adminlabs' ) . '</a>';
			}

			return $links;
		}

		/**
		 *  Add our page to admin menu
		 *
		 *  @since 0.0.1-alpha
		 */
		public function add_menu_page() {
			add_options_page(
				esc_attr__( 'AdminLabs', 'adminlabs' ),
				esc_attr__( 'AdminLabs', 'adminlabs' ),
				'manage_options',
				'adminlabs',
				array( $this, 'page_output' )
			);
		} // end add_menu_page

		/**
		 *  Output the settings page
		 *
		 *  @since  1.0.0
		 */
		public function page_output() {
			$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'settings'; // @codingStandardsIgnoreLine ?>
			<div class="wrap">

				<br />
			  <img src="https://www.adminlabs.com/media/adminlabs-logo.png" height="50" />
			  <h2></h2>

			  <h2 class="nav-tab-wrapper">
				<a href="?page=adminlabs&tab=settings" class="nav-tab <?php echo ( 'settings' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Settings', 'adminlabs' ) ?></a>

				<?php if ( self::$api_instance->check_credentials_health() ) : ?>
					<a href="?page=adminlabs&tab=maintenance" class="nav-tab <?php echo ( 'maintenance' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Maintenance', 'adminlabs' ) ?></a>
				<?php endif; ?>

				<a href="?page=adminlabs&tab=bugreport" class="nav-tab <?php echo ( 'bugreport' === $active_tab ) ? 'nav-tab-active' : ''; ?>"><?php esc_attr_e( 'Send bug report', 'adminlabs' ) ?></a>
				</h2>

				<?php if ( 'settings' === $active_tab ) : ?>
					<form method="post" action="options.php">
						<?php settings_fields( 'adminlabs_settings' );
						do_settings_sections( 'adminlabs_settings_api' );
						do_settings_sections( 'adminlabs_settings_monitors' );
						submit_button(); ?>
					</form>
					<p>
						<a href="?page=adminlabs&adminlabs_reset_credentials"><?php esc_attr_e( 'Reset API Credentials', 'adminlabs' ) ?></a>
					</p>
				<?php elseif ( 'maintenance' === $active_tab && self::$api_instance->check_credentials_health() ) :
					require plugin_dir_path( __DIR__ ) . 'views/admin/maintenance.php';
				elseif ( 'bugreport' === $active_tab ) :
					require plugin_dir_path( __DIR__ ) . 'views/admin/bugreport.php';
				endif; ?>
			</div>
		<?php } // end page_output

		/**
		 *  Register setting sections and fields.
		 *
		 *  @since 0.0.1-alpha
		 */
		public function add_setting_sections_and_fields() {
			register_setting( 'adminlabs_settings', 'adminlabs_api_account_id' );
			register_setting( 'adminlabs_settings', 'adminlabs_api_key' );
			register_setting( 'adminlabs_settings', 'adminlabs_user' );
			register_setting( 'adminlabs_settings', 'adminlabs_monitors', array( $this, 'sanitize_callback_no_empty_array_values' ) );

			add_settings_section(
				'adminlabs_settings_api',
				esc_attr__( 'API Credentials', 'adminlabs' ),
				array( $this, 'section_callback_api' ),
				'adminlabs_settings_api'
			);

			add_settings_field(
				'adminlabs_api_account_id',
				esc_attr__( 'Account ID', 'adminlabs' ),
				array( $this, 'field_callback_api_account_id' ),
				'adminlabs_settings_api',
				'adminlabs_settings_api'
			);

			add_settings_field(
				'adminlabs_api_key',
				esc_attr__( 'API Key', 'adminlabs' ),
				array( $this, 'field_callback_api_key' ),
				'adminlabs_settings_api',
				'adminlabs_settings_api'
			);

			// Show monitor selection only when we can connect to API.
			if ( self::$api_instance->check_credentials_health() ) {
				add_settings_field(
					'adminlabs_api_user',
					esc_attr__( 'User', 'adminlabs' ),
					array( $this, 'field_callback_user' ),
					'adminlabs_settings_api',
					'adminlabs_settings_api'
				);

				add_settings_section(
					'adminlabs_settings_monitors',
					esc_attr__( 'Monitor', 'adminlabs' ),
					array( $this, 'section_callback_monitors' ),
					'adminlabs_settings_monitors'
				);

				add_settings_field(
					'adminlabs_monitors',
					esc_attr__( 'Monitor', 'adminlabs' ),
					array( $this, 'field_callback_monitors' ),
					'adminlabs_settings_monitors',
					'adminlabs_settings_monitors'
				);
			}
		} // end add_setting_sections_and_fields

		/**
		 *  So, there's really no good reason to document all these field callbacks
		 *
		 *  @codingStandardsIgnoreStart
		 */
		public function section_callback_api() {
			echo '<p>';
			echo wp_kses( 'AdminLabs WordPress plugin uses the official AdminLabs REST API. Generate and copy your API credentials on <a href="https://dashboard.adminlabs.com/settings/api" target="_blank">AdminLabs dashboard</a>.', 'adminlabs' );
			echo '</p>';
		}

		public function field_callback_api_account_id() {
			$value = get_option( 'adminlabs_api_account_id' );
			$value = ( ! empty( $value ) ) ? $value : '';
			$readonly = ( ! empty( $value ) ) ? ' readonly' : '';

			echo '<input type="text" id="adminlabs_api_account_id" name="adminlabs_api_account_id" value="' . esc_attr( $value ) . '"' . $readonly . '/>';
		}

		public function field_callback_api_key() {
			$value = get_option( 'adminlabs_api_key' );
			$value = ( ! empty( $value ) ) ? $value : '';
			$readonly = ( ! empty( $value ) ) ? ' readonly' : '';

			echo '<input type="password" id="adminlabs_api_key" name="adminlabs_api_key" value="' . esc_attr( $value ) . '"' . $readonly . '/>';
		}

		public function field_callback_user() {
			$value = get_option( 'adminlabs_user' );
			$all_users = self::$api_instance->call_api( 'users', null, true, 60 );

			if ( is_array( $all_users ) ) {
				echo '<select id="adminlabs_user" name="adminlabs_user">';

				foreach ( $all_users as $user ) {
					$selected = ( $user['id'] === $value ) ? ' selected' : '';
					echo '<option value="' . esc_attr( $user['id'] ) . '"' . $selected . '>' . esc_html( $user['name'] ) . '</option>';
				}

				echo '</select>';
				echo '<p class="description">' . esc_attr__( 'Comments to outages and maintenances are sent in name of this user.', 'adminlabs' ) . '</p>';
			}
		} // end function field_callback_monitors

		public function section_callback_monitors() {
			echo '<p>';
			esc_html_e( 'Select the monitor to show in dashboard.', 'adminlabs' );
			echo '</p>';
		}

		public function field_callback_monitors() {
			$value = get_option( 'adminlabs_monitors' );
			$all_monitors = self::$api_instance->call_api( 'monitors', null, true, 60 );

			if ( ! $all_monitors || ! is_array( $all_monitors ) ) {
				echo '<p>';
				esc_attr_e( "You don't have any monitors yet.", 'adminlabs' );
				echo '<br /><a href="https://dashboard.adminlabs.com/monitor/list#edit" target="_blank">';
				esc_attr_e( 'Add your first one!', 'adminlabs' );
				echo '</a></p>';
			} else {
				echo '<select id="adminlabs_monitors" name="adminlabs_monitors[]">';
				echo '<option value="">' . esc_attr__( 'Select monitor', 'adminlabs' ) . '</option>';

				foreach ( $all_monitors as $monitor ) {
					if ( 'enabled' !== $monitor['state'] ) {
						continue;
					}

					$option_value = esc_attr( $monitor['id'] ) . '|' . esc_attr( $monitor['name'] ) . '|' . esc_attr( $monitor['interval'] );
					$selected = ( in_array( $option_value, $value ) ) ? ' selected' : '';
					echo '<option value="' . esc_attr( $option_value ) . '"' . $selected . '>' . esc_html( $monitor['name'] ) . '</option>';
				}

				echo '</select>';
			}
		}
		// @codingStandardsIgnoreEnd

		/**
		 *  Remove empty values from array
		 *
		 *  @since  1.0.0
		 *  @param  array  $array original array
		 *  @return array         array without empty values
		 */
		public function sanitize_callback_no_empty_array_values( $array = array() ) {
			if ( ! is_array( $array ) ) {
				return array();
			}

			return array_filter( $array );
		} // end sanitize_callback_no_empty_array_values
	}

endif;
