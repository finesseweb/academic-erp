# ADR 060 — College Admission Builder Controller/Route Contract Restoration

Date: 2026-08-31
Status: Accepted

A later College Admission Form Setup controller patch regressed the previously complete builder
controller and left routes pointing to methods that no longer existed.

Observed failure:
`CollegeAdmissionFormSetupController::publicAccess()` was routed but absent, causing HTTP 500 when
saving applicant portal step layout / public access.

The regression was broader than `publicAccess`: the controller was also missing the established
Template, Step, Panel, Field and Mapping update/delete handlers.

Resolution:
- restore the complete College Admission Form Setup controller contract from the latest full-project baseline;
- restore Template CRUD, Step CRUD, optional Panel CRUD, Field CRUD, Mapping removal and Public Access;
- retain generic conditional-field / academic-scope handling from the complete builder;
- retain scoped RBAC and College-extension governance;
- preserve the newer configurable Registration Number format and College-editable Applicant Portal Help Text settings;
- verify every College Admission Form Setup route action has a corresponding public controller method.

No database migration is introduced by this hotfix.
