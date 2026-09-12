# Create Role Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: Role Management
- Route: `/super-admin/roles/create`
- Permission: `role.create`
- Delivery phase: SA-04

## Fields
- role name
- role code
- description
- status
- optional "Save & Configure Permissions"

## Validation
Name required; code required and unique; protected/system codes cannot be reused; code becomes a stable identifier.

## API
`POST /admin/roles`

## Audit
`ROLE_CREATED`
