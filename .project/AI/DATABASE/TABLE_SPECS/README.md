# Table Specifications

Create one Markdown specification per important database table/model using `../TABLE_SPEC_TEMPLATE.md`.

The purpose is to give both developers and AI agents a reliable data dictionary: table purpose, row grain, columns, relationships, indexes, common joins, tenant scope and legacy mapping.

Rules:
- Keep specs synchronized with actual Laravel migrations / Eloquent/MySQL schema.
- Do not document guessed relationships as facts.
- Use the physical MySQL table name in the filename where practical, e.g. `students.md`.
- When a page/report uses a table, its PAGE_SPEC should link/name the relevant table specs.
