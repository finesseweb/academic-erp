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
