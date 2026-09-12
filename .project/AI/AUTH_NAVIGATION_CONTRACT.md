# Authentication Navigation Contract

## Status
MANDATORY PROJECT-WIDE STANDARD

## Login
The ERP login screen is the canonical unauthenticated entry point.

Named route:
`login`

Fortify renders the project login UI:
`resources/js/pages/auth/login`

## Logout
Every successful logout must:
1. invalidate/end the authenticated session through the existing Fortify logout flow;
2. redirect to the named `login` route;
3. never redirect to Laravel's default/welcome page;
4. behave the same for Super Administrator, University-level users, College Administrator, and College-scoped users.

Do not implement role-specific logout destinations unless an approved future requirement explicitly changes this contract.

## Guest / Protected Routes
Unauthenticated access to protected ERP pages must continue to use the framework authentication middleware and resolve to the login page.

Do not bypass `auth` middleware merely to fix navigation.

## Root/Home Route
The existence or future behavior of `/` is independent from logout.
Logout must explicitly target `route('login')` rather than relying on `/`, `HOME`, Fortify home fallback, or a hard-coded dashboard URL.

This keeps logout behavior stable even if the root page is redesigned later.

## Future Implementation Rule
Any future authentication change (SSO, MFA, College portal entry, password reset, session timeout, forced logout, account disabled flow) must check this contract and document any intentional exception before implementation.
