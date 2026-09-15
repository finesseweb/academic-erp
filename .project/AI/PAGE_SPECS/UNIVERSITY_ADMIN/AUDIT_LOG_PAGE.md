# Audit Log Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Identity
- Module: Audit & Security
- Route: `/super-admin/audit-logs`
- Permission: `audit.view`
- Delivery phase: SA-06

## Purpose
Search immutable security and high-impact business administration events.

## UI
Filters: user, action, resource, College, date range, IP where allowed. Columns: timestamp, actor, action, resource, resource id, scope, IP summary. Detail drawer may show safe before/after JSON.

## Rules
Audit records are not editable from the UI. Sensitive secrets/tokens/passwords must never be stored in audit payloads.

## API
`GET /admin/audit-logs`
