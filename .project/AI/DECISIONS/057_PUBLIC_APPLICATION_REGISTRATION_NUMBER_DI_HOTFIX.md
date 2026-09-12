# ADR 057 — Public Application Registration Number DI Hotfix

Date: 2026-08-31
Status: Accepted

`PublicAdmissionApplicationController::show()` uses `ApplicantRegistrationNumberService`
to ensure and expose the applicant's persisted Registration Number.

The previous patch imported the service but did not inject it into the `show()` action,
causing `Undefined variable $registrationNumbers`.

Fix:
- inject `ApplicantRegistrationNumberService $registrationNumbers` directly into `show()`;
- keep Laravel container resolution as the single dependency mechanism;
- no manual service construction and no hard-coded Registration Number fallback;
- no schema change and no new migration.
