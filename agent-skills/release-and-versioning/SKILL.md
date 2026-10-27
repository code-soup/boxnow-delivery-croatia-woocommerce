---
name: release-and-versioning
description: Bump plugin version and compatibility headers for a release. Use when preparing a new version for develop or master.
---

# Release and versioning

## Files to update together

| File | What to change |
|------|----------------|
| `index.php` | `Version`, `Requires at least`, `Tested up to`, `Requires PHP`, `Requires Plugins`, `WC requires at least`, `WC tested up to` |
| `run.php` | `PLUGIN_VERSION`, `MIN_WP_VERSION`, `MIN_PHP_VERSION`, `MIN_WC_VERSION`, `MIN_WP_VERSION_SUPPORT_TERMS` |
| `package.json` / `package-lock.json` | `version` field |
| `CHANGELOG.md` | New section under Keep a Changelog format |

## Runtime checks

`RequirementChecksTrait` reads `MIN_*` from `run.php` config — must match `index.php` minimums.

## Frontend assets

After JS/SCSS changes: `npm run build` and commit `dist/` if your release pipeline ships built assets (`dist/` may be gitignored locally; force-add for release commits when needed).

## Rules

- Use semver for `PLUGIN_VERSION`
- "Tested up to" uses major.minor for WordPress and WooCommerce headers; document exact patch versions tested in README/CHANGELOG
