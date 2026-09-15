# Codex Prompt — University Administration Phase

ALWAYS read from `.project/AI/` first.

Before coding, read:
- `.project/AI/README.md`
- `.project/AI/PROJECT_CONSTITUTION.md`
- `.project/AI/DOMAIN/UNIVERSITY_HIERARCHY.md`
- `.project/AI/DOMAIN/FEE_GOVERNANCE_AND_INSTALLMENTS.md`
- `.project/AI/ARCHITECTURE.md`
- `.project/AI/DATABASE_RULES.md`
- `.project/AI/SECURITY/` relevant docs
- `.project/AI/THEMING/THEME_SYSTEM.md`
- relevant `.project/AI/PAGE_SPECS/UNIVERSITY_ADMIN/` page specs

The software is a University ERP. University is the business root. SUPER_ADMIN is the highest University-level role.

Do not create a competing generic institution/tenant domain. Affiliated Colleges belong to the University.

Implement only the approved current milestone. Maintain premium UI, strict RBAC, University/College scope, Laravel migrations / Eloquent/MySQL standards and documentation synchronization.

College Fee Management must follow University fee governance; College-level Installment Plans are allowed only within approved policy.

After implementation, update all affected authoritative documentation under `.project/AI/`.
