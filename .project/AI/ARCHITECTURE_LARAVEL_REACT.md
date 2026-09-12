# ARCHITECTURE — REACT + LARAVEL + MYSQL

## Frozen Technology Stack
Frontend:
- React
- TypeScript
- Vite
- React Router
- reusable component/design-system approach
- API client/service layer
- permission-aware navigation and actions

Backend:
- Laravel
- PHP
- REST API
- Eloquent ORM
- Form Request validation
- Policies / Gates / middleware for authorization
- Service / Action layer for non-trivial business logic
- Events / Listeners / Jobs where appropriate
- Laravel WebSockets / Reverb / compatible broadcast layer only where real-time behavior is justified

Database:
- MySQL
- Laravel migrations
- foreign keys
- proper indexes
- transactions for critical workflows
- no destructive schema changes without migration/approval

## Domain Root
The business root is UNIVERSITY.
SUPER_ADMIN is a University-level role.

## Security Model
`USER -> ROLE -> PERMISSION -> SCOPE`

Authorization must be enforced in Laravel backend.
React menu/button visibility is UX only.

## API Principle
React never talks directly to MySQL.
React calls Laravel REST APIs.
Laravel validates, authorizes, executes business rules, and reads/writes MySQL.

## Realtime Principle
REST is the default.
Use WebSocket / Laravel Reverb only for genuine realtime needs such as:
- notifications
- chat
- presence
- long-running job completion
- meaningful live dashboard updates

Do not use WebSocket for ordinary CRUD.

## Documentation Root
Always read `.project/AI/` before project work.
