# PAGE FLOW STANDARD — FROZEN

Every page must define not only layout but behavior before it is considered complete.

## Standard Page Flow
1. User reaches Laravel route.
2. Laravel authenticates and authorizes user and scope.
3. Controller/service prepares data.
4. Inertia renders the React page.
5. React displays an appropriate initial/loading state where navigation/data is pending.
6. Page content renders using the shared app shell and design system.
7. User actions display immediate hover/focus/pressed feedback.
8. Mutations show pending state and prevent duplicate submission.
9. Laravel validates, authorizes and executes the business action.
10. UI shows clear success or error feedback after server response.
11. Relevant data refreshes without unnecessary full-page disruption.
12. Audit/realtime behavior occurs when required by PAGE_SPEC.

## Mandatory Page States
Each page/feature must deliberately handle, where applicable:
- loading
- loaded
- empty/first use
- no search/filter results
- validation failure
- action/server failure
- success feedback
- warning/info condition
- permission denied/read-only
- disabled/pending action
- destructive confirmation
- session expired/authentication failure

## Navigation Behavior
Use Inertia Link/router for normal application navigation.
Show a global navigation progress indicator or equivalent professional feedback for delayed page transitions.
Preserve useful list/filter state when returning from contextual edit/detail flows where practical.

## List Page Pattern
PageHeader → optional summary cards → filter/search bar → data table → pagination.
Provide skeleton rows during initial load when the content is not yet available.
Provide explicit empty/no-results/error states.
Row actions and bulk actions must honor permissions.

## Create/Edit Pattern
Use full page, drawer or modal according to complexity as defined by `UI_UX_GUIDELINES.md`.
Show inline validation errors.
Submit control must show spinner/pending copy and be protected from duplicate submissions.
On success, show professional confirmation and navigate/refresh predictably.
On failure, preserve data when safe and provide actionable feedback.

Date inputs in create/edit flows use the shared `DatePicker` standard from `UI_UX_GUIDELINES.md`, including direct month/year selection for long-range dates and matching backend bounds validation.

## Delete/Destructive Pattern
Require a specific confirmation dialog.
Show pending state while processing.
On success remove/refresh the item and show success feedback.
On failure retain the item and show a clear error.

## Feedback Standard
Use semantic, concise messages:
- success: completed action
- error: failed action + useful recovery if available
- warning: risk/attention
- info: contextual non-error state
Never expose internal exception details.

## Responsiveness and Accessibility
Every page must explicitly support desktop/tablet/mobile and keyboard/focus use.
Use consistent iconography and semantic status communication.

## Source of Truth
Detailed visual/behavioral requirements are mandatory in `UI_UX_GUIDELINES.md`.

## Selection / Default Consistency Gate
Before a form/page is considered complete, verify `.project/AI/UI_DATA_SELECTION_CONSISTENCY.md`:
- Current ACTIVE Academic Session is the default for new session-scoped records.
- Configured `display_order` is respected where present.
- Dependent selectors clear/filter invalid children.
- New-record eligibility is filtered without rewriting historical Edit/View references.
- Backend validation repeats material frontend selection rules.
