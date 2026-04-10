# Translation Tracker

> **Beta** — v0.1.4-beta. The first stable release will be 1.0.0
> after thorough testing.

A WordPress plugin that displays translation progress of
[learn.wordpress.org](https://learn.wordpress.org) content as an
interactive dashboard. Developed for the WordPress DACH training team,
but supports any locale.

---

## Features

- Shortcode `[translation_tracker]` — embed on any page or post
- Reads translation status directly from GitHub Issues
- Filters by any locale via GitHub Project V2 (`Locale` field)
- Hierarchical grouping: Learning Pathway → Course → Section
- Course structure fetched automatically from learn.wordpress.org API
- Sortable columns, search, and status filters
- Collapsible group headers
- Creator / Reviewer shown as clickable GitHub profile links
- WordPress.tv and YouTube links per translation
- Auto-refresh (configurable: 1h – 72h)
- Full i18n support — ships with English and German
- All CSS classes prefixed with `tt-` (no theme conflicts)
- Full-width viewport breakout layout

---

## Requirements

| | |
| --- | --- |
| WordPress | 5.8 or higher |
| PHP | 7.4 or higher |
| GitHub Token | Required for Project V2 (`project` scope) |

---

## Installation

### A — Upload ZIP (recommended)

1. Download or clone this repository
2. In WordPress: **Plugins → Add New → Upload Plugin**
3. Select the ZIP and click **Install Now**
4. Activate the plugin

### B — Manual via FTP/SFTP

1. Copy the `wp-translation-tracker` folder to `wp-content/plugins/`
2. Activate under **Plugins** in the WordPress admin

---

## Quick Start

1. Go to **Settings → Translation Tracker**
2. Enter your GitHub Token — needs `project` scope,
   [create one here](https://github.com/settings/tokens)
3. Set **Project Number** to your project (e.g. `104`) and
   **Locale Filter** to your locale (e.g. `German`)
4. Save settings
5. Add the shortcode to any page:

```text
[translation_tracker]
```

---

## GitHub Issue Format

Each translation issue needs a status table and a few extra fields
so the dashboard can display it correctly.
See the [User Guide](docs/USER-GUIDE.md) for the exact format.

---

## Documentation

| Document | Audience |
| --- | --- |
| [User Guide](docs/USER-GUIDE.md) | Translators — how to fill in issues |
| [Developer Docs](docs/DEVELOPER.md) | Admins & developers — settings, API |

---

## License

[GPL-2.0-or-later](LICENSE)
