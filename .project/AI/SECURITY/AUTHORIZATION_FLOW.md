# Authorization Flow

## Runtime Flow

`Request -> JWT Authentication -> Active User Check -> Required Permission -> Role Assignment -> Scope Check -> Resource/Ownership Check -> Workflow Rule -> Service -> Repository -> Database`

## Laravel Responsibilities

### Controller
- Declares the required permission(s), e.g. `student.create`.
- Performs request/DTO validation through approved framework patterns.
- Contains no raw authorization SQL/Laravel migrations / Eloquent logic.

### Authentication Guard
- Validates token/session.
- Resolves authenticated user identity.
- Rejects inactive/invalid authentication.
- The implemented access-token guard validates JWT signature/expiry, parses user/session IDs, and re-queries active user plus active unexpired `user_sessions` state before attaching the current user.
- `/auth/me` returns effective permissions grouped with each assignment's own scope. It does not collapse permissions across unrelated scopes.

### Permission Guard
- Reads required permission metadata.
- Resolves effective permission grants for the current authorization context.
- Denies when the required permission is absent.

### Service
The service remains responsible for business/resource authorization that cannot be decided from route metadata alone, including:
- Requested college belongs to allowed scope.
- Department/course is inside allowed assignment.
- Student self-service target is the authenticated student's own record.
- Faculty is assigned to the relevant course/class where required.
- Workflow state allows the action.
- Maker-checker separation is respected where required.

### Repository
- Receives explicit authorized scope/filter values.
- Must not query a wider tenant set and then rely on frontend filtering.
- Prefer tenant/scope constraints in the database query itself.

## React Responsibilities
- Obtain effective permissions from an approved authenticated endpoint/session representation.
- Render navigation based on permissions.
- Hide or disable unauthorized actions.
- Handle 401/403 responses correctly.
- Never treat hidden UI as authorization.

## API Error Semantics
- `401 Unauthorized`: authentication missing/invalid.
- `403 Forbidden`: authenticated but permission/scope/business authorization fails.
- Avoid leaking whether a protected cross-tenant resource exists when that would expose information.

## Example
A faculty user calls `POST /attendance` for Course X.

Required checks:
1. JWT valid.
2. User active.
3. Has `attendance.mark`.
4. College scope matches Course X.
5. Faculty assignment includes Course X/class offering as required by the academic model.
6. Attendance period/workflow allows marking.
7. Service performs write in required transaction.

Changing `courseId` manually in the request must not bypass steps 4-6.
