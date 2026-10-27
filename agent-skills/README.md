# Agent skills (BoxNow WooCommerce plugin)

Skills in this folder guide AI agents and contributors on how this plugin is structured. They are **not** loaded automatically by WordPress; they document conventions for development in this repository.

## Skill index

| Skill | Use when |
|-------|----------|
| [wordpress-plugin-boilerplate](wordpress-plugin-boilerplate/SKILL.md) | Overall architecture and adding features |
| [hooker](hooker/SKILL.md) | Registering WordPress hooks |
| [service-provider](service-provider/SKILL.md) | DI registration and boot |
| [container](container/SKILL.md) | Resolving services from the container |
| [file-naming](file-naming/SKILL.md) | PHP filenames and namespaces |
| [security](security/SKILL.md) | Escaping, nonces, capabilities |
| [ajax-handler](ajax-handler/SKILL.md) | Admin/checkout AJAX |
| [asset-management](asset-management/SKILL.md) | Webpack `dist/` assets and enqueue |
| [traits](traits/SKILL.md) | `Logging_Trait`, `RequirementChecksTrait` |
| [activation-deactivation](activation-deactivation/SKILL.md) | Plugin lifecycle hooks |
| [i18n-translation](i18n-translation/SKILL.md) | Text domain `codesoup-woo-boxnow` |
| [webpack-config](webpack-config/SKILL.md) | `src/config/` build |
| [scss-workflow](scss-workflow/SKILL.md) | `src/styles/` |
| [rest-api](rest-api/SKILL.md) | Generic REST boilerplate (not used in this plugin yet) |
| [custom-post-type](custom-post-type/SKILL.md) | Generic CPT boilerplate (not used in this plugin) |

## Audit notes (2026-10-27)

Skills were reviewed against the current codebase on `develop`. Common drift patterns:

1. **Boilerplate vs this plugin** — Several skills describe a generic WPPB layout (`includes/api/`, `includes/ajax/`, `Contracts\`). This plugin uses `includes/services/`, `includes/integrations/woocommerce/`, and `CodeSoup\BoxNow\Interfaces\`.
2. **Hooker bulk helpers** — `add_actions()` / `add_filters()` take an array of hook tuples, not `( $this, array( 'hook_names' ) )`. There is no `Hooker::register()` method.
3. **Assets class** — `CodeSoup\BoxNow\Core\Assets` resolves manifest URLs only; enqueue uses `wp_enqueue_script()` / `wp_enqueue_style()` in `Admin\Init` and `Frontend\Init` (sometimes `add_action` directly when hooks must run before `Hooker::run()` completes).
4. **Logging trait** — Class is `Logging_Trait` in `trait-logging.php`; methods are `log_info()`, `log_error()`, etc. (WooCommerce logger), not a generic `log( $msg, 'info' )`.
5. **AJAX** — Handlers live under `CodeSoup\BoxNow\Services\Ajax\`; admin uses `myAjax` + `ApiClient` (fetch), nonce action `codesoup_boxnow_nonce`.
6. **Requirements** — Runtime config in `run.php` (WordPress, PHP, WooCommerce minimums) should match `index.php` headers.

When you change architecture, update the relevant skill in the same PR as the code change.
