<?php
/**
 * Footer signpost — renders a slim "Responsible play" link strip via wp_footer.
 *
 * Compromise note: the theme's footer template (template-parts/footer.php) uses
 * dynamic_sidebar() columns with no do_action hook, so the only zero-edit insertion
 * point is wp_footer (just before </body>). The strip therefore appears after the
 * theme footer visually, not inside the column grid.
 *
 * Wave 2: implement render().
 *
 * @package Nera_Responsible_Play
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Nera_RP_Footer
 */
class Nera_RP_Footer {

	/**
	 * Register hooks.
	 *
	 * Priority 50 so it runs after the theme's wp_footer hooks but before the
	 * Alpine/toast script output at priority 999.
	 */
	public static function init() {
		// Wave 2: implement
		add_action( 'wp_footer', array( __CLASS__, 'render' ), 50 );
	}

	/**
	 * Output the footer responsible-play strip.
	 *
	 * Guards:
	 * - Signpost toggle enabled in settings.
	 * - Help page must exist (non-zero id, non-trashed).
	 *
	 * @return void
	 */
	public static function render() {
		if ( ! Nera_RP_Settings::is_signpost_enabled( 'footer' ) ) {
			return;
		}

		$id = Nera_RP_Settings::help_page_id();

		if ( $id <= 0 ) {
			return;
		}

		if ( is_admin() ) {
			return;
		}

		if ( 'publish' !== get_post_status( $id ) ) {
			return;
		}

		$url = esc_url( get_permalink( $id ) );

		if ( ! $url ) {
			return;
		}
		?>
		<div class="nera-rp-footer-strip" role="contentinfo" aria-label="<?php esc_attr_e( 'Responsible play support', 'nera-responsible-play-plugin' ); ?>">
			<div class="nera-rp-footer-strip__inner">
				<svg class="nera-rp-footer-strip__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
					<circle cx="12" cy="12" r="10"/>
					<circle cx="12" cy="12" r="4"/>
					<line x1="12" y1="2" x2="12" y2="6"/>
					<line x1="12" y1="18" x2="12" y2="22"/>
					<line x1="2" y1="12" x2="6" y2="12"/>
					<line x1="18" y1="12" x2="22" y2="12"/>
				</svg>
				<p class="nera-rp-footer-strip__text">
					<?php echo esc_html__( 'Need support with your play?', 'nera-responsible-play-plugin' ); ?>
				</p>
				<a class="nera-rp-footer-strip__link" href="<?php echo $url; ?>">
					<?php echo esc_html__( 'Help & support', 'nera-responsible-play-plugin' ); ?>
				</a>
			</div>
		</div>
		<?php
	}
}
