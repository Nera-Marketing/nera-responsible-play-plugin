<?php
/**
 * Admin settings: appends a "Responsible Play" field group to the shared
 * "Theme Settings → Nera Features" options sub-page, plus typed getters.
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Settings
 */
class Nera_RP_Settings {

	const OPTIONS_SLUG    = 'nera-features';
	const OPTIONS_POST_ID = 'nera-features'; // ACF "post_id" for get_field() on this options page.
	const FIELD_GROUP_KEY = 'group_nera_rp';

	/**
	 * Default intro copy shown on the Help & support page when no CMS override is saved.
	 */
	const DEFAULT_INTRO_COPY = '<p>We want you to enjoy our competitions responsibly. If you ever feel that spending is becoming a concern, or you simply need a break, free help is available. The organisations below offer confidential, impartial advice at no charge.</p><p>You can also contact us directly at any time to pause or close your account.</p>';

	/**
	 * Init hooks.
	 */
	public static function init() {
		add_action( 'acf/init', array( __CLASS__, 'register_options_page' ) );
		add_action( 'acf/init', array( __CLASS__, 'register_fields' ) );
	}

	/**
	 * Idempotently ensure the shared Theme Settings parent and the Nera Features sub-page.
	 * Mirrors the pattern in nera-spending-amount-limit-plugin/includes/class-settings.php.
	 */
	public static function register_options_page() {
		if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
			return;
		}

		// Ensure the shared Theme Settings parent exists (the theme normally creates it, but
		// guard so this plugin can function standalone).
		if ( function_exists( 'acf_add_options_page' ) &&
			( ! function_exists( 'acf_get_options_page' ) || ! acf_get_options_page( 'theme-settings' ) ) ) {
			acf_add_options_page(
				array(
					'page_title' => 'Theme Settings',
					'menu_title' => 'Theme Settings',
					'menu_slug'  => 'theme-settings',
					'capability' => 'manage_options',
					'redirect'   => false,
				)
			);
		}

