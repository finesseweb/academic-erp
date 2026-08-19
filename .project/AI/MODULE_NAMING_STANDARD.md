# MODULE NAMING STANDARD — FROZEN

## Goal
The same business term should be recognizable in:
- PAGE_SPEC
- React module
- Laravel controller/service/model
- route
- permission
- database migration/table

## Example: Academic Sessions

PAGE_SPEC:
`PAGE_SPECS/UNIVERSITY_ACADEMIC/ACADEMIC_SESSIONS_PAGE.md`

React:
`frontend/src/modules/academic-setup/academic-sessions/`

Laravel:
- `AcademicSessionController`
- `StoreAcademicSessionRequest`
- `UpdateAcademicSessionRequest`
- `AcademicSessionService`
- `AcademicSession`
- `AcademicSessionPolicy`

Route:
`/api/v1/university/academic-sessions`

Permission:
- `academic_session.view`
- `academic_session.create`
- `academic_session.update`

Database:
`academic_sessions`

## React Feature Folder Standard

```text
academic-sessions/
├── pages/
│   ├── AcademicSessionsListPage.tsx
│   ├── AcademicSessionCreatePage.tsx
│   ├── AcademicSessionEditPage.tsx
│   └── AcademicSessionViewPage.tsx
├── components/
│   ├── AcademicSessionForm.tsx
│   ├── AcademicSessionFilters.tsx
│   └── AcademicSessionTable.tsx
├── api/
│   └── academicSessions.api.ts
├── hooks/
│   └── useAcademicSessions.ts
├── types/
│   └── academicSession.types.ts
├── validation/
│   └── academicSession.schema.ts
└── index.ts
```

Use this pattern for all modules unless a page is too small to justify every folder.

## Laravel Feature Naming Standard

For a normal CRUD module:

- Controller: singular domain + `Controller`
- Model: singular domain
- Requests: `Store...Request`, `Update...Request`
- Resource: `...Resource`
- Policy: `...Policy`
- Service: `...Service` for business logic
- Action: task-oriented action classes only where useful

Do not create unnecessary abstraction layers for trivial CRUD.
