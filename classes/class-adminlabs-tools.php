<?php

/**
 * Multipurpose tools for this plugin
 *
 * @Author: Timi Wahalahti
 * @Date:   2017-08-10 22:45:57
 * @Last Modified by:   Timi Wahalahti
 * @Last Modified time: 2017-12-19 14:59:47
 *
 * @package AdminLabs
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AdminLabs_Tools' ) ) :

	/**
	 *  Class for tools
	 *
	 *  @since  1.0.0
	 */
	class AdminLabs_Tools {

		/**
		 *  Check if API settings are saved to databse
		 *
		 *  @since  1.0.0
		 *  @return boolean  true if settings exist, false otherwise
		 */
		public static function check_api_settings_existance() {
			$api_account_id = get_option( 'adminlabs_api_account_id' );
			$api_key = get_option( 'adminlabs_api_key' );

			if ( empty( $api_account_id ) || empty( $api_key )  ) {
				return false;
			}

			return true;
		} // end check_api_settings_existance

		/**
		 *  Save endpoint response to transient and store transient name to option,
		 *  because we need those when resetting plugin
		 *
		 *  @since 0.0.1-alpha
		 *  @param string  $key        key/name for transient
		 *  @param mixed   $value      value to save
		 *  @param integer $expiration how long this transient should exist, in seconds
		 */
		public static function set_transient( $key = null, $value = null, $expiration = 900 ) {
			$transient_keys = get_option( 'adminlabs_transient_keys' );

			if ( set_transient( $key, $value, $expiration ) ) {
				$transient_keys[ $key ] = true;
				update_option( 'adminlabs_transient_keys', $transient_keys );
				return true;
			}

			return false;
		} // end set_transient

		/**
		 *  Remove endpoint response from transient cache
		 *
		 *  @since 0.0.1-alpha
		 *  @param string  $key        key/name for transient
		 */
		public static function delete_transient( $key = null ) {
			$transient_keys = get_option( 'adminlabs_transient_keys' );

			$delete = delete_transient( $key );
			if ( ! $delete ) {
				delete_transient( 'adminlabs_api_response_' . md5( $key ) );
			}

			if ( $delete ) {
				unset( $transient_keys[ $key ] );
				update_option( 'adminlabs_transient_keys', $transient_keys );
				return true;
			}

			return false;
		} // end delete_transient

		/**
		 *  Purge our transient/endpoint response cache
		 *
		 *  @since  1.0.0
		 */
		public static function purge_cache() {
			$transient_keys = get_option( 'adminlabs_transient_keys', array() );

			foreach ( $transient_keys as $transient_key => $value ) {
				$deleted = delete_transient( $transient_key );

				if ( $deleted ) {
					unset( $transient_keys[ $transient_key ] );
				}
			}

			update_option( 'adminlabs_transient_keys', $transient_keys );
		} // end purge_cache

		/**
		 *  Reset whole plugin
		 *
		 *  @since  1.0.0
		 */
		public static function reset() {
			if ( current_user_can( 'manage_options' ) ) {
				delete_option( 'adminlabs_api_account_id' );
				delete_option( 'adminlabs_api_key' );
				delete_option( 'adminlabs_monitors' );

				self::purge_cache();
			}
		} // end reset

		/**
		 *  Count average response time for defined period
		 *
		 *  @since  1.0.0
		 *  @param  array   $scans  detailed scan data from adminlabs
		 *  @param  integer $period for which time period to count average
		 *  @return array          	average response times
		 */
		public static function count_scan_averages( $scans, $period = 30 ) {
			$time = $scans[0]['runTime'];
			$averages = array();

			/**
			 *  Loop throught every single scan and check and check if it fits
			 *  to some batch for average counting. If not, start an new batch
			 */
			foreach ( $scans as $scan ) {
				if ( ( ( $scan['runTime'] - $time ) / 60 ) > $period ) {
					$averages[ $scan['runTime'] ][] = $scan['loadTime'];
					$time = $scan['runTime'];
				} else {
					$averages[ $time ][] = $scan['loadTime'];
				}
			}

			// Loop throught every scan batch and count average for that.
			foreach ( $averages as $key => $loadtimes ) {
				$averages[ $key ] = array_sum( $loadtimes ) / count( $loadtimes );
			}

			return $averages;
		} // end count_scan_averages

		/**
		 *  Round timestamp to defined precision in minutes
		 *
		 *  @since  1.0.0
		 *  @param  string  $timestamp unix timestamp to round
		 *  @param  string  $format    in which format to return the rounded time
		 *  @param  integer $precision rounding precision in minutes
		 *  @return string             rounded time in defined format
		 */
		public static function round_timestamp( $timestamp, $format = 'H:i', $precision = 30 ) {
			$precision = 60 * $precision;
			return self::convert_time_to_user_zone( round( $timestamp / $precision ) * $precision, $format );
		} // end round_timestamp

		/**
		 *  Get difference between two timestamps
		 *
		 *  @since  1.0.0
		 *  @param  string  $timestamp1 unix timestamp to compare
		 *  @param  string  $timestamp2 second unix timestamp to compare
		 *  @return array               array containing difference in hours, minutes and seconds
		 */
		public static function get_time_difference( $timestamp1, $timestamp2 ) {
			$time1 = new DateTime( date( 'Y-m-d H:i:s', $timestamp1 ) );
			$time2 = new DateTime( date( 'Y-m-d H:i:s', $timestamp2 ) );
			$diff = $time1->diff( $time2 );

			return array(
				'hours'			=> ltrim( $diff->format( '%H' ), '0'),
				'minutes'		=> ltrim( $diff->format( '%I' ), '0'),
				'seconds'		=> ltrim( $diff->format( '%s' ), '0'),
			);
		} // end get_time_difference

		/**
		 *  Convert timestamp to user timezone
		 *
		 *  @since  1.0.0
		 *  @param  integer $timestamp Unix timestamp to convert
		 *  @param  string  $format    Return timesamp in this format
		 *  @return string             Timezone cenverted time
		 */
		public static function convert_time_to_user_zone( $timestamp, $format = 'Y-m-d H:i:s' ) {
			$timezone = get_option( 'adminlabs_timezone' );
			$default_timezone = date_default_timezone_get();

			date_default_timezone_set( $timezone );

			$return = date( $format, $timestamp );

			date_default_timezone_set( $default_timezone );

			return $return;
		} // end function convert_time_to_user_zone

		/**
		 *  Validate UUID
		 *  @since  0.0.1-aplha
		 *  @param  string  $uuid String to validate
		 *  @return blloen        Is UUID valid
		 */
		public static function validate_uuid( $uuid = '' ) {
			return preg_match( '/^\{?[a-z0-9]{8}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{4}-[a-z0-9]{12}\}?$/', $uuid );
		} // end function validate_uuid
	}

endif;
