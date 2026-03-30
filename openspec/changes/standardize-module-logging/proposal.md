## Why

The module currently logs operational events through scattered `logModuleCall` and `logActivity` calls with inconsistent action names, payload shapes, and error details. This makes troubleshooting slower, increases ambiguity in production diagnostics, and raises the risk of logging sensitive or low-value data without a consistent contract.

## What Changes

- Define a standard logging contract for the module, covering event naming, log levels or outcome categories, minimum context fields, and safe payload formatting.
- Introduce a central logging abstraction for module code paths that currently call `logModuleCall` or `logActivity` directly.
- Standardize logs across callback handling, invoice lifecycle hooks, NFE.io API interactions, migrations, repositories, and admin actions.
- Establish sanitization and redaction rules so operational logs preserve debugging value without leaking secrets or excessive raw payloads.
- Align success, warning, and error logs so similar flows emit comparable metadata and can be searched consistently.

### Current Event Inventory To Normalize

The standardization work must start from the events that already exist in the module today. These are the current event groups that need to be preserved, normalized, and reused as the basis for the new logging contract:

- Callback and webhook input validation: `callback`, `callback_success`, `callback_error`, `callback_error_environment`, `webhook_hmac`.
- Invoice lifecycle hooks and queueing: `nf_invoice_creation`, `nf_invoice_paid`, `hook_aftercronjob_1`, `hook_aftercronjob_2`, `Hook - DailyCronJob`, `nf_queue`.
- NFE.io issuance and status synchronization: `nf_emit_for_customer`, `nf_emit`, `nf_emit_error`, `updateLocalNfeStatus`, `updateLocalNfeStatusByExternalId`, `updateNfStatusByExternalId_error`, `updateNfStatusByNfeId_error`, `updateServiceInvoice_error`, `createServiceInvoice_error`.
- NFE.io reissue, cancel, and communication flows: `email_nfe`, `nf_reissue_series_by_nf`, `nf_reissue_series_by_nf_error`, `nf_reissue_series_by_invoice`, `nf_cancel_series_by_invoice`.
- Webhook lifecycle and verification: `create_webhook`, `create_webhook_error`, `get_webhook`, `get_webhook_error`, `webhook_verify_notfound`, `webhook_verify_url_mismatch`, `webhook_verify_success`, `webhook_verify_error`.
- Admin and configuration operations: `associateClients`, `associateCompany`, `edit_company`, `edit_company_error`, `save_company_error`, `delete_company`, `delete_company_error`, `get_default_company_error`, `client_issue_condition`.
- Migrations and schema maintenance: `migrateTimestampColumns`, `changeProductCodeTimestampColumnsName`, `changeProductCodeTimestampColumnsName_error`, `addCompanyIdColumn` and the other migration-oriented `logModuleCall` entries in `Migrations.php`.
- Legacy integration and compatibility flows: `ibge_error`, `nf_issue_curl_error`, `nf_issue_curl_success`, `nf_product_delete`, `nf_product_delete_error`.
- WHMCS activity log audit messages that still need an explicit policy: `NFE.io: Client associated`, `NFE.io: Company updated`, `NFE.io: Company associated`, `NFE.io: Código de serviço atualizado`.

These existing events should not be discarded blindly. The new contract must state which ones become canonical event families, which ones are merged into broader standardized events, and which activity-log messages remain audit-only exceptions.

## Capabilities

### New Capabilities
- `module-logging`: Defines how the module records operational events with standardized event names, structured context, result metadata, and safe handling of sensitive data.

### Modified Capabilities
- None.

## Impact

Affected areas include `callback.php`, hook handlers, admin controllers, migration routines, repositories, legacy integration helpers, and the NFE.io integration layer. This change is expected to add a shared logging helper or service and refactor existing log call sites, while explicitly mapping current event families into a documented taxonomy instead of replacing them with arbitrary new names. It should not alter external module APIs or WHMCS-facing workflows beyond improving observability.