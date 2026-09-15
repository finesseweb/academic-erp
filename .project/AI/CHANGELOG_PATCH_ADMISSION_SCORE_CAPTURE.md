# Changelog Patch — Admission Score Capture / Normalization

## 2026-08-26
- Added `college_admission_scores`.
- Added Score Capture / Normalization College Admission page and permissions.
- Merit and Entrance raw marks normalize to 0-100 on the backend.
- Qualification consumes the exact Selection Rule version locked on the Application Choice.
- Interview-weighted rules remain PENDING_INTERVIEW until the later Interview module supplies a normalized score.
- Added Score records to Test Data Cleanup and Full Academic Reset before Application deletion.
