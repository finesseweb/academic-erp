# ADR 064 — Add Field dialog collection normalization

The Admission Form builder must not assume every hydrated step has non-null `fields` and `panels` arrays while opening a client-side dialog. All source-field discovery and panel rendering now normalizes collections with `Array.isArray` and filters malformed/null rows before property access. This keeps the existing builder architecture and advanced field-rule layer intact while preventing a client render exception from blanking the Inertia page.
