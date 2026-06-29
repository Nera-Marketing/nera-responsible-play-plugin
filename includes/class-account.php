<?php
/**
 * My Account menu signpost — inserts a "Need support?" item before the Logout link,
 * and filters its endpoint URL to the Help & support page.
 *
 * Assumes WooCommerce core navigation.php (no theme override — verified in
 * nera-competitions-standard/inc/woocommerce.php ~L1705 which uses
 * woocommerce_account_menu_items and wc_get_account_endpoint_url).
 *
 * Wave 2: implement add_menu_item() and endpoint_url().
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Account
 */
class Nera_RP_Account {

	const ENDPOINT_KEY = 'nera-support';

	/**
	 * Register hooks.
	 */
	public static function init() {
		// Wave 2: implement
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ), 20 );
		add_filter( 'woocommerce_get_endpoint_url', array( __CLASS__, 'endpoint_url' ), 10, 4 );
	}

	/**
	 * Insert "Need support?" before the customer-logout item.
	 *
	 * @param array $items Existing menu items keyed by endpoint slug.
	 * @return array
	 */
	public static function add_menu_item( array $items ) {
		if ( ! Nera_RP_Settings::is_signpost_enabled( 'account' ) ) {
			return $items;
		}
		if ( Nera_RP_Settings::help_page_id() < 1 ) {
			return $items;
		}

		$new_item = array( self::ENDPOINT_KEY => __( 'Need support?', 'nera-responsible-play-plugin' ) );
		$result   = array();
		$inserted = false;

		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) {
				$result   = array_merge( $result, $new_item );
				$inserted = true;
			}
			$result[ $key ] = $label;
		}

		if ( ! $inserted ) {
			$result = array_merge( $result, $new_item );
		}

		return $result;
	}

	/**
	 * Override the URL for the nera-support pseudo-endpoint to point at the Help page.
	 *
	 * Without this filter WooCommerce builds a 404-producing /my-account/nera-support/ URL.
	 *
	 * @param string $url        Current endpoint URL.
	 * @param string $endpoint   Endpoint slug.
	 * @param mixed  $value      Endpoint value (unused).
	 * @param string $permalink  My Account permalink (unused).
	 * @return string
	 */
	public static function endpoint_url( $url, $endpoint, $value, $permalink ) {
		if ( self::ENDPOINT_KEY !== $endpoint ) {
			return $url;
		}

		$page_id = Nera_RP_Settings::help_page_id();
		if ( $page_id < 1 ) {
			return $url;
		}

		$permalink_url = get_permalink( $page_id );
		if ( ! $permalink_url ) {
			return $url;
		}

		return (string) esc_url( $permalink_url );
	}
}
