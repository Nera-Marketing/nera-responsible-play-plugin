<?php
/**
 * Uninstall handler — runs when the plugin is deleted from the Plugins screen.
 *
 * CONSERVATIVE uninstall policy:
 * - Only the plugin's internal marker options are removed.
 * - The Help & support PAGE is NOT deleted (content created by/for the site).
 * - ACF services data is NOT deleted (admin-authored content).
 * - The ACF field group definitions are local (PHP-registered) so they vanish
 *   automatically when the plugin is removed.
 *
 * @package Nera_Responsible_Play
 */

// Guard: must be called via WP uninstall pipeline, not directly.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nera_rp_help_page_id' );
delete_option( 'nera_rp_seeded' );
