# ENR-4.4.1 — Migration Recovery + DB Documentation Hardening

Date: 2026-09-17
Status: OWNER QA PENDING

- Fixed MySQL error 1059 caused by an auto-generated FK identifier exceeding the identifier-length limit.
- Replaced long generated FK names with explicit short names.
- Hardened migration for rerun after the known partial-DDL failure by checking columns, FKs and indexes before adding them.
- Documented authoritative Enrollment/Curriculum relationships and indexes/constraints in DB impact documentation.
- Added ADR 205: every DB-affecting change must update DB documentation in the same patch and pass a documentation-completeness closure gate.
