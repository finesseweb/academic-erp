# Table Specification: User Theme Preferences

## Identity
- Physical table / Laravel migrations / Eloquent model: `user_theme_preferences` / `UserThemePreference`
- Domain/scope/grain: Appearance; zero or one row per user
- Purpose: store an allow-listed personal built-in theme preference

## Keys and Columns
- PK/FK: unsigned BIGINT `user_id` references `users.id`.
- Preference: `theme_code`.
- Audit timestamps: `created_at`, `updated_at`.

## Relationships and Delete Rule
- Required one-to-one child of `users`.
- `ON DELETE CASCADE`; the preference has no independent historical meaning. Theme changes are retained separately in immutable audit logs.

## Indexes / Rules
- Primary key enforces one preference per user.
- `theme_code` index supports future usage analysis and theme-disable impact review.
- A stored preference is ignored unless policy allows personal selection and RBAC grants `theme.select_own`.

## Change History
- 2026-08-13: Created by `20260813183000_theme_access_and_password_security`.
