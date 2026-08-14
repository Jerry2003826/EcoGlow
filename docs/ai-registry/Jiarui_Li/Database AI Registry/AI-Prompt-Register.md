# Database AI Registry

**Deliverable category (PGP):** Database / ER Diagram  
**Member:** Jiarui Li  
**Team:** Team 236 — FIT3047 / FIT3048 Industry Experience  
**Compiled:** 14/08/2026

This folder is the GenAI footprint for this deliverable category, as required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.
Copy the folder into the matching PGP deliverable directory and keep the folder name.

## Entries

### AI-006 — Database design — review Database Pack v2

| Field | Value |
| --- | --- |
| ID | AI-006 |
| Date | 14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Design |
| Purpose | Database design — review Database Pack v2 |
| Outcome | Modified — waited for the MySQL/CakePHP v3 pack instead of forcing v2 onto MariaDB. |

**Prompt (as submitted)**

打开 Eco_Glow_Lighting_Database_Pack_v2_Expanded.zip。你和我说你想修改什么，我改完了发给你。或者如果你愿意自己做，我可以只发送给你 sql 脚本。 (Look at this pack. Tell me what you want changed, and I will send it back — or just send SQL.)

**Response summary**

Reported that v2 was PostgreSQL-oriented (JSONB, TIMESTAMPTZ, CITEXT, generated columns, stored procedures) and would not run cleanly on the team’s MariaDB / future cPanel MySQL host. Listed concrete incompatibilities.

**Validation performed**

Compared pack SQL to local MariaDB 12.2.2 behaviour and CakePHP 5 conventions.

### AI-007 — Database design — export fields the storefront already uses

| Field | Value |
| --- | --- |
| ID | AI-007 |
| Date | 14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Design |
| Purpose | Database design — export fields the storefront already uses |
| Outcome | Accepted |

**Prompt (as submitted)**

我们之前做的所有东西得保留（指的是和咱们的前端相关的）。这个前提下，你给出你的修改建议。这样，你提供一下我们的现有字段。其他的我自己来做。 (Keep everything frontend-related we already built. Give modification advice, then just list our existing fields — I will do the rest.)

**Response summary**

Wrote docs/current-schema-and-data.md and docs/frontend-seed-data.json describing live MySQL tables plus the hardcoded catalogue/copy used by the storefront.

**Validation performed**

Read templates and information_schema / migrations. Student used the field list to brief the database author.

### AI-008 — Database design — integrate MySQL CakePHP Database Pack v3

| Field | Value |
| --- | --- |
| ID | AI-008 |
| Date | 14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Development |
| Purpose | Database design — integrate MySQL CakePHP Database Pack v3 |
| Outcome | Modified and adopted — some uniqueness/overlap checks stay in PHP by design. |

**Prompt (as submitted)**

Provided Eco_Glow_Lighting_MySQL_CakePHP_Database_Pack_v3.zip and: 只需要支持 CakePHP 的就行了，咱们本地的可以换，未来支持 cPanel 就行。 (CakePHP support is enough. We can change the local stack. Future host is cPanel.)

**Response summary**

Adapted SQL migrations for MariaDB/MySQL 8, wired CakePHP migrations/seeds, and documented leftover service-layer constraints (price-list uniqueness, appointment overlap) that the engine cannot enforce the same way as PostgreSQL.

**Validation performed**

Ran migrations and seeds locally. Recorded remaining constraints in docs/database/service-layer-constraints.md. Confirmed frontend seed data still matched the redesigned pages.
