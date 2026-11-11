# Vibey Leads — Database Design (for review)

> **Status: PROPOSED — not yet migrated.** Only the auth-foundation tables
> (`users`, `personal_access_tokens`, `cache`, `jobs`) exist so far. The
> business tables below are documented for your review first, per Phase 1.
> No business migrations will be generated until this schema is approved.

Engine: **MySQL 8**, `utf8mb4_unicode_ci`, InnoDB. All tables carry
`created_at` / `updated_at` unless noted. All money is stored as
`DECIMAL(12,2)` (ETB). All FKs are indexed.

---

## 1. Design principles

The frontend reuses three generic components everywhere — `NotesCard`,
`ActivityTimelineCard`, `AttachmentsCard`. The schema mirrors that reuse with
**three polymorphic tables** instead of duplicating notes/activity/attachment
columns on every entity:

- `notes` — attaches to leads, clients, projects, meetings…
- `activities` — the timeline/audit log for any entity
- `attachments` — Cloudinary file references for any entity

Multi-value intake fields that are never queried individually (a lead's
`services`, `reference_links`, feature checklists) are stored as **JSON
columns** rather than over-normalized pivots. Money and status fields that
*are* filtered/aggregated get real columns + indexes.

---

## 2. Entity overview & relationships

```
users ──< (assigned_to) leads
users ──1:1?── clients            (a portal user belongs to one client company)
clients ──< projects ──< tasks
                     ├─< milestones
                     └─< design_submissions
clients ──< client_contacts
clients ──< payments
clients ──< documents >── projects        (a document optionally belongs to a project)
documents ──< document_line_items
documents ──< document_payments
meetings >── clients | projects | leads   (nullable links, at most one context)
meetings ──< meeting_attendees
leads ──1── ai_analyses
reminders >── leads (nullable) , users
notes / activities / attachments ──(polymorphic)── any of the above
roles ──< role_permissions >── permissions      (Settings › Roles matrix)
settings (key → json)                            (company profile, prefs, security)
```

Relationship summary:
- **User → Lead**: one user (sales/PM) is assigned many leads (`leads.assigned_to`).
- **User ↔ Client**: a **client-portal** user (`role = client`) belongs to one
  `client` via `users.client_id`. Team users have `client_id = NULL`.
- **Client → Project**: one-to-many.
- **Project → Milestone / Task / DesignSubmission**: one-to-many.
- **Project ↔ User (team)**: many-to-many via `project_user`.
- **Client → Document**, **Project → Document (optional)**: one-to-many.
- **Document → LineItem / Payment**: one-to-many.
- **Meeting → Client | Project | Lead**: each nullable; a meeting has at most
  one primary context.
- **Lead → AiAnalysis**: one-to-one (latest analysis).
- **Notes / Activities / Attachments**: polymorphic one-to-many on any subject.

---

## 3. Tables

### 3.1 `users`  *(exists — extended with `role`, `client_id`)*
| column | type | notes |
|---|---|---|
| id | BIGINT UNSIGNED PK | |
| name | VARCHAR(255) | |
| email | VARCHAR(255) UNIQUE | |
| email_verified_at | TIMESTAMP NULL | |
| password | VARCHAR(255) | bcrypt (hashed cast) |
| role | VARCHAR(20) INDEX | enum: `admin` \| `sales` \| `project_manager` \| `client` |
| client_id | BIGINT UNSIGNED NULL FK→clients | set only for portal users |
| remember_token | VARCHAR(100) NULL | |
| timestamps | | |

Constraints: `FK client_id → clients.id ON DELETE CASCADE`.
> `client_id` is added in the **clients** migration (created after `clients` exists) to avoid a circular dependency.

### 3.2 `leads`
Captures the public "Request a Quote" wizard + pipeline state.

