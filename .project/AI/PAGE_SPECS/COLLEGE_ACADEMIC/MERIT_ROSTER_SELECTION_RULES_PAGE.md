# Merit / Roster / Selection Rules Page

## Purpose
Configure versioned admission selection rules for an effective College Intake seat bucket after Intake / Seat Capacity. Reservation / Seat Distribution is optional per seat bucket; if a Reservation Plan exists for the selected bucket, that plan must be ACTIVE before the bucket is eligible.

## Hierarchy
Program Offering → Intake / Seat Capacity → optional Reservation / Seat Distribution → Merit / Roster / Selection Rules.

## Create / Edit Interaction
- Use the shared React/Inertia Dialog pattern because this is a focused rule configuration form.
- The dialog must be viewport-safe and must not inherit the shared `sm:max-w-lg` width when the rule form requires more room.
- Required container pattern: viewport-safe width, explicit responsive max-width override, bounded viewport height and a vertically scrollable form body.
- Header and footer remain visible while the form body scrolls.
- Every field wrapper uses `min-w-0`; Input, Select trigger and textarea controls remain within the dialog width.
- Do not use one global Seat Bucket dropdown across all Program Offerings.
- Creation starts with a searchable **Program Offering** picker; selecting an offering filters the dependent **Admission Seat Bucket** picker to only that offering.
- Both Program Offering and Admission Seat Bucket pickers are searchable by visible labels/codes/context.
- Current-session Program Offerings are preferred first, but other eligible Program Offerings remain searchable/selectable.
- The dependent Seat Bucket picker stays disabled until a Program Offering is selected and resets when the offering changes.
- Search result menus are height-bounded and vertically scrollable so large Program/Seat datasets never create an oversized dialog.
- Long labels wrap safely inside result menus and truncate safely in the selected trigger.
- Two/three-column grids are responsive and collapse where field width would become narrow.
- No horizontal overflow is permitted at desktop, tablet or mobile widths.

## Selection Formula
- MERIT = 100% Merit; Entrance and Interview = 0%.
- ENTRANCE = 100% Entrance; Merit and Interview = 0%.
- INTERVIEW = 100% Interview; Merit and Entrance = 0%.
- COMBINED = at least two positive Merit / Entrance / Interview weights totaling exactly 100%.
- Interview is a first-class score component, not free-text policy metadata.

## Qualifying Thresholds
All threshold fields use a normalized 0-100 score.

- MERIT shows Minimum Merit Score.
- ENTRANCE shows Minimum Entrance Score.
- INTERVIEW shows Minimum Interview Score.
- COMBINED may show Minimum Merit, Entrance and Interview Score plus Minimum Final Weighted Score; a component minimum is valid only when its weight is positive.
- Irrelevant threshold fields must be cleared rather than silently retained when mode changes.

## Structured Tie-breakers
The page must provide an ordered structured editor, not only a free-text textarea.

Each row has:
- Priority (derived from visible row order)
- Criterion
- Preference/direction
- Subject/Field Reference when `RELEVANT_SUBJECT_SCORE` is selected

Supported criteria:
- Qualifying Exam Score
- Entrance Score
- Interview Score
- Relevant Subject Score
- Date of Birth
- Application Submitted At

Admin may Add, Remove, Move Up and Move Down. Maximum 10 rows. At least one row is required before activation, although an incomplete rule may still be saved as INACTIVE.

Free-text Tie-break Policy Notes remain optional for official wording/explanation and are never executed by the ranking engine.

## Seat Bucket Eligibility
- ACTIVE Program Offering is required.
- ACTIVE Intake / Seat Capacity is required.
- Reservation not defined: bucket remains eligible and the full effective bucket is treated as Open/General.
- Reservation defined and INACTIVE: that bucket is blocked until Reservation is activated.
- Reservation defined and ACTIVE: selection rule consumes that Reservation context.

## Lifecycle
New selection-rule versions start INACTIVE. Activation is a separate lifecycle action. Historical rule versions remain traceable for future Admission records.

## QA Gate
Before this page is OWNER_QA_COMPLETE, verify:
1. create and edit dialog widths on desktop/tablet/mobile;
2. no horizontal form overflow;
3. long seat-bucket labels do not resize the dialog;
4. form body scrolls without losing header/footer actions;
5. Reservation optional/active/inactive eligibility behaves consistently on frontend and backend;
6. mode changes expose only applicable normalized threshold fields and clear irrelevant values;
7. structured tie-breakers can be added, removed and reordered;
8. Relevant Subject Score requires a subject/field reference;
9. duplicate structured tie-break criterion/reference combinations are rejected;
10. activation is blocked until at least one structured tie-breaker exists;
11. rule cards display the persisted tie-break order;
12. Program Offering search filters by program/session/code context;
13. Admission Seat Bucket search shows only buckets from the chosen Program Offering;
14. changing Program Offering clears any previously selected bucket;
15. large searchable result sets remain bounded/scrollable without horizontal overflow;
16. INTERVIEW-only enforces 0 / 0 / 100 weights and exposes only Interview threshold;
17. COMBINED accepts Merit + Entrance, Merit + Interview, Entrance + Interview, or all three, provided at least two components are positive and total is exactly 100%;
18. component thresholds are rejected when that component weight is zero;
19. Interview Score is available as an ordered structured tie-breaker.
