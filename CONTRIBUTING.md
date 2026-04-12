# Contributing to Training Translation Tracker

Thank you for your interest in contributing!

## Getting Started

1. Fork the repository and clone it locally.
2. Symlink or copy the `plugin/` subfolder into your WordPress installation as `wp-content/plugins/training-training-translation-tracker`.
3. Activate the plugin in the WordPress admin and configure it under **Settings → Training Translation Tracker**.

To build the installable ZIP locally:

```bash
git archive --format=zip --prefix=training-training-translation-tracker/ HEAD:plugin -o training-translation-tracker.zip
```

## Coding Standards

- PHP: [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- JavaScript: [WordPress JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/)
- All strings must be wrapped in `__()` / `esc_html__()` etc. with the
  `training-translation-tracker` text domain.

## Branch Strategy

- `main` = latest stable release
- Feature work and bug fixes are developed on short-lived feature branches (e.g. `feature/add-collapse-button`, `fix/cache-key-multisite`).
- Open a Pull Request against `main` when your change is ready for review.

## Submitting a Pull Request

1. Make sure your branch is up to date with `main`.
2. Run the code through PHPCS with the WordPress ruleset before submitting.
3. Describe **what** the PR does and **why** in the PR description.
4. Reference any related Issue with `Fixes #123` or `Relates to #123`.

## Reporting Issues

Use the GitHub Issue templates provided in this repository:
- **Bug report** – for anything that does not work as expected.
- **Feature request** – for new ideas or improvements.

For security vulnerabilities, please read [SECURITY.md](.github/SECURITY.md) first.