| column | type | notes |
|---|---|---|
| id | BIGINT PK | |
| assigned_to | BIGINT NULL FK→users | pipeline owner |
| converted_client_id | BIGINT NULL FK→clients | set when a lead is won → client |
| full_name | VARCHAR(255) | |
| company_name | VARCHAR(255) NULL | |
| email | VARCHAR(255) INDEX | |
| phone | VARCHAR(50) NULL | |
| whatsapp | VARCHAR(50) NULL | |
| city | VARCHAR(120) NULL | |
| preferred_contact | VARCHAR(20) NULL | WhatsApp/Call/Email |
| business_name | VARCHAR(255) | |
| industry | VARCHAR(120) NULL | |
| website_url | VARCHAR(255) NULL | |
| social_links | VARCHAR(500) NULL | |
| business_description | TEXT NULL | |
| project_idea | TEXT NULL | |
| main_goal | TEXT NULL | |
| target_users | TEXT NULL | |
| problems | TEXT NULL | |
| core_features | TEXT NULL | |
| services | JSON | e.g. `["Website","E-commerce"]` |
| other_service | VARCHAR(255) NULL | |
| features | JSON NULL | per-service feature checklist map |
| other_feature | VARCHAR(255) NULL | |
| design_style | VARCHAR(50) NULL | |
| reference_links | JSON NULL | |
| budget | VARCHAR(50) NULL | intake band, e.g. "50,000 – 100,000 ETB" |
| timeline | VARCHAR(50) NULL | |
| additional_notes | TEXT NULL | |
| status | VARCHAR(30) INDEX | 10 stages (see §4.1) |
| priority | VARCHAR(10) INDEX | `hot`\|`warm`\|`cold` |
| ai_analysis_status | VARCHAR(10) | `pending`\|`ready` |
| stage_entered_at | TIMESTAMP | drives "days in stage" |
| due_date | DATE NULL | |
| timestamps | | |

Indexes: `(status)`, `(priority)`, `(assigned_to)`, `(email)`, `(created_at)`.
Design files / general files → `attachments` (polymorphic). Notes → `notes`.
Timeline → `activities`.

### 3.3 `clients`
| column | type | notes |
|---|---|---|
| id | BIGINT PK | |
| name | VARCHAR(255) | |
| industry | VARCHAR(120) NULL | |
| city | VARCHAR(120) NULL | |
| status | VARCHAR(20) INDEX | `active`\|`inactive`\|`past` |
| last_interaction_at | TIMESTAMP NULL | |
| timestamps | | |

### 3.4 `client_contacts`
| id PK | client_id FK→clients CASCADE | name | role VARCHAR(120) | email | phone | timestamps |

### 3.5 `payments`  *(client-level payments, distinct from invoice payments)*
| id PK | client_id FK→clients CASCADE | description | amount DECIMAL(12,2) | status `paid`\|`pending` | paid_at TIMESTAMP NULL | timestamps |

### 3.6 `projects`
| column | type | notes |
|---|---|---|
| id | BIGINT PK | |
| client_id | BIGINT FK→clients CASCADE | |
| name | VARCHAR(255) | |
| description | TEXT NULL | |
| status | VARCHAR(20) INDEX | `planning`\|`in-progress`\|`on-hold`\|`completed`\|`maintenance` |
| progress | TINYINT UNSIGNED | 0–100 |
| budget | DECIMAL(12,2) | |
| start_date | DATE NULL | |
| end_date | DATE NULL | |
| timestamps | | |

### 3.7 `project_user`  *(team pivot)*
| project_id FK CASCADE | user_id FK CASCADE | PRIMARY KEY(project_id, user_id) |

### 3.8 `milestones`
| id PK | project_id FK CASCADE | title | due_date DATE NULL | is_done BOOLEAN default 0 | timestamps |

### 3.9 `tasks`
| id PK | project_id FK CASCADE | assigned_to FK→users NULL | title | status `todo`\|`in-progress`\|`done` INDEX | timestamps |

### 3.10 `design_submissions`
Team submits a design; client approves/rejects (portal).

| id PK | project_id FK CASCADE | attachment_id FK→attachments NULL | name | size INT | mime_type VARCHAR(120) NULL | status `pending`\|`approved`\|`rejected` INDEX | submitted_by FK→users | submitted_at TIMESTAMP | decided_by FK→users NULL | decided_at TIMESTAMP NULL | note TEXT NULL | timestamps |

### 3.11 `documents`
Single table for proposals, contracts, invoices (shared columns + JSON body for
type-specific narrative; money normalized into line items/payments).

| column | type | notes |
|---|---|---|
| id | BIGINT PK | |
| type | VARCHAR(20) INDEX | `proposal`\|`contract`\|`invoice` |
| client_id | BIGINT FK→clients CASCADE | |
| project_id | BIGINT NULL FK→projects SET NULL | |
| title | VARCHAR(255) | |
| number | VARCHAR(50) NULL UNIQUE | invoice/contract number |
| status | VARCHAR(20) INDEX | proposal: draft/sent/accepted/rejected · invoice: draft/sent/paid/overdue |
| version | SMALLINT default 1 | proposals |
| currency | CHAR(3) default 'ETB' | |
| issue_date | DATE NULL | |
| due_date | DATE NULL | invoices |
| tax_rate | DECIMAL(5,2) default 0 | percent |
| body | JSON NULL | executive_summary, scope, deliverables[], timeline_phases[], terms, contract parties/signatures |
| created_by | BIGINT NULL FK→users | |
| timestamps | | |

Totals (subtotal/total) are derived from `document_line_items` in the resource
layer, so they can't drift.

