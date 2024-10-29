<?php

/**
 * Our custom REST API endpoints
 *
 * @Author: Timi Wahalahti
 * @Date:   2017-08-10 22:45:57
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-12-19 14:59:53
 *
 * @package AdminLabs
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AdminLabs_REST' ) ) :

	/**
	 *  Class for all this
	 *
	 *  @since  1.0.0
	 */
	class AdminLabs_REST {
		/**
		 *  Instance of this class
		 *
		 *  @var resource
		 */
		public static $instance;

		/**
		 *  Instance of AdminLabs_REST class
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
			require_once 'class-adminlabs-api.php';
			require_once 'class-adminlabs-tools.php';

			if ( is_null( self::$instance ) ) {
				self::$instance = new AdminLabs_REST();
			}

			return self::$instance;
		} // end init

		/**
		 *  Set variables and place few hooks
		 *
		 *  @since 0.0.1-alpha
		 */
		public function __construct() {
			self::$api_instance = new AdminLabs_API();
			self::$api_instance->init();

			add_action( 'rest_api_init', function () {
				register_rest_route( 'adminlabs/v1', '/outage/comment', array(
					'methods'							=> WP_REST_Server::CREATABLE,
					'callback'						=> array( $this, 'comment_outage' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					}
				) );
			} );

			add_action( 'rest_api_init', function () {
				register_rest_route( 'adminlabs/v1', '/outage/comment', array(
					'methods'							=> WP_REST_Server::DELETABLE,
					'callback'						=> array( $this, 'delete_comment' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					}
				) );
			} );

			add_action( 'rest_api_init', function () {
				register_rest_route( 'adminlabs/v1', '/maintenance', array(
					'methods'							=> WP_REST_Server::CREATABLE,
					'callback'						=> array( $this, 'add_maintenance' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					}
				) );
			} );

			add_action( 'rest_api_init', function () {
				register_rest_route( 'adminlabs/v1', '/maintenance', array(
					'methods'							=> WP_REST_Server::DELETABLE,
					'callback'						=> array( $this, 'delete_maintenance' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					}
				) );
			} );
		} // end __construct

		public function comment_outage( WP_REST_Request $request ) {
			$monitor_id = sanitize_text_field( $request->get_param( 'monitor_id' ) );
			$outage_id = sanitize_text_field( $request->get_param( 'outage_id' ) );
			$comment = sanitize_textarea_field( $request->get_param( 'comment' ) );
			$flush_endpoint = $request->get_param( 'flush_endpoint' );
			$user_id = get_option( 'adminlabs_user' );

			if ( empty( $monitor_id ) || empty( $outage_id ) || empty( $comment ) || empty( $user_id ) ) {
				return false;
			}

			if ( ! AdminLabs_Tools::validate_uuid( $monitor_id ) || ! AdminLabs_Tools::validate_uuid( $outage_id ) ) {
				return false;
			}

			$body = array(
				'userId'            => $user_id,
				'showOnStatusPage'  => true,
				'comment'           => $comment,
			);

			if ( ! empty( $flush_endpoint ) && false !== mb_strpos( $flush_endpoint, $monitor_id ) ) {
				AdminLabs_Tools::delete_transient( $flush_endpoint );
			}

			return self::$api_instance->call_api_post( "monitors/{$monitor_id}/outages/{$outage_id}/comments", $body );
		} // end comment_outage

		public function delete_comment( WP_REST_Request $request ) {
			$monitor_id = sanitize_text_field( $request->get_param( 'monitor_id' ) );
			$outage_id = sanitize_text_field( $request->get_param( 'outage_id' ) );
			$comment_id = sanitize_text_field( $request->get_param( 'comment_id' ) );
			$flush_endpoint = $request->get_param( 'flush_endpoint' );

			if ( empty( $monitor_id ) || empty( $outage_id ) || empty( $comment_id ) ) {
				return false;
			}

			if ( ! AdminLabs_Tools::validate_uuid( $monitor_id ) || ! AdminLabs_Tools::validate_uuid( $outage_id ) || ! AdminLabs_Tools::validate_uuid( $comment_id ) ) {
				return false;
			}

			if ( ! empty( $flush_endpoint ) && false !== mb_strpos( $flush_endpoint, $monitor_id ) ) {
				AdminLabs_Tools::delete_transient( $flush_endpoint );
			}

			return self::$api_instance->call_api_delete( "monitors/{$monitor_id}/outages/{$outage_id}/comments/{$comment_id}" );
		} // end delete_comment

		public function add_maintenance( WP_REST_Request $request ) {
			$monitor_id = sanitize_text_field( $request->get_param( 'monitor_id' ) );
			$start = $request->get_param( 'start' );
			$end = $request->get_param( 'end' );
			$title = sanitize_text_field( $request->get_param( 'title' ) );
			$description = sanitize_textarea_field( $request->get_param( 'description' ) );
			$flush_endpoint = $request->get_param( 'flush_endpoint' );

			if ( empty( $monitor_id ) || empty( $start ) || empty( $end ) || empty( $title ) ) {
				return false;
			}

			if ( ! AdminLabs_Tools::validate_uuid( $monitor_id ) ) {
				return false;
			}

			$body = array(
				'start'           => strtotime( $start ),
				'end'  						=> strtotime( $end ),
				'title'           => $title,
				'description'			=> $description,
				'monitors'				=> array(
					$monitor_id,
				),
				'ignoreOutages'		=> true,
			);

			if ( ! empty( $flush_endpoint ) && false !== mb_strpos( $flush_endpoint, $monitor_id ) ) {
				AdminLabs_Tools::delete_transient( $flush_endpoint );
			}

			return self::$api_instance->call_api_post( "monitors/maintenance", $body );
		} // end add_maintenance

		public function delete_maintenance( WP_REST_Request $request ) {
			$monitor_id = sanitize_text_field( $request->get_param( 'monitor_id' ) );
			$maintenance_id = sanitize_text_field( $request->get_param( 'maintenance_id' ) );
			$flush_endpoint = $request->get_param( 'flush_endpoint' );

			if ( empty( $monitor_id ) || empty( $maintenance_id ) ) {
				return false;
			}

			if ( ! AdminLabs_Tools::validate_uuid( $monitor_id ) || ! AdminLabs_Tools::validate_uuid( $maintenance_id ) ) {
				return false;
			}

			if ( ! empty( $flush_endpoint ) && false !== mb_strpos( $flush_endpoint, $monitor_id ) ) {
				AdminLabs_Tools::delete_transient( $flush_endpoint );
			}

			return self::$api_instance->call_api_delete( "monitors/{$monitor_id}/maintenance/{$maintenance_id}" );
		} // end delete_maintenance
	}

endif;
