# Coding AI Registry

**Deliverable category (PGP):** Development / Source Code  
**Member:** Jiarui Li  
**Team:** Team 236 — FIT3047 / FIT3048 Industry Experience  
**Compiled:** 14/08/2026

This folder is the GenAI footprint for this deliverable category, as required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.
Copy the folder into the matching PGP deliverable directory and keep the folder name.

## Entries

### AI-012 — Coding assistance — start the server and fix defects

| Field | Value |
| --- | --- |
| ID | AI-012 |
| Date | 06/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Development |
| Purpose | Coding assistance — start the server and fix defects |
| Outcome | Accepted |

**Prompt (as submitted)**

启动服务器，检查 bug。然后：完全修复。 (Start the server, check for bugs. Then: fix them completely.)

**Response summary**

Fixed contact maxLength validation (SQLSTATE data-too-long), invalid admin IDs, logout redirect loop, non-idempotent UsersSeed, layout unread-count query, and related tests.

**Validation performed**

PHPUnit and PHPCS. Reproduced the overlong-email 500 before/after the Table validation change.

### AI-013 — Coding assistance — password reset and onboarding acceptance gaps

| Field | Value |
| --- | --- |
| ID | AI-013 |
| Date | 10/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Development |
| Purpose | Coding assistance — password reset and onboarding acceptance gaps |
| Outcome | Accepted |

**Prompt (as submitted)**

切换到计划模式列举个完整的修复计划 — then Build. (Switch to plan mode and list a complete repair plan. Then implement it.)

**Response summary**

Implemented forgot/reset password (hashed token, anti-enumeration), env-driven mail, HTTPS enforcer in non-debug, contrast/label fixes, and docs/test-cases.md.

**Validation performed**

UsersControllerTest for reset, invalid/expired token, and enumeration. Manual login-page link check.

### AI-014 — Coding assistance — staff console (RBAC, orders, inventory)

| Field | Value |
| --- | --- |
| ID | AI-014 |
| Date | 14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) with Grok sub-agents |
| Project phase | Development |
| Purpose | Coding assistance — staff console (RBAC, orders, inventory) |
| Outcome | Modified and adopted |

**Prompt (as submitted)**

Same overnight brief as AI-005, focused on “完整的控制台功能” (a complete staff console).

**Response summary**

Added admin layout, dashboard, orders, inventory adjustments, customers, messages, invoices/reports placeholders where needed, and permission maps.

**Validation performed**

Logged in with the seeded staff account. Checked that Standard role is not a superuser. Later visual fixes in admin.css (pagination, code colour, checkboxes).

### AI-015 — Coding assistance — storefront data, customer accounts, cart, mail queue

| Field | Value |
| --- | --- |
| ID | AI-015 |
| Date | 14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) with Grok sub-agents |
| Project phase | Development |
| Purpose | Coding assistance — storefront data, customer accounts, cart, mail queue |
| Outcome | Accepted. Account/checkout visual refresh is recorded separately as AI-027. |

**Prompt (as submitted)**

Implied by the same complete-product brief: connect the redesigned pages to the database and customer accounts.

**Response summary**

Replaced hardcoded catalogue arrays with CatalogService queries, added /account register/login, persistent carts with merge-on-login, save-for-later, and SendOutboundMessagesCommand.

**Validation performed**

Confirmed shop/product/cart read seeded products. Registration creates a users row linked to customers without privilege escalation. Mail consumer is idempotent in tests.

### AI-016 — Coding assistance — Stripe test-mode checkout, CLI webhook, feature flag

| Field | Value |
| --- | --- |
| ID | AI-016 |
| Date | 14/08/2026 |
| Tool | Cursor IDE agent (Grok 4.6) |
| Project phase | Development |
| Purpose | Coding assistance — Stripe test-mode checkout, CLI webhook, feature flag |
| Outcome | Modified and adopted. Local-only secrets; feature flag left on for demonstration. |

**Prompt (as submitted)**

具体支付请使用 stripe 接口。我要导入沙盒 stripe，我需要什么。帮我操作完成直到可以使用。 (Use Stripe for payment. Set up Stripe sandbox for Eco Glow checkout: check the feature flag, install or use Stripe CLI, forward webhooks to the local CakePHP server, store the webhook secret in local config only, and confirm a test customer can pay.)

**Response summary**

Identified commerce.online_payments, wrote test publishable/secret keys into gitignored config/app_local.php, started stripe listen to /webhooks/stripe, stored the whsec locally, and used the demo customer account. CheckoutController, Payment Element, webhook endpoint, and service booking were already in the overnight build. Secrets were not printed in chat and are not repeated here.

**Validation performed**

Opened /checkout as customer@ecoglow.local. Completed a test-card payment. Order ORD-2026-001001 moved to paid. A later order ORD-2026-001002 stayed pending until the Pay-button work in AI-021. Keys never committed.

### AI-017 — UI/UX — Admin Users page: foldable, searchable sections

| Field | Value |
| --- | --- |
| ID | AI-017 |
| Date | 14/08/2026 |
| Tool | Cursor IDE agent (Grok 4.6) |
| Project phase | UI / UX |
| Purpose | UI/UX — Admin Users page: foldable, searchable sections |
| Outcome | Modified and adopted |

**Prompt (as submitted)**

我希望 staff account 这里可以折叠，可以搜索。这一页其他的也都是。 (On Admin Users, make Staff accounts, Permission matrix, and Per-user overrides collapsible and searchable.)

**Response summary**

Wrapped each section in data-admin-fold with a toggle, search field, and empty state. admin.js filters rows from data-search. UsersControllerTest asserts the fold hooks and search labels.

**Validation performed**

Opened /admin/users as master. Toggles and search fields render. PHPUnit UsersControllerTest::testIndexOkForMaster passed the new assertions.