### 3.12 `document_line_items`
| id PK | document_id FK CASCADE | description | quantity DECIMAL(10,2) default 1 | unit_price DECIMAL(12,2) | sort_order SMALLINT default 0 |

### 3.13 `document_payments`  *(invoice payment history)*
| id PK | document_id FK CASCADE | amount DECIMAL(12,2) | method VARCHAR(50) | paid_at TIMESTAMP | timestamps |

### 3.14 `meetings`
| column | type | notes |
|---|---|---|
| id | BIGINT PK | |
| title | VARCHAR(255) | |
| scheduled_at | DATETIME INDEX | |
| duration_minutes | SMALLINT default 30 | |
| location | VARCHAR(255) NULL | Google Meet / Phone / … |
| status | VARCHAR(20) INDEX | `upcoming`\|`completed`\|`cancelled` |
| client_id | BIGINT NULL FK→clients SET NULL | |
| project_id | BIGINT NULL FK→projects SET NULL | |
| lead_id | BIGINT NULL FK→leads SET NULL | |
| created_by | BIGINT NULL FK→users | |
| timestamps | | |

CHECK (app-level): at most one of client/project/lead set as primary context.

### 3.15 `meeting_attendees`
| id PK | meeting_id FK CASCADE | user_id FK→users NULL | name VARCHAR(255) | (name kept for external attendees) |

### 3.16 `reminders`
| id PK | user_id FK→users CASCADE | lead_id FK→leads CASCADE NULL | title | due_at DATETIME INDEX | completed_at TIMESTAMP NULL | timestamps |

### 3.17 `ai_analyses`
| id PK | lead_id FK→leads CASCADE | status `pending`\|`ready` | summary TEXT NULL | score TINYINT NULL | recommendations JSON NULL | model VARCHAR(60) NULL | timestamps |

### 3.18 Polymorphic shared tables

**`notes`**
| id PK | noteable_type VARCHAR(255) | noteable_id BIGINT | user_id FK→users NULL | body TEXT | timestamps | INDEX(noteable_type, noteable_id) |

**`activities`** (timeline / audit)
| id PK | subject_type VARCHAR(255) | subject_id BIGINT | user_id FK→users NULL | description VARCHAR(500) | properties JSON NULL | created_at | INDEX(subject_type, subject_id) |

**`attachments`** (Cloudinary refs)
| id PK | attachable_type VARCHAR(255) | attachable_id BIGINT | uploaded_by FK→users NULL | uploaded_by_role VARCHAR(20) NULL | name VARCHAR(255) | mime_type VARCHAR(120) NULL | size INT UNSIGNED | cloudinary_public_id VARCHAR(255) NULL | url VARCHAR(500) NULL | secure_url VARCHAR(500) NULL | timestamps | INDEX(attachable_type, attachable_id) |

### 3.19 Settings tables

**`settings`** (singletons as key → JSON): company profile, notification prefs,
security prefs.
| id PK | key VARCHAR(100) UNIQUE | value JSON |

**`roles`** | id PK | name UNIQUE | description |
**`permissions`** | id PK | key UNIQUE | label |
**`role_permissions`** | role_id FK CASCADE | permission_id FK CASCADE | enabled BOOLEAN | PK(role_id, permission_id) |

> Note: coarse auth gating uses `users.role` (enum). The `roles`/`permissions`
> tables power the **Settings › Roles & Permissions** matrix (fine-grained,
> configurable). Kept separate deliberately; no extra package needed.

---

## 4. Enumerations (mirrored as PHP enums)

**4.1 Lead status:** new, contacted, discovery-call, proposal-sent,
negotiation, won, lost, in-development, delivered, maintenance
**Lead priority:** hot, warm, cold · **AI status:** pending, ready
**Client status:** active, inactive, past
**Project status:** planning, in-progress, on-hold, completed, maintenance
**Task status:** todo, in-progress, done · **Design status:** pending, approved, rejected
**Document type:** proposal, contract, invoice
**Meeting status:** upcoming, completed, cancelled
**User role:** admin, sales, project_manager, client

---

## 5. Open questions for review
1. **Documents**: single-table + JSON `body` (proposed) vs. separate
   `proposals` / `contracts` / `invoices` tables. I chose single-table for
   fewer joins; happy to split if you prefer strict typing per document.
2. **Team assignment on leads/projects**: frontend stores names; I map them to
   `users`. Confirm every "assignee" is a real user account.
3. **AI outputs** (proposal/email/task-breakdown generation): treat as
   stateless generation endpoints (not persisted), except `ai_analyses` which
   backs the lead's AI status. Confirm you don't need a history of every
   generation.
4. **Reminders** scope: currently lead-linked; keep `lead_id` nullable for
   general reminders — OK?
```
