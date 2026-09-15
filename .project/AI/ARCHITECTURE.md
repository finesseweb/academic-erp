# ARCHITECTURE

## Technology
Frontend: React + TypeScript + Vite
Backend: Laravel REST API
Database: MySQL

Detailed standards:
- `ARCHITECTURE_LARAVEL_REACT.md`
- `PROJECT_FOLDER_STRUCTURE.md`
- `REACT_FRONTEND_STANDARD.md`
- `LARAVEL_BACKEND_STANDARD.md`
- `PAGE_FLOW_STANDARD.md`
- `MODULE_NAMING_STANDARD.md`

## Core Domain
UNIVERSITY is the organizational root.
Affiliated Colleges are children of the University.

## Access
`USER -> ROLE -> PERMISSION -> SCOPE`

## Request Architecture
React
→ Laravel REST API
→ validation
→ authorization
→ business logic
→ Eloquent
→ MySQL

## Realtime
Use Laravel-compatible WebSocket/broadcasting only where genuine realtime behavior is needed.
REST remains the default.

## Ownership
Classify domain records as:
- University-owned
- College-owned
- Shared/inherited

Do not automatically add college_id to every table.

## Documentation
`.project/AI/` is authoritative.
