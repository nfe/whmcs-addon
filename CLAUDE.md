# Project Guidelines

## Code Style
- Preserve PHP 7.4 compatibility. Avoid typed properties, union types, match expressions, constructor property promotion, and other newer syntax.
- Follow the existing module style in `modules/addons/NFEioServiceInvoices/lib/`: procedural WHMCS entrypoints at the module root, namespaced classes under `lib/`, and Smarty templates under `lib/templates/`.
- Keep changes focused and consistent with nearby code. This codebase already contains selective `phpcs:ignore` comments for legacy formatting constraints; do not reformat unrelated files just to satisfy a style preference.
- Do not edit committed vendor code under `modules/addons/NFEioServiceInvoices/lib/vendor/` unless the task explicitly requires a dependency change.

## Architecture
- This repository is a WHMCS addon module. Primary entrypoints are `modules/addons/NFEioServiceInvoices/NFEioServiceInvoices.php`, `modules/addons/NFEioServiceInvoices/hooks.php`, and `modules/addons/NFEioServiceInvoices/callback.php`.
- `modules/addons/NFEioServiceInvoices/Loader.php` boots Composer autoloading from the module-local vendor directory and initializes the module namespace.
- Admin and client routing flow through dispatcher/controller pairs in `modules/addons/NFEioServiceInvoices/lib/Admin/` and `modules/addons/NFEioServiceInvoices/lib/Client/`.
- Hook handlers live in `modules/addons/NFEioServiceInvoices/lib/Hooks/`. Legacy integration code remains in `modules/addons/NFEioServiceInvoices/lib/Legacy/` and is still active in some flows.
- Data access is repository-oriented under `modules/addons/NFEioServiceInvoices/lib/Models/`. External NFE.io interactions are centralized in `modules/addons/NFEioServiceInvoices/lib/NFEio/Nfe.php`.

## Build And Test
- Install dependencies from the repository root with `composer install`. Composer is configured to place dependencies in `modules/addons/NFEioServiceInvoices/lib/vendor`.
- There is no verified repo-level PHPUnit or PHPCS configuration file in this workspace. Do not assume a standard `vendor/bin/phpunit` or `phpcs` command will work without adding project configuration first.
- For small PHP changes, prefer targeted validation such as `php -l <file>` on touched files when PHP is available.

## Conventions
- Treat `modules/addons/NFEioServiceInvoices/whmcs.json` as module metadata, not application logic.
- Keep WHMCS root entrypoints thin. Put reusable logic in namespaced classes under `lib/` and call them from hooks, callbacks, or controllers.
- Preserve existing hook registration patterns in `modules/addons/NFEioServiceInvoices/hooks.php`: instantiate the relevant hook or legacy class inside the hook closure and call `run()` or the legacy method.
- Be careful with operational logging work. The module currently has many direct `logModuleCall` and some `logActivity` usages across callbacks, hooks, repositories, migrations, legacy helpers, and `lib/NFEio/Nfe.php`; follow the active OpenSpec change before normalizing those call sites.
- Avoid introducing new assumptions about a standalone runtime. Many code paths expect a WHMCS environment initialized through `init.php`, `Capsule`, and WHMCS helper functions.

## OpenSpec Workflow
- OpenSpec is active in this repo under `openspec/` with `schema: spec-driven`.
- When a task maps to an existing change, read the relevant files in `openspec/changes/<change>/` before editing code and keep implementation aligned with `proposal.md`, `design.md`, `specs/`, and `tasks.md`.
- Update task checkboxes in the relevant `tasks.md` only when the corresponding implementation work is actually complete.