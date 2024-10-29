<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( current_user_can( 'manage_options' ) && isset( $_POST['adminlabs-sendbugreport'] ) ) :

	if ( isset( $_POST['adminlabs_bugreport_send'] ) && wp_verify_nonce( $_POST['adminlabs_bugreport_send'], 'adminlabs_bugreport' ) ) :
		global $wp_version;

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins = get_plugins();
		$theme = wp_get_theme();

		$message = 'Name: ' . sanitize_text_field( $_POST['name'] ) . "\r\n";
		$message .= 'Email: ' . sanitize_text_field( $_POST['email'] ) . "\r\n\r\n";
		$message .= sanitize_textarea_field( $_POST['message'] ) . "\r\n\r\n";
		$message .= "WP: {$wp_version}\r\n";
		$message .= 'PHP: ' . phpversion() . "\r\n";
		$message .= 'Timezone: ' . get_option( 'timezone_string' ) . " / " . get_option( 'gmt_offset' ) . "\r\n";
		$message .= 'Account ID: ' . get_option( 'adminlabs_api_key' ) . "\r\n";
		$message .= 'API health: ' . get_transient( 'adminlabs_api_credentials_health' ) . "\r\n";
		$message .= 'Theme: ' . $theme->get( 'Name' ) . ' (' . $theme->get( 'Version' ) . ")\r\n";
		$message .= 'Plugins:' . "\r\n";

		foreach ( $plugins as $plugin => $data ) {
			$message .= "{$plugin}: " . $data['Version'] . "\r\n";
		}

		wp_mail( 'timi@adminlabs.com', 'WP Plugin bug report', $message ); ?>

		<div class="notice notice-info">
	    <p>
	    	<?php echo esc_attr_e( 'Thank you! Bug report has been sent and we will look into it and contact you if needed.', 'adminlabs' ); ?>
	    </p>
		</div>
	<?php endif; ?>
<?php endif; ?>

<h2><?php esc_attr_e( 'Send bug report', 'adminlabs' ) ?></h2>

<p><b><?php esc_attr_e( 'Oh snap! Found a bug?', 'adminlabs' ) ?></b><br />
<?php esc_attr_e( 'Please report it to use so we can give it a look, fix issues and improve this plugin.', 'adminlabs' ) ?></p>

<form method="post">
	<label for="start"><b><?php esc_attr_e( 'Your name', 'adminlabs' ) ?></b></label><br />
	<input type="text" name="name" placeholder="<?php _e( 'Your name', 'adminlabs' ) ?>" size="50" /><br /><br />

	<label for="start"><b><?php esc_attr_e( 'Your email address', 'adminlabs' ) ?></b></label><br />
	<input type="email" name="email" placeholder="<?php _e( 'Your email address', 'adminlabs' ) ?>" size="50" /><br /><br />

	<label for="start"><b><?php esc_attr_e( 'Bug report / free message', 'adminlabs' ) ?></b></label><br />
	<textarea name="message" placeholder="<?php _e( 'Bug report / free message', 'adminlabs' ) ?>" rows="6" style="width:50%;"></textarea><br />

	<input type="hidden" name="adminlabs-sendbugreport" />
	<?php wp_nonce_field( 'adminlabs_bugreport', 'adminlabs_bugreport_send' ); ?>
	<button class="button button-primary"><?php esc_attr_e( 'Send', 'adminlabs' ) ?></button>
	<p><i><?php esc_attr_e( 'To help solving your problem, we will send also your account id, WP version, plugin list and php version.', 'adminlabs' ) ?></i></p>
</form>
