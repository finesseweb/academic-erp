# Changelog Patch — Merit / Roster Generation

Date: 2026-09-02

- Added immutable Admission Merit entry persistence and permissions.
- Added College Merit / Roster Generation controller/service/page/routes/sidebar entry.
- Final generation requires the complete locked-rule candidate population to be resolved; partial generation is rejected.
- Ranking uses stored final weighted score, structured tie-breakers and a deterministic final fallback.
- Exact locked Selection Rule version remains authoritative even if later RETIRED.
- Generated Merit entries activate existing downstream Score/Interview edit locks.
- Reservation / Quota processing remains deferred to Seat Allocation / Consumption.
- Added ADR 085 and page specification without rewriting prior frozen Interview/Selection Rule decisions.
