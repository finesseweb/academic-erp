# ADR 133 — Seat Allocation Confirmed-Admission Guard + Toast Consistency

**Date:** 2026-09-07  
**Status:** Accepted / Implemented

## Context
ADR 132 added candidate Reservation Category mapping/fallback to Seat Allocation. During that change, the previously QA-passed UI projection of linked Admission status was unintentionally dropped, causing `Cancel Allocation` to appear enabled even when the allocation was already consumed by a `CONFIRMED` Admission.

The project also has a canonical mutation feedback path under ADR 125: server `flash.toast` for mutation outcomes and `useFlashToast()` validation-error fallback inside `AppLayout`. Seat Allocation must not reintroduce page-specific browser alerts for success/failure feedback.

## Decision
1. Restore linked Admission status to Seat Allocation screen payload.
2. An ACTIVE allocation linked to `admissions.status = CONFIRMED` renders `Cancel Allocation` disabled with guidance to revoke Admission first.
3. Backend cancellation remains authoritative and independently rejects cancellation while linked Admission is `CONFIRMED`.
4. A linked `REVOKED` Admission does not permanently consume/cancel-lock the allocation; after valid revocation, an authorized operator may explicitly cancel the allocation.
5. Candidate Reservation Category mapping/manual fallback from ADR 132 remains unchanged and independent of this guard.
6. Seat Allocation success/failure feedback uses the existing project toast architecture only:
   - controller success -> `flash.toast` -> Sonner success toast;
   - validation failure -> Inertia errors -> `useFlashToast()` error toast;
   - no page-specific `window.alert()` success/error path.
7. Processing state disables repeat clicks while cancellation is being submitted.

## Result
The old Admission Confirmation QA rule is restored without rolling back ADR 132, and Seat Allocation follows the same notification behavior as current Fee Management and other project mutations.
