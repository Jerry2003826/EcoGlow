# AI Prompt Register — master index

**Member:** Jiarui Li (jlii0474@student.monash.edu)  
**Team:** Team 236  
**Unit:** FIT3047 / FIT3048 Industry Experience  
**Client:** Eco Glow Lighting  
**Period covered:** 30/07/2026 – 14/08/2026  
**Primary tool:** Cursor IDE agent (Grok 4.6 / Composer)  
**Compiled:** 14/08/2026

Merged from the chronological PGP register and the 14/08 artefact-level Word file.
Each project row is a material GenAI interaction. Detail lives in the category folder.

## Document control

| Field | Content |
| --- | --- |
| Unit | FIT3047 / FIT3048 Industry Experience |
| Project | Eco Glow Lighting (CakePHP 5 storefront and staff console) |
| Team | Team 236 |
| Member | Jiarui Li (jlii0474@student.monash.edu) |
| Period covered | 30 July 2026 – 14 August 2026 |
| Primary tool | Cursor IDE agent (Grok 4.6) |
| Last updated | 14 August 2026 |
| Intended PGP folders | BV AI Registry; Requirements AI Registry; Architecture AI Registry; Database AI Registry; UIUX AI Registry; Coding / Implementation AI Registry; Checkout AI Registry; Admin UI AI Registry; Testing AI Registry; Debugging AI Registry; Security AI Registry; Report AI Registry |
| Sources merged | This file merges (1) the chronological PGP register compiled from Cursor transcripts (30/07–14/08) with (2) the 14/08 artefact-level register that recorded Stripe sandbox, Pay-button recovery, Admin Users folds, sidebar navigation, account/checkout restyle, and the individual diary trail. IDs stay chronological. The six 14/08 rows from the shorter register map to AI-016, AI-021, AI-017, AI-020, AI-027, and AI-023. |

## Register

| ID | Date | Tool | Phase | Category | Purpose | Outcome |
| --- | --- | --- | --- | --- | --- | --- |
| AI-001 | 10/08/2026 | Cursor (Grok 4.6 / Composer) | Analysis | Requirements AI Registry | Requirements elicitation — gap analysis against the onboarding brief | Modified and adopted — became the 10/08 repair plan; not treated as a new requirements spec. |
| AI-002 | 13/08/2026 | Cursor (Grok 4.6 / Composer) | Analysis | Requirements AI Registry | Requirement generation — plan from the client needs document | Modified and adopted — frontend first, then database pack, then staff console. |
| AI-003 | 14/08/2026 | Cursor (Grok 4.6 / Composer) + Trello | Analysis | Requirements AI Registry | User story development — compare Trello stories to the running app | Modified and adopted — used to scope the 14/08 console and checkout work. |
| AI-004 | 13/08/2026 | Cursor (Grok 4.6 / Composer) | Design | Architecture AI Registry | Architecture design — decide what survives a frontend rewrite | Accepted |
| AI-005 | 14/08/2026 | Cursor (Grok 4.6 / Composer) with Grok sub-agents | Design | Architecture AI Registry | Architecture design — staff console, customer area, Stripe, bookings | Modified and adopted — Standard role narrowed; Stripe remains sandbox-only. |
| AI-006 | 14/08/2026 | Cursor (Grok 4.6 / Composer) | Design | Database AI Registry | Database design — review Database Pack v2 | Modified — waited for the MySQL/CakePHP v3 pack instead of forcing v2 onto MariaDB. |
| AI-007 | 14/08/2026 | Cursor (Grok 4.6 / Composer) | Design | Database AI Registry | Database design — export fields the storefront already uses | Accepted |
| AI-008 | 14/08/2026 | Cursor (Grok 4.6 / Composer) | Development | Database AI Registry | Database design — integrate MySQL CakePHP Database Pack v3 | Modified and adopted — some uniqueness/overlap checks stay in PHP by design. |
| AI-009 | 13/08/2026 | Cursor (Grok 4.6 / Composer) | Design | UIUX AI Registry | UI/UX design — rebuild the storefront from the Vision Board | Modified and adopted — first pass was not accepted as-is. |
| AI-010 | 13/08/2026–14/08/2026 | Cursor (Grok 4.6 / Composer) | Design | UIUX AI Registry | UI/UX design — student-directed visual corrections | Modified and adopted — human direction overrode the first AI layout. |
| AI-011 | 13/08/2026 | Cursor image generation | Design | UIUX AI Registry | UI/UX design — catalogue and material photographs | Modified and adopted as labelled placeholders only. |
| AI-012 | 06/08/2026 | Cursor (Grok 4.6 / Composer) | Development | Coding AI Registry | Coding assistance — start the server and fix defects | Accepted |
| AI-013 | 10/08/2026 | Cursor (Grok 4.6 / Composer) | Development | Coding AI Registry | Coding assistance — password reset and onboarding acceptance gaps | Accepted |
| AI-014 | 14/08/2026 | Cursor (Grok 4.6 / Composer) with Grok sub-agents | Development | Coding AI Registry | Coding assistance — staff console (RBAC, orders, inventory) | Modified and adopted |
| AI-015 | 14/08/2026 | Cursor (Grok 4.6 / Composer) with Grok sub-agents | Development | Coding AI Registry | Coding assistance — storefront data, customer accounts, cart, mail queue | Accepted. Account/checkout visual refresh is recorded separately as AI-027. |
| AI-016 | 14/08/2026 | Cursor IDE agent (Grok 4.6) | Development | Coding AI Registry | Coding assistance — Stripe test-mode checkout, CLI webhook, feature flag | Modified and adopted. Local-only secrets; feature flag left on for demonstration. |
| AI-017 | 14/08/2026 | Cursor IDE agent (Grok 4.6) | UI / UX | Coding AI Registry | UI/UX — Admin Users page: foldable, searchable sections | Modified and adopted |
| AI-018 | 10/08/2026 | Cursor (Grok 4.6 / Composer) | Testing | Testing AI Registry | Test case generation — TC1–TC9 for PGP / Trello | Accepted |
| AI-019 | 06/08/2026 | Cursor (Grok 4.6 / Composer) | Development | Debugging AI Registry | Debugging — reCAPTCHA test-key warning | Accepted |
| AI-020 | 14/08/2026 | Cursor IDE agent (Grok 4.6) | UI / UX | Debugging AI Registry | Debugging — keep the staff sidebar still across admin navigation | Modified and adopted. Rejected: view-transitions, 1-second scroll locks, and hide-until-positioned. |
| AI-021 | 14/08/2026 | Cursor IDE agent (Grok 4.6) | Development | Debugging AI Registry | Debugging — card-only PaymentIntent, Pay button recovery, resume unpaid checkout | Modified and adopted. Wallet methods were deliberately rejected for this demo path. |
| AI-022 | 06/08/2026 | Cursor (Grok 4.6 / Composer) | Development | Security AI Registry | Security review — full pass then fix | Accepted |
| AI-023 | 14/08/2026 | Cursor IDE agent (Grok 4.6) | Documentation | Report AI Registry | Report writing — compile and merge this AI Prompt Register | Modified and adopted — two registers merged; student owns the signed copy. |
| AI-025 | 13/08/2026 | Cursor (Grok 4.6 / Composer) | Analysis | BV AI Registry | Research / brainstorming — interpret the Vision Board for product direction | Modified — used as design interpretation only; rejected as a stand-in BV. |
| AI-026 | 30/07/2026 | Cursor | Design | Architecture AI Registry | Architecture design — login TDD planning notes | Accepted as internal notes only. |
| AI-027 | 14/08/2026 | Cursor IDE agent (Grok 4.6) | UI / UX | UIUX AI Registry | UI/UX — refresh account/auth and checkout visuals without breaking Stripe or tokens | Modified and adopted |

