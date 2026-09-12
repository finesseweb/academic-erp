# Fee Adjustments / Reversal / Refund — College Finance

Route: `/college/{college}/fee-adjustments`

Purpose: controlled post-demand/post-payment corrections without rewriting original financial transactions.

## Page sections
- Academic Session + student/admission/demand search.
- Manual Adjustment form: Demand, Fee Head, date, CREDIT/DEBIT, amount, reason code, mandatory reason.
- Posted Payment register: refundable balance, Refund action, full Receipt Reversal action.
- Adjustment Register with controlled reversal.
- Refund Register.

## Rules
- CREDIT cannot exceed the selected Fee Demand Item's current principal outstanding.
- DEBIT increases liability and is separately auditable.
- Cancelled demands cannot be adjusted.
- Receipt reversal is full only and cannot run after a posted refund exists.
- Refund is partial/full but only from original refundable Fee Demand Item allocations.
- Refund allocation is deterministic, latest original allocation first, and never exceeds unrefunded paid amount.
- Every mutation writes an audit event and recalculates authoritative Fee Demand summary values.

## Layout integration
- This page uses the ERP's global Inertia `AppLayout` supplied by `resources/js/app.tsx`.
- The page component must **not** wrap itself in another `AppLayout`; doing so creates a duplicate header/sidebar and overlapping content.
- Page content follows the same `p-4 md:p-6` content spacing convention used by existing College Fee pages.

## Shared form-control standard
- All text/number controls use the project's shared `Input` component.
- All dropdowns use the project's shared `Select`, `SelectTrigger`, `SelectContent`, `SelectItem`, and `SelectValue` components; native page-specific `<select>` controls are not used on this page.
- Adjustment Date and Refund Date use the project's shared theme-aware `DatePicker`; native `<input type="date">` is not used.
- Long-form reason fields use the shared `Textarea`; actions use the shared `Button` component.
- Every business input has a visible `Label`, controls use full available width, and wrappers use `min-w-0` where long student/demand labels can occur.
- This page must inherit the same theme tokens, focus states, control height, responsive behavior, and validation treatment as the rest of the ERP.

## Scalable payment register — 2026-09-12
- The Posted Payments area is **student/admission grouped**, not a flat receipt list.
- One parent row represents one Admission and shows Student, Admission No., Programme Offering, total posted amount, total refundable balance and receipt count.
- Expanding the parent row shows that student's individual posted receipts; Refund and full Receipt Reversal remain receipt-level actions.
- The payment register is server-paginated by **student/admission** (25 / 50 / 100), so one student with many receipts remains on one page and cannot flood the register.
- Filters are `Academic Session → Programme Offering → Search`.
- Programme Offering options are restricted to the selected Academic Session. Changing Session resets Programme Offering to All.
- Search must match Student Name, Application No., Admission No., Fee Demand No., Receipt No. and Payment Reference No.
- A receipt/demand search may locate the student, but once that student is returned, all posted receipts for that student within the selected Session/Programme Offering remain available inside the expanded row.
- Financial amounts are right-aligned; descriptive columns are left-aligned. Parent and nested receipt tables use explicit minimum widths and horizontal overflow rather than compressing/misaligning columns.
- Existing adjustment/refund/reversal accounting rules are unchanged by this grouping/filtering UI.
