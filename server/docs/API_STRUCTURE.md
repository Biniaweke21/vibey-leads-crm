# Vibey Leads — API Structure (for review)

> **⚠️ STALE — Phase 1 planning doc, not current-state documentation.** Every
> module below has since been built, and the actual routes/role model diverge
> from this catalog (roles are now `admin`/`team_member`/`client`, not
> `Admin/Sales/Project Manager/Client`; several endpoints here were never
> built as sketched or were built differently). Treat `routes/api.php` as the
> source of truth, not this file.

> **Status: DESIGN.** Only the **Authentication** endpoints are implemented in
> Phase 1. Everything else is the planned catalog — not built yet.

- Base path: **`/api`** · Format: **JSON** · Auth: **Sanctum bearer token**
  (`Authorization: Bearer <token>`).
- Every response uses the standard envelope:
  ```json
  { "success": true, "message": "", "data": {} }
  ```
  Errors: `{ "success": false, "message": "...", "errors": {...} }` ·
  Lists add a `meta` object (`current_page`, `per_page`, `total`, `last_page`).
- Standard status codes: `200/201` ok · `422` validation · `401` unauthenticated
  · `403` forbidden (role) · `404` not found · `500` server error.

### Access legend
`[A]` Admin · `[S]` Sales · `[P]` Project Manager · `[C]` Client · `[*]` any authenticated

---

## Authentication ✅ implemented
| Method | Endpoint | Access | Purpose |
|---|---|---|---|
| POST | `/api/login` | public | issue bearer token |
| POST | `/api/logout` | `[*]` | revoke current token |
| GET | `/api/user` | `[*]` | current user |

---

## Leads  `[A,S]`
| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/leads` | list (filter: status, priority, assigned_to, search; paginated, sortable) |
| POST | `/api/leads` | create (also the public request-form intake) |
| GET | `/api/leads/{lead}` | show |
| PUT | `/api/leads/{lead}` | update |
| DELETE | `/api/leads/{lead}` | delete `[A]` |
| PATCH | `/api/leads/{lead}/status` | move pipeline stage (Kanban drag) |
| PATCH | `/api/leads/{lead}/priority` | set priority |
| PATCH | `/api/leads/{lead}/assign` | assign to a user |
| POST | `/api/leads/{lead}/convert` | convert won lead → client (+ optional project) |
| GET/POST/PUT/DELETE | `/api/leads/{lead}/notes[/{note}]` | notes CRUD |
| GET/POST | `/api/leads/{lead}/activities` | timeline (list, log) |
| POST/DELETE | `/api/leads/{lead}/attachments[/{attachment}]` | upload / remove |
| POST | `/api/leads/{lead}/ai-analysis` | run AI analysis |

## Clients  `[A,S,P]`
| Method | Endpoint | Purpose |
|---|---|---|
| GET/POST | `/api/clients` | list / create |
| GET/PUT/DELETE | `/api/clients/{client}` | show / update / delete `[A]` |
| GET/POST/PUT/DELETE | `/api/clients/{client}/contacts[/{contact}]` | contacts CRUD |
| GET/POST | `/api/clients/{client}/payments` | client payments |
| GET/POST/PUT/DELETE | `/api/clients/{client}/notes[/{note}]` | notes |
| GET/POST | `/api/clients/{client}/activities` | timeline |
| POST/DELETE | `/api/clients/{client}/documents[/{attachment}]` | document files |

## Projects  `[A,S,P]`
| Method | Endpoint | Purpose |
|---|---|---|
| GET/POST | `/api/projects` | list (filter: client, status) / create |
| GET/PUT/DELETE | `/api/projects/{project}` | show / update / delete `[A]` |
| PATCH | `/api/projects/{project}/status` | change status |
| PATCH | `/api/projects/{project}/progress` | set progress % |
| PUT | `/api/projects/{project}/team` | sync team members |
| GET/POST/PATCH/DELETE | `/api/projects/{project}/milestones[/{milestone}]` | milestones (PATCH toggles done) |
| GET/POST/PATCH/DELETE | `/api/projects/{project}/tasks[/{task}]` | tasks (PATCH sets status) |
| GET/POST | `/api/projects/{project}/designs` | list / submit design `[A,S,P]` |
| PATCH | `/api/projects/{project}/designs/{design}` | approve / reject `[C]` |
| POST/DELETE | `/api/projects/{project}/attachments[/{attachment}]` | files |
| GET/POST | `/api/projects/{project}/activities` | timeline |

## Documents  `[A,S,P]`
| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/documents` | list (filter: type, client, status) |
| GET/POST/PUT/DELETE | `/api/documents/{document}` | CRUD |
| POST | `/api/proposals` · `/api/contracts` · `/api/invoices` | typed create shortcuts |
| POST | `/api/documents/{document}/send` | mark sent |
| POST | `/api/documents/{document}/duplicate` | clone (proposals) |
| POST | `/api/invoices/{document}/pay` | record payment / mark paid |
| GET | `/api/documents/{document}/pdf` | render/download PDF |

