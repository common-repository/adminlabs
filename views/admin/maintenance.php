<?php

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$monitors = get_option( 'adminlabs_monitors' );
foreach ( $monitors as $key => $monitor ) {
	$monitor_details = explode( '|', $monitor );
	$monitors[ $key ] = array(
		'id'				=> $monitor_details[0],
		'name'			=> $monitor_details[1],
		'interval'	=> $monitor_details[2],
	);
}

$default_monitor = reset( $monitors );

$maintenances = self::$api_instance->call_api( "monitors/{$default_monitor['id']}/maintenance", null, true ); ?>

<h2><?php esc_attr_e( 'Maintenance', 'adminlabs' ) ?></h2>

<?php if ( ! empty( $maintenances ) ) : ?>
	<div class="adminlabs-maintenance-list">
		<div class="head">
			<div class="col time"><?php esc_attr_e( 'Start', 'adminlabs' ) ?></div>
			<div class="col time"><?php esc_attr_e( 'End', 'adminlabs' ) ?></div>
			<div class="col title"><?php esc_attr_e( 'Title', 'adminlabs' ) ?></div>
			<div class="col desc"><?php esc_attr_e( 'Description', 'adminlabs' ) ?></div>
			<div class="col actions"><?php esc_attr_e( '', 'adminlabs' ) ?></div>
		</div>
		<?php foreach ( $maintenances as $maintenance ) : ?>
			<div class="adminlabs-maintenance" data-monitorid="<?php echo $default_monitor['id'] ?>" data-maintennaceid="<?php echo $maintenance['id'] ?>">
				<div class="col time"><p><?php echo AdminLabs_Tools::convert_time_to_user_zone( $maintenance['start'], 'j.n.Y H:i' ) ?></p></div>
				<div class="col time"><p><?php echo AdminLabs_Tools::convert_time_to_user_zone( $maintenance['end'], 'j.n.Y H:i' ) ?></p></div>
				<div class="col title"><p><?php echo $maintenance['title'] ?></p></div>
				<div class="col desc"><p><?php echo $maintenance['description'] ?></p></div>
				<div class="col actions">
					<p><?php if ( ! $maintenance['isActive'] ) : ?><span class="dashicons dashicons-trash"></span><?php endif; ?>
					</p>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<div class="adminlabs-maintenance-new" data-monitorid="<?php echo $default_monitor['id'] ?>">
	<h3><?php esc_attr_e( 'New maintenance', 'adminlabs' ) ?></h3>

	<div class="row">
		<label for="start"><?php esc_attr_e( 'Start', 'adminlabs' ) ?></label>
		<input type="text" name="start" required />
	</div>

	<div class="row">
		<label for="end"><?php esc_attr_e( 'End', 'adminlabs' ) ?></label>
		<input type="text" name="end" required />
	</div>

	<div class="row">
		<label for="title"><?php esc_attr_e( 'Title', 'adminlabs' ) ?></label>
		<input type="text" name="title" required />
	</div>

	<div class="row desc">
		<label for="description"><?php esc_attr_e( 'Description', 'adminlabs' ) ?></label>
		<textarea name="description"></textarea>
	</div>

	<div class="row">
		<button class="button button-primary"><?php esc_attr_e( 'Schedule maintenance', 'adminlabs' ) ?></button>
	</div>
</div>
