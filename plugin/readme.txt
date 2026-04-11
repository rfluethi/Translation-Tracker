=== Translation Tracker ===
Contributors: learn-wp-dach
Tags: translation, workflow, dashboard, github, progress
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 0.1.4-beta
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Track and display translation progress for learn.wordpress.org 
by reading GitHub issue statuses in a clear, filterable table.

== Description ==

**Translation Tracker** connects your WordPress site to GitHub and 
displays the current translation progress for learn.wordpress.org 
content. It reads status tables directly from GitHub Issues and 
presents them in a clean, responsive table — filtered by language 
and always up to date.

This plugin is especially useful for translation teams who manage 
their workflow via GitHub Issues and want to share the current 
progress publicly on a WordPress page.

**Features:**

* Shortcode `[translation_tracker]` — insert on any page or post
* Reads status tables directly from GitHub Issues
* Server-side caching (configurable cache duration)
* Filter, search and visual progress bars
* CSV export of translation data
* AJAX refresh button — updates data without page reload
* Adapts to the active WordPress theme (light design)
* Settings page under Settings → Translation Tracker

**How it works:**

1. Translation status is maintained inside GitHub Issues using a 
   defined table format.
2. The plugin fetches and parses that data via the GitHub API.
3. Results are displayed on any page using the 
   `[translation_tracker]` shortcode.

**Use Case:**

This plugin was built for the German-speaking learn.wordpress.org 
community (learn-wp-dach) but can be used for any language team 
that tracks translations via GitHub Issues.

== Installation ==

**Automatic installation:**

1. Go to Plugins → Add New in your WordPress admin
2. Search for "Translation Tracker"
3. Click "Install Now" and then "Activate"

**Manual installation:**

1. Download the plugin ZIP file
2. Upload the `wp-translation-tracker` folder to `/wp-content/plugins/`
3. Activate the plugin in the WordPress admin under Plugins
4. Go to Settings → Translation Tracker to configure the plugin
5. Add the shortcode `[translation_tracker]` to any page or post

**Required configuration:**

* GitHub repository URL containing the translation issues
* Target language to display
* (Optional) GitHub API token for higher rate limits
* (Optional) Cache duration in minutes

== Frequently Asked Questions ==

= Do I need a GitHub account to use this plugin? =

No. The plugin reads public GitHub Issues without authentication. 
However, for higher API rate limits, you can optionally add a 
GitHub personal access token in the plugin settings.

= What format must the GitHub Issue use? =

The translation status must be maintained in a Markdown table 
inside the GitHub Issue. The plugin parses this table to extract 
the translation progress data. Please refer to the plugin 
documentation for the exact required table format.

= Can I use this plugin for languages other than German? =

Yes. The plugin is language-agnostic. You can configure any 
language and any GitHub repository in the settings.

= How often is the data refreshed? =

The plugin caches the GitHub data on the server. You can configure 
the cache duration in the plugin settings. Users can also trigger 
a manual refresh via the AJAX refresh button on the frontend.

= Is the plugin compatible with page builders? =

The shortcode `[translation_tracker]` works in any context that 
supports standard WordPress shortcodes, including most page 
builders (Elementor, Beaver Builder, etc.).

= Where do I find the Settings page? =

Go to your WordPress admin panel → Settings → Translation Tracker.

== Screenshots ==

1. Frontend table view with progress bars, search and filter
2. CSV export button and AJAX refresh in action
3. Settings page under Settings → Translation Tracker

== Changelog ==

= 0.1.4-beta =
* Beta release

== Upgrade Notice ==

= 0.1.4-beta =
Beta release of Translation Tracker.
