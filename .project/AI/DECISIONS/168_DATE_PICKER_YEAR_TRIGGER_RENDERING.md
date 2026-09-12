# ADR 168 — DatePicker Year Trigger Rendering

## Problem
ADR 167 correctly synchronised the DatePicker view with dynamic min/max bounds, but the shared Select trigger could still render a blank selected Year label in bounded pickers even while the calendar grid itself was showing a valid year.

## Decision
The shared DatePicker Year trigger now renders `view.getFullYear()` directly rather than relying on the Select component's selected-value presentation. The Select remains controlled by the same year value and its option list is unchanged.

## Why
The visible calendar month/year is already authoritative in DatePicker state. Rendering that year directly prevents a blank trigger caused by Select presentation/value-registration timing while preserving normal year selection.

## Scope
Shared `resources/js/components/ui/date-picker.tsx`. This fixes Academic Period and any other project screen using the shared DatePicker, including bounded Fee Due Date pickers.

## Business impact
None. No date rules, validation, persistence, RBAC, Academic Period architecture, or Fee linkage changed.

## Migration
None.
