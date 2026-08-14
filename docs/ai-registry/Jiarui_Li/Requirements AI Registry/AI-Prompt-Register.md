# Requirements AI Registry

**Deliverable category (PGP):** Requirements / User Stories  
**Member:** Jiarui Li  
**Team:** Team 236 — FIT3047 / FIT3048 Industry Experience  
**Compiled:** 14/08/2026

This folder is the GenAI footprint for this deliverable category, as required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.
Copy the folder into the matching PGP deliverable directory and keep the folder name.

## Entries

### AI-001 — Requirements elicitation — gap analysis against the onboarding brief

| Field | Value |
| --- | --- |
| ID | AI-001 |
| Date | 10/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Analysis |
| Purpose | Requirements elicitation — gap analysis against the onboarding brief |
| Outcome | Modified and adopted — became the 10/08 repair plan; not treated as a new requirements spec. |

**Prompt (as submitted)**

Pasted the unit handout “FIT3047 - Onboarding project. User stories + acceptance criteria + an example of test case for one aspect - V1” (Landing Page, Contact Form, Admin Login, and TC1–TC9) and asked: 阅读这些资料，我们还差什么 (Read this. What are we still missing?)

**Response summary**

Produced a gap analysis against the live CakePHP site: accessibility (labels vs placeholders, contrast, skip-link), password reset, HTTPS enforcement, test-case document for PGP/Trello links, and several security/validation defects already partially present in code.

**Validation performed**

Compared each claimed gap to templates, controllers, phpunit tests, and the Trello instruction that acceptance checklists must link to test cases in the PGP. Discarded items that were already implemented.

### AI-002 — Requirement generation — plan from the client needs document

| Field | Value |
| --- | --- |
| ID | AI-002 |
| Date | 13/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Analysis |
| Purpose | Requirement generation — plan from the client needs document |
| Outcome | Modified and adopted — frontend first, then database pack, then staff console. |

**Prompt (as submitted)**

阅读 /Users/lijiarui/Movies/2026-08-13 15-37-13_用户需求分析.md，就目前而言，排一个什么计划？ (Read this user-needs document. Given where we are now, what plan should we make?)

**Response summary**

Proposed a phased Eco Glow plan: keep existing auth/security; evolve the storefront from a contact-only site into catalogue, cart, accounts, and later services/admin.

**Validation performed**

Checked the document against the current routes and templates. Later cross-checked against Trello user stories and the Vision Board so the plan did not invent scope.

### AI-003 — User story development — compare Trello stories to the running app

| Field | Value |
| --- | --- |
| ID | AI-003 |
| Date | 14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) + Trello |
| Project phase | Analysis |
| Purpose | User story development — compare Trello stories to the running app |
| Outcome | Modified and adopted — used to scope the 14/08 console and checkout work. |

**Prompt (as submitted)**

阅读 trello 里面的 story，看看有没有我们缺少的？ (Read the Trello stories. What are we missing?)

**Response summary**

Mapped open Trello cards (US-1.x landing/catalogue, US-2.x enquiry, later commerce and staff work) to implemented pages and listed gaps: persistent cart, customer account, checkout, staff order/inventory console.

**Validation performed**

Opened the live board UGIE 26S2 Team 236 (FIT3047) and compared card titles to routes/controllers. Did not rewrite official user-story wording on Trello.
