# Table: `college_admission_form_panels`

## Purpose
Stores data related to the college admission form panels domain in Academic ERP.

## Database Table
`college_admission_form_panels`

## Columns

- `college_admission_form_step_id` (unsignedBigInteger)
- `title` (string)
- `code` (string)
- `description` (text)
- `display_order` (unsignedSmallInteger)
- `is_locked` (boolean)
- `status` (enum)
- `college_admission_form_step_id` (foreign)
- `college_admission_form_panel_id` (unsignedBigInteger)
- `college_admission_form_panel_id` (foreign)
- `caff_panel_fk` (dropForeign)
- `caff_panel_order_idx` (dropIndex)
- `college_admission_form_panel_id` (dropColumn)
- `id`
- `timestamps`

## Relationships

Relationships should follow foreign key definitions from Laravel migrations and Eloquent models.

## Business Usage

Used by the related ERP module workflows.

## Documentation Status

Generated during documentation synchronization on 2026-09-11. Review and enrich with business rules when module documentation is finalized.