		// Register (or no-op if already registered by the sibling plugin) the Nera Features sub-page.
		acf_add_options_sub_page(
			array(
				'page_title'  => __( 'Nera Features', 'nera-responsible-play-plugin' ),
				'menu_title'  => __( 'Nera Features', 'nera-responsible-play-plugin' ),
				'menu_slug'   => self::OPTIONS_SLUG,
				'parent_slug' => 'theme-settings',
				'capability'  => 'manage_options',
				'post_id'     => self::OPTIONS_POST_ID,
			)
		);
	}

	/**
	 * Register the Responsible Play field group, appending to the Nera Features page.
	 */
	public static function register_fields() {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			array(
				'key'    => self::FIELD_GROUP_KEY,
				'title'  => __( 'Responsible Play', 'nera-responsible-play-plugin' ),
				'fields' => array(

					// ── Section intro message ──────────────────────────────────────
					array(
						'key'       => 'field_nera_rp_section',
						'label'     => __( 'Responsible Play', 'nera-responsible-play-plugin' ),
						'name'      => '',
						'type'      => 'message',
						'message'   => __( 'Configure the Help &amp; support page content and control where signpost links appear. Signposts can be toggled independently per surface.', 'nera-responsible-play-plugin' ),
						'new_lines' => 'wpautop',
						'esc_html'  => 0,
					),

					// ── Help page intro copy ───────────────────────────────────────
					array(
						'key'           => 'field_nera_rp_intro_copy',
						'label'         => __( 'Intro Copy', 'nera-responsible-play-plugin' ),
						'name'          => 'nera_rp_intro_copy',
						'type'          => 'wysiwyg',
						'instructions'  => __( 'Responsible-play guidance shown at the top of the Help &amp; support page, above the services directory.', 'nera-responsible-play-plugin' ),
						'toolbar'       => 'full',
						'media_upload'  => 0,
						'default_value' => self::DEFAULT_INTRO_COPY,
						'wrapper'       => array( 'width' => '100' ),
					),

					// ── Signpost toggles ───────────────────────────────────────────
					array(
						'key'           => 'field_nera_rp_sp_footer',
						'label'         => __( 'Footer Signpost', 'nera-responsible-play-plugin' ),
						'name'          => 'nera_rp_sp_footer',
						'type'          => 'true_false',
						'instructions'  => __( 'Show a slim "Responsible play" link strip at the bottom of every page.', 'nera-responsible-play-plugin' ),
						'ui'            => 1,
						'ui_on_text'    => __( 'Yes', 'nera-responsible-play-plugin' ),
						'ui_off_text'   => __( 'No', 'nera-responsible-play-plugin' ),
						'default_value' => 1,
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'           => 'field_nera_rp_sp_account',
						'label'         => __( 'Account Menu Signpost', 'nera-responsible-play-plugin' ),
						'name'          => 'nera_rp_sp_account',
						'type'          => 'true_false',
						'instructions'  => __( 'Add a "Need support?" item to the My Account navigation menu.', 'nera-responsible-play-plugin' ),
						'ui'            => 1,
						'ui_on_text'    => __( 'Yes', 'nera-responsible-play-plugin' ),
						'ui_off_text'   => __( 'No', 'nera-responsible-play-plugin' ),
						'default_value' => 1,
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'           => 'field_nera_rp_sp_checkout',
						'label'         => __( 'Checkout Signpost', 'nera-responsible-play-plugin' ),
						'name'          => 'nera_rp_sp_checkout',
						'type'          => 'true_false',
						'instructions'  => __( 'Show a support signpost at checkout when the customer is over their spending limit (requires nera-spending-amount-limit-plugin).', 'nera-responsible-play-plugin' ),
						'ui'            => 1,
						'ui_on_text'    => __( 'Yes', 'nera-responsible-play-plugin' ),
						'ui_off_text'   => __( 'No', 'nera-responsible-play-plugin' ),
						'default_value' => 1,
						'wrapper'       => array( 'width' => '25' ),
					),
					array(
						'key'           => 'field_nera_rp_sp_close',
						'label'         => __( 'Account-close Signpost', 'nera-responsible-play-plugin' ),
						'name'          => 'nera_rp_sp_close',
						'type'          => 'true_false',
						'instructions'  => __( 'Show a support notice (toast) after a customer closes their account.', 'nera-responsible-play-plugin' ),
						'ui'            => 1,
						'ui_on_text'    => __( 'Yes', 'nera-responsible-play-plugin' ),
						'ui_off_text'   => __( 'No', 'nera-responsible-play-plugin' ),
						'default_value' => 1,
						'wrapper'       => array( 'width' => '25' ),
					),

					// ── Services repeater ──────────────────────────────────────────
					array(
						'key'          => 'field_nera_rp_services',
						'label'        => __( 'Support Services', 'nera-responsible-play-plugin' ),
						'name'         => 'nera_rp_services',
						'type'         => 'repeater',
						'instructions' => __( 'The directory of support organisations shown on the Help &amp; support page and in signpost notices. Admins can add, remove, or reorder rows.', 'nera-responsible-play-plugin' ),
						'button_label' => __( 'Add service', 'nera-responsible-play-plugin' ),
						'layout'       => 'table',
						'min'          => 0,
						'max'          => 0,
						'sub_fields'   => array(
							array(
								'key'       => 'field_nera_rp_services_name',
								'label'     => __( 'Name', 'nera-responsible-play-plugin' ),
								'name'      => 'name',
								'type'      => 'text',
								'required'  => 1,
								'wrapper'   => array( 'width' => '20' ),
							),
							array(
								'key'     => 'field_nera_rp_services_blurb',
								'label'   => __( 'Blurb', 'nera-responsible-play-plugin' ),
								'name'    => 'blurb',
								'type'    => 'text',
								'wrapper' => array( 'width' => '35' ),
							),
							array(
								'key'     => 'field_nera_rp_services_phone',
								'label'   => __( 'Phone', 'nera-responsible-play-plugin' ),
								'name'    => 'phone',
								'type'    => 'text',
								'wrapper' => array( 'width' => '20' ),
							),
							array(
								'key'     => 'field_nera_rp_services_url',
								'label'   => __( 'Website URL', 'nera-responsible-play-plugin' ),
								'name'    => 'url',
								'type'    => 'url',
								'wrapper' => array( 'width' => '25' ),
							),
						),
						'wrapper' => array( 'width' => '100' ),
					),
				),
				'location' => array(
					array(
						array(
							'param'    => 'options_page',
							'operator' => '==',
							'value'    => self::OPTIONS_SLUG,
						),
					),
				),
				'menu_order'            => 10,
				'position'              => 'normal',
				'style'                 => 'default',
				'label_placement'       => 'top',
				'instruction_placement' => 'label',
				'active'                => true,
				'description'           => '',
			)
		);
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Typed getters
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Whether a named signpost is enabled.
	 *
	 * Defaults to TRUE when ACF is absent or the field has never been saved,
	 * so all surfaces are live out of the box without a trip to the admin.
	 *
	 * @param string $which One of 'footer' | 'account' | 'checkout' | 'close'.
	 * @return bool
	 */
	public static function is_signpost_enabled( $which ) {
		if ( ! function_exists( 'get_field' ) ) {
			return true; // Safe default: show signpost.
		}
		$field_name = 'nera_rp_sp_' . $which;
		$value      = get_field( $field_name, self::OPTIONS_POST_ID );
		// get_field returns null when the field has never been saved; true_false returns
		// false (off) or true (on). Treat null as "not yet set" → default ON.
		if ( null === $value ) {
			return true;
		}
		return (bool) $value;
	}

	/**
	 * Stored Help & support page ID (0 when not yet created).
	 *
	 * @return int
	 */
	public static function help_page_id() {
		return (int) get_option( 'nera_rp_help_page_id', 0 );
	}

	/**
	 * Intro copy for the Help page (falls back to built-in default).
	 *
	 * @return string HTML — already safe for wp_kses_post output.
	 */
	public static function intro_copy() {
		if ( ! function_exists( 'get_field' ) ) {
			return self::DEFAULT_INTRO_COPY;
		}
		$copy = get_field( 'nera_rp_intro_copy', self::OPTIONS_POST_ID );
		if ( empty( $copy ) || ! is_string( $copy ) ) {
			return self::DEFAULT_INTRO_COPY;
		}
		return $copy;
	}
}
