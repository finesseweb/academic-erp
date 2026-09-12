# Changelog Patch — Admission Cycle Shared DatePicker

## 2026-08-26
- Replaced all four browser-native Admission Cycle date inputs with the ERP shared `DatePicker`.
- Added Program Offering Academic Session `starts_on` / `ends_on` bounds to the picker UI.
- Added dependent minimum-date behavior for Application End, Admission Start and Admission End.
- Preserved backend/service validation as the authoritative enforcement layer.
- Recorded the Admission Cycle-specific consistency rule so future Admission modules reuse the existing date component rather than creating native/page-specific date controls.
