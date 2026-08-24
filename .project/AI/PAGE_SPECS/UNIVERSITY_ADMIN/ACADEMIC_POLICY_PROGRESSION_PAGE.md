# Academic Policy — Promotion / Progression UI Correction

## Default / All Stages

`Default / All Stages` is the single fallback rule for progression stages where no specific term-target rule is configured.

When enabled:
- `applies_to_all_stages = true`;
- Curriculum/structure binding is cleared at rule level;
- source Terms / Semesters are cleared;
- Progress To Term is cleared;
- term-specific structure fields are hidden;
- progression thresholds and recovery controls remain available because they are the fallback conditions.

When disabled again:
- the current applicable structure is restored automatically;
- source and target terms must be selected before save.

### Frontend state rule

Changes that affect multiple fields must be applied with one atomic rule patch. Do not issue consecutive `setData` calls from the same checkbox event, because later updates can overwrite earlier state.

## Checkbox help text

Every progression control must show a one-line description directly below its label:

- Mandatory Courses Must Be Passed — all mandatory courses must be cleared before the rule can pass.
- Allow Carry Forward / ATKT — eligible backlog courses may be carried forward within configured limits.
- Allow Detention — failed progression may place the student in detained status.
- Allow Year Back — a controlled year-back decision may be used when progression requirements fail.
- Allow Re-admission — eligible detained/year-back/discontinued students may enter the later re-admission workflow.

The description is explanatory only; the checkbox controls whether the future progression engine may use that behavior.
