# Contributing

## For AI coding agents

Read **[AGENTS.md](AGENTS.md)** first. It is the portable instruction file for release workflow, branches, and code conventions (Cursor, Claude Code, Copilot, and others).

## Branches

- **`develop`** — default integration branch; open pull requests here.
- **`master`** — production plugin tree only; updated by the Deploy Production workflow, not by feature PRs.

## Local setup

```bash
composer install
npm ci
npm run dev   # or npm run build:prod to verify production webpack build
```

`dist/` is gitignored on `develop`. Do not commit built assets; CI builds them when deploying to `master`.

## Webpack

- Entry points: `src/config/config.user.js` (`admin-order`, `checkout`, `checkout-blocks`)
- Production build (CI): `npm run build:prod`
- Output: `dist/` + `manifest.json` (see `includes/core/class-assets.php`)

## Pull requests

1. Branch from `develop`.
2. Change PHP under `includes/` and JS/SCSS under `src/`.
3. Do not add `dist/` to the PR.
4. Merging to **`develop` does not release** to `master`.

## Production release

Full steps are in **[AGENTS.md § Release workflow](AGENTS.md#release-workflow-wp-plugin-release)**.

Short version:

1. Merge release-ready changes into `develop` (version headers, `CHANGELOG.md`, etc.).
2. Run **Actions → Deploy Production** with matching `release_version` and `DEPLOY`.
3. Optionally tag `vX.Y.Z` on `master` and publish a GitHub Release.
