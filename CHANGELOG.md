# Changelog

<!-- markdownlint-disable MD013 -->

All notable changes to Training Translation Tracker are documented here.

> **This plugin is currently in beta.** Versions below 1.0.0 are pre-release and may change. The first stable release will be 1.0.0 after thorough testing.

---

## [Unreleased]

## [0.1.5-beta] — 2026-04-12

### Added

- `CONTRIBUTING.md` with setup instructions, coding standards, and PR process.
- `uninstall.php`: removes all plugin options and transients when the plugin is deleted.
- Settings page: section headings **GitHub Project** and **Cache & Refresh** added.
- Search now also matches `pathway`, `course`, and `section` group labels.
- Group collapse state (`groupState`) is reset when the status filter changes.
- `.github/SECURITY.md` for private vulnerability reporting.
- `Requires at least: 5.8` and `Requires PHP: 8.0` in plugin header.
- `wporg-assets/` folder for WordPress.org banners and icons (not included in plugin ZIP).
- `USER-GUIDE.md`: new section explaining filter button labels (GitHub Project status values, intentionally in English).
- `USER-GUIDE.md`: new FAQ entry explaining when and how to clear the Course Structure Cache.

### Changed

- **Repository restructured:** plugin files moved to `plugin/` subdirectory; WP.org assets to `wporg-assets/`; GitHub-only files remain at repo root. ZIP is now built with `git archive HEAD:plugin`.
- `readme.txt`: aligned with `README.md` — updated description to mention GitHub Project V2, expanded feature list, removed non-existent CSV export, updated screenshots.
- `README.md`: updated installation instructions, requirements (PHP 8.0), repository structure overview, and documentation references.
- Settings page: removed redundant "General" section heading.
- GitHub Token description in settings: clarified that a token is effectively required in both modes.
- `release.yml`: updated ZIP build to use `HEAD:plugin`; pinned `softprops/action-gh-release` to commit SHA; fixed double prefix bug (`training-training-translation-tracker` → `training-translation-tracker`).
- `CONTRIBUTING.md`: fixed double prefix in plugin folder path and ZIP build command.
- `DEVELOPER.md`: corrected PHP requirement from 7.4 to 8.0; updated file structure overview and ZIP build instructions.
- `training-translation-tracker.php`: replaced `parse_url()` with `wp_parse_url()`.
- `.po`/`.mo`/`.pot`: updated to include all new i18n strings (`refresh`, `refreshing`, `collapse_all`, `expand_all`, filter labels, new settings strings); stale entries removed; compiled with `msgfmt`.

### Fixed

- Plugin Check errors: `translators:` comments moved directly above `esc_html__()` calls inside `printf()` (lines 212 and 298).
- Plugin Check errors: unescaped `$val` / `$refresh_hours` in `printf`, `parse_url` usage.
- Plugin Check warnings: added `phpcs:ignore` for intentional bulk transient deletions via direct DB query.
- CSP compatibility: replaced all inline `onclick` / `oninput` event handlers with a single delegated `addEventListener` in `dashboard.js`. Filter buttons, sort headers, group-toggle rows, and the search field no longer require `unsafe-inline` in a Content Security Policy.

### Removed

- Dead function `lessonOverallStatus()` in `dashboard.js` (was never called).
- Global `window.ttSetFilter`, `window.ttSort`, `window.ttToggleGroup`, `window.ttRender` — all functions are now private to the IIFE.
- Unused `FIELDS` constant in `dashboard.js`.

### Renamed

- Plugin name: **Training Translation Tracker** (was: Translation Tracker).
- Plugin slug and text domain: `training-translation-tracker` (was: `translation-tracker`).
  Resolves Plugin Check `textdomain_mismatch` and `trademarked_term` warnings.
- Main plugin file: `training-translation-tracker.php`.
- Language files: `training-translation-tracker-de_DE.{po,mo,pot}`.

## [0.1.4-beta] — 2026-04-10

### Added

- Hierarchical grouping of translations by Learning Pathway → Course → Section
- Course structure fetched automatically from the learn.wordpress.org REST API (`sensei-internal/v1/course-structure`)
- Collapsible group headers with translation count per group
- Manual `Order:` field in GitHub issues to control sort order within a section
- Separate cache for course structure (`tt_lwp_cache_hours`, default 24h)
- Separate "Clear Course Structure Cache" button in settings
- Recognises new issue field names `Original title` and `Translation title` in addition to legacy `English lesson name` / `German lesson name`

### Changed

- Column headers renamed: "English Lesson" → "Original", "German Lesson" → "Translation"
- Stats bar and search placeholder now use "Translations" instead of "Lessons"
- Default sort order follows course structure; column header clicks switch to flat sorted view
- Plugin is no longer locale-specific — any translation locale can be tracked

### Fixed

- `_lesson_course` does not exist in the public REST API — replaced with a course-map approach via `sensei-internal/v1/course-structure`

---

## [0.1.3-beta] — 2026-03-15

### Added

- Translation names read from issue body (`English lesson name` / `German lesson name`)
- Italic fallback when no name is defined
- Clickable creator and reviewer GitHub profile links in status badge tooltips
- Tooltip hover bridge so the tooltip stays open when moving the mouse into it
- WordPress.tv and YouTube links displayed beneath translation names
- Viewport breakout layout (`width: 100vw`) for full-width display inside any theme
- Sortable columns
- Auto-refresh interval setting (1h – 72h)

### Fixed

- Stats bar: number and label no longer run together

---

## [0.1.2-beta] — 2026-02-01

### Added

- GitHub Project V2 GraphQL API mode with Locale filter
- Status filter buttons (All / Done / In Progress / Open)
- Search field
- Progress bar per translation
- Legend

---

## [0.1.1-beta] — 2026-01-15

### Added

- Translation support (`.po` / `.mo`)
- Settings page under **Settings → Training Translation Tracker**
- Cache using WordPress Transients

---

## [0.1.0-beta] — 2026-01-01

Initial beta release. REST API mode with label filter, basic status table parsing.
