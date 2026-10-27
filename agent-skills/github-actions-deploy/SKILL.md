---
name: github-actions-deploy
description: Build develop and publish a production-only tree to master using code-soup/wp-plugin-production-deploy. Use when changing CI, .deployignore, or release flow.
---

# Production deploy (develop → master)

This plugin uses the shared Code Soup action:

[code-soup/wp-plugin-production-deploy](https://github.com/code-soup/wp-plugin-production-deploy) `@v1.0.0`

Workflow: `.github/workflows/deploy-production.yml`

## Triggers

- **Manual only** — `workflow_dispatch` (Actions → Deploy Production → Run workflow)

Do **not** add `on: push: develop` unless every merge to `develop` should immediately overwrite `master` (docs-only PRs would still ship to production).

## What the action does

1. `npm ci` and `npm run build:prod` (webpack → `dist/`)
2. `composer install --no-dev --optimize-autoloader`
3. Copy default production paths (`includes`, `vendor`, `dist`, `index.php`, `run.php`, etc.)
4. Apply exclusions from `.deployignore` (removes `src/`, `.github/`, `agent-skills/`, dev configs, …)
5. Force-push a single commit to **`master`** (installable plugin tree only)

`master` is the distribution branch; day-to-day development happens on **`develop`**.

## Local files

| File | Role |
|------|------|
| `.deployignore` | Patterns stripped from the production copy (like `.gitignore`) |
| `package.json` | `build:prod` must succeed in CI |
| `composer.json` | Production vendor tree on master |

## Changing the action

- Pin to a release tag (for example `@v1.0.0`), not `@master`.
- If you add root-level files required on master, either extend the action `production-files` input in the workflow or ensure they are copied as part of an included directory.

## Permissions

The workflow sets `permissions: contents: write` so `GITHUB_TOKEN` can force-push `master`.

## Rules

- Do not commit `node_modules/` or built `dist/` to `develop` unless the team explicitly wants that; CI builds `dist/` for production.
- Keep screenshot URLs in README pointing at `develop` paths under `.github/assets/` (those assets are not on `master`).