## Meetings  `[A,S,P]`
| Method | Endpoint | Purpose |
|---|---|---|
| GET/POST | `/api/meetings` | list (filter: range, status, client/project/lead) / create |
| GET/PUT/DELETE | `/api/meetings/{meeting}` | CRUD |
| PATCH | `/api/meetings/{meeting}/status` | complete / cancel |
| GET/POST | `/api/meetings/{meeting}/notes` | meeting notes |

## Reminders  `[A,S,P]`
| GET/POST | `/api/reminders` · GET `?filter=overdue|due-today|upcoming` |
| PATCH/DELETE | `/api/reminders/{reminder}` | complete / delete |

## Analytics  `[A]`  *(revenue data — admins only)*
| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/analytics/overview` | KPI tiles |
| GET | `/api/analytics/pipeline` | leads by stage |
| GET | `/api/analytics/revenue` | revenue trend (range param) |
| GET | `/api/analytics/conversion` | funnel / win rate |
| GET | `/api/analytics/team-performance` | per-user metrics |
| GET | `/api/analytics/services` | services breakdown |

## Settings  `[A]`
| Method | Endpoint | Purpose |
|---|---|---|
| GET/PUT | `/api/settings/company` | company profile |
| GET | `/api/settings/team` · POST/PUT/DELETE `/api/settings/team[/{user}]` | team accounts |
| GET/PUT | `/api/settings/roles` | roles & permission matrix |
| GET/PUT | `/api/settings/notifications` | notification prefs |
| GET/PUT | `/api/settings/security` | security prefs |

## AI  `[A,S,P]`  *(mostly stateless generation)*
| Method | Endpoint | Purpose |
|---|---|---|
| POST | `/api/ai/analyze-lead/{lead}` | requirement analysis → persists `ai_analyses` |
| POST | `/api/ai/proposal` | generate proposal draft (from lead/client/project) |
| POST | `/api/ai/email` | generate email draft |
| POST | `/api/ai/tasks` | generate project task breakdown |

## Uploads (shared)  `[*]`
| POST | `/api/uploads` | upload a file to Cloudinary, returns metadata (used by attachment endpoints) |

## Client Portal  `[C]`  *(scoped to the authenticated client's `client_id`)*
| Method | Endpoint | Purpose |
|---|---|---|
| GET | `/api/portal/dashboard` | client KPIs + recent items |
| GET | `/api/portal/projects[/{project}]` | own projects (read-only) |
| GET | `/api/portal/documents` | own proposals/contracts/invoices |
| GET/POST | `/api/portal/uploads` | client-uploaded documents |
| PATCH | `/api/portal/designs/{design}` | approve / reject a submitted design |
| GET | `/api/portal/meetings[/{meeting}]` | own meetings |
| GET | `/api/portal/timeline` | own activity feed |
| GET/PUT | `/api/portal/profile` | own profile |

---

## Conventions
- **Resource controllers** (`apiResource`) for the standard 5 verbs; explicit
  `PATCH` sub-routes for state transitions (status/priority/progress).
- **Form Requests** validate every write; failures return the 422 envelope.
- **API Resources** shape every response; totals are computed there.
- **Policies** enforce ownership (a PM sees their projects; a client sees only
  their own data via `client_id` scoping).
- **Role middleware** (`role:admin`, `role:admin,sales`) gates coarse access;
  analytics/revenue is `role:admin`.
- No versioning prefix for now (matches your `/api/login` examples); a `/v1`
  prefix can be added later without breaking clients.
