# college_applicant_registration_settings

College-scoped configuration for the public Applicant registration gateway and Applicant Portal assistance.

## Important columns
- `college_id` — owning College.
- `registration_enabled` — enables/disables new public Applicant account registration.
- `email_verification_required` — requires Applicant email verification before continuing.
- `captcha_required` — enables the registration CAPTCHA gate.
- `registration_number_format` — tokenized Applicant Registration Number format. Must include a sequence token.
- `registration_sequence_next` — next transactionally reserved Applicant Registration Number sequence.
- `application_help_phone` — optional admission/help phone shown to applicants.
- `application_help_email` — optional admission/help email shown to applicants.
- `application_help_text` — optional free-form help notes/instructions shown to applicants.
- `updated_by` — last settings actor where available.

## Registration-number lifecycle
Public Applicant account creation does not consume a registration sequence. The Applicant Registration Number is finalized on the first successful application submission using the current College setting. Legacy pre-submission numbers may be replaced at that first submission. After an earlier submitted application exists for the applicant in the College, the number remains stable.
