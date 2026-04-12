<?php
/**
 * Uninstall Training Translation Tracker.
 *
 * Removes all plugin options and transients from the database when the plugin
 * is deleted via the WordPress admin (Plugins → Delete).
 * This file is NOT called on deactivation — only on full deletion.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// ---- Options ----
$options = [
	'tt_github_org',
	'tt_project_number',
	'tt_locale_filter',
	'tt_github_repo',
	'tt_github_label',
	'tt_github_token',
	'tt_refresh_hours',
	'tt_lwp_cache_hours',
	'tt_last_fetched',
];

foreach ( $options as $option ) {
	delete_option( $option );
}

// ---- Transients (bulk delete via direct DB query) ----
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional bulk transient deletion; no WP API covers this pattern.
$wpdb->query(
	"DELETE FROM {$wpdb->options}
	 WHERE option_name LIKE '_transient_tt_proj_%'
	    OR option_name LIKE '_transient_timeout_tt_proj_%'
	    OR option_name LIKE '_transient_tt_issues_%'
	    OR option_name LIKE '_transient_timeout_tt_issues_%'
	    OR option_name LIKE '_transient_tt_lwp_%'
	    OR option_name LIKE '_transient_timeout_tt_lwp_%'
	    OR option_name LIKE '_transient_tt_sc_%'
	    OR option_name LIKE '_transient_timeout_tt_sc_%'"
);
