<?php
/**
 * Help & support page management: activation creation, shortcode, dynamic block,
 * and admin_init self-heal.
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Page
 */
class Nera_RP_Page {

	const PAGE_SLUG    = 'help-and-support';
	const PAGE_TITLE   = 'Help and support';
	const SHORTCODE    = 'nera_responsible_play';
	const BLOCK_NAME   = 'nera/responsible-play';

	/**
	 * Init hooks.
	 */
	public static function init() {
		// Shortcode (fallback for classic editor pages).
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );

		// Dynamic block registration (requires WP 5.9+ / block API v3).
		add_action( 'init', array( __CLASS__, 'register_block' ) );

		// Admin self-heal: recreate the Help page if it was trashed.
		add_action( 'admin_init', array( __CLASS__, 'maybe_heal_page' ) );

		// Seed default services once, AFTER the ACF field group is registered
		// (class-settings registers it on acf/init at the default priority 10).
		// Running at priority 20 guarantees update_field() can resolve the field
		// name to its key; the activation hook fires before our field group is
		// registered, so seeding must happen here, not inline in activate().
		add_action( 'acf/init', array( __CLASS__, 'maybe_seed_services' ), 20 );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Shortcode
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Shortcode callback — renders the full directory with intro copy.
	 *
	 * @return string HTML.
	 */
	public static function render_shortcode() {
		return Nera_RP_Services::render_directory( array( 'intro' => true ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Dynamic block
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Register the nera/responsible-play dynamic block.
	 */
	public static function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK_NAME,
			array(
				'api_version'     => 3,
				'title'           => __( 'Responsible Play Directory', 'nera-responsible-play-plugin' ),
				'description'     => __( 'Renders the responsible-play services directory with intro copy.', 'nera-responsible-play-plugin' ),
				'category'        => 'widgets',
				'render_callback' => array( __CLASS__, 'render_block' ),
				'supports'        => array(
					'html' => false,
				),
			)
		);
	}

	/**
	 * Block render callback — same output as the shortcode.
	 *
	 * @return string HTML.
	 */
	public static function render_block() {
		return Nera_RP_Services::render_directory( array( 'intro' => true ) );
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Activation
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Activation hook callback — idempotent page creation and service seeding.
	 *
	 * Call order:
	 *   1. Check if stored page ID still points to a live published page → skip.
	 *   2. Adopt an existing published page with slug help-and-support if found.
	 *   3. Otherwise create a new page with the dynamic block in its content.
	 *   4. Seed default services into ACF if ACF is available and not yet seeded.
	 *      If ACF is not yet available at activation, defer seeding to acf/init.
	 */
	public static function activate() {
		// ── Page creation / adoption ───────────────────────────────────────
		$stored_id = (int) get_option( 'nera_rp_help_page_id', 0 );

		if ( $stored_id > 0 ) {
			$page = get_post( $stored_id );
			if ( $page && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
				// Existing live page — skip creation, proceed to seed check.
				self::maybe_seed_services();
				return;
			}
		}

		// Try to adopt an existing page with the canonical slug.
		$existing = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'name'           => self::PAGE_SLUG,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			$page_id = (int) $existing[0];
		} else {
			// Create a new page with the dynamic block as its content.
			$page_id = wp_insert_post(
				array(
					'post_title'   => self::PAGE_TITLE,
					'post_name'    => self::PAGE_SLUG,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => '<!-- wp:' . self::BLOCK_NAME . ' /-->',
				),
				true // Return WP_Error on failure.
			);

			if ( is_wp_error( $page_id ) ) {
				// Log but do not fatal — plugin still activates.
				error_log( '[nera-responsible-play] Failed to create Help page: ' . $page_id->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
				self::maybe_seed_services();
				return;
			}
		}

		update_option( 'nera_rp_help_page_id', (int) $page_id, false );

		self::maybe_seed_services();
	}

	/**
	 * Seed default services into ACF if not yet seeded.
	 *
	 * Called from activate() (synchronous path) and from acf/init (deferred path
	 * for environments where ACF loads after the activation hook).
	 */
	public static function maybe_seed_services() {
		if ( '1' === get_option( 'nera_rp_seeded', '' ) ) {
			return; // Already seeded.
		}

		// Need ACF's read/write API.
		if ( ! function_exists( 'update_field' ) || ! function_exists( 'get_field' ) ) {
			return; // Retry on a later request (the acf/init:20 hook fires every load).
		}

		// The field group must be registered, otherwise update_field() cannot map
		// the field NAME to its key and the value silently fails to persist. The
		// activation hook runs before our group is registered, so bail and let the
		// acf/init:20 hook seed on the next request.
		if ( function_exists( 'acf_get_field' ) && ! acf_get_field( 'field_nera_rp_services' ) ) {
			return;
		}

		update_field( 'nera_rp_services', Nera_RP_Services::default_services(), Nera_RP_Settings::OPTIONS_POST_ID );

		// Only mark as seeded once the value has actually persisted — prevents the
		// flag latching on a failed write and blocking all future retries.
		$check = get_field( 'nera_rp_services', Nera_RP_Settings::OPTIONS_POST_ID );
		if ( is_array( $check ) && ! empty( $check ) ) {
			update_option( 'nera_rp_seeded', '1', false );
		}
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Self-heal
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Recreate the Help page on admin_init if it was trashed or deleted.
	 * Silent no-op when the page is healthy.
	 */
	public static function maybe_heal_page() {
		$stored_id = Nera_RP_Settings::help_page_id();

		if ( $stored_id > 0 ) {
			$page = get_post( $stored_id );
			if ( $page && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
				return; // Healthy.
			}
		}

		// Page is missing or trashed — recreate (same logic as activate).
		self::activate();
	}
}
