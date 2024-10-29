<?php

/**
 * Dashboard widget
 *
 * @Author: Timi Wahalahti
 * @Date:   2017-08-10 22:45:57
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-12-19 16:44:06
 *
 * @package AdminLabs
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AdminLabs_Widget' ) ) :

	class AdminLabs_Widget {
		public static $instance;

		protected static $graph_interval_settings;

		private static $api_instance;

		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new AdminLabs_Widget();
			}

			return self::$instance;
		} // end init

		public function __construct() {
			self::$api_instance =  new AdminLabs_API();

			self::$graph_interval_settings = array(
				'1' => array(
					'avg_period'		=> 30,
					'series_count'	=> 19,
				),
				'2' => array(
					'avg_period'		=> 30,
					'series_count'	=> 19,
				),
				'3' => array(
					'avg_period'		=> 30,
					'series_count'	=> 19,
				),
				'4' => array(
					'avg_period'		=> 30,
					'series_count'	=> 19,
				),
				'5' => array(
					'avg_period'		=> 30,
					'series_count'	=> 19,
				),
				'10' => array(
					'avg_period'		=> 30,
					'series_count'	=> 19,
				),
				'15' => array(
					'avg_period'		=> 30,
					'series_count'	=> 19,
				),
				'30' => array(
					'avg_period'		=> 60,
					'series_count'	=> 25,
				),
				'60' => array(
					'avg_period'		=> 60,
					'series_count'	=> 25,
				),
			);

			add_action( 'wp_dashboard_setup', array( $this, 'setup_dashboard_widget' ) );
		} // end __construct

		public function setup_dashboard_widget() {
			if ( self::$api_instance->check_credentials_health() ) {
				wp_add_dashboard_widget( 'adminlabs', esc_attr__( 'AdminLabs', 'adminlabs' ), array( $this, 'show_dashboard_widget' ) );
			}
		} // end setup_dashboard_widget

		public function show_dashboard_widget() {
			$account_details = self::$api_instance->call_api( 'account', null, true );

			$monitors = get_option( 'adminlabs_monitors' );
			if ( ! empty( $monitors ) ) {
				foreach ( $monitors as $key => $monitor ) {
					$monitor_details = explode( '|', $monitor );
					$monitors[ $key ] = array(
						'id'				=> $monitor_details[0],
						'name'			=> $monitor_details[1],
						'interval'	=> $monitor_details[2],
					);
				}

				$default_monitor = reset( $monitors );
				$default_monitor_settings = self::$graph_interval_settings[ $default_monitor['interval'] ];

				$scans = self::$api_instance->call_api( "monitors/{$default_monitor['id']}/scans", null, true );
				$scans = AdminLabs_Tools::count_scan_averages( $scans, $default_monitor_settings['avg_period'] );
				$scans = array_slice( $scans, -$default_monitor_settings['series_count'], $default_monitor_settings['series_count'], true );

				$outages = self::$api_instance->call_api( "monitors/{$default_monitor['id']}/outages/", null, true );
			}

			if ( $account_details['funds'] < 2 ) : ?>
				<div class="adminlabs-notice adminlabs-warning">
					<p><?php echo wp_sprintf( wp_kses( 'Your account balance is below 2$ warning, <a href="%s">transfer more funds</a> to keep monitors running.', 'adminlabs' ), 'https://dashboard.adminlabs.com/settings/billing' ) ?></p>
				</div>
			<?php endif; ?>

			<div class="adminlabs-account-information">
				<div class="monitor"><div class="inner"><p><?php echo wp_sprintf( wp_kses( '<b>Monitor:</b> %s', 'adminlabs' ), $default_monitor['name'] ) ?></p></div></div>
				<div class="funds"><div class="inner"><p><?php echo wp_sprintf( wp_kses( '<b>Account balance:</b> %s$', 'adminlabs' ), round( $account_details['funds'], 2 ) ) ?></p></div></div>
			</div>

			<?php if ( empty( $monitors ) ) {
				echo wp_sprintf( wp_kses( 'Set default monitor in plugin <a href="%s">settings</a> to use this widget :)', 'adminlabs' ), 'options-general.php?page=adminlabs' );
				return;
			} ?>

			<div class="adminlabs-chart">
				<h3><?php esc_attr_e( 'Average load time (ms)', 'adminlabs' ); ?></h3>
				<div class="adminlabs-chart-chartist"></div>
				<script>
					var chart_labels = [
				  	<?php foreach ( $scans as $time => $scan ) {
				  		$human = AdminLabs_Tools::round_timestamp( $time );
				    	echo "'{$human}',";
				    } ?>
				  ];

				  var chart_series = [
				    [<?php foreach ( $scans as $time => $scan ) {
				    	$scan = str_replace( ',', '.', $scan );
				    	$start_human = AdminLabs_Tools::round_timestamp( $time, 'H:i' );
				    	$end_human = AdminLabs_Tools::round_timestamp( strtotime( '+30 min', $time ), 'H:i' );
				    	echo "{meta: '{$start_human} - {$end_human} avg', value: {$scan}},";
				    } ?>],
				  ];

				  var chart_low = <?php echo str_replace( ',', '.', min( $scans )-50 ) ?>;
				  var chart_high = <?php echo str_replace( ',', '.', max( $scans )+50 ) ?>;
				</script>
			</div>

			<?php if ( ! empty( $outages ) ) :
				$outages = array_slice( array_reverse( $outages ), 0, 3); ?>
				<div class="adminlabs-outages">
					<h3><?php esc_attr_e( 'Latest outages', 'adminlabs' ); ?></h3>

					<div class="adminlabs-outages-list">
						<?php foreach ( $outages as $outage ) :
							$lasted = AdminLabs_Tools::get_time_difference( $outage['started'], $outage['ended'] ); ?>
							<div class="adminlabs-outage" data-monitorid="<?php echo $default_monitor['id'] ?>" data-outageid="<?php echo $outage['id'] ?>">
								<div class="details">
									<p class="date-wrapper">
										<span class="date"><?php echo AdminLabs_Tools::convert_time_to_user_zone( $outage['started'], 'j.n.Y H:i:s' ) ?> - <?php echo AdminLabs_Tools::convert_time_to_user_zone( $outage['ended'], 'H:i:s' ) ?></span>

										<span class="lasted">
											<?php if ( ! empty( $lasted['hours'] ) ) {
												echo wp_sprintf( wp_kses( '%s hours', 'adminlabd' ), $lasted['hours'] );
											} ?>

											<?php if ( ! empty( $lasted['minutes'] ) ) {
												echo wp_sprintf( wp_kses( '%s mins', 'adminlabd' ), $lasted['minutes'] );
											} ?>

											<?php if ( ! empty( $lasted['seconds'] ) ) {
												echo wp_sprintf( wp_kses( '%s secs', 'adminlabd' ), $lasted['seconds'] );
											} ?>
										</span>
									</p>
									<p class="comments-link-wrapper"><a href="#"><?php echo ( ! empty( $outage['comments'] ) ) ? wp_sprintf( wp_kses( '+ Add/view comments (%s)', 'adminlabs' ), count( $outage['comments'] ) ) : esc_attr__( '+ Add comments', 'adminlabs' ) ?></a></p>
								</div>

								<?php if ( ! empty( $outage['comments'] ) ) : ?>
									<div class="adminlabs-comments">
										<h4><?php esc_attr_e( 'Comments', 'adminlabs' ); ?></h4>
										<div class="adminlabs-comments-list">
											<?php foreach ( $outage['comments'] as $comment ) : ?>
												<div class="adminlabs-comment" data-commentid="<?php echo esc_attr( $comment['id'] ) ?>">
													<span class="dashicons dashicons-trash"></span>
													<p><?php echo esc_textarea( $comment['comment'] ) ?></p>
													<p class="posted"><?php echo AdminLabs_Tools::convert_time_to_user_zone( $comment['posted'], 'j.n.Y H:i:s' ) ?></p>
												</div>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
								<div class="adminlabs-comment-new">
									<h4><?php esc_attr_e( 'New comment', 'adminlabs' ); ?></h4>
									<textarea name="comment"></textarea>
									<button class="button button-primary"><?php esc_attr_e( 'Post comment', 'adminlabs' ) ?></button>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endif; ?>
		<?php } // end show_dashboard_widget
	}

endif;
