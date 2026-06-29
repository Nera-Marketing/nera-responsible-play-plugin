<?php
/**
 * Checkout signpost — clause 1.4.
 *
 * Renders a brief "Need support?" link at checkout ONLY when the customer is
 * over their spending limit (evaluated via the sibling nera-spending-amount-limit-plugin).
 * Fully inert (no fatal, no output) when the sibling plugin is absent, the user
 * has no limit set, or the limit is not breached.
 *
 * Decoupled from the sibling's own checkout card (which renders at priority 10).
 * This class renders at priority 20 — an independent, secondary element that does
 * NOT duplicate the sibling's message or form fields.
 *
 * Integration guards (all must be true for output):
 *   - Signpost toggle enabled in settings.
 *   - User is logged in.
 *   - Nera_SL_User_Limit class + evaluate_for_user() method exist.
 *   - Cart total evaluates to state 'over_soft' or 'over_blocked'.
 *
 * Wave 2: implement render_signpost() and fragment().
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Checkout
 */
class Nera_RP_Checkout {

	const SIGNPOST_ID = 'nera-rp-checkout-signpost';

	/**
	 * Register hooks.
	 */
	public static function init() {
		// Wave 2: implement
		add_action( 'woocommerce_checkout_before_terms_and_conditions', array( __CLASS__, 'render_signpost' ), 20 );
		add_filter( 'woocommerce_update_order_review_fragments', array( __CLASS__, 'fragment' ), 20 );
	}

	/**
	 * Render the checkout support signpost.
	 *
	 * Always emits the wrapper node (empty when guards fail) so the AJAX fragment
	 * target exists and clears cleanly on cart-review refresh.
	 *
	 * @return void
	 */
	public static function render_signpost() {
		$empty = '<div id="' . esc_attr( self::SIGNPOST_ID ) . '"></div>';

		if ( ! Nera_RP_Settings::is_signpost_enabled( 'checkout' ) ) {
			echo $empty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr().
			return;
		}
		if ( ! is_user_logged_in() ) {
			echo $empty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr().
			return;
		}
		// Decoupled from the sibling plugin: bail before any reference to it when absent.
		if ( ! class_exists( 'Nera_SL_User_Limit' ) || ! method_exists( 'Nera_SL_User_Limit', 'evaluate_for_user' ) ) {
			echo $empty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr().
			return;
		}

		$total = ( function_exists( 'WC' ) && WC()->cart ) ? (float) WC()->cart->get_total( 'edit' ) : 0.0;

		// A sibling-side error must never break checkout.
		try {
			$eval = Nera_SL_User_Limit::evaluate_for_user( get_current_user_id(), $total );
		} catch ( \Throwable $e ) {
			echo $empty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr().
			return;
		}

		if ( ! is_array( $eval ) || empty( $eval['has_limit'] ) ) {
			echo $empty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr().
			return;
		}
		if ( ! in_array( $eval['state'], array( 'over_soft', 'over_blocked' ), true ) ) {
			echo $empty; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_attr().
			return;
		}

		$message      = __( 'Reached your spending limit? Support and advice is available.', 'nera-responsible-play-plugin' );
		$help_page_id = Nera_RP_Settings::help_page_id();
		$help_url     = ( $help_page_id > 0 && 'publish' === get_post_status( $help_page_id ) ) ? get_permalink( $help_page_id ) : '';

		echo '<div id="' . esc_attr( self::SIGNPOST_ID ) . '"><div class="nera-rp-checkout-signpost">';
		echo esc_html( $message );
		if ( $help_url ) {
			echo ' <a class="nera-rp-checkout-signpost__link" href="' . esc_url( $help_url ) . '">'
				. esc_html__( 'Help & support', 'nera-responsible-play-plugin' ) . '</a>';
		}
		echo '</div></div>';
	}

	/**
	 * Return the signpost HTML as an AJAX fragment so it updates on cart review refresh.
	 *
	 * @param array $fragments Existing fragment map (CSS selector => HTML).
	 * @return array
	 */
	public static function fragment( array $fragments ) {
		ob_start();
		self::render_signpost();
		$fragments[ '#' . self::SIGNPOST_ID ] = ob_get_clean();
		return $fragments;
	}
}
