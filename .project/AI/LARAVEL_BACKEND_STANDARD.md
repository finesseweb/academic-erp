# LARAVEL BACKEND STANDARD — FROZEN

## API Versioning
Use:
`/api/v1/...`

## Request Flow
Route
→ Middleware/Auth
→ Controller
→ Form Request validation
→ Policy/Gate authorization
→ Service/Action
→ Eloquent/transaction
→ API Resource response

## Controller Rule
Controllers should remain thin.
Do not put large business workflows directly in controllers.

## Validation
Use Laravel Form Requests for create/update validation.

## Authorization
Use Policies/Gates/middleware.
Backend is authoritative.

## Transactions
Use DB transactions for workflows such as:
- fee collection
- result publication
- role/scope changes where multi-write consistency matters
- admission finalization
- curriculum activation when multiple records are locked/versioned

## Models
Use Eloquent relationships.
Avoid free-text duplication where a foreign key/master exists.

## Deletion
Do not hard-delete referenced historical academic/financial/security data.
Use status, archive, revoke, reversal or soft-delete only where domain rules permit.

## Audit
Sensitive changes must write to the existing audit architecture.

## API Responses
Use consistent JSON shape through API Resources / response helpers.

## Errors
Return predictable validation, authorization and domain errors.

## Tests
Use Laravel Feature tests for API/security/business flows.
Use Unit tests for isolated calculation/rule logic.
