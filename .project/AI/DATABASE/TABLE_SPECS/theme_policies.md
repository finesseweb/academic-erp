# Table Specification: Theme Policies

## Identity
- Physical table / Laravel migrations / Eloquent model: `theme_policies` / `ThemePolicy`
- Domain/scope/grain: Appearance; one row per canonical policy scope
- Purpose: store the enforced default built-in theme and whether personal selection is allowed

## Keys and Columns
- PK: unsigned BIGINT `id`.
- Unique: (`scope_type`, `scope_reference`).
- Policy: `default_theme_code`, `allow_personal_selection`.
- Audit timestamps: `created_at`, `updated_at`.

## Scope and Validation
- Current row is `GLOBAL` + `global`.
- The model supports future Affiliated College scope, but College resolution is prohibited until an College entity and ownership validation exist.
- Theme codes must be validated against the backend allow-list. The table never stores CSS, HTML, scripts or unvalidated token JSON.

## Indexes / Access
- Unique scope key supports deterministic policy resolution.
- (`scope_type`, `scope_reference`, `allow_personal_selection`) supports scoped policy reads.

## Change History
- 2026-08-13: Created by `20260813183000_theme_access_and_password_security`.
