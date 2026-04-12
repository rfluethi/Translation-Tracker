# Training Translation Tracker

> **Beta** — v0.1.5-beta. The first stable release will be 1.0.0
> after thorough testing.

A WordPress plugin that displays translation progress of
[learn.wordpress.org](https://learn.wordpress.org) content as an
interactive dashboard. Developed for the WordPress DACH training team,
but supports any locale.

---

## Features

- Shortcode `[translation_tracker]` — embed on any page or post
- Reads translation status directly from GitHub Issues or GitHub Project V2
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
| PHP | 8.0 or higher |
| GitHub Token | Required for Project V2 mode (`project` scope); strongly recommended for REST mode |

---

## Installation

### A — Download ZIP (recommended)

```bash
git clone https://github.com/your-org/training-translation-tracker.git
cd training-translation-tracker
git archive --format=zip --prefix=training-translation-tracker/ HEAD:plugin \
  -o training-translation-tracker.zip
```

Then in WordPress: **Plugins → Add New → Upload Plugin**, select the ZIP,
click **Install Now** and activate.

### B — Manual via FTP/SFTP

Copy the contents of the `plugin/` folder to
`wp-content/plugins/training-translation-tracker/` and activate under **Plugins**
in the WordPress admin.

---

## Quick Start

1. Go to **Settings → Training Translation Tracker**
2. Enter your GitHub Token
   - Project mode (default): needs `project` scope —
     [create one here](https://github.com/settings/tokens)
   - REST mode (Project Number = 0): `public_repo` scope, strongly recommended
3. Set **Project Number** to your project (e.g. `104`) and
   **Locale Filter** to your locale (e.g. `German`)
4. Save settings
5. Add the shortcode to any page:

```text
[translation_tracker]
```

> **Tip:** Set the `TT_GITHUB_TOKEN` environment variable on your server
> instead of storing the token in the database.

---

## GitHub Issue Format

Each translation issue needs a status table and a few extra fields
so the dashboard can display it correctly.
See the [User Guide](docs/USER-GUIDE.md) for the exact format.

---

## Repository Structure

```text
training-translation-tracker/
├── plugin/          Plugin files — these are what the ZIP contains
├── wporg-assets/    WordPress.org banners and icons (not in ZIP)
├── docs/            Documentation
└── .github/         GitHub configuration (workflows, templates, security)
```

---

## Documentation

| Document | Description |
| --- | --- |
| [User Guide](docs/USER-GUIDE.md) | How to fill in GitHub issues for the dashboard |
| [Developer Docs](docs/DEVELOPER.md) | Settings, API details, ZIP build, file structure |
| [Changelog](CHANGELOG.md) | Version history |
| [Contributing](CONTRIBUTING.md) | How to contribute — setup, standards, PR process |
| [Security Policy](.github/SECURITY.md) | How to report vulnerabilities |

---

## License

[GPL-2.0-or-later](LICENSE)
