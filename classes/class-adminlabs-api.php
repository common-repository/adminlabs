<?php
/**
 * API connector
 *
 * @Author: Timi Wahalahti
 * @Date:   2017-08-10 22:45:57
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-12-19 14:59:55
 *
 * @package AdminLabs
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AdminLabs_API' ) ) :

	/**
	 *  Class for calling the AdminLabs API
	 *
	 *  @since  1.0.0
	 */
	class AdminLabs_API {
		/**
		 *  Instance of this class
		 *
		 *  @var resource
		 */
		public static $instance;

		/**
		 *  AdminLabs API base url
		 *
		 *  @var string
		 */
		private static $api_base_url = 'https://api.adminlabs.com/v1';

		/**
		 *  Holder for account ID from settings
		 *
		 *  @var string
		 */
		private static $account_id;

		/**
		 *  Holder for API key from settings
		 *
		 *  @var string
		 */
		private static $api_key;

		/**
		 *  Start the magic
		 *
		 *  @since  1.0.0
		 *  @return object  Resource to use this class
		 */
		public static function init() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new AdminLabs_API();
			}

			return self::$instance;
		} // end init

		/**
		 *  Set variables
		 *
		 *  @since 0.0.1-alpha
		 */
		public function __construct() {
			self::$account_id = get_option( 'adminlabs_api_account_id' );
			self::$api_key = get_option( 'adminlabs_api_key' );
		} // end __construct

		/**
		 *  Check that we can connect to API
		 *
		 *  @since  1.0.0
		 *  @return boolean  true if we can connect, false if not
		 */
		public function check_credentials_health() {

			// Check if the health is temporarily cached.
			$health = get_transient( 'adminlabs_api_credentials_health' );

			if ( 'ok' === $health ) {
				return true;
			}

			/**
			 *  Make API call to check if credentials work
			 */
			$api_response = self::$instance->call_api();

			if ( ! $api_response ) {
				// Can't connect. Not healthy.
				delete_transient( 'adminlabs_api_credentials_health' );
				return false;
			} elseif ( $api_response['id'] !== self::$account_id ) {
				// Account ID in response differences from ours in databse. Not healthy.
				delete_transient( 'adminlabs_api_credentials_health' );
				return false;
			}

			// API connection is healthy. Cache result five minutes.
			AdminLabs_Tools::set_transient( 'adminlabs_api_credentials_health', 'ok', 300 );
			return true;
		} // end check_credentials_health

		public function call_api_delete( $endpoint = null ) {
			return self::$instance->call_api( $endpoint, null, false, 0, 'DELETE' );
		} // end call_api_delete

		public function call_api_post( $endpoint = null, $data = null ) {
			return self::$instance->call_api( $endpoint, $data, false, 0, 'POST' );
		} // end call_api_delete

		/**
		 *  Make call to API
		 *
		 *  @since  1.0.0
		 *  @param  string  $endpoint       Which endpoint to call
		 *  @param  mixed 	$data 					data that we send to API as a body, null if no data and make get request
		 *  @param  boolean $cache          true if response should be cached, defaults to false
		 *  @param  integer $cache_lifetime Set cache lifetime, defaults to 15 minutes
		 *  @return mixed                  	Payload from API response if call succesful, false if some error happened
		 */
		public function call_api( $endpoint = 'account', $data = null, $cache = false, $cache_lifetime = 900, $method = 'GET' ) {

			if ( 'POST' === $method || 'DELETE' === $method ) {
				$cache = false;
			}

			// Check that there's no response cached for this endpoint.
			if ( $cache ) {
				$data_from_cache = self::$instance->get_api_response_cache( $endpoint );

				if ( false !== $data_from_cache ) {
					return $data_from_cache;
				}
			}

			// Arguments for API call.
			$args = array(
				'headers'	=> array(
					'Content-Type'	=> 'application/json',
					'User-Agent'		=> 'adminlabs/wordpress;php',
					'account-id'		=> self::$account_id,
					'api-key'				=> self::$api_key,
				),
				//'sslverify'	=> false,
			);

			if ( 'POST' === $method ) {
				$args['body'] = json_encode( $data );
				$response = wp_safe_remote_post( trailingslashit( self::$api_base_url ) . $endpoint, $args );
			} else if ( 'DELETE' === $method ) {
				$args['method'] = 'DELETE';
				$response = wp_safe_remote_request( trailingslashit( self::$api_base_url ) . $endpoint, $args );
			} else {
				$response = wp_safe_remote_get( trailingslashit( self::$api_base_url ) . $endpoint, $args );
			}

			// WP couldn't make the call for some reason, return false as a error.
			if ( is_wp_error( $response ) ) {
				return false;
			}

			// Test that API response code is 200-299 which proves succesfull request.
			$response_code = (int) $response['response']['code'];
			if ( ( $response_code <= 200 && $response_code > 300 ) || $response_code > 300 ) {
				return false;
			}

			// Get request response body and endcode the JSON data.
			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );

			// Response was not valid JSON, return false as a error.
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				return false;
			}

			// If response should be cached, cache it.
			if ( $cache ) {
				self::$instance->set_api_response_cache( $endpoint, $data, $cache_lifetime );
			}

			if ( 'account' === $endpoint ) {
				add_option( 'adminlabs_timezone', $data['timeZone'] );
			}

			return $data;
		} // end call_api

		/**
		 *  Get response for endpoint from cache
		 *
		 *  @since  1.0.0
		 *  @param  string  $endpoint For what endpoint we want response
		 *  @return mixed            	String if there's cached response, false if not
		 */
		private function get_api_response_cache( $endpoint ) {
			return get_transient( 'adminlabs_api_response_' . md5( $endpoint ) );
		} // end get_api_response_cache

		/**
		 *  Save response for endpoint to cache
		 *
		 *  @since 0.0.1-alpha
		 *  @param string  $endpoint For what endpoint to cache response
		 *  @param string  $data     Response data to cache
		 *  @param integer $lifetime How long the response should be cached, defaults to 15 minutes
		 */
		private function set_api_response_cache( $endpoint = null, $data = null, $lifetime = 900 ) {
			if ( ! $endpoint || ! $data ) {
				return;
			}

			AdminLabs_Tools::set_transient( 'adminlabs_api_response_' . md5( $endpoint ), $data, $lifetime );
		} // end set_api_response_cache
	}

endif;
