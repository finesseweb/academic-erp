# ADR-001 Brand-New Database

## Decision
Use a brand-new MySQL database with Laravel migrations / Eloquent as ORM/migration authority.

## Rationale
The new Laravel/React ERP should have a clean, domain-driven, maintainable schema rather than inherit legacy Zend database constraints and technical debt.

## Legacy Position
Legacy Zend/MySQL may be consulted for business behavior and historical requirements only. It is not the target schema authority.

## Consequence
New tables and schema modifications are allowed as requirements evolve, provided they follow database standards, versioned migrations, testing and documentation rules. Destructive/data-loss changes require explicit approval.
