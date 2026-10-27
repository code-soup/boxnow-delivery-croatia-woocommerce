# Proposed agent skills (BoxNow plugin)

Skills below are **recommended additions** not yet required for every task. This file explains gaps found during the 2026-10-27 audit. Implementations live in sibling folders when marked **Added**.

## Priority

| Priority | Skill | Why |
|----------|--------|-----|
| P0 | [woocommerce-integration](woocommerce-integration/SKILL.md) | **Added** — settings, shipping, HPOS, orders |
| P0 | [boxnow-api](boxnow-api/SKILL.md) | **Added** — delivery requests, parcels, auth |
| P1 | [checkout-locker](checkout-locker/SKILL.md) | **Added** — session vs POST, blocks vs classic |
| P1 | [constants-and-meta](constants-and-meta/SKILL.md) | **Added** — `Option_Keys`, `Meta_Keys`, `Form_Fields` |
| P2 | [release-and-versioning](release-and-versioning/SKILL.md) | **Added** — version bump checklist |
| P2 | woocommerce-logging (not added) | Document in `traits/SKILL.md` instead |
| P3 | [github-actions-deploy](github-actions-deploy/SKILL.md) | `.github/workflows`, `.deployignore` |

## Not recommended as skills

- **Generic REST API / CPT** — already documented as unused; keep boilerplate skills unless the plugin gains REST/CPT features.
- **Duplicate security** — extend `security/SKILL.md` instead of a second security skill.

## Merge order

1. Land [agent-skills audit fixes](../pull/9) (or current audit PR) first.
2. Add P0 skills from this PR; link them from `agent-skills/README.md` in a follow-up commit.
