# Table: `fee_demand_items`

## Purpose
Stores data related to the fee demand items domain in Academic ERP.

## Database Table
`fee_demand_items`

## Columns

- Refer to migration definition

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.

## Current Snapshot Contract - 2026-09-12

Each row belongs to a `fee_demand` and snapshots the source Fee Structure Item,
Fee Head, `source_period_no`, effective `due_date`, amount, mandatory flag,
enrollment-clearance flag, installment permission, and refundability. These
values descend from Academic-Period-validated Fee Setup and remain stable when
Calendar or Fee Setup configuration later changes.
