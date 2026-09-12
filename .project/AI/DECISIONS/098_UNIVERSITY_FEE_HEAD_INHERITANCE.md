# ADR 098 — University Fee Head Inheritance for Colleges

Date: 2026-09-04
Status: Accepted

## Decision
University-owned Fee Heads are reusable authoritative charge definitions for Colleges under the same University. A College must not duplicate a University Fee Head merely to use that charge in a College-owned Fee Structure.

College Fee Management therefore exposes both:
- University Fee Heads (`college_id = null`) as inherited/read-only; and
- Fee Heads owned by the current College (`college_id = current college`) as local/editable.

A College Fee Structure Item may reference an ACTIVE Fee Head from either source. University Fee Heads remain owned and maintained by the University; College administrators cannot edit, activate, deactivate, or otherwise mutate them.

If the University master does not contain a genuinely College-specific charge, the College may create a local Fee Head and use it in its own Fee Structures.

## Rules
1. University Fee Head exists → College reuses it; no duplicate local definition is required.
2. University Fee Head is inherited read-only in College Fee Management.
3. College can create local Fee Heads only for local charges not adequately represented by the University master.
4. College Fee Structure Items can use ACTIVE University or ACTIVE current-College Fee Heads.
5. Cross-University and other-College Fee Heads are never selectable or accepted by the backend.
6. Ownership and lifecycle permissions remain with the owning University/College.
7. Fee Head inheritance is independent of University Fee Structure Mandatory/Optional applicability in ADR 097.

## Rationale
This prevents duplicate masters, keeps reporting and charge semantics consistent across affiliated Colleges, and still preserves College flexibility for genuine local charges.
