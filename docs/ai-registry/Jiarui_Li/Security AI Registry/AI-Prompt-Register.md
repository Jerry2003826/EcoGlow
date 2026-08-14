# Security AI Registry

**Deliverable category (PGP):** Security  
**Member:** Jiarui Li  
**Team:** Team 236 — FIT3047 / FIT3048 Industry Experience  
**Compiled:** 14/08/2026

This folder is the GenAI footprint for this deliverable category, as required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.
Copy the folder into the matching PGP deliverable directory and keep the folder name.

## Entries

### AI-022 — Security review — full pass then fix

| Field | Value |
| --- | --- |
| ID | AI-022 |
| Date | 06/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Development |
| Purpose | Security review — full pass then fix |
| Outcome | Accepted |

**Prompt (as submitted)**

/code-review 再次检查有无报错等等。还有一些可以改善的情况，充分完整检查。然后：完全修复。

**Response summary**

Mass-assignment lockdown on ContactMessage/User, login throttle middleware, security headers, CSRF SameSite/secure cookies, recaptcha fail-closed in production, and tests for those behaviours.

**Validation performed**

New unit/controller/middleware tests. ApplicationTest middleware order updated. Did not treat the review as a penetration-test report.
