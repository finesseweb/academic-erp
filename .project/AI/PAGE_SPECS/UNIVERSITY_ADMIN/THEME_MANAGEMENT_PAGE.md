# Theme Access and Management

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Identity
- Module: Appearance / Theme Management
- Current route: embedded on `/admin`
- Future full custom-theme route: `/admin/themes`
- Delivery phase: Theme Access foundation; custom editor remains SA-06

## Purpose
Allow authorized administrators to set the global built-in default and decide whether users with explicit RBAC permission may save a personal theme.

## Permissions
- Resolve applicable theme: any authenticated active session; this reveals only non-sensitive appearance policy
- Choose own theme: `theme.select_own`
- Set global default: `theme.set_global_default`
- Enable/disable personal selection globally: `theme.manage_personal_selection`
- Existing create/update/disable permissions remain reserved for the future validated custom-theme module.

## Current UI and Rules
- Theme controls live in the `/admin` workspace, not the top header.
- Super Admin can set one of Premium Light, Premium Dark, Ocean Blue or Emerald as global default.
- Personal selection requires both global policy allowance and the user's effective `theme.select_own` grant.
- Without both conditions, the global default is applied at login and the selector is unavailable.
- With both conditions, the saved `user_theme_preferences` value takes precedence.
- Current resolution: `permitted user preference -> global default -> premium-light`.
- College default slots are supported by the scoped policy model but are not resolved until the College module exists.

## API
- `GET /api/v1/themes/context` (authenticated; available even without selection permission so the enforced default applies at login)
- `PUT /api/v1/themes/preference` with `{ themeCode }`
- `GET /api/v1/themes/policy`
- `PUT /api/v1/themes/policy` with `{ defaultThemeCode, allowPersonalSelection }`
- Theme codes are allow-listed built-in identifiers; raw CSS/HTML/JS is never accepted.

## Database / Audit
- `theme_policies`: one policy per canonical scope.
- `user_theme_preferences`: at most one preference per user.
- Audit events: `THEME_PREFERENCE_CHANGED`, `THEME_GLOBAL_POLICY_CHANGED`.

## Future Custom Theme Scope
Theme cards, custom token editor, duplication, disabling, accessibility preview and persisted custom themes remain unimplemented. Do not create a `themes` table until that milestone.

## Realtime
REST only. Theme changes apply after persistence; WebSocket is not justified.

## Change History
- 2026-08-13: Implemented built-in global policy and permission-controlled personal preferences.
