<?php
/**
 * Account-close signpost — clause 1.5.
 *
 * Adds a WooCommerce 'notice' type toast BEFORE the theme's own "account deleted"
 * success toast when a customer lands on the homepage after closing their account
 * (?nera_account_closed=1, logged-out).
 *
 * Load-order dependency (confirmed in nera-competitions-standard/inc/woocommerce.php):
 *   - pri 0 : nera_handle_deactivate_account_request (deletes user, redirects, exit)
 *   - pri 1 : nera_account_closed_flash_notice (adds WC success notice, redirects, exit)
 *   - pri 1 : THIS CALLBACK (adds support notice, returns — NO redirect, NO exit)
 *
 * This plugin loads before the theme on plugins_loaded, so at equal priority 1 our
 * callback runs first, adds its notice, then returns.  The theme's pri-1 flash handler
 * adds its notice and exits.  Both notices persist in the WC session and are drained as
 * toasts on the clean homepage by nera_output_toast_data (wp_footer, pri 999).
 *
 * Toast HTML pipeline: theme's wp_kses_post() call in nera_output_toast_data preserves
 * the allowed tags used by render_directory(['notice'=>true]): <a>, <ul>, <li>,
 * <strong>, <br>.  If the toast renderer strips HTML to plain text, links degrade
 * gracefully — service names + phone numbers remain readable.
 *
 * Wave 2: implement maybe_add_support_notice().
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Account_Close
 */
class Nera_RP_Account_Close {

	/**
	 * Register hooks.
	 *
	 * Priority 1 — same as the theme's flash handler so our notice is added first.
	 * Do NOT use priority 0 (that is the account-deletion handler which calls exit).
	 */
	public static function init() {
		// Wave 2: implement
		add_action( 'template_redirect', array( __CLASS__, 'maybe_add_support_notice' ), 1 );
	}

	/**
	 * Conditionally queue the responsible-play support notice as a WC 'notice' toast.
	 *
	 * Guards (all must be true):
	 * - Signpost toggle 'close' is enabled in settings.
	 * - GET parameter nera_account_closed === '1'.
	 * - User is NOT logged in (account has been deleted/closed).
	 * - wc_add_notice() function exists (WooCommerce active).
	 *
	 * Deliberately NO redirect and NO exit — lets the theme's pri-1 flash handler run next.
	 *
	 * @return void
	 */
	public static function maybe_add_support_notice(): void {
		// Load-order assumption: this plugin loads before the theme on plugins_loaded, so at
		// equal priority 1 our callback is registered first and therefore runs first.  We add
		// our notice then return — no redirect, no exit — so the theme's own pri-1 handler
		// (nera_account_closed_flash_notice) runs next, adds its success notice, and performs
		// the wp_safe_redirect/exit.  Both notices persist in the WC session and are rendered
		// together as Alpine toasts by nera_output_toast_data (wp_footer, pri 999).

		// Guard 1: signpost toggle for 'close' must be enabled.
		if ( ! Nera_RP_Settings::is_signpost_enabled( 'close' ) ) {
			return;
		}

		// Guard 2: ?nera_account_closed=1 must be present and equal '1' (string comparison only —
		// no SQL, no echo; no nonce needed for a read-only presence check).
		if ( ! isset( $_GET['nera_account_closed'] ) || '1' !== (string) $_GET['nera_account_closed'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		// Guard 3: user must be logged out (account has been deleted/closed).
		if ( is_user_logged_in() ) {
			return;
		}

		// Guard 4: WooCommerce must be active and wc_add_notice available.
		if ( ! function_exists( 'wc_add_notice' ) ) {
			return;
		}

		// Add the responsible-play support directory as a WC 'notice' toast.
		// Deliberately no wp_safe_redirect, no exit — the theme's pri-1 handler runs next.
		wc_add_notice( Nera_RP_Services::render_directory( array( 'notice' => true ) ), 'notice' );
	}
}
