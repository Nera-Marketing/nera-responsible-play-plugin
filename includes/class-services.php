<?php
/**
 * Single source of truth for responsible-play service data and HTML rendering.
 *
 * Used by: the shortcode, the dynamic block, the clause-1.5 account-close notice,
 * and optionally the checkout signpost.  All callers invoke render_directory() so
 * output escaping and markup live in exactly one place.
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Services
 */
class Nera_RP_Services {

	/**
	 * Hook registrations (none required for this class; shortcode/block live in Page).
	 */
	public static function init() {
		// Wave 2: implement (placeholder — class is stateless; nothing to hook).
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Data layer
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * The four seeded default services.
	 *
	 * These are editable defaults loaded into the ACF repeater on first activation.
	 * Phone numbers are best-effort as of plugin creation — VERIFY before launch.
	 *
	 * @return array<int,array{name:string,blurb:string,phone:string,url:string}>
	 */
	public static function default_services() {
		return array(
			array(
				'name'  => 'Citizens Advice',
				'blurb' => 'general consumer & money advice',
				'phone' => '0800 144 8848',
				'url'   => 'https://www.citizensadvice.org.uk',
			),
			array(
				'name'  => 'National Debtline (Money Advice Trust)',
				'blurb' => 'free debt advice',
				'phone' => '0808 808 4000',
				'url'   => 'https://www.nationaldebtline.org',
			),
			array(
				'name'  => 'Samaritans',
				'blurb' => 'emotional distress, available 24/7',
				'phone' => '116 123',
				'url'   => 'https://www.samaritans.org',
			),
			array(
				'name'  => 'Mind',
				'blurb' => 'mental health support',
				'phone' => '0300 123 3393',
				'url'   => 'https://www.mind.org.uk',
			),
		);
	}

	/**
	 * Retrieve and sanitize the list of support services.
	 *
	 * Reads the ACF repeater; falls back to default_services() when nothing is saved.
	 * Sanitizes every row on read so a tampered option cannot inject markup.
	 *
	 * @return array<int,array{name:string,blurb:string,phone:string,url:string}>
	 */
	public static function get_services() {
		$rows = array();

		if ( function_exists( 'get_field' ) ) {
			$raw = get_field( 'nera_rp_services', Nera_RP_Settings::OPTIONS_POST_ID );
			if ( is_array( $raw ) && ! empty( $raw ) ) {
				$rows = $raw;
			}
		}

		if ( empty( $rows ) ) {
			$rows = self::default_services();
		}

		$sanitized = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$phone_raw = isset( $row['phone'] ) ? (string) $row['phone'] : '';
			$sanitized[] = array(
				'name'  => sanitize_text_field( isset( $row['name'] ) ? (string) $row['name'] : '' ),
				'blurb' => sanitize_text_field( isset( $row['blurb'] ) ? (string) $row['blurb'] : '' ),
				// Normalise phone: keep digits, +, spaces, hyphens, parentheses.
				'phone' => preg_replace( '/[^0-9+\s()\-]/', '', $phone_raw ),
				'url'   => esc_url_raw( isset( $row['url'] ) ? (string) $row['url'] : '' ),
			);
		}

		return $sanitized;
	}

	// ─────────────────────────────────────────────────────────────────────────
	// Rendering
	// ─────────────────────────────────────────────────────────────────────────

	/**
	 * Build and return escaped HTML for the services directory.
	 *
	 * @param array $args {
	 *     Optional rendering flags.
	 *
	 *     @type bool   $intro   Prepend the intro copy (wysiwyg) from settings.  Default false.
	 *     @type bool   $notice  Compact markup safe for wp_kses_post toast
	 *                           (only <a><ul><li><strong><br> tags).  Default false.
	 *     @type string $heading Override the section heading text.
	 * }
	 * @return string Escaped HTML ready for echo/return.
	 */
	public static function render_directory( array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'intro'   => false,
				'notice'  => false,
				'heading' => '',
			)
		);

		$services = self::get_services();
		$is_notice = (bool) $args['notice'];

		ob_start();

		if ( $is_notice ) {
			// ── Compact notice variant (wp_kses_post safe) ─────────────────
			// Allowed tags: <a>, <ul>, <li>, <strong>, <br>.
			echo '<strong>' . esc_html__( 'Help is available — free, confidential services:', 'nera-responsible-play-plugin' ) . '</strong><br>';
			echo '<ul>';
			foreach ( $services as $service ) {
				echo '<li>';
				echo '<strong>' . esc_html( $service['name'] ) . '</strong>';
				if ( $service['blurb'] ) {
					echo ' — ' . esc_html( $service['blurb'] );
				}
				if ( $service['phone'] ) {
					$tel_href = 'tel:' . preg_replace( '/[^0-9+]/', '', $service['phone'] );
					echo ' · <a href="' . esc_attr( $tel_href ) . '">' . esc_html( $service['phone'] ) . '</a>';
				}
				if ( $service['url'] ) {
					echo ' · <a href="' . esc_url( $service['url'] ) . '" rel="noopener" target="_blank">' . esc_html( wp_parse_url( $service['url'], PHP_URL_HOST ) ?: $service['name'] ) . '</a>';
				}
				echo '</li>';
			}
			echo '</ul>';
		} else {
			// ── Full page/block variant ────────────────────────────────────
			echo '<div class="nera-rp-directory">';

			if ( (bool) $args['intro'] ) {
				$intro = Nera_RP_Settings::intro_copy();
				echo '<div class="nera-rp-directory__intro">' . wp_kses_post( $intro ) . '</div>';
			}

			$heading = $args['heading'] ? (string) $args['heading'] : __( 'Support organisations', 'nera-responsible-play-plugin' );
			echo '<h2 class="nera-rp-directory__heading">' . esc_html( $heading ) . '</h2>';

			if ( empty( $services ) ) {
				echo '<p>' . esc_html__( 'No support services are currently listed.', 'nera-responsible-play-plugin' ) . '</p>';
			} else {
				echo '<ul class="nera-rp-directory__list">';
				foreach ( $services as $service ) {
					echo '<li class="nera-rp-directory__item">';
					echo '<strong class="nera-rp-directory__name">' . esc_html( $service['name'] ) . '</strong>';
					if ( $service['blurb'] ) {
						echo '<span class="nera-rp-directory__blurb"> — ' . esc_html( $service['blurb'] ) . '</span>';
					}
					echo '<span class="nera-rp-directory__contacts">';
					if ( $service['phone'] ) {
						$tel_href = 'tel:' . preg_replace( '/[^0-9+]/', '', $service['phone'] );
						echo '<a class="nera-rp-directory__phone" href="' . esc_attr( $tel_href ) . '">' . esc_html( $service['phone'] ) . '</a>';
					}
					if ( $service['url'] ) {
						echo '<a class="nera-rp-directory__link" href="' . esc_url( $service['url'] ) . '" rel="noopener noreferrer" target="_blank">' . esc_html( $service['url'] ) . '</a>';
					}
					echo '</span>';
					echo '</li>';
				}
				echo '</ul>';
			}

			echo '</div><!-- .nera-rp-directory -->';
		}

		return ob_get_clean();
	}
}
