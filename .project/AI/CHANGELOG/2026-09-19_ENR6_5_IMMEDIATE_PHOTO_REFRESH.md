# ENR-6.5 — Immediate Student Profile Photo Refresh

- Fixed stale profile image after replacing an existing Student Profile photo.
- Private profile-photo URLs now include the profile-value update timestamp as a cache-busting version.
- Add Photo and Update Photo now share the same immediate post-upload rendering behavior.
- No DB/schema/reference-data change.
