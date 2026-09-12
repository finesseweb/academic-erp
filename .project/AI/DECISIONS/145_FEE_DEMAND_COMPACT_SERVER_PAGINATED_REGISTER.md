# ADR 145 — Fee Demand Compact Server-Paginated Register

**Status:** Implemented — QA Pending  
**Date:** 2026-09-09

## Context
Fee Demand is a high-growth transactional register. Rendering one large card per admission/demand and loading the entire college demand history into the browser will not scale as sessions and student cohorts accumulate. ADR 144 already established the project-wide rule that high-volume student operational registers must use compact rows, lifecycle-aware academic context, on-demand detail, and server-side pagination.

## Decision
1. Fee Demand uses a compact table at register level; detailed demand/item/benefit/installment actions remain available only after `View` / `Details` expansion.
2. The register is grouped by Admission/Application, preserving the existing financial grouping semantics while avoiding giant cards.
3. Register pagination is server-side with 25 / 50 / 100 rows per page. The controller first paginates admission groups and loads detailed Fee Demands only for the visible page.
4. Session is the first register context. The canonical `academic_sessions.is_current` session is auto-selected on initial load; historical sessions remain selectable.
5. Programme Offering is optional and is constrained to the selected Session. Status and free-text search are optional filters.
6. Search supports Student Name, Application No., Admission No., Demand No. and billing-period text.
7. Batch is not introduced as a universal Fee Demand filter. Batch belongs to the Enrollment lifecycle; Fee Demand can exist for confirmed admissions before Batch assignment.
8. Financial invariants and existing actions are unchanged: gross demand remains immutable after benefit adjustment, demand detail still exposes fee heads, benefit audit, installment management and valid cancellation.

## Project-wide consistency rule
Any operational screen expected to accumulate large student/transaction volumes must default to compact register/table presentation, server-side filtering/pagination, and on-demand detail rather than fully expanded cards. Academic filters must respect lifecycle ownership; Session is preferred first context and Current Session is the default where the module is session-bound.

## QA gate
- Current Session auto-selects.
- Historical Session can be selected.
- Programme Offering options follow selected Session.
- Search and Status filters preserve Session context.
- 25/50/100 server pagination works without loading all historical demands.
- View opens only the selected admission group; Details opens only the selected demand.
- Existing benefit/installment/cancellation actions remain functional.
- No financial calculation or demand-generation behavior changes.
