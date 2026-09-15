# ADR 179 — Payment Gateway Validation Errors Stay In-App

## Status
Implemented; QA pending.

## Context
During Payment Gateway QA, activating an INACTIVE Razorpay configuration without Key ID / Secret correctly failed at the backend, but the controller used `abort(..., 422, ...)`. In local/debug mode this rendered Laravel's exception page instead of keeping the user inside the ERP shell.

## Decision
Expected business-rule validation failures in Payment Gateway configuration must be returned through the normal Inertia redirect + shared toast flow, not as an unhandled HTTP exception page.

For gateway activation:
- If Key / Client ID or Secret is missing, activation is refused.
- Gateway status remains INACTIVE.
- User stays on Payment Gateway Configuration.
- An error toast displays: `Key ID and secret are required before activation.`

For credential editing:
- If the gateway is ACTIVE, editing is refused until it is deactivated.
- User stays on the same page.
- An error toast displays: `Deactivate the gateway before editing credentials.`

## Project UI rule reinforced
Expected user-correctable business validation must not surface Laravel/Symfony exception screens. Use the project's normal validation/flash/toast UX so the application shell remains intact. Reserve exceptions/HTTP aborts for true authorization/resource/error conditions where appropriate.

## Scope
Modified only `CollegePaymentGatewayController`; no schema, route, permission, or accounting changes.

## QA
Repeat activation of an INACTIVE gateway with no Key ID / Secret. Expected: error toast, no exception page, status still INACTIVE.
