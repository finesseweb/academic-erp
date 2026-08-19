# Theme System

## Purpose
Provide a premium, reusable theming architecture for the ERP. Theme choice must never be hard-coded page-by-page.

## Core Rule
All pages and reusable components must consume semantic design tokens exposed as CSS variables. Do not place theme-specific hex colors directly inside page components.

## Built-in Themes
The application ships with four built-in themes:

1. `premium-light` — bright neutral surfaces, strong contrast, restrained professional accents.
2. `premium-dark` — dark neutral surfaces, elevated panels, accessible high-contrast text.
3. `ocean-blue` — premium blue/indigo business theme with light surfaces.
4. `emerald` — professional emerald/teal academic theme with light surfaces.

Exact token values belong in the frontend theme registry, not in business components.

## Custom Themes
Authorized users may create custom themes from the Theme Management page. A custom theme is a validated token set, not arbitrary CSS or JavaScript.

Recommended editable tokens:
- display name
- theme code / slug
- mode: light or dark
- primary
- primary-foreground
- secondary
- secondary-foreground
- background
- foreground
- surface / card
- card-foreground
- muted
- muted-foreground
- border
- input
- ring / focus
- success
- warning
- danger
- info
- sidebar-background
- sidebar-foreground
- sidebar-active
- topbar-background
- radius scale

## Theme Storage
Built-in themes live in source-controlled frontend configuration.
Custom themes may be persisted in the database once Theme Management is implemented.

Implemented built-in-theme persistence uses:
- `theme_policies` for a default theme and personal-selection flag at a canonical scope
- `user_theme_preferences` for one optional saved preference per user

These tables store allow-listed theme codes only and do not store arbitrary theme tokens or CSS.

Suggested entity when needed:
`themes`
- id
- uuid
- college_id nullable (NULL = University/global theme)
- code
- name
- mode
- tokens_json
- is_builtin
- is_active
- created_by
- updated_by
- created_at
- updated_at

Do not create this table until the Theme Management module is actually implemented.

## Theme Scope
A theme may be:
- global/default
- Affiliated College default
- user preference

Recommended resolution priority:
`user preference -> Affiliated College default -> University global default -> premium-light`

Current implemented resolution is `permitted user preference -> University global default -> premium-light`. Affiliated College policy resolution is deferred until Affiliated College identities and ownership validation exist. A user preference is effective only when the applicable policy allows personal selection and effective RBAC includes `theme.select_own`.

## Permissions
- `theme.view`
- `theme.select_own`
- `theme.manage_personal_selection`
- `theme.create`
- `theme.update`
- `theme.disable`
- `theme.set_global_default`
- `theme.set_institution_default`

## Security
Custom theme input must be validated. Never accept raw `<style>`, CSS expressions, URLs, scripts, HTML, or arbitrary class names from users.

## Accessibility
Every theme must satisfy readable contrast, visible focus states, disabled states, error states and table/form readability. Theme preview must warn when token combinations are too low-contrast.

## Frontend Architecture
Recommended structure:

```text
src/
  theme/
    tokens.ts
    theme.types.ts
    theme.registry.ts
    theme-provider.tsx
    theme-utils.ts
    builtins/
      premium-light.ts
      premium-dark.ts
      ocean-blue.ts
      emerald.ts
```

Pages use semantic classes/tokens only. The ThemeProvider applies the active theme to the application root.

## Custom Theme Creation Workflow
1. User opens Theme Management.
2. User chooses "Create Theme" or duplicates an existing theme.
3. User edits validated tokens.
4. Live preview renders dashboard, form, table, buttons and alerts.
5. Accessibility/validation checks run.
6. Theme is saved as draft or active.
7. Authorized user may make it global or Affiliated College default.
8. Change is audited.

## Audit Events
- `THEME_CREATED`
- `THEME_UPDATED`
- `THEME_DISABLED`
- `THEME_GLOBAL_DEFAULT_CHANGED`
- `THEME_INSTITUTION_DEFAULT_CHANGED`
- `THEME_PREFERENCE_CHANGED`
- `THEME_GLOBAL_POLICY_CHANGED`
