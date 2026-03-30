## 1. Define Logging Contract

- [ ] 1.1 Introduce a shared module logger abstraction that centralizes module log writing and supports standardized event identity, outcome metadata, and normalized context fields.
- [ ] 1.2 Implement redaction and payload-normalization rules for sensitive values such as API credentials, webhook signatures, authorization headers, and oversized request or response payloads.
- [ ] 1.3 Document or codify the event taxonomy and the criteria for when a flow writes only a module log versus both a module log and a WHMCS activity log.

## 2. Migrate High-Value Logging Flows

- [ ] 2.1 Refactor callback logging paths in `callback.php` and related validation helpers to use the shared logger with standardized events and safe payload handling.
- [ ] 2.2 Refactor NFE.io integration logging in `lib/NFEio/Nfe.php` so emission, webhook, cancellation, reissue, and status-update flows share the same event and outcome conventions.
- [ ] 2.3 Refactor invoice lifecycle hooks and admin-triggered actions to use the shared logger and keep activity-log writes only where audit visibility is intentionally required.
- [ ] 2.4 Refactor migration and repository log sites to emit normalized internal diagnostics through the shared logger instead of ad hoc direct calls.

## 3. Verify Consistency And Safety

- [ ] 3.1 Review remaining direct `logModuleCall` and `logActivity` usages in the module and either migrate them or confirm they comply with the new logging contract.
- [ ] 3.2 Validate that representative success, warning, and error paths emit consistent event metadata and preserve key identifiers such as invoice, client, company, NFE, and external IDs when available.
- [ ] 3.3 Verify that sensitive values are redacted in persisted logs and update any affected developer or operator documentation if the new event naming changes troubleshooting workflows.