## Context

The module currently emits logs from multiple layers, including callback entrypoints, hook handlers, admin controllers, migrations, repositories, and the NFE.io integration service. These call sites use `logModuleCall` and `logActivity` directly with inconsistent action names, mixed free-form and structured payloads, and no shared rules for correlation identifiers or sensitive-data handling.

This is a cross-cutting change because logging behavior is embedded in several execution paths that operators rely on to diagnose invoice issuance, webhook validation, migration outcomes, and manual admin actions. The design therefore needs to improve consistency without changing WHMCS-visible workflows or requiring a new external logging backend.

## Goals / Non-Goals

**Goals:**
- Provide one shared logging abstraction for module code that standardizes event naming, context structure, and result reporting.
- Keep module diagnostics compatible with WHMCS by continuing to write through the existing module log mechanisms.
- Define safe defaults for redaction so secrets, signatures, and excessive raw payloads are not logged blindly.
- Make logs from comparable flows searchable and understandable across callbacks, hooks, admin actions, migrations, repositories, and NFE.io API operations.
- Support incremental adoption so existing call sites can be migrated in phases without blocking unrelated module behavior.

**Non-Goals:**
- Replacing WHMCS module logging with a new third-party logging platform.
- Redesigning user-facing admin messages, flash messages, or webhook behavior beyond the data captured in logs.
- Retrofitting historical log entries that were already written before this change.
- Introducing broad behavior changes to invoice issuance, synchronization, or migration logic beyond log standardization.

## Decisions

### Use a central module logger wrapper
The implementation will introduce a shared logger utility or service inside the module namespace and route new or refactored log calls through it instead of calling `logModuleCall` or `logActivity` ad hoc.

Rationale:
- Centralizing the formatting rules avoids repeating naming and payload decisions at each call site.
- It allows consistent sanitization and field normalization before data reaches WHMCS logs.
- It preserves the current operational model because the wrapper can still delegate to `logModuleCall` and, when explicitly needed, `logActivity`.

Alternatives considered:
- Keep direct `logModuleCall` usage and document conventions only. Rejected because the module already shows widespread drift and new code would likely continue diverging.
- Introduce an external PSR-3 logger or Monolog integration. Rejected for this change because it adds deployment and dependency complexity without solving the immediate need to normalize WHMCS-native logs.

### Separate event identity from outcome metadata
Standardized log entries will use a stable event identifier for the operation being recorded and store success, warning, or error outcome separately in structured metadata rather than encoding every variation in the action name.

Rationale:
- Current action names often blend operation and result, which fragments searchability.
- Separating identity from outcome keeps related events grouped while still exposing execution state.

Alternatives considered:
- Continue suffixing action names with `_error` or `_success`. Rejected because it duplicates event families and makes consistency hard to enforce.

### Normalize context fields around module identifiers
The wrapper will accept context arrays and normalize commonly available identifiers such as invoice ID, client ID, company ID, local service invoice ID, remote NFE ID, external ID, environment, and source component.

Rationale:
- These identifiers recur across callbacks, hooks, repository operations, and NFE.io API calls.
- Normalized keys make operational searches and comparisons practical even when different code paths emit the event.

Alternatives considered:
- Allow each caller to shape context arbitrarily. Rejected because it preserves today’s inconsistency.

### Apply defensive redaction and payload trimming by default
The logger wrapper will sanitize sensitive fields such as webhook signatures, API credentials, secrets, authorization headers, and overly large raw payloads before writing logs.

Rationale:
- Current code sometimes logs request headers, payloads, and exception output directly.
- Operators need enough detail to debug, but not uncontrolled secrets or unreadable payload blobs.

Alternatives considered:
- Log full payloads everywhere for easier debugging. Rejected because it increases leakage risk and makes logs noisy.

### Migrate high-value flows first, then converge remaining call sites
Implementation should start with the most operationally important and inconsistent paths: webhook callback handling, NFE.io API flows, invoice lifecycle hooks, admin-triggered actions, and migrations. Remaining repository or legacy calls can then be aligned using the same wrapper and conventions.

Rationale:
- This reduces risk while improving the logs operators use most frequently.
- It supports incremental review of naming conventions and sanitization behavior before complete adoption.

Alternatives considered:
- Big-bang rewrite of every log call in one pass. Rejected because the change is cross-cutting and harder to validate safely.

## Risks / Trade-offs

- [Incomplete migration leaves mixed conventions in place] -> Mitigation: define the wrapper contract first and prioritize the highest-volume call sites before sweeping the remaining direct calls.
- [Sanitization removes details that were useful for debugging] -> Mitigation: preserve non-sensitive identifiers and summarized payload metadata so failures remain diagnosable without exposing secrets.
- [Additional wrapper logic slightly increases implementation complexity] -> Mitigation: keep the helper focused on normalization, redaction, and delegation rather than building a full logging framework.
- [Changing action names may disrupt existing operator search habits] -> Mitigation: document the new event taxonomy in the change and keep naming predictable across all migrated flows.

## Migration Plan

1. Introduce the shared logger abstraction and define the event/context contract.
2. Migrate callback, NFE.io service, hook, admin, and migration log sites to the shared logger.
3. Update remaining direct module log call sites that fall inside the module codebase and align any required activity-log usage.
4. Verify that sensitive values are redacted and that success/error flows emit consistent event metadata.
5. Roll back by restoring the previous direct log calls if the wrapper causes behavioral regressions, since this change does not require schema or data migrations.

## Open Questions

- Whether any current `logActivity` entries must remain duplicated in the WHMCS activity log for audit visibility, or whether some can be replaced by standardized module logs only.
- Whether the final event identifier format should prefer dotted names or normalized snake_case names, as long as the convention is applied consistently through the wrapper.