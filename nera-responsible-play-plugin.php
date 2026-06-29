<?php
/**
 * Plugin Name: Nera – Responsible Play
 * Plugin URI: https://github.com/Nera-Marketing/nera-responsible-play-plugin
 * Description: Responsible-play / player-protection feature for WooCommerce competition sites. Auto-creates a "Help and support" page with a services directory, admin-editable via ACF. Signposts support at checkout (clause 1.4), account area, account-close flow (clause 1.5), and a sitewide footer strip.
 * Version: 1.0.2
 * Author: Nera
 * Text Domain: nera-responsible-play-plugin
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 *
 * @package Nera_Responsible_Play
 */

use YahnisElsts\PluginUpdateChecker\v5p5\Vcs\GitHubApi;

defined( 'ABSPATH' ) || exit;

define( 'NERA_RP_VERSION', '1.0.2' );
define( 'NERA_RP_PLUGIN_FILE', __FILE__ );
define( 'NERA_RP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NERA_RP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * GitHub updates (Plugin Update Checker v5.5). On by default when `lib/plugin-update-checker/load-v5p5.php` exists.
 * Parity with nera-spending-amount-limit-plugin / nera-instant-win-threshold / nera-spin-to-win.
 *
 * Disable:      define( 'NERA_RP_DISABLE_GITHUB_UPDATES', true );
 * Private repo: define( 'NERA_RP_GITHUB_TOKEN', 'ghp_...' );
 * Custom URL:   define( 'NERA_RP_GITHUB_REPO_URL', 'https://github.com/Owner/repo/' );  (or filter nera_rp_github_repo_url)
 *
 * PUC reads the `Version` header from the GitHub ref it selects. Bump `Version` + `NERA_RP_VERSION` for every
 * release, then tag/push to match. A custom setReleaseFilter (always true) + maxReleases > 1 makes GitHubApi
 * use the paginated /releases endpoint instead of /latest (which 404s without a GitHub "latest" release).
 * enableReleaseAssets() prefers the attached zip over the tag tarball.
 *
 * @link https://github.com/YahnisElsts/plugin-update-checker
 */
if ( ! defined( 'NERA_RP_DISABLE_GITHUB_UPDATES' ) || ! NERA_RP_DISABLE_GITHUB_UPDATES ) {
	$nera_rp_github_repo_default = 'https://github.com/Nera-Marketing/nera-responsible-play-plugin/';
	if ( defined( 'NERA_RP_GITHUB_REPO_URL' ) && is_string( NERA_RP_GITHUB_REPO_URL ) && NERA_RP_GITHUB_REPO_URL !== '' ) {
		$nera_rp_github_repo_default = NERA_RP_GITHUB_REPO_URL;
	}
	$nera_rp_github_repo = apply_filters( 'nera_rp_github_repo_url', $nera_rp_github_repo_default );

	$nera_rp_puc_loader = NERA_RP_PLUGIN_DIR . 'lib/plugin-update-checker/load-v5p5.php';
	if ( is_readable( $nera_rp_puc_loader ) ) {
		require_once $nera_rp_puc_loader;
		// Fourth argument: check period in hours (PUC default is 12).
		$nera_rp_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
			$nera_rp_github_repo,
			__FILE__,
			'nera-responsible-play',
			6
		);
		$nera_rp_update_checker->setBranch( 'main' );

		if ( defined( 'NERA_RP_GITHUB_TOKEN' ) && is_string( NERA_RP_GITHUB_TOKEN ) && NERA_RP_GITHUB_TOKEN !== '' ) {
			$nera_rp_update_checker->setAuthentication( NERA_RP_GITHUB_TOKEN );
		}

		// GitHub-hosted updates carry no plugin icon, so the Dashboard → Updates and
		// Plugins screens show a blank logo. Inject the bundled logo.png as the icon.
		$nera_rp_update_checker->addResultFilter(
			static function ( $plugin_info ) {
				if ( is_object( $plugin_info ) && is_readable( NERA_RP_PLUGIN_DIR . 'logo.png' ) ) {
					$logo                = NERA_RP_PLUGIN_URL . 'logo.png';
					$plugin_info->icons = array(
						'1x'      => $logo,
						'2x'      => $logo,
						'default' => $logo,
					);
				}
				return $plugin_info;
			}
		);

		$nera_rp_puc_vcs = $nera_rp_update_checker->getVcsApi();
		if ( $nera_rp_puc_vcs instanceof GitHubApi ) {
			$nera_rp_puc_vcs->setReleaseFilter(
				static function ( $version_number, $release_object ) {
					unset( $version_number, $release_object );
					return true;
				},
				\YahnisElsts\PluginUpdateChecker\v5p5\Vcs\Api::RELEASE_FILTER_SKIP_PRERELEASE,
				20
			);
			$nera_rp_puc_vcs->enableReleaseAssets();
		}
	}
}

require_once NERA_RP_PLUGIN_DIR . 'includes/class-settings.php';
require_once NERA_RP_PLUGIN_DIR . 'includes/class-services.php';
require_once NERA_RP_PLUGIN_DIR . 'includes/class-page.php';
require_once NERA_RP_PLUGIN_DIR . 'includes/class-assets.php';
require_once NERA_RP_PLUGIN_DIR . 'includes/class-footer.php';
require_once NERA_RP_PLUGIN_DIR . 'includes/class-account.php';
require_once NERA_RP_PLUGIN_DIR . 'includes/class-checkout.php';
require_once NERA_RP_PLUGIN_DIR . 'includes/class-account-close.php';

/**
 * Bootstrap plugin.
 */
function nera_rp_init() {
	load_plugin_textdomain( 'nera-responsible-play-plugin', false, dirname( plugin_basename( NERA_RP_PLUGIN_FILE ) ) . '/languages' );

	// Settings (ACF options page) and page/shortcode/block load regardless of WooCommerce.
	Nera_RP_Settings::init();
	Nera_RP_Services::init();
	Nera_RP_Page::init();

	// The customer-facing signpost features require WooCommerce.
	if ( class_exists( 'WooCommerce' ) ) {
		Nera_RP_Account::init();
		Nera_RP_Checkout::init();
		Nera_RP_Account_Close::init();
		Nera_RP_Assets::init();
		Nera_RP_Footer::init();
	}
}
add_action( 'plugins_loaded', 'nera_rp_init', 20 );

/**
 * WooCommerce HPOS (custom order tables) compatibility.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Activation: create/adopt Help page and seed default services.
 */
register_activation_hook( NERA_RP_PLUGIN_FILE, array( 'Nera_RP_Page', 'activate' ) );
