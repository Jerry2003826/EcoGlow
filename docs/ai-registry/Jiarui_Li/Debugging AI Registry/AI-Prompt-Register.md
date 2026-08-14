# Debugging AI Registry

**Deliverable category (PGP):** Development / Source Code  
**Member:** Jiarui Li  
**Team:** Team 236 — FIT3047 / FIT3048 Industry Experience  
**Compiled:** 14/08/2026

This folder is the GenAI footprint for this deliverable category, as required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.
Copy the folder into the matching PGP deliverable directory and keep the folder name.

## Entries

### AI-019 — Debugging — reCAPTCHA test-key warning

| Field | Value |
| --- | --- |
| ID | AI-019 |
| Date | 06/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Development |
| Purpose | Debugging — reCAPTCHA test-key warning |
| Outcome | Accepted |

**Prompt (as submitted)**

这个怎么处理 (How should we handle this?) — screenshot of the Google reCAPTCHA test-key warning.

**Response summary**

Explained test keys vs site keys. Student later supplied localhost keys (not repeated here). RecaptchaVerifier was already fail-closed in production if Google’s published test secret is used.

**Validation performed**

RecaptchaVerifierTest. Keys stored in config/app_local.php, which is gitignored.

### AI-020 — Debugging — keep the staff sidebar still across admin navigation

| Field | Value |
| --- | --- |
| ID | AI-020 |
| Date | 14/08/2026 |
| Tool | Cursor IDE agent (Grok 4.6) |
| Project phase | UI / UX |
| Purpose | Debugging — keep the staff sidebar still across admin navigation |
| Outcome | Modified and adopted. Rejected: view-transitions, 1-second scroll locks, and hide-until-positioned. |

**Prompt (as submitted)**

左边在右边下拉的时候不应该上去。左侧栏刷新有问题，点一下就会自动刷到最上面。会闪烁一下。点击最下面的两个选项依旧会跳动。 (The left sidebar still flickers. It must stay put while the right side scrolls. Clicking the bottom two items — Users & roles and Feature flags — jumps the rail up toward Orders. Fix it properly.)

**Response summary**

Scroll-restore, visibility hiding, and view-transitions were tried and rejected: a full page reload always paints the nav at scrollTop 0 first. Final design: intercept /admin GET links, fetch HTML, replace only .admin-stage, sync current-item classes, leave the sidebar DOM in place. Brand and storefront link stay outside the scroller.

**Validation performed**

Reproduced in a desktop-width browser. After clicking Users and Feature flags, the same sidebar node remained (sameSidebar = true) and scrollTop stayed at 43.5. URL, title, and heading updated. Failed earlier CSS/JS restore approaches were discarded (see § What was rejected).

### AI-021 — Debugging — card-only PaymentIntent, Pay button recovery, resume unpaid checkout

| Field | Value |
| --- | --- |
| ID | AI-021 |
| Date | 14/08/2026 |
| Tool | Cursor IDE agent (Grok 4.6) |
| Project phase | Development |
| Purpose | Debugging — card-only PaymentIntent, Pay button recovery, resume unpaid checkout |
| Outcome | Modified and adopted. Wallet methods were deliberately rejected for this demo path. |

**Prompt (as submitted)**

无法点击这个按钮。 (The Pay button stayed disabled after confirmPayment hung on a Link / Klarna / Zip overlay. Restrict PaymentIntents to cards, turn off Link wallets, re-enable the button on error, keep the button above the iframe, and resume the latest unpaid web checkout on GET /checkout.)

**Response summary**

StripePaymentGateway now creates card-only PaymentIntents. Checkout JS wraps confirmPayment, re-enables Pay on failure, and pre-fills billing name/email. CheckoutService.resumePending reloads the latest draft web order and retrieveClientSecret. FakePaymentGateway and CheckoutControllerTest were updated.

**Validation performed**

Ran testPayButtonStaysEnabledAndGetResumesPendingIntent. Confirmed the Pay control is not rendered disabled. GET /checkout after POST showed the held pending payment and the same client secret. Manual checkout on localhost:8765. No live card data was used.
