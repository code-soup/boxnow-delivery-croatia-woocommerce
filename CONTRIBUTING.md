# Contributing

## Branches

- **`develop`** — default integration branch; open pull requests here.
- **`master`** — production plugin tree only; updated by automation, not by direct feature PRs.

## Local setup

```bash
composer install
npm ci
npm run dev   # or npm run build:prod to verify production webpack build
```

`dist/` is listed in `.gitignore` on `develop`. Do not commit built assets to `develop`; the deploy pipeline builds them.

## Webpack

- Entry points: `src/config/config.user.js` (`admin-order`, `checkout`, `checkout-blocks`)
- Production build command (also used in CI): `npm run build:prod`
- Output: `dist/` with `manifest.json` for hashed filenames consumed by `includes/core/class-assets.php`

## Pull requests

1. Branch from `develop`.
2. Change PHP under `includes/` and JS/SCSS under `src/` as needed.
3. Do not add `dist/` to the PR (unless the team explicitly changes that policy).
4. Merging to **`develop` does not release** to `master`.

Cursor Cloud Agents should follow `.cursor/rules/wp-plugin-release.mdc` for version and release tasks.

## Production release (wp-plugin-release)

### 1. Prepare `develop`

Merge all fixes and version bumps:

| File | Fields |
|------|--------|
| `index.php` | Plugin headers including `Version`, WordPress/WC compatibility |
| `run.php` | `PLUGIN_VERSION`, `MIN_*` |
| `package.json` | `version` |
| `CHANGELOG.md` | New `[X.Y.Z]` section |

Use semver based on changes in `includes/` and `src/` since the last tag or last production deploy.

### 2. Deploy to `master` (manual)

1. Confirm `develop` is correct on GitHub.
2. **Actions** → **Deploy Production** → **Run workflow**.
3. The job checks out `develop` and runs [wp-plugin-production-deploy@v1.0.0](https://github.com/code-soup/wp-plugin-production-deploy):

   | Step | Detail |
   |------|--------|
   | Node 22 | `npm ci`, `npm run build:prod` |
   | PHP 8.1 | `composer install --no-dev` |
   | Package | Copies `includes`, `vendor`, `dist`, `index.php`, `run.php`, etc. |
   | Strip | `.deployignore` removes `src/`, `.github/`, `agent-skills/`, dev configs |
   | Publish | Force-push single commit to `master` |

### 3. Optional GitHub Release

On `master` after deploy:

```bash
git fetch origin master
git tag vX.Y.Z origin/master
git push origin vX.Y.Z
gh release create vX.Y.Z --notes-file CHANGELOG-snippet.md
```

## Current open PR guidance

| PR | Recommendation |
|----|----------------|
| Deploy / release integration | Merge first (manual deploy only) |
| #5–#7 Feature/fix | Merge to `develop`; #7 should not include `dist/` |
| #8 Version 1.2.0 | Merge after fixes |
| #9–#10 agent-skills | Optional human docs only; not required for Cursor (see `.cursor/rules/`) |