## Nil returns

| Category | Statement |
| --- | --- |
| Presentation preparation | No GenAI was used to draft or generate presentation slides in this period. |
| Meeting minutes | Meeting minutes were not authored by GenAI. AI was not asked to invent attendance, decisions, or quotes. |
| Academic literature / references | No AI-generated citations were used. No literature review was produced by GenAI. |
| Interviews, surveys, or fabricated evidence | GenAI was not used to invent stakeholder interviews, survey results, or test outcomes. Automated tests were executed with PHPUnit; manual checks were done in a browser. |

## What was rejected

These AI suggestions were tried and then removed from the product:

- Locking body overflow to hide page scroll (blanked the lower admin stage).
- CSS view-transitions on the admin rail (the rail slid toward Orders).
- sessionStorage scroll restore plus visibility:hidden (still flashed on Users / Feature flags).
- 1-second scroll locks and hide-until-positioned (still flashed).

The surviving sidebar approach is recorded in AI-020.

## Individual assessment (not a project artefact)

The following interaction influenced a Moodle reflective diary, not Eco Glow source code.

### AI-IND-001 — Understand the HD structure and turn a Chinese outline into an English draft

| Field | Value |
| --- | --- |
| ID | AI-IND-001 |
| Date | 14/08/2026 |
| Tool | Cursor IDE agent (Grok 4.6) |
| Project phase | Individual reflective diary |
| Purpose | Understand the HD structure and turn a Chinese outline into an English draft |
| Outcome | Modified. I will edit the draft in my own words before Moodle submit. |

**Prompt (as submitted)**

Translate the FIT3047/3048 Individual Reflective Diary specification and the good examples; recommend an HD structure; then turn my Chinese six-part draft into an English submission of about 500 words.

**Response summary**

English draft using I-voice, one pattern (freezing when watched), evaluation, analysis, a trial already run, and a three-part action plan with a next-week check. This register does not reproduce the diary text.

**Validation performed**

Read against the unit rubric: evaluation/analysis of self, and an action plan with tried steps. Word count about 500. No fabricated team incidents.
