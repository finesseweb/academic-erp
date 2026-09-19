# DB Impact — ADR 204A Imported Student Login

## Migration
`2026_09_19_160000_add_must_change_password_to_users.php`

Adds `users.must_change_password BOOLEAN NOT NULL DEFAULT FALSE`.

Ownership: authentication/access lifecycle on the existing canonical `users` table. No Student, Enrollment, Applicant, Admission, Curriculum, Fee, or course-choice table is added or duplicated.

Relationship remains `students.user_id -> users.id`. Imported account provisioning populates that existing FK.

Migration classification: additive, backward-compatible. Existing users default to false. The migration guards `Schema::hasColumn` so a retry after partial/manual application does not attempt to add the column twice. No new FK/index is required.

Temporary plaintext credentials are not persisted in DB. A one-time CSV is held on the private local disk and deleted on first authorized download.


## Credential recovery addendum
No additional schema change. Recovery updates the existing hashed `users.password` and `users.must_change_password` fields only. The replacement plaintext password exists only in a private one-time CSV and is never persisted in database/audit data.
