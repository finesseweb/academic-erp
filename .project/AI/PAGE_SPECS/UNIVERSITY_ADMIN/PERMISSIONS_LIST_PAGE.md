# Permissions Catalog Page

Documentation Status: APPROVED
Implementation Status: IMPLEMENTED
Implementation Approval: APPROVED
Review Status: PENDING_REVIEW


## Identity
- Module: Permission Management
- Route: `/super-admin/permissions`
- Permission: `permission.view`
- Delivery phase: SA-04

## Purpose
Read/search the canonical application permission catalog.

## UI
Columns: Module, Resource, Action, Code, Description, Sensitive?, Status/Implementation metadata if maintained.

## Rule
Initial implementation is read-only. Arbitrary permissions should not be created through the UI because a permission must correspond to an implemented backend capability.

## API
`GET /admin/permissions`
