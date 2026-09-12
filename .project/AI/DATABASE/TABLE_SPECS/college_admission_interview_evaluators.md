# college_admission_interview_evaluators

Panel/evaluator child rows for one Admission Interview.

Stores:
- Interview reference
- ACTIVE College user evaluator reference
- evaluator name snapshot
- raw score, maximum score, backend normalized score
- evaluator remarks

An evaluator may appear only once in one Interview panel. Normalization formula: `(raw / maximum) * 100`.
