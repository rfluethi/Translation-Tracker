<?php
/**
 * Plugin Name:  Training Translation Tracker
 * Plugin URI:   https://learn-wp-dach.org/
 * Description:  This plugin reads the status of the translation issues for learn.wordpress.org directly from GitHub and displays the current translation progress for a selected language in a clear table on the website. The relevant information must be defined in the respective translation issue on GitHub.
 * Version:      0.1.4-beta
 * Requires at least: 5.8
 * Requires PHP: 8.0
 * Author:       WordPress Training Team DACH
 * Author URI:   https://learn-wp-dach.org/
 * License:      GPL-2.0-or-later
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  training-translation-tracker
 * Domain Path:  /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TT_VERSION', '0.1.4-beta' );

/* -------------------------------------------------------
 *  Transient key helper
 * ------------------------------------------------------- */

/**
 * Return a site-qualified transient key.
 *
 * On WordPress Multisite each site gets its own cache bucket by appending
 * the current blog ID. On single-site installations the key is unchanged.
 *
 * @param string $base Base transient key (e.g. 'tt_proj_abc123').
 * @return string
 */
function tt_cache_key( $base ) {
	if ( is_multisite() ) {
		return $base . '_' . get_current_blog_id();
	}
	return $base;
}

/* -------------------------------------------------------
 *  Activation / Deactivation
 * ------------------------------------------------------- */

register_activation_hook( __FILE__, 'tt_activate' );
register_deactivation_hook( __FILE__, 'tt_deactivate' );

function tt_activate() {
	if ( ! wp_next_scheduled( 'tt_cron_refresh_course_map' ) ) {
		wp_schedule_event( time(), 'tt_lwp_interval', 'tt_cron_refresh_course_map' );
	}
}

function tt_deactivate() {
	wp_clear_scheduled_hook( 'tt_cron_refresh_course_map' );
}

// Register custom cron interval matching the configured cache duration.
add_filter( 'cron_schedules', function ( $schedules ) {
	$hours = absint( get_option( 'tt_lwp_cache_hours', 24 ) );
	$schedules['tt_lwp_interval'] = [
		'interval' => $hours * HOUR_IN_SECONDS,
		'display'  => sprintf( 'Every %d hours (Translation Tracker course map)', $hours ),
	];
	return $schedules;
} );

// Cron callback: rebuild the course map in the background.
add_action( 'tt_cron_refresh_course_map', 'tt_cron_build_course_map' );

function tt_cron_build_course_map() {
	delete_transient( tt_cache_key( 'tt_lwp_course_map' ) );
	tt_build_course_map();
}

/* -------------------------------------------------------
 *  Settings
 * ------------------------------------------------------- */

add_action( 'admin_menu', 'tt_add_settings_page' );
add_action( 'admin_init', 'tt_register_settings' );

function tt_add_settings_page() {
	add_options_page(
		__( 'Translation Tracker', 'training-translation-tracker' ),
		__( 'Translation Tracker', 'training-translation-tracker' ),
		'manage_options',
		'training-translation-tracker',
		'tt_settings_page_html'
	);
}

