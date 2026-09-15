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

## Manual-adjustment eligibility — QA clarification 2026-09-13
- The Manual Adjustment demand selector lists only non-cancelled Fee Demands whose authoritative `outstanding_amount` is greater than zero.
- A fully settled / zero-outstanding demand is not eligible for a new manual adjustment and must not appear in that selector.
- Within an eligible demand, the Fee Head selector lists only demand items whose current adjustable principal liability is greater than zero after approved benefits, posted generic adjustments, posted payments and posted refunds are considered.
- The backend repeats these checks when posting; this is a business-rule guard and not UI-only filtering.
- Fully paid students/receipts remain available in the separate Posted Payments — Reversal / Refund register when their posted receipts are otherwise eligible. Zero-outstanding filtering therefore does not remove legitimate reversal/refund history.


## Transaction-date integrity — QA clarification 2026-09-14
- Manual `Adjustment Date` must not be in the future.
- The UI DatePicker caps Manual Adjustment at the user's local current date.
- The backend independently enforces `adjustment_date <= today`; this rule cannot be bypassed through a crafted/stale request.
- Adjustment reversal remains timestamped by the server at reversal time. Preventing future-dated source adjustments guarantees the reversal cannot legitimately precede its source transaction in the Student Fee Ledger.
- Backdated adjustments remain allowed when otherwise valid.
- Existing test/legacy rows created before this guard are not silently rewritten; incorrect QA data should be cleaned and recreated.

## QA / UX patch — 2026-09-14 — Theme-native reversal prompts
- Adjustment reversal reason entry and complete payment-reversal confirmation must use the global ERP-themed application dialog layer from ADR 191.
- Browser-native `prompt()` / `confirm()` dialogs are not permitted.
- Reversal behavior, authorization and accounting semantics remain unchanged; this patch changes only the interaction shell.

## Semantic action icons — QA consistency patch 2026-09-14
- This page follows ADR 192 and the mandatory icon rules in `UI_UX_GUIDELINES.md`.
- Search, Post Adjustment, View/Hide receipts, Refund, Adjustment Reversal and Payment Reversal use Lucide semantic icons together with visible text.
- Refund and reversal actions must not be rendered as plain text-only action buttons when this page is changed in future.
- Themed reversal prompt/confirmation actions use the dialog provider's `confirmIcon` capability so the same semantic reversal cue is present inside the modal.
- Standard action icon size is `size-4` unless the shared component requires another size.

## Installment reconciliation rule — QA hardened 2026-09-14
When a CREDIT/DEBIT adjustment changes an installment-enabled Fee Head, the installment schedule must be rebalanced to the exact new Fee Head liability while protecting all already-paid amounts. Paid principal is immutable through an adjustment: an installment's effective amount may never be reduced below its `paid_amount`. Any proportional shortfall created by that paid floor must be redistributed to other installments; it must not inflate the schedule total. Refund/reversal remains the controlled mechanism for changing posted payment principal.

## QA rule — installment recalculation symmetry
For an installment-enabled Fee Head, posting or reversing an adjustment must recalculate from the original installment allocation basis. Previous adjusted row amounts must never become the basis for the next adjustment/reversal. Already-paid principal is a hard floor. Installment due dates do not change allocation; they only determine whether the remaining balance is overdue/current/upcoming.


## Mixed receipt and cumulative refund contract — QA hardened 2026-09-15
- Refundability is allocation-level, never receipt-total-level. One receipt may contain refundable and non-refundable Fee Heads.
- Owner QA PASS example: ₹22,000 receipt = ₹20,000 non-refundable Tuition + ₹2,000 refundable Library; the register must show `Paid ₹22,000 / Refundable ₹2,000`.
- A refund reopens liability only on the refundable source Fee Demand Item allocation. The non-refundable portion remains settled.
- Multiple refunds against the same receipt consume the same original refundable payment allocations cumulatively. Remaining refundable = original refundable paid allocation minus all POSTED refund allocations already applied to it.
- The backend must explicitly select/alias the payment-allocation amount used in this calculation; joined-query object shape must not be assumed.
- Over-refund is checked against the cumulative remaining refundable amount on every request.
- Sensitive refund/reversal mutations by delegated College staff must use the existing audit subsystem with the actual actor and exact College scope so authorized College Audit Log views can surface the event.

### Repeat partial refund integrity — 2026-09-15
A receipt may receive multiple partial refunds only up to the remaining refundable paid allocation. Remaining refundable amount = refundable payment allocation − SUM(all POSTED refund allocations against that allocation). The aggregate must use a deterministic SQL alias. A repeat refund must return controlled validation when over limit and must never produce a raw 500. Successful actions must create College-scoped audit records.
