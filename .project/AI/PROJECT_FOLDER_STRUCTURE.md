# PROJECT FOLDER STRUCTURE — FROZEN

## Purpose
Every developer and AI agent must be able to understand the project structure immediately.

Do not create random feature files outside the approved structure.

## Repository Root

```text
project-root/
│
├── .project/
│   └── AI/
│       ├── DOCUMENTATION_ROOT.md
│       ├── PROJECT_CONSTITUTION.md
│       ├── MASTER_DEVELOPMENT_HIERARCHY.md
│       ├── NEXT_WORKFLOW.md
│       ├── CURRENT_IMPLEMENTATION_STATE.md
│       ├── PROJECT_FOLDER_STRUCTURE.md
│       ├── PAGE_FLOW_STANDARD.md
│       ├── MODULE_NAMING_STANDARD.md
│       ├── PAGE_SPECS/
│       ├── DOMAIN/
│       ├── SECURITY/
│       ├── DATABASE/
│       ├── THEMING/
│       ├── REALTIME/
│       ├── DECISIONS/
│       └── PROMPTS/
│
├── frontend/
│   ├── src/
│   │   ├── app/
│   │   │   ├── router/
│   │   │   ├── providers/
│   │   │   ├── layouts/
│   │   │   └── guards/
│   │   │
│   │   ├── modules/
│   │   │   ├── university/
│   │   │   ├── access-management/
│   │   │   ├── academic-setup/
│   │   │   ├── college/
│   │   │   ├── finance/
│   │   │   ├── admission/
│   │   │   ├── student/
│   │   │   ├── faculty/
│   │   │   ├── attendance/
│   │   │   ├── examination/
│   │   │   ├── result/
│   │   │   └── system/
│   │   │
│   │   ├── shared/
│   │   │   ├── components/
│   │   │   ├── forms/
│   │   │   ├── tables/
│   │   │   ├── feedback/
│   │   │   ├── hooks/
│   │   │   ├── utils/
│   │   │   ├── types/
│   │   │   └── constants/
│   │   │
│   │   ├── services/
│   │   │   ├── api/
│   │   │   ├── auth/
│   │   │   └── realtime/
│   │   │
│   │   ├── styles/
│   │   │   ├── tokens/
│   │   │   ├── themes/
│   │   │   └── globals/
│   │   │
│   │   └── main.tsx
│   │
│   ├── public/
│   ├── package.json
│   ├── tsconfig.json
│   └── vite.config.ts
│
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   └── Api/
│   │   │   │       └── V1/
│   │   │   ├── Requests/
│   │   │   ├── Resources/
│   │   │   └── Middleware/
│   │   │
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Services/
│   │   ├── Actions/
│   │   ├── Repositories/         # only where justified
│   │   ├── Enums/
│   │   ├── Events/
│   │   ├── Listeners/
│   │   ├── Jobs/
│   │   └── Support/
│   │
│   ├── database/
│   │   ├── migrations/
│   │   ├── seeders/
│   │   └── factories/
│   │
│   ├── routes/
│   │   ├── api.php
│   │   ├── web.php
│   │   └── channels.php
│   │
│   ├── tests/
│   │   ├── Feature/
│   │   └── Unit/
│   │
│   ├── config/
│   ├── storage/
│   ├── composer.json
│   └── artisan
│
├── README.md
└── .env.example
```

## Rule
Frontend and backend remain clearly separated.

Frontend feature code goes under:
`frontend/src/modules/<module>/`

Backend API/business code goes under:
`backend/app/...`

Database migrations go only under:
`backend/database/migrations/`

Do not create mixed PHP/React files in random page folders.
