<?php
/**
 * Enqueue the plugin's front-end stylesheet site-wide.
 *
 * The responsible-play.css is intentionally small (footer strip, directory list,
 * checkout signpost) and is loaded on every front-end page so the footer strip
 * renders without a conditional check here.
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Assets
 */
class Nera_RP_Assets {

	const STYLE_HANDLE = 'nera-rp-styles';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
	}

	/**
	 * Register and enqueue the plugin stylesheet on all front-end pages.
	 * Admin pages are skipped (is_admin() is true during wp_enqueue_scripts only
	 * on block-editor iframes; the action itself does not fire for wp-admin).
	 */
	public static function enqueue() {
		if ( is_admin() ) {
			return;
		}

		wp_register_style(
			self::STYLE_HANDLE,
			NERA_RP_PLUGIN_URL . 'assets/css/responsible-play.css',
			array(),
			NERA_RP_VERSION
		);

		wp_enqueue_style( self::STYLE_HANDLE );
	}
}
