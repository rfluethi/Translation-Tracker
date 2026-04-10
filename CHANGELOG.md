# Changelog

All notable changes to Translation Tracker are documented here.

> **This plugin is currently in beta.** Versions below 1.0.0 are pre-release and may change. The first stable release will be 1.0.0 after thorough testing.

---

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
- Settings page under **Settings → Translation Tracker**
- Cache using WordPress Transients

---

## [0.1.0-beta] — 2026-01-01

Initial beta release. REST API mode with label filter, basic status table parsing.
