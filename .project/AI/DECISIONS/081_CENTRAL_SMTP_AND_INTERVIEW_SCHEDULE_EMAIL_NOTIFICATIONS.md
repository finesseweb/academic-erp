# ADR 081 — Central SMTP and Interview Schedule Email Notifications

## Status
Accepted.

## Decision
All ERP email delivery uses Laravel's configured mail transport. SMTP credentials are environment secrets and must be supplied once through the deployment `.env`; credentials must not be stored in controllers, notifications, database rows, React code, or committed project files.

The same central mail transport is shared by Applicant email verification, password-reset mail, Interview schedule notifications and future Laravel mail notifications.

Interview Scheduling sends candidate mail only on meaningful scheduling lifecycle changes:
- first save as `SCHEDULED` -> Interview Scheduled email
- change to schedule/panel/venue while `SCHEDULED`, or return to `SCHEDULED` -> Interview Rescheduled email
- transition to `CANCELLED` -> Interview Cancelled email
- `COMPLETED` does not send another schedule email

The recipient is the linked Applicant user email when present, otherwise the admission application's email snapshot. A malformed/missing email does not block Interview persistence. Mail transport failure is logged and does not roll back the saved academic transaction.

## Environment contract
Deployment provides the normal Laravel mail variables in `.env`, for example:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=erp@example.com
MAIL_PASSWORD=change-me
MAIL_FROM_ADDRESS=erp@example.com
MAIL_FROM_NAME="Academic ERP"
```

TLS/scheme must follow the installed Laravel `config/mail.php` contract and the SMTP provider's requirement. Secrets must never be committed.

After changing production `.env`, clear/rebuild Laravel configuration cache so the runtime reads the new values.
