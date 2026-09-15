# Super Admin Dashboard Page

Documentation Status: APPROVED
Implementation Status: NOT_STARTED
Implementation Approval: REQUIRED
Review Status: NOT_APPLICABLE


## Page Identity
- Module: Platform Administration
- Route: `/admin`
- Page type: dashboard
- Delivery phase: SA-02

## Purpose
Give the global administrator an immediate platform overview without overbuilding analytics in the first milestone.

## Current Delivery State
The Super Admin application shell is implemented at `/admin`. Until the dashboard milestone is implemented, this route renders only a small authenticated welcome/status card. It must not display fabricated metrics or call the future dashboard APIs.

The shared shell provides:
- protected authenticated access with redirect to `/login`
- responsive, collapsible desktop sidebar and mobile drawer
- permission-aware navigation using backend-issued effective permissions
- sticky header with notification placeholder and account/logout menu
- reusable breadcrumb and page-header action regions
- semantic-token support for all four built-in themes

Change Password is active from the account menu. Profile remains an unavailable placeholder. Built-in theme access and global policy controls are hosted in the temporary workspace until the full Theme Management page is delivered.

## Permission
`platform.dashboard.view`, global scope.

## Initial Widgets
The following widgets are future dashboard scope and are not implemented by the application-shell milestone:
- Total Affiliated Colleges
- Active Affiliated Colleges
- Total Users
- Active Users
- Total Roles
- Recent security/admin activity

## Actions
Links to add affiliated College, create user, manage roles, view audit logs.

## API
- `GET /admin/dashboard/summary`
- `GET /admin/audit-logs?limit=...`

## Realtime
REST initially. Do not add WebSocket until live platform counters/alerts are a proven requirement.

## Theme
Dashboard cards/charts/components must use semantic tokens and render correctly in all four built-ins and custom themes.
