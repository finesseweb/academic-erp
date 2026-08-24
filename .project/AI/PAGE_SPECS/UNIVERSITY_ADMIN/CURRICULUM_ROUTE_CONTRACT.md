# Curriculum Route Contract — 2026-08-22

## Reviewed
User-provided `routes/web.php` reviewed against current Curriculum and Approval controllers.

## Required Curriculum lifecycle routes
- GET `admin/curricula`
- POST `admin/curricula`
- PATCH `admin/curricula/{curriculum}`
- DELETE `admin/curricula/{curriculum}`
- POST `admin/curricula/{curriculum}/clone-structure`
- POST `admin/curricula/{curriculum}/amend`
- POST `admin/curricula/{curriculum}/submit-for-approval`
- PATCH `admin/curricula/{curriculum}/retire`
- PATCH `admin/curricula/{curriculum}/restore`
- GET `admin/curricula/{curriculum}/structure/validate`

## Fix
`curricula.restore` was missing from the uploaded route file while the Curriculum UI/controller already used Restore. It is now restored.

## Validation endpoint note
The existing GET validation endpoint is retained for compatibility with the current Terms/Semesters validation client. It now records only validation/audit metadata after checking structure; it does not mutate academic structure.

A later HTTP-semantics cleanup may convert this action to POST together with its client in one atomic change request. Do not change the route verb independently of the frontend.


## Amendment route — 2026-08-24
`POST admin/curricula/{curriculum}/amend` is the controlled path for changes to the current `ACTIVE / APPROVED` Curriculum. It creates a linked editable DRAFT; it never unlocks the source.
