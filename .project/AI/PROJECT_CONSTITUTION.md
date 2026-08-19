# University ERP Constitution

Project: University ERP

## Core Stack
- Backend: Laravel
- Frontend: React
- Database: MySQL
- ORM and migrations: Laravel migrations / Eloquent
- Realtime transport: Laravel WebSocket Gateway with Socket.IO where realtime behavior provides clear value

## Core Goals
- Professional, premium-grade ERP architecture.
- Brand-new domain-driven database designed for this project.
- Clean, modular, readable and testable code.
- Documentation-first development.
- Reusable frontend/backend components.
- REST for normal request/response business operations.
- WebSocket for justified realtime workflows.
- One University with multiple affiliated Colleges and explicit University/College scope isolation.
- Granular RBAC with explicit scope.
- Auditable high-impact actions.
- Safe, versioned Laravel migrations / Eloquent/MySQL migrations.

## New Database Constitution
This ERP uses a BRAND-NEW database design. The legacy Zend/MySQL ERP is NOT the schema authority and must not constrain the new schema.

Legacy code/database may be inspected only to understand business rules, workflows, terminology, reports and edge cases when useful. New tables, relationships, constraints, indexes and naming must be designed according to the new architecture and current requirements.

Schema evolution is expected. When a feature genuinely requires a professional schema change, update Laravel migrations / Eloquent, create/review a versioned migration, test it, and update database documentation.

## Authentication Contexts
1. Super Admin / University Administration - University-wide administration.
2. College Admin / Staff - assigned affiliated-College scope according to roles and permissions.
3. Student - self-service access to authorized own resources.

## Authorization Constitution
- Authorization uses RBAC with granular `resource.action` permissions and explicit tenant/resource scope.
- Roles are permission bundles; do not scatter hard-coded role-name authorization when permission checks can express the rule.
- Laravel is the security authority. React visibility checks are UX only.
- Every tenant-sensitive operation must enforce University/College scope and any narrower department/course/student/resource scope.
- Sensitive workflows may use separate create/request, verify/approve, publish/refund permissions.
- Authorization and high-impact actions must be auditable.

## Realtime Constitution
- WebSocket is a first-class supported capability, not the default transport for every feature.
- Use REST for ordinary CRUD, queries, reports and transactional commands unless realtime interaction is genuinely required.
- Use WebSocket for notifications, presence, chat, live job/status updates, targeted alerts and other justified realtime experiences.
- WebSocket connections and rooms must enforce the same authentication, RBAC and tenant/scope rules as REST APIs.
- Never trust a client-provided room, college, role or user identifier as proof of authorization.

No AI agent or developer may violate this constitution without an explicit approved architecture decision.

## Theme Constitution
- The ERP ships with four built-in themes: Premium Light, Premium Dark, Ocean Blue and Emerald.
- The UI architecture must also support validated custom themes without rewriting business pages.
- Business components consume semantic design tokens; they must not hard-code theme-specific colors.
- Custom themes may define approved design tokens only. Raw CSS, HTML or JavaScript from theme creators is prohibited.
- Theme changes must never bypass accessibility, authorization or tenant-scope rules.

## Living Documentation Constitution Rule
The ``.project/AI/` directory is an authoritative part of the project. Implementation and documentation must remain synchronized. All contributors and AI agents MUST follow `DOCUMENTATION_MAINTENANCE.md`. Documentation maintenance is part of the Definition of Done. Major architectural/product deviations require approval and an ADR; normal implementation changes must automatically update their respective documentation.

## Documentation Location

The canonical documentation root is `.project/AI/`. Read `DOCUMENTATION_ROOT.md` before resolving any documentation path. Do not assume `/AI` or `AI/` exists elsewhere in the repository.

## University Domain Constitution
- The organizational root is one University.
- The University may have multiple affiliated Colleges.
- Colleges are governed child organizational units, not unrelated customers/tenants.
- Canonical scope path: `University -> College -> Faculty/School -> Department -> Program -> Course/Class -> Own Record / Linked Child`.
- University-wide academic and finance governance can coexist with College-level permitted operations.
- College Fee Management includes College Fee Structure and Installment Plans where allowed by University policy.
- See `DOMAIN/UNIVERSITY_HIERARCHY.md` and `DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`.

## University ERP Domain Root — Mandatory
This software is a University ERP.

The business/domain root is the UNIVERSITY.
SUPER_ADMIN is the highest University-level administrative role; it is not the business/domain root.

Canonical organization:
University → Affiliated Colleges → Faculty/School → Department → Program → Academic structures → People/operations.

Finance governance is layered between University and affiliated Colleges as defined in `DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`.

All work must read from `.project/AI/` first.

## Owner Approval Gate — Mandatory
Documentation does not constitute implementation approval.

Do not implement a PAGE_SPEC, module, migration, API, workflow, or future feature merely because it is documented.
Implement only the page/milestone explicitly approved by the project owner.
After completing approved work, stop and report. Do not automatically continue to the next page.

See `IMPLEMENTATION_APPROVAL_POLICY.md`.

## NEXT Command Contract — Mandatory

The project uses:
- `MASTER_DEVELOPMENT_HIERARCHY.md`
- `NEXT_WORKFLOW.md`
- `CURRENT_IMPLEMENTATION_STATE.md`

When the project owner says `next`, that message is explicit approval for exactly ONE next eligible milestone.

Codex must:
1. read `.project/AI/`;
2. inspect repository reality;
3. reconcile implementation status;
4. select the earliest eligible incomplete milestone;
5. read its PAGE_SPEC(s);
6. implement it;
7. validate it;
8. update documentation/state;
9. stop.

Do not require the owner to name the milestone when the frozen hierarchy makes it clear.
Do not start another milestone until the owner says `next` again.
