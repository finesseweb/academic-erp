# reservation_categories

University-owned configurable Reservation / Quota category master.

## Columns
- `id`
- `university_id`
- `name`
- `code`
- `nature` — `VERTICAL|HORIZONTAL`
- `description`
- `display_order`
- `status`
- actor/timestamps

## Rules
Vertical categories partition physical admission seats.
Horizontal categories overlay the same physical seats and do not increase capacity.
Category names are data, not hard-coded ERP constants.
All selectors respect `display_order` first, then `name`.
