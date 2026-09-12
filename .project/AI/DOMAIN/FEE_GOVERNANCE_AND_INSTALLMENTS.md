# Fee Governance and College Installment Model

## Core Principle
Fee governance is layered between the University and its affiliated Colleges.

The University defines common fee policy and centrally controlled charges. A College can configure or define only those fee components permitted by University policy. The student's final payable amount can combine University-level and College-level fee components.

## Fee Control Types

### UNIVERSITY_FIXED
University owns the fee definition and controlled amount/rule. College may apply and collect it but cannot alter the controlled amount.

Examples: University examination fee, enrollment/registration fee, migration fee, degree/certificate fee.

### COLLEGE_CONFIGURABLE
University defines the fee head and permitted rule/range. College configures the allowed value within policy.

### COLLEGE_DEFINED
College creates an approved local fee component, subject to permissions, approval, audit and University policy.

Examples can include tuition where permitted, lab, library, transport, hostel, development or activity charges.

## University Finance Governance
Authorized University users may define fee heads, University-fixed charges, fee policies, approval thresholds, late-fee rules, scholarship/waiver rules and consolidated University reports.

## College Fee Management
Authorized College users may configure permitted fee structures, assign fees to students, collect payments, issue receipts, manage dues and create installment plans where University policy permits.

## College Installment Plans
Installments are a payment schedule for an approved payable amount; they are not a separate fee and must not be used to change a University-fixed fee.

A College installment plan should support:
- plan name;
- academic session;
- program / semester / category applicability;
- fee structure / payable amount;
- number of installments;
- installment amount or percentage;
- due dates;
- grace period;
- late fee / penalty rule;
- minimum first installment where required;
- partial-payment policy;
- advance-payment policy;
- eligibility;
- approval status;
- effective dates and version/history.

Example: total payable INR 40,000 may be scheduled as four INR 10,000 installments.

## Integrity Rules
- A College installment plan cannot change a `UNIVERSITY_FIXED` amount.
- Published schedules must not be silently overwritten after financial activity exists.
- Rescheduling must preserve prior schedule/history.
- Each installment tracks due, paid, outstanding and status.
- Discounts, waivers, refunds, reversals and rescheduling are permission-controlled and auditable.
- Posted payment history must never be deleted merely because an invoice, checkout or schedule is later reversed.

## Academic Period to Finance Chain - Current Implemented Contract

Curriculum Academic Periods govern when term/year charges may become due, but
finance records intentionally preserve immutable snapshots instead of depending
on a mutable Calendar row forever.

`Current approved Curriculum -> ACTIVE Curriculum Term -> University Academic Calendar Period -> Fee Setup billing period and due date -> Fee Demand snapshot -> Benefit/Installment/Late Fine -> Payment Allocation`

- Fee Setup derives valid period numbers from ACTIVE Terms of the exact current approved Curriculum (or the exact Curriculum on a College Program Offering).
- For `PER_TERM`, `period_no` is the Curriculum Term sequence. For `PER_ACADEMIC_YEAR`, it is a derived grouping of the Curriculum Terms required for that academic year.
- A Standard Due Date for a recurring Fee Item must fall inside the corresponding configured Calendar Academic Period. Academic-year charges require all covered Term periods.
- Fee Demand snapshots `billing_period_no`, `billing_period_label`, each item's `source_period_no`, and its effective `due_date` plus period-specific policy flags and amount.
- Approved Student Benefits attach to the Fee Demand/Demand Items. They reduce payable balances without changing the Calendar Period or original Fee Setup definition.
- Installment schedules attach to Demand Items and replace collection timing with auditable installment due dates; they do not redefine the academic period.
- Late Fine calculation uses the snapshotted Demand Item or Installment due date.
- Payment Collection reads outstanding Demand Items, Installments, Benefits, and Late Fines, then stores deterministic allocations with the consumed due date.
- There is no direct `academic_calendar_term_period_id` foreign key on every finance table. The enforced link exists at Fee Setup validation, then continues through immutable period/due-date snapshots so later Calendar edits cannot reinterpret posted financial history.

See ADRs 099-105, 107, 112, 115, 118, 130, 142-143, 158,
160-164, and 170-175.
