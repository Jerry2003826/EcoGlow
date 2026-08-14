# Architecture AI Registry

**Deliverable category (PGP):** Architecture  
**Member:** Jiarui Li  
**Team:** Team 236 — FIT3047 / FIT3048 Industry Experience  
**Compiled:** 14/08/2026

This folder is the GenAI footprint for this deliverable category, as required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.
Copy the folder into the matching PGP deliverable directory and keep the folder name.

## Entries

### AI-004 — Architecture design — decide what survives a frontend rewrite

| Field | Value |
| --- | --- |
| ID | AI-004 |
| Date | 13/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Design |
| Purpose | Architecture design — decide what survives a frontend rewrite |
| Outcome | Accepted |

**Prompt (as submitted)**

咱们的前端内容可能要重做，但是其他的可以保留吧，比如安全性那些 (The frontend may need a redo, but we can keep the rest, such as the security work, right?)

**Response summary**

Confirmed that middleware (CSRF, login throttle, security headers, HTTPS enforcer), Authentication, RecaptchaVerifier, password-reset flow, and PHPUnit tests can stay. Templates, CSS, and public IA can be replaced without discarding that stack.

**Validation performed**

Read src/Application.php, UsersController, and the middleware tests before agreeing.

### AI-005 — Architecture design — staff console, customer area, Stripe, bookings

| Field | Value |
| --- | --- |
| ID | AI-005 |
| Date | 14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) with Grok sub-agents |
| Project phase | Design |
| Purpose | Architecture design — staff console, customer area, Stripe, bookings |
| Outcome | Modified and adopted — Standard role narrowed; Stripe remains sandbox-only. |

**Prompt (as submitted)**

Student pasted the team’s website/staff requirements, then: 先按照你的想法做完。数据库确定没有问题后，请你使用子代理 grok 4.6 extra high fast 完整做完整个控制台还有其他内容，注意风格需要美观。……我去睡觉了，所以你不要老是问问题。我需要你做一个很完整的版本，就像我之前发送过的条件那样。具体支付请使用 stripe 接口。 (Finish it your way. After the database is sound, delegate the console and the rest to a Grok sub-agent. Make it complete. Use Stripe for payment. Do not keep asking questions.)

**Response summary**

Designed Admin prefix + RBAC, orders/inventory/customers/messages, /account for customers, persistent carts, outbound mail consumer, Stripe Payment Element + webhook, and service booking. Implementation was delegated, then reviewed.

**Validation performed**

Checked routes, permission seed against the required Standard-role set (refunds.process, invoices.issue, orders.dispatch, payments.record, orders.view, customers.view), and that Stripe keys are not committed. Visual pass on admin CSS.

### AI-026 — Architecture design — login TDD planning notes

| Field | Value |
| --- | --- |
| ID | AI-026 |
| Date | 30/07/2026 |
| Tool | Cursor |
| Project phase | Design |
| Purpose | Architecture design — login TDD planning notes |
| Outcome | Accepted as internal notes only. |

**Prompt (as submitted)**

Internal planning for a login TDD demonstration (docs/superpowers specs and plan dated 30/07/2026).

**Response summary**

Wrote docs/superpowers/specs/2026-07-30-login-tdd-demo-design.md and the matching plan. Later superseded by the production auth/reset work.

**Validation performed**

Files remain in the repo as planning notes, not as the shipped design.
