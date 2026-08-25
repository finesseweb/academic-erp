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


## Project Context Access and Consistency Constitution — Mandatory
All development must preserve consistency with the current University ERP across architecture, database, UI/design, backend, frontend, routes, security, naming, workflow and documentation.

An AI agent/developer with full access to the current repository and `.project/AI/` MUST inspect the relevant existing code and authoritative documentation and develop from those references without requesting information that is already available.

An AI agent/developer without full access to the relevant current project files MUST NOT guess or invent incompatible patterns. When an existing project reference is required to make a consistent implementation decision, it MUST request the specific missing reference from the project owner before finalizing that part of the work. This includes, as applicable, design/UI patterns, database schema and relationships, migrations, backend structure, frontend components, routes, permissions/RBAC/scope, naming, workflows, audit rules and documentation.

The purpose of this rule is to ensure that full-access and partial-access agents both produce work that fits the same ERP instead of creating isolated or conflicting implementations.

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


## Common Academic Approval Engine Constitution — Mandatory
The ERP uses one reusable Academic Approval engine for approval-enabled academic and operational modules. Curriculum and Academic Policy are the first integrated subject types; future modules that require approval MUST integrate with this common engine instead of creating an isolated approval subsystem.

The common engine owns reusable workflow mechanics: workflow selection, University scope, ordered stages, role-based approver assignment, pending-stage routing, Approve / Return / Reject decisions, remarks requirements, stage history, request history, authorization and auditability.

Each subject/module remains responsible for its own domain validation, submission eligibility, correction/resubmission rules and final lifecycle action. Examples include activating an approved Curriculum, activating/current-version handling for an Academic Policy, and future module-specific publish/lock/activate actions.

Every submission MUST validate server-side that the selected workflow belongs to the same University, is ACTIVE, and `applies_to` the submitting subject type. Frontend filtering is UX only and never replaces backend validation.

New subject types should be added through a reusable subject-handler/registration pattern when expansion makes controller branching unwieldy. Do not duplicate the approval engine for each module.

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
Implement only the page/milestone count explicitly approved by the project owner.
If the owner approves a batch with `next N` or `nextN`, that entire batch of N sequential eligible milestones is explicitly approved. Complete, validate, and document each approved milestone in order without asking for another command between them. Stop only after the full approved count is completed, or when a genuine blocker requires owner input.

See `IMPLEMENTATION_APPROVAL_POLICY.md`.

## NEXT Command Contract — Mandatory

The project uses:
- `MASTER_DEVELOPMENT_HIERARCHY.md`
- `NEXT_WORKFLOW.md`
- `CURRENT_IMPLEMENTATION_STATE.md`

When the project owner says `next`, that message is explicit approval for exactly 1 next eligible milestone.
When the project owner says `next N` or `nextN`, that message is explicit approval for exactly N sequential eligible incomplete milestones in the same run.

Codex must:
1. read `.project/AI/`;
2. inspect repository reality;
3. reconcile implementation status;
4. select the earliest eligible incomplete milestone;
5. read its PAGE_SPEC(s);
6. implement it;
7. validate it;
8. update documentation/state;
9. if the approved batch counter is still below N, immediately select and execute the next eligible incomplete milestone;
10. stop only when the full approved count has been completed, or when a genuine blocker requires owner input.

Do not require the owner to name milestone names when the frozen hierarchy makes the sequence clear.
For `next N`/`nextN`, do not ask the owner to say `next` again between milestones; the original batch command already approves all N milestones.


## Batch Command Precedence — Mandatory
If any older wording anywhere in `.project/AI/` appears to imply that every `next`-style command can advance only one milestone, this section and `NEXT_WORKFLOW.md` control the interpretation:
- plain `next` = 1 milestone;
- `next N` / `nextN` = N milestones in one sequential approved batch.
A batch command is not reduced to one milestone by any generic "one page", "one milestone", "stop after approved work", or approval-gate wording elsewhere. Those rules apply per milestone inside the approved batch and to work beyond the approved count.

## Automatic `next` / `next N` Workflow — Mandatory
`next` approves exactly 1 next eligible incomplete milestone. `next N` or `nextN` approves exactly N sequential eligible incomplete milestones. The agent MUST inspect repository reality first, reconcile stale implementation statuses, resume at the earliest genuinely incomplete eligible milestone, skip already-completed milestones without counting them, complete/validate/document each selected milestone in order, and stop after exactly the requested count. Replacing workflow instruction files never resets implementation progress.
