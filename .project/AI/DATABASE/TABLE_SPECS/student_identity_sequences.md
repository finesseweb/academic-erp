# student_identity_sequences
ENR-3 concurrency/sequence infrastructure (ADR 203). Unique by College + identity type + scope key. Rows are locked during assignment so concurrent requests cannot allocate the same sequence. This is supporting identity infrastructure, not a Student domain aggregate.