function tt_register_settings() {
	register_setting( 'tt_settings', 'tt_github_org', [
		'type'              => 'string',
		'default'           => 'WordPress',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	register_setting( 'tt_settings', 'tt_project_number', [
		'type'              => 'integer',
		'default'           => 104,
		'sanitize_callback' => 'absint',
	] );
	register_setting( 'tt_settings', 'tt_locale_filter', [
		'type'              => 'string',
		'default'           => 'German',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	register_setting( 'tt_settings', 'tt_github_repo', [
		'type'              => 'string',
		'default'           => 'WordPress/Learn',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	register_setting( 'tt_settings', 'tt_github_label', [
		'type'              => 'string',
		'default'           => '[Content] Translation',
		'sanitize_callback' => 'sanitize_text_field',
	] );
	register_setting( 'tt_settings', 'tt_github_token', [
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'tt_sanitize_token',
	] );
	register_setting( 'tt_settings', 'tt_refresh_hours', [
		'type'              => 'integer',
		'default'           => 4,
		'sanitize_callback' => 'absint',
	] );
	register_setting( 'tt_settings', 'tt_lwp_cache_hours', [
		'type'              => 'integer',
		'default'           => 24,
		'sanitize_callback' => 'absint',
	] );
}

/**
 * Sanitize the GitHub token: strip whitespace, reject obviously wrong values.
 */
function tt_sanitize_token( $value ) {
	return sanitize_text_field( trim( $value ) );
}

/**
 * Return the GitHub token.
 * Prefers the environment variable TT_GITHUB_TOKEN so the token never has
 * to be stored in the database at all.
 */
function tt_get_token() {
	$env = getenv( 'TT_GITHUB_TOKEN' );
	if ( ! empty( $env ) ) {
		return trim( $env );
	}
	return get_option( 'tt_github_token', '' );
}

function tt_settings_page_html() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Handle GitHub cache clear
	if ( isset( $_POST['tt_clear_cache'] ) && check_admin_referer( 'tt_clear_cache_nonce' ) ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional bulk transient deletion; no WP API covers this pattern.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tt_proj_%' OR option_name LIKE '_transient_timeout_tt_proj_%' OR option_name LIKE '_transient_tt_issues_%' OR option_name LIKE '_transient_timeout_tt_issues_%'" );
		tt_clear_shortcode_cache();
		delete_option( 'tt_last_fetched' );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Cache cleared. Data will be reloaded on next page view.', 'training-translation-tracker' ) . '</p></div>';
	}

	// Handle learn.wordpress.org structure cache clear
	if ( isset( $_POST['tt_clear_lwp_cache'] ) && check_admin_referer( 'tt_clear_lwp_cache_nonce' ) ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional bulk transient deletion; no WP API covers this pattern.
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tt_lwp_%' OR option_name LIKE '_transient_timeout_tt_lwp_%'" );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Course structure cache cleared. Structure will be reloaded on next page view.', 'training-translation-tracker' ) . '</p></div>';
	}

	$last_fetched   = get_option( 'tt_last_fetched', '' );
	$refresh_hours  = absint( get_option( 'tt_refresh_hours', 4 ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Translation Tracker – Settings', 'training-translation-tracker' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'tt_settings' ); ?>

			<h2><?php esc_html_e( 'GitHub Project', 'training-translation-tracker' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="tt_github_org"><?php esc_html_e( 'GitHub Organisation', 'training-translation-tracker' ); ?></label></th>
					<td>
						<input type="text" id="tt_github_org" name="tt_github_org"
							   value="<?php echo esc_attr( get_option( 'tt_github_org', 'WordPress' ) ); ?>"
							   class="regular-text" placeholder="WordPress">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tt_project_number"><?php esc_html_e( 'Project Number', 'training-translation-tracker' ); ?></label></th>
					<td>
						<input type="number" id="tt_project_number" name="tt_project_number"
							   value="<?php echo esc_attr( get_option( 'tt_project_number', 104 ) ); ?>"
							   class="small-text" min="0">
						<p class="description">
							<?php
							/* translators: %s: example GitHub project URL shown as code snippet */
							printf(
								esc_html__( 'From the project URL: %s', 'training-translation-tracker' ),
								'<code>github.com/orgs/WordPress/projects/<strong>104</strong></code>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tt_locale_filter"><?php esc_html_e( 'Locale Filter', 'training-translation-tracker' ); ?></label></th>
					<td>
						<input type="text" id="tt_locale_filter" name="tt_locale_filter"
							   value="<?php echo esc_attr( get_option( 'tt_locale_filter', 'German' ) ); ?>"
							   class="regular-text" placeholder="German">
						<p class="description"><?php esc_html_e( 'Value of the project field "Locale". Leave empty to show all locales.', 'training-translation-tracker' ); ?></p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Cache &amp; Refresh', 'training-translation-tracker' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><label for="tt_github_token"><?php esc_html_e( 'GitHub Token', 'training-translation-tracker' ); ?></label></th>
					<td>
						<input type="password" id="tt_github_token" name="tt_github_token"
							   value="<?php echo esc_attr( get_option( 'tt_github_token', '' ) ); ?>"
							   class="regular-text" placeholder="ghp_xxxxxxxxxxxx">
						<?php if ( getenv( 'TT_GITHUB_TOKEN' ) ) : ?>
							<p class="description" style="color:#2a7a2a;">
								<?php esc_html_e( 'Token is provided via environment variable TT_GITHUB_TOKEN — the field above is ignored.', 'training-translation-tracker' ); ?>
							</p>
						<?php endif; ?>
						<p class="description">
							<?php esc_html_e( 'Project mode (Project Number > 0): token with "project" scope required.', 'training-translation-tracker' ); ?><br>
							<?php esc_html_e( 'REST mode (Project Number = 0): token with "public_repo" scope strongly recommended — without a token the GitHub rate limit (60 requests/hour per server IP) is almost always exceeded in practice.', 'training-translation-tracker' ); ?><br>
							<?php esc_html_e( 'Recommended: set the environment variable TT_GITHUB_TOKEN on the server to avoid storing the token in the database.', 'training-translation-tracker' ); ?>
							<a href="https://github.com/settings/tokens" target="_blank"><?php esc_html_e( 'Create token', 'training-translation-tracker' ); ?></a>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tt_refresh_hours"><?php esc_html_e( 'Auto-Refresh Interval (GitHub)', 'training-translation-tracker' ); ?></label></th>
					<td>
						<select id="tt_refresh_hours" name="tt_refresh_hours">
							<?php
							$current = absint( get_option( 'tt_refresh_hours', 4 ) );
							$options = [ 1 => '1h', 2 => '2h', 4 => '4h', 6 => '6h', 12 => '12h', 24 => '24h', 48 => '48h', 72 => '72h' ];
							foreach ( $options as $val => $label ) {
								printf( '<option value="%d"%s>%s</option>', absint( $val ), selected( $current, $val, false ), esc_html( $label ) );
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'GitHub data is automatically reloaded after this interval on the next page view.', 'training-translation-tracker' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="tt_lwp_cache_hours"><?php esc_html_e( 'Auto-Refresh Interval (Course Structure)', 'training-translation-tracker' ); ?></label></th>
					<td>
						<select id="tt_lwp_cache_hours" name="tt_lwp_cache_hours">
							<?php
							$current = absint( get_option( 'tt_lwp_cache_hours', 24 ) );
							$options = [ 6 => '6h', 12 => '12h', 24 => '24h', 48 => '48h', 72 => '72h' ];
							foreach ( $options as $val => $label ) {
								printf( '<option value="%d"%s>%s</option>', absint( $val ), selected( $current, $val, false ), esc_html( $label ) );
							}
							?>
						</select>
						<p class="description"><?php esc_html_e( 'How long course structure data from learn.wordpress.org is cached. Course structures change rarely – 24h is recommended.', 'training-translation-tracker' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'training-translation-tracker' ) ); ?>
		</form>

		<hr>
		<h2><?php esc_html_e( 'Data Status & Refresh', 'training-translation-tracker' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Last data load', 'training-translation-tracker' ); ?></th>
				<td>
					<?php if ( $last_fetched ) : ?>
						<strong><?php echo esc_html( $last_fetched ); ?></strong>
						&mdash;
						<?php
						/* translators: %d: number of hours until the next automatic data refresh */
						printf(
							esc_html__( 'Next auto-refresh after %d hours from last load.', 'training-translation-tracker' ),
							absint( $refresh_hours )
						);
						?>
					<?php else : ?>
						<?php esc_html_e( 'No data loaded yet. Visit the page with the [translation_tracker] shortcode to load data.', 'training-translation-tracker' ); ?>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<form method="post" style="display:inline-block;margin-right:1rem;">
			<?php wp_nonce_field( 'tt_clear_cache_nonce' ); ?>
			<input type="hidden" name="tt_clear_cache" value="1">
			<?php submit_button( __( 'Clear GitHub Cache', 'training-translation-tracker' ), 'secondary', 'tt_clear_cache_submit', false ); ?>
		</form>
		<form method="post" style="display:inline-block;">
			<?php wp_nonce_field( 'tt_clear_lwp_cache_nonce' ); ?>
			<input type="hidden" name="tt_clear_lwp_cache" value="1">
			<?php submit_button( __( 'Clear Course Structure Cache', 'training-translation-tracker' ), 'secondary', 'tt_clear_lwp_cache_submit', false ); ?>
		</form>
		<p class="description" style="margin-top:0.5rem;">
			<?php esc_html_e( 'GitHub Cache: translation statuses from GitHub issues. Course Structure Cache: pathway/course/section data from learn.wordpress.org.', 'training-translation-tracker' ); ?>
		</p>

		<hr>
		<h2><?php esc_html_e( 'Usage', 'training-translation-tracker' ); ?></h2>
		<p><?php esc_html_e( 'Shortcode without parameters (uses above settings):', 'training-translation-tracker' ); ?></p>
		<pre><code>[translation_tracker]</code></pre>
		<p><?php esc_html_e( 'Project mode explicit:', 'training-translation-tracker' ); ?></p>
		<pre><code>[translation_tracker org="WordPress" project="104" locale="German"]</code></pre>
		<p><?php esc_html_e( 'REST mode explicit:', 'training-translation-tracker' ); ?></p>
		<pre><code>[translation_tracker repo="WordPress/Learn" label="[Content] Translation"]</code></pre>
	</div>
	<?php
}

/* -------------------------------------------------------
 *  GitHub GraphQL API – Project V2
 * ------------------------------------------------------- */

function tt_fetch_project_issues( $org, $project_number, $locale_filter, $token = '' ) {
	if ( empty( $token ) ) {
		return [ 'error' => __( 'A GitHub Token is required for Project mode (GraphQL). Please add one under Settings → Translation Tracker.', 'training-translation-tracker' ) ];
	}

	$cache_key     = tt_cache_key( 'tt_proj_' . md5( $org . $project_number . $locale_filter ) );
	$refresh_hours = absint( get_option( 'tt_refresh_hours', 4 ) );
	$cached        = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	$all_items = [];
	$after     = null;

	$query = '
		query($org: String!, $num: Int!, $after: String) {
			organization(login: $org) {
				projectV2(number: $num) {
					items(first: 100, after: $after) {
						pageInfo { hasNextPage endCursor }
						nodes {
							content {
								... on Issue {
									title number url state body
								}
							}
							fieldValues(first: 20) {
								nodes {
									... on ProjectV2ItemFieldSingleSelectValue {
										name
										field { ... on ProjectV2SingleSelectField { name } }
									}
									... on ProjectV2ItemFieldTextValue {
										text
										field { ... on ProjectV2Field { name } }
									}
								}
							}
						}
					}
				}
			}
		}
	';

	$page = 0;
	do {
		$variables = [
			'org'   => $org,
			'num'   => intval( $project_number ),
			'after' => $after,
		];

		$args = [
			'method'  => 'POST',
			'timeout' => 30,
			'headers' => [
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/vnd.github+json',
				'User-Agent'    => 'WordPress-Translation-Tracker/' . TT_VERSION,
				'Authorization' => 'Bearer ' . $token,
			],
			'body' => wp_json_encode( [ 'query' => $query, 'variables' => $variables ] ),
		];

		$response = wp_remote_post( 'https://api.github.com/graphql', $args );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['errors'] ) ) {
			return [ 'error' => 'GraphQL: ' . ( $body['errors'][0]['message'] ?? __( 'Unknown error', 'training-translation-tracker' ) ) ];
		}

		$nodes     = $body['data']['organization']['projectV2']['items']['nodes'] ?? [];
		$page_info = $body['data']['organization']['projectV2']['items']['pageInfo'] ?? [];

		foreach ( $nodes as $item ) {
			if ( empty( $item['content'] ) ) {
				continue;
			}
			$all_items[] = $item;
		}

		$has_next = ! empty( $page_info['hasNextPage'] );
		$after    = $page_info['endCursor'] ?? null;
		$page++;

	} while ( $has_next && $page < 20 );

	$filtered = [];
	foreach ( $all_items as $item ) {
		if ( empty( $locale_filter ) ) {
			$filtered[] = $item;
			continue;
		}
		$item_locale = tt_get_project_field( $item, 'locale' );
		if ( strtolower( $item_locale ) === strtolower( $locale_filter ) ) {
			$filtered[] = $item;
		}
	}

	$lessons = [];
	foreach ( $filtered as $item ) {
		$issue = $item['content'];
		if ( empty( $issue ) ) {
			continue;
		}
		$lessons[] = tt_build_lesson( $issue['body'] ?? '', [
			'title'          => $issue['title'],
			'issue_number'   => $issue['number'],
			'issue_url'      => $issue['url'],
			'issue_state'    => strtolower( $issue['state'] ),
			'project_status' => tt_get_project_field( $item, 'status' ),
		] );
	}

	usort( $lessons, function ( $a, $b ) {
		return $a['issue_number'] - $b['issue_number'];
	} );

	$now    = current_time( 'mysql' );
	$result = [ 'lessons' => $lessons, 'fetched' => $now, 'count' => count( $lessons ) ];
	set_transient( $cache_key, $result, $refresh_hours * HOUR_IN_SECONDS );
	update_option( 'tt_last_fetched', $now );

	return $result;
}

function tt_get_project_field( $item, $field_name_lower ) {
	foreach ( $item['fieldValues']['nodes'] ?? [] as $fv ) {
		$name = strtolower( $fv['field']['name'] ?? '' );
		if ( $name === $field_name_lower ) {
			return $fv['name'] ?? $fv['text'] ?? '';
		}
	}
	return '';
}

/* -------------------------------------------------------
 *  GitHub REST API
 * ------------------------------------------------------- */

function tt_fetch_issues( $repo, $label, $token = '' ) {
	$cache_key     = tt_cache_key( 'tt_issues_' . md5( $repo . $label ) );
	$refresh_hours = absint( get_option( 'tt_refresh_hours', 4 ) );
	$cached        = get_transient( $cache_key );

	if ( false !== $cached ) {
		return $cached;
	}

	$all_issues = [];
	$page       = 1;
	$per_page   = 100;

	while ( true ) {
		$url = sprintf(
			'https://api.github.com/repos/%s/issues?labels=%s&state=all&per_page=%d&page=%d',
			rawurlencode( $repo ),
			rawurlencode( $label ),
			$per_page,
			$page
		);
		$url = str_replace( rawurlencode( $repo ), str_replace( '%2F', '/', rawurlencode( $repo ) ), $url );

		$args = [
			'timeout' => 30,
			'headers' => [
				'Accept'     => 'application/vnd.github+json',
				'User-Agent' => 'WordPress-Translation-Tracker/' . TT_VERSION,
			],
		];

		if ( ! empty( $token ) ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			return [ 'error' => $response->get_error_message() ];
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			return [ 'error' => sprintf( 'GitHub API %d: %s', $code, $body['message'] ?? __( 'Unknown error', 'training-translation-tracker' ) ) ];
		}

		$issues = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $issues ) ) {
			break;
		}

		$all_issues = array_merge( $all_issues, $issues );

		if ( count( $issues ) < $per_page || $page >= 20 ) {
			break;
		}
		$page++;
	}

	$lessons = [];
	foreach ( $all_issues as $issue ) {
		if ( isset( $issue['pull_request'] ) ) {
			continue;
		}
		$lessons[] = tt_build_lesson( $issue['body'] ?? '', [
			'title'        => $issue['title'],
			'issue_number' => $issue['number'],
			'issue_url'    => $issue['html_url'],
			'issue_state'  => $issue['state'],
		] );
	}

	usort( $lessons, function ( $a, $b ) {
		return $a['issue_number'] - $b['issue_number'];
	} );

	$now    = current_time( 'mysql' );
	$result = [ 'lessons' => $lessons, 'fetched' => $now, 'count' => count( $lessons ) ];
	set_transient( $cache_key, $result, $refresh_hours * HOUR_IN_SECONDS );
	update_option( 'tt_last_fetched', $now );

	return $result;
}

/* -------------------------------------------------------
 *  Shared helpers
 * ------------------------------------------------------- */

function tt_slug_from_url( $url ) {
	if ( empty( $url ) ) {
		return '';
	}
	// Extract last path segment: https://learn.wordpress.org/lesson/what-is-wordpress/ → what-is-wordpress
	$path = rtrim( wp_parse_url( $url, PHP_URL_PATH ), '/' );
	return basename( $path );
}

/**
 * Build a full reverse-index stored as two maps in one transient:
 *   by_id  : lesson_id (int) → [ pathway, course, section ]
 *   by_slug : lesson_slug (string) → [ pathway, course, section ]
 *
 * The slug index eliminates per-lesson API calls in tt_fetch_lesson_structure().
 * Only called when the transient is missing. In normal operation this runs via
 * WP-Cron in the background (see tt_cron_build_course_map).
 */
function tt_build_course_map() {
	$by_id   = []; // lesson_id (int) → [ pathway, course, section ]
	$by_slug = []; // lesson_slug (string) → [ pathway, course, section ]
	$ua      = [ 'timeout' => 15, 'user-agent' => 'WordPress-Training-Translation-Tracker/' . TT_VERSION ];
	$empty   = [ 'by_id' => [], 'by_slug' => [] ];

	// 1. Learning-pathway terms
	$pathways = [];
	$resp = wp_remote_get( 'https://learn.wordpress.org/wp-json/wp/v2/learning-pathway?_fields=id,name&per_page=100', $ua );
	if ( ! is_wp_error( $resp ) && wp_remote_retrieve_response_code( $resp ) === 200 ) {
		foreach ( json_decode( wp_remote_retrieve_body( $resp ), true ) ?: [] as $t ) {
			$pathways[ $t['id'] ] = $t['name'];
		}
	}

	// 2. All courses
	$resp = wp_remote_get( 'https://learn.wordpress.org/wp-json/wp/v2/courses?per_page=100&_fields=id,title,learning-pathway&status=publish', $ua );
	if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
		set_transient( tt_cache_key( 'tt_lwp_course_map' ), $empty, HOUR_IN_SECONDS );
		return $empty;
	}
	$courses = json_decode( wp_remote_retrieve_body( $resp ), true ) ?: [];

	// 3. Course structure → build by_id index
	foreach ( $courses as $course ) {
		$course_id    = intval( $course['id'] );
		$course_name  = wp_strip_all_tags( $course['title']['rendered'] ?? '' );
		$pw_ids       = $course['learning-pathway'] ?? [];
		$pathway_name = ! empty( $pw_ids ) ? ( $pathways[ $pw_ids[0] ] ?? '' ) : '';

		$sr = wp_remote_get( 'https://learn.wordpress.org/wp-json/sensei-internal/v1/course-structure/' . $course_id, $ua );
		if ( is_wp_error( $sr ) || wp_remote_retrieve_response_code( $sr ) !== 200 ) {
			continue;
		}
		$structure = json_decode( wp_remote_retrieve_body( $sr ), true ) ?: [];

		foreach ( $structure as $item ) {
			if ( $item['type'] === 'module' ) {
				$section = $item['title'] ?? '';
				foreach ( $item['lessons'] ?? [] as $lesson ) {
					$by_id[ intval( $lesson['id'] ) ] = [
						'pathway' => $pathway_name,
						'course'  => $course_name,
						'section' => $section,
					];
				}
			} elseif ( $item['type'] === 'lesson' ) {
				$by_id[ intval( $item['id'] ) ] = [
					'pathway' => $pathway_name,
					'course'  => $course_name,
					'section' => '',
				];
			}
		}
	}

	// 4. Fetch all lesson slugs and build by_slug index.
	//    One paginated REST call per 100 lessons — far cheaper than one call per lesson.
	$page = 1;
	do {
		$resp = wp_remote_get(
			'https://learn.wordpress.org/wp-json/wp/v2/lessons?per_page=100&page=' . $page . '&_fields=id,slug&status=publish',
			$ua
		);
		if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
			break;
		}
		$lesson_page = json_decode( wp_remote_retrieve_body( $resp ), true ) ?: [];
		foreach ( $lesson_page as $lesson ) {
			$id   = intval( $lesson['id'] );
			$slug = $lesson['slug'] ?? '';
			if ( $slug !== '' && isset( $by_id[ $id ] ) ) {
				$by_slug[ $slug ] = $by_id[ $id ];
			}
		}
		$page++;
	} while ( count( $lesson_page ) === 100 && $page <= 20 );

	$map         = [ 'by_id' => $by_id, 'by_slug' => $by_slug ];
	$cache_hours = absint( get_option( 'tt_lwp_cache_hours', 24 ) );
	set_transient( tt_cache_key( 'tt_lwp_course_map' ), $map, $cache_hours * HOUR_IN_SECONDS );
	return $map;
}

/**
 * Return the cached course map. On a cache miss the map is built synchronously
 * (first page load only). After that WP-Cron keeps the cache warm in the background.
 *
 * @return array { by_id: array<int, array>, by_slug: array<string, array> }
 */
function tt_get_course_map() {
	$cached = get_transient( tt_cache_key( 'tt_lwp_course_map' ) );
	// Handle old transient format (flat id→struct array from before 0.1.5).
	if ( false !== $cached && ! isset( $cached['by_id'] ) ) {
		delete_transient( tt_cache_key( 'tt_lwp_course_map' ) );
		$cached = false;
	}
	if ( false !== $cached ) {
		return $cached;
	}
	return tt_build_course_map();
}

/**
 * Return the pathway/course/section for a lesson identified by its URL slug.
 *
 * Uses the by_slug index built into the course map — no per-lesson API call
 * is made as long as the map transient is warm.
 */
function tt_fetch_lesson_structure( $slug ) {
	if ( empty( $slug ) ) {
		return [ 'pathway' => '', 'course' => '', 'section' => '' ];
	}

	$empty = [ 'pathway' => '', 'course' => '', 'section' => '' ];
	$map   = tt_get_course_map();

	// Fast path: slug already in the map — no HTTP call needed.
	if ( isset( $map['by_slug'][ $slug ] ) ) {
		return $map['by_slug'][ $slug ];
	}

	// Slow path (rare): slug not in map, e.g. lesson added after last map build.
	// Fall back to a single REST call and cache the result per slug.
	$cache_key   = tt_cache_key( 'tt_lwp_' . sanitize_key( $slug ) );
	$cache_hours = absint( get_option( 'tt_lwp_cache_hours', 24 ) );
	$per_slug    = get_transient( $cache_key );
	if ( false !== $per_slug ) {
		return $per_slug;
	}

	$resp = wp_remote_get(
		'https://learn.wordpress.org/wp-json/wp/v2/lessons?slug=' . rawurlencode( $slug ) . '&_fields=id&per_page=1',
		[ 'timeout' => 15, 'user-agent' => 'WordPress-Training-Translation-Tracker/' . TT_VERSION ]
	);
	if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) {
		set_transient( $cache_key, $empty, HOUR_IN_SECONDS );
		return $empty;
	}
	$lessons = json_decode( wp_remote_retrieve_body( $resp ), true );
	if ( empty( $lessons ) || empty( $lessons[0]['id'] ) ) {
		set_transient( $cache_key, $empty, HOUR_IN_SECONDS );
		return $empty;
	}

	$lesson_id = intval( $lessons[0]['id'] );
	$result    = $map['by_id'][ $lesson_id ] ?? $empty;

	set_transient( $cache_key, $result, $cache_hours * HOUR_IN_SECONDS );
	return $result;
}

function tt_extract_order( $body ) {
	if ( empty( $body ) ) {
		return 9999;
	}
	if ( preg_match( '/^-\s*order[^:]*:\s*(\d+)/im', $body, $m ) ) {
		return intval( $m[1] );
	}
	return 9999;
}

function tt_build_lesson( $body_text, $meta ) {
	$status_data = tt_parse_status_table( $body_text );
	$en_url      = tt_extract_lesson_url( $body_text );
	$slug        = tt_slug_from_url( $en_url );
	$struct      = $slug ? tt_fetch_lesson_structure( $slug ) : [ 'pathway' => '', 'course' => '', 'section' => '' ];

	$lesson = array_merge( $meta, [
		'en_name'     => tt_extract_lesson_name( $body_text ),
		'de_name'     => tt_extract_lesson_de_name( $body_text ),
		'en_url'      => $en_url,
		'de_url'      => tt_extract_lesson_de_url( $body_text ),
		'tv_url'      => tt_extract_tv_url( $body_text ),
		'youtube_url' => tt_extract_youtube_url( $body_text ),
		'pathway'     => $struct['pathway'],
		'course'      => $struct['course'],
		'section'     => $struct['section'],
		'order'       => tt_extract_order( $body_text ),
		'hasTable'    => ! empty( $status_data ),
	] );

	foreach ( [ 'thumbnails', 'text', 'subtitles', 'exercise', 'quiz', 'audio', 'video' ] as $f ) {
		$lesson[ $f ] = ! empty( $status_data[ $f ] )
			? $status_data[ $f ]
			: [ 'status' => 'open', 'creator' => '', 'reviewer' => '' ];
	}

	return $lesson;
}

function tt_parse_status_table( $body ) {
	if ( empty( $body ) ) {
		return [];
	}

	$valid_fields = [ 'thumbnails', 'text', 'subtitles', 'exercise', 'quiz', 'audio', 'video' ];

	// 1. Preferred: extract block between HTML comment markers
	$start = strpos( $body, '<!-- TRANSLATION-STATUS-START -->' );
	$end   = strpos( $body, '<!-- TRANSLATION-STATUS-END -->' );

	if ( false !== $start && false !== $end ) {
		$table_block = substr( $body, $start + strlen( '<!-- TRANSLATION-STATUS-START -->' ), $end - $start - strlen( '<!-- TRANSLATION-STATUS-START -->' ) );
	} else {
		// 2. Fallback: find any markdown table that contains at least one known component name
		// Match a block of lines that all start with |
		if ( ! preg_match_all( '/(?:^|\n)((?:\|[^\n]+\n?){2,})/m', $body, $matches ) ) {
			return [];
		}
		$table_block = '';
		foreach ( $matches[1] as $candidate ) {
			foreach ( $valid_fields as $field ) {
				if ( stripos( $candidate, $field ) !== false ) {
					$table_block = $candidate;
					break 2;
				}
			}
		}
		if ( $table_block === '' ) {
			return [];
		}
	}

	$lines = array_filter( array_map( 'trim', explode( "\n", $table_block ) ), function ( $l ) {
		return strpos( $l, '|' ) === 0;
	} );

	$result = [];

	foreach ( $lines as $line ) {
		if ( strpos( $line, '---' ) !== false || stripos( $line, 'component' ) !== false ) {
			continue;
		}

		$cells = array_values( array_filter( array_map( 'trim', explode( '|', $line ) ), function ( $c ) {
			return $c !== '';
		} ) );

		if ( count( $cells ) < 2 ) {
			continue;
		}

		$component = strtolower( trim( $cells[0] ) );
		$status    = strtolower( trim( $cells[1] ) );
		$creator   = isset( $cells[2] ) ? ltrim( trim( $cells[2] ), '@' ) : '';
		$reviewer  = isset( $cells[3] ) ? ltrim( trim( $cells[3] ), '@' ) : '';

		if ( in_array( $component, $valid_fields, true ) ) {
			$result[ $component ] = [
				'status'   => $status,
				'creator'  => $creator,
				'reviewer' => $reviewer,
			];
		}
	}

	return $result;
}

function tt_extract_lesson_name( $body ) {
	if ( empty( $body ) ) {
		return '';
	}
	if ( preg_match( '/(?:english lesson name|original title)[^:]*:\s*([^\n]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

function tt_extract_lesson_de_name( $body ) {
	if ( empty( $body ) ) {
		return '';
	}
	if ( preg_match( '/(?:(?:german|deutsch(?:er|e)?)\s+lesson name|translation title)[^:]*:\s*([^\n]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

function tt_extract_lesson_url( $body ) {
	if ( empty( $body ) ) {
		return '';
	}
	if ( preg_match( '/Link to original content[^:]*:\s*(https:\/\/learn\.wordpress\.org\/lesson\/[^\s\n]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

function tt_extract_lesson_de_url( $body ) {
	if ( empty( $body ) ) {
		return '';
	}
	// Only match URLs from an explicit labelled field. A URL-pattern fallback
	// (e.g. matching /de/ or ?lang=de) was removed because it caused false
	// positives when issue bodies contained unrelated links to German-language
	// pages (e.g. wikipedia.org/wiki/de/…).
	if ( preg_match( '/(?:link to translated content|translated content|german lesson|deutsche lektion|übersetzt[ae]? lektion|translation url|de url)[^:]*:\s*(https:\/\/[^\s\n<]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

function tt_extract_tv_url( $body ) {
	if ( empty( $body ) ) {
		return '';
	}
	// Explicit field in issue body
	if ( preg_match( '/link to tv[^:]*:\s*(https?:\/\/[^\s\n<]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	// Auto-detect wordpress.tv URLs
	if ( preg_match( '/(https?:\/\/wordpress\.tv\/[^\s\n<]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

function tt_extract_youtube_url( $body ) {
	if ( empty( $body ) ) {
		return '';
	}
	// Explicit field in issue body
	if ( preg_match( '/link to youtube[^:]*:\s*(https?:\/\/[^\s\n<]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	// Auto-detect YouTube URLs
	if ( preg_match( '/(https?:\/\/(?:www\.)?youtube\.com\/[^\s\n<]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	if ( preg_match( '/(https?:\/\/youtu\.be\/[^\s\n<]+)/i', $body, $m ) ) {
		return trim( $m[1] );
	}
	return '';
}

function tt_load_data( $atts ) {
	$token          = tt_get_token();
	$project_number = isset( $atts['project'] ) ? absint( $atts['project'] ) : absint( get_option( 'tt_project_number', 104 ) );

	if ( $project_number > 0 ) {
		$org    = isset( $atts['org'] ) ? sanitize_text_field( $atts['org'] ) : get_option( 'tt_github_org', 'WordPress' );
		$locale = isset( $atts['locale'] ) ? sanitize_text_field( $atts['locale'] ) : get_option( 'tt_locale_filter', 'German' );
		return tt_fetch_project_issues( $org, $project_number, $locale, $token );
	}

	$repo  = isset( $atts['repo'] ) ? sanitize_text_field( $atts['repo'] ) : get_option( 'tt_github_repo', 'WordPress/Learn' );
	$label = isset( $atts['label'] ) ? sanitize_text_field( $atts['label'] ) : get_option( 'tt_github_label', '[Content] Translation' );
	return tt_fetch_issues( $repo, $label, $token );
}

/* -------------------------------------------------------
 *  AJAX Endpoint
 * ------------------------------------------------------- */

add_action( 'wp_ajax_tt_refresh', 'tt_ajax_refresh' );
add_action( 'wp_ajax_nopriv_tt_refresh', 'tt_ajax_refresh' );

function tt_ajax_refresh() {
	check_ajax_referer( 'tt_refresh_nonce', 'nonce' );

	$token          = tt_get_token();
	$project_number = absint( get_option( 'tt_project_number', 104 ) );

	// Always clear shortcode-level cache so the updated data is served on the
	// next regular page load after a successful AJAX refresh.
	tt_clear_shortcode_cache();

	if ( $project_number > 0 ) {
		$org    = get_option( 'tt_github_org', 'WordPress' );
		$locale = get_option( 'tt_locale_filter', 'German' );
		$cache_key = tt_cache_key( 'tt_proj_' . md5( $org . $project_number . $locale ) );
		delete_transient( $cache_key );
		wp_send_json( tt_fetch_project_issues( $org, $project_number, $locale, $token ) );
	}

	$repo  = get_option( 'tt_github_repo', 'WordPress/Learn' );
	$label = get_option( 'tt_github_label', '[Content] Translation' );
	$cache_key = tt_cache_key( 'tt_issues_' . md5( $repo . $label ) );
	delete_transient( $cache_key );
	wp_send_json( tt_fetch_issues( $repo, $label, $token ) );
}

/**
 * Delete all shortcode-level data transients (tt_sc_*).
 * Called on AJAX refresh and from the settings page cache-clear button.
 */
function tt_clear_shortcode_cache() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Intentional bulk transient deletion; no WP API covers this pattern.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tt_sc_%' OR option_name LIKE '_transient_timeout_tt_sc_%'" );
}

/* -------------------------------------------------------
 *  Shortcode [translation_tracker]
 * ------------------------------------------------------- */

add_shortcode( 'translation_tracker', 'tt_shortcode_render' );

function tt_shortcode_render( $atts ) {
	$atts = shortcode_atts( [
		'repo'    => get_option( 'tt_github_repo', 'WordPress/Learn' ),
		'label'   => get_option( 'tt_github_label', '[Content] Translation' ),
		'project' => get_option( 'tt_project_number', 104 ),
		'org'     => get_option( 'tt_github_org', 'WordPress' ),
		'locale'  => get_option( 'tt_locale_filter', 'German' ),
	], $atts, 'translation_tracker' );

	// Shortcode-level cache: avoids deserialising the full lessons array on
	// every page view when WordPress's own page cache is not active.
	$sc_key      = tt_cache_key( 'tt_sc_' . md5( wp_json_encode( $atts ) ) );
	$refresh_ttl = absint( get_option( 'tt_refresh_hours', 4 ) ) * HOUR_IN_SECONDS;
	$data        = get_transient( $sc_key );

	if ( false === $data ) {
		$data = tt_load_data( $atts );
		// Only cache successful responses — errors must be retried on the next load.
		if ( empty( $data['error'] ) ) {
			set_transient( $sc_key, $data, $refresh_ttl );
		}
	}

	$asset_ver = ( defined( 'WP_DEBUG' ) && WP_DEBUG )
		? filemtime( TT_PLUGIN_DIR . 'assets/dashboard.css' )
		: TT_VERSION;
	wp_enqueue_style( 'tt-dashboard', TT_PLUGIN_URL . 'assets/dashboard.css', [], $asset_ver );
	wp_enqueue_script( 'tt-dashboard', TT_PLUGIN_URL . 'assets/dashboard.js', [], $asset_ver, true );
	wp_localize_script( 'tt-dashboard', 'ttData', [
		'lessons' => $data['lessons'] ?? [],
		'error'   => $data['error'] ?? '',
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonce'   => wp_create_nonce( 'tt_refresh_nonce' ),
		'project' => absint( $atts['project'] ),
		'org'     => $atts['org'],
		'locale'  => $atts['locale'],
		'repo'    => $atts['repo'],
		'label'   => $atts['label'],
		'i18n'    => [
			'search_placeholder' => __( 'Search…', 'training-translation-tracker' ),
			'filter_all'                 => __( 'All', 'training-translation-tracker' ),
			'filter_looking'             => __( 'Looking for Translator', 'training-translation-tracker' ),
			'filter_awaiting'            => __( 'Awaiting Triage', 'training-translation-tracker' ),
			'filter_in_progress'         => __( 'Translation in Progress', 'training-translation-tracker' ),
			'filter_ready_for_review'    => __( 'Ready for Review', 'training-translation-tracker' ),
			'filter_preparing_to_publish'=> __( 'Preparing to Publish', 'training-translation-tracker' ),
			'filter_published'           => __( 'Published or Closed', 'training-translation-tracker' ),
			'status_done'        => __( 'Done', 'training-translation-tracker' ),
			'status_review'      => __( 'Review', 'training-translation-tracker' ),
			'status_wip'         => __( 'In Progress', 'training-translation-tracker' ),
			'status_open'        => __( 'Open', 'training-translation-tracker' ),
			'status_na'          => '—', // Intentional Unicode em-dash; not translatable by design.
			'stat_lessons'       => __( 'Translations', 'training-translation-tracker' ),
			'stat_done'          => __( 'Done', 'training-translation-tracker' ),
			'stat_wip'           => __( 'In Progress', 'training-translation-tracker' ),
			'stat_open'          => __( 'Open', 'training-translation-tracker' ),
			'no_lessons'         => __( 'No translations found.', 'training-translation-tracker' ),
			'issue_closed'       => __( 'closed', 'training-translation-tracker' ),
			'issue_open'         => __( 'open', 'training-translation-tracker' ),
			'no_status_table'    => __( 'no status table', 'training-translation-tracker' ),
			'info_loaded'        => __( 'issues loaded', 'training-translation-tracker' ),
			'info_with_table'    => __( 'with status table', 'training-translation-tracker' ),
			'info_without_table' => __( 'without table (showing default «open»)', 'training-translation-tracker' ),
			'legend_done'        => __( 'Done', 'training-translation-tracker' ),
			'legend_review'      => __( 'In Review', 'training-translation-tracker' ),
			'legend_wip'         => __( 'In Progress', 'training-translation-tracker' ),
			'legend_open'        => __( 'Open', 'training-translation-tracker' ),
			'sort_asc'           => __( 'Sort ascending', 'training-translation-tracker' ),
			'sort_desc'          => __( 'Sort descending', 'training-translation-tracker' ),
			'tv'                 => __( 'WordPress.tv', 'training-translation-tracker' ),
			'youtube'            => __( 'YouTube', 'training-translation-tracker' ),
			'refresh'            => __( 'Refresh', 'training-translation-tracker' ),
			'refreshing'         => __( 'Refreshing…', 'training-translation-tracker' ),
			'collapse_all'       => __( 'Collapse all', 'training-translation-tracker' ),
			'expand_all'         => __( 'Expand all', 'training-translation-tracker' ),
		],
	] );

	ob_start();
	?>
	<div id="tt-tracker" class="tt-tracker">
		<div class="tt-stats" id="tt-stats"></div>

		<div class="tt-filters">
			<input type="text" id="tt-search" class="tt-search"
				   placeholder="<?php esc_attr_e( 'Search…', 'training-translation-tracker' ); ?>">
			<button class="tt-filter-btn active" data-filter="all"><?php esc_html_e( 'All', 'training-translation-tracker' ); ?></button>
			<button class="tt-filter-btn" data-filter="Looking for Translator"><?php esc_html_e( 'Looking for Translator', 'training-translation-tracker' ); ?></button>
			<button class="tt-filter-btn" data-filter="Awaiting Triage"><?php esc_html_e( 'Awaiting Triage', 'training-translation-tracker' ); ?></button>
			<button class="tt-filter-btn" data-filter="Translation in Progress"><?php esc_html_e( 'Translation in Progress', 'training-translation-tracker' ); ?></button>
			<button class="tt-filter-btn" data-filter="Ready for Review"><?php esc_html_e( 'Ready for Review', 'training-translation-tracker' ); ?></button>
			<button class="tt-filter-btn" data-filter="Preparing to Publish"><?php esc_html_e( 'Preparing to Publish', 'training-translation-tracker' ); ?></button>
			<button class="tt-filter-btn" data-filter="Published or Closed"><?php esc_html_e( 'Published or Closed', 'training-translation-tracker' ); ?></button>
			<button class="tt-collapse-btn" id="tt-collapse-all"><?php esc_html_e( 'Collapse all', 'training-translation-tracker' ); ?></button>
			<button class="tt-refresh-btn" id="tt-refresh"><?php esc_html_e( 'Refresh', 'training-translation-tracker' ); ?></button>
		</div>

		<div class="tt-table-wrap">
			<table class="tt-table">
				<thead>
					<tr>
						<th class="tt-col-lesson tt-sortable" data-sort="title"><?php esc_html_e( 'Original', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-col-lesson"><?php esc_html_e( 'Translation', 'training-translation-tracker' ); ?></th>
						<th class="tt-sortable" data-sort="issue_number"><?php esc_html_e( 'Issue', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-status-col tt-sortable" data-sort="thumbnails"><?php esc_html_e( 'Thumb.', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-status-col tt-sortable" data-sort="text"><?php esc_html_e( 'Text', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-status-col tt-sortable" data-sort="subtitles"><?php esc_html_e( 'Subtitles', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-status-col tt-sortable" data-sort="exercise"><?php esc_html_e( 'Exercise', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-status-col tt-sortable" data-sort="quiz"><?php esc_html_e( 'Quiz', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-status-col tt-sortable" data-sort="audio"><?php esc_html_e( 'Audio', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
						<th class="tt-status-col tt-sortable" data-sort="video"><?php esc_html_e( 'Video', 'training-translation-tracker' ); ?><span class="tt-sort-indicator"></span></th>
					</tr>
				</thead>
				<tbody id="tt-tbody"></tbody>
			</table>
		</div>

		<div class="tt-legend" id="tt-legend"></div>
		<div class="tt-info" id="tt-info"></div>
	</div>
	<?php
	return ob_get_clean();
}
