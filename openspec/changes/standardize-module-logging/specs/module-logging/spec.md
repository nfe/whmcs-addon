## ADDED Requirements

### Requirement: Standardized Module Log Events
The module SHALL record operational events through a shared logging interface that applies a consistent event taxonomy across callbacks, hooks, admin actions, migrations, repositories, and NFE.io integration flows. Each emitted module log entry MUST identify the operation being logged and MUST record the execution outcome separately from the operation identity.

#### Scenario: Callback validation failure is logged consistently
- **WHEN** the webhook callback receives an invalid or missing signature
- **THEN** the module SHALL emit a standardized callback validation event with an error outcome instead of using an ad hoc action name

#### Scenario: Invoice emission success and failure share the same event family
- **WHEN** the module logs the result of an NFE.io invoice emission attempt
- **THEN** both success and failure paths SHALL use the same operation identity and SHALL differ only by standardized outcome metadata

### Requirement: Logs Include Normalized Operational Context
The shared logging interface SHALL normalize context fields so that operational identifiers use consistent keys whenever the data is available. At minimum, the logger MUST preserve relevant identifiers such as invoice ID, client ID, company ID, local service invoice ID, remote NFE ID, external ID, execution environment, and source component when supplied by the caller.

#### Scenario: Hook flow includes invoice and client identifiers
- **WHEN** a hook emits a log related to invoice creation, payment, or scheduled processing
- **THEN** the log entry SHALL expose invoice and client identifiers using the standardized context keys defined by the module logger

#### Scenario: Missing optional identifiers do not break logging
- **WHEN** a caller does not have every supported identifier available
- **THEN** the logger SHALL emit the event with the identifiers that are available and SHALL omit unavailable optional fields without failing

### Requirement: Sensitive Data Is Redacted Before Logging
The module SHALL sanitize log payloads before writing them to WHMCS module logs or activity logs. Sensitive values including secrets, API keys, webhook signatures, authorization headers, and equivalent credential material MUST be redacted or excluded. Oversized raw payloads MUST be reduced to safe and useful diagnostic data.

#### Scenario: Webhook request headers contain secret material
- **WHEN** callback validation code logs request headers or signature-related data
- **THEN** the logger MUST prevent the raw secret or signature value from being written to the persisted log entry

#### Scenario: API failure includes a large response payload
- **WHEN** an NFE.io integration flow logs an error with request or response data
- **THEN** the logger SHALL preserve diagnostic metadata and safe excerpts while excluding sensitive fields and avoiding uncontrolled raw payload dumping

### Requirement: Activity Log Usage Is Intentional And Consistent
The module SHALL use WHMCS activity logs only for operator-facing audit events that require visibility outside the module log. All other operational diagnostics MUST use the shared module logger.

#### Scenario: Admin association action requires audit visibility
- **WHEN** an administrator performs a business-relevant association or configuration action that must remain visible in the WHMCS activity log
- **THEN** the module SHALL emit the standardized module log entry and MAY emit a companion activity log entry only if that action is classified as audit-relevant by the logging contract

#### Scenario: Internal migration diagnostic does not require activity log visibility
- **WHEN** a migration or repository operation emits an internal diagnostic event
- **THEN** the module SHALL record it through the shared module logger without writing an unnecessary WHMCS activity log entry