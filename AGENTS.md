# Agent instructions (BoxNow Delivery for WooCommerce)

This file is the **provider-neutral** guide for AI coding agents (Cursor, Claude Code, GitHub Copilot, Windsurf, Codex, etc.). Humans should also read [CONTRIBUTING.md](CONTRIBUTING.md).

**Canonical source:** keep release and branch policy here—not in `agent-skills/` or tool-specific skills unless a thin pointer file is required by a product.

## Project summary

- **Type:** WordPress plugin + WooCommerce (BoxNow locker delivery, Croatia)
- **PHP:** `includes/` (services, integrations, admin), strict types, `CodeSoup\BoxNow\` namespace
- **Frontend:** `src/` → webpack → `dist/` (manifest-based assets in `includes/core/class-assets.php`)
- **Text domain:** `codesoup-woo-boxnow`

## Branch model

| Branch | Purpose |
|--------|---------|
| `develop` | Integration; **open all PRs here** |
| `master` | Production installable plugin only (built `dist/`, no `src/`) |

**Merging into `develop` does not publish `master`.** Publishing is a manual GitHub Actions workflow.

## Day-to-day development

1. Branch from `develop`.
2. PHP under `includes/`; JS/SCSS under `src/scripts/` and `src/styles/`.
3. Prefer the **Hooker** service for WordPress hooks; inject dependencies via the container / service providers.
4. Use `array()` in PHP per project style; escape output and sanitize input.
5. **Do not commit `dist/`** on `develop` (`dist/` is gitignored). Production assets are built in CI during deploy.

### Webpack (local or CI)

- Config: `src/config/config.user.js`
- Bundles: `admin-order`, `checkout`, `checkout-blocks`
- Production build: `npm run build:prod` (same command used by [wp-plugin-production-deploy](https://github.com/code-soup/wp-plugin-production-deploy))

### Paths to focus when reviewing changes

- `includes/`
- `src/`
- `index.php`, `run.php`, `uninstall.php`

## Pull requests

- Target branch: **`develop`** only (never feature PRs to `master`).
- JS changes: edit `src/` only; do not add `dist/` to the PR.
- Do not rely on `agent-skills/` for behavior; that folder is optional human/boilerplate docs.

## Release workflow (wp-plugin-release)

Adapted from common “github-release” patterns for this repo’s **develop → manual deploy → master** flow.

### Prerequisites

- `git` and `gh` (for optional GitHub Release) authenticated
- Clean working tree before version bumps
- Local Node 22+ / PHP 8.1+ only needed to debug builds; **release webpack build runs in CI**

### Version files (keep in sync for X.Y.Z)

| File | What to update |
|------|----------------|
| `index.php` | `Version`, `Requires at least`, `Tested up to`, `Requires PHP`, `Requires Plugins`, `WC requires at least`, `WC tested up to` |
| `run.php` | `PLUGIN_VERSION`, `MIN_WP_VERSION`, `MIN_PHP_VERSION`, `MIN_WC_VERSION` |
| `package.json` / `package-lock.json` | `version` |
| `CHANGELOG.md` | New `[X.Y.Z]` section (Keep a Changelog) |

Confirm the version with the user before writing.

### Steps

**1–3. Analyze (read-only)**

```bash
git fetch origin develop master --tags
PREV_TAG=$(git tag --sort=-version:refname | grep -E '^v?[0-9]+\.[0-9]+\.[0-9]+' | head -1)
git log "${PREV_TAG:-$(git rev-list --max-parents=0 HEAD)}"..origin/develop --oneline --no-merges
git diff "${PREV_TAG:-$(git rev-list --max-parents=0 HEAD)}"..origin/develop -- includes/ src/ index.php run.php
```

Derive semver from **code impact** (breaking → major, features → minor, fixes → patch).

**4. Integration**

Open PR(s) to `develop` with fixes and version/changelog updates.

**5. Publish to `master` (manual, human-triggered)**

1. GitHub → **Actions** → **Deploy Production**
2. Inputs: **`release_version`** (must match `index.php` `Version`) and **`confirm`** = `DEPLOY`
3. The job checks out `develop` and runs `code-soup/wp-plugin-production-deploy@v1.0.0`:
   - `npm ci` + `npm run build:prod` → `dist/`
   - `composer install --no-dev`
   - Copy production tree, apply `.deployignore`
   - Force-push to `master`

**6. Optional:** tag `vX.Y.Z` on `master` and create a GitHub Release from `CHANGELOG.md`.

### Release mistakes to avoid

| Situation | Action |
|-----------|--------|
| Deploy without merged release on `develop` | Cancel; merge integration first |
| Committed `dist/` on `develop` | Remove; CI builds on deploy |
| Auto-deploy on every `develop` push | Must stay **workflow_dispatch** only |

## Tool-specific pointers (optional)

| Tool | How to use this repo |
|------|----------------------|
| **Any agent** | Read this file (`AGENTS.md`) |
| **Cursor** | `.cursor/rules/wp-plugin-release.mdc` points here |
| **Humans** | [CONTRIBUTING.md](CONTRIBUTING.md) |

Do not duplicate release policy in multiple skills; update **this file** first.
