# UIUX AI Registry

**Deliverable category (PGP):** UI/UX / Wireframes  
**Member:** Jiarui Li  
**Team:** Team 236 — FIT3047 / FIT3048 Industry Experience  
**Compiled:** 14/08/2026

This folder is the GenAI footprint for this deliverable category, as required by
*FIT3047/FIT3048 — Instructions in Using GenAI Tools (AI Registry)*.
Copy the folder into the matching PGP deliverable directory and keep the folder name.

## Entries

### AI-009 — UI/UX design — rebuild the storefront from the Vision Board

| Field | Value |
| --- | --- |
| ID | AI-009 |
| Date | 13/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Design |
| Purpose | UI/UX design — rebuild the storefront from the Vision Board |
| Outcome | Modified and adopted — first pass was not accepted as-is. |

**Prompt (as submitted)**

目前只考虑前端。/Users/lijiarui/Downloads/Eco Glow Lighting Vision Board.pdf，美术给了我设计稿，我感觉咱们可以重新做了。 (Frontend only. The art team gave this Vision Board. I think we should redo it.)

**Response summary**

Replaced the earlier night-glow theme with a warm earthy system: self-hosted Playfair Display + Inter, e-commerce IA (Shop / Categories / About / Contact), and new shop, product, cart, and register templates. Security pages kept their behaviour.

**Validation performed**

Compared type and palette to the Vision Board. Checked contrast notes in site.css. Student then rejected several AI-looking layouts in later prompts (see AI-010).

### AI-010 — UI/UX design — student-directed visual corrections

| Field | Value |
| --- | --- |
| ID | AI-010 |
| Date | 13/08/2026–14/08/2026 |
| Tool | Cursor (Grok 4.6 / Composer) |
| Project phase | Design |
| Purpose | UI/UX design — student-directed visual corrections |
| Outcome | Modified and adopted — human direction overrode the first AI layout. |

**Prompt (as submitted)**

A sequence of pointed visual prompts, not a single brief: 这里有点丑 (filters look ugly); 这些地方鼠标放上去是两条线 (hover shows two underlines); 这里的 ai 感很重，参考 PDF (too AI; follow the PDF); 不要有边缘 (no edges on the hero); 这些地方有奇怪的重叠 / 这里不如原来的布局 (overlaps; best-sellers worse than before); 这里的 ai 味道有点太大 (installation/about still feel generated); 比例太大一个屏幕塞不下 / 这里最好也小一点 (hero and product image too large); 两边拉开，购买抬上去 / 你搞错了是左右靠拢 / 右边不要空 / 这两个紧凑一些 (product-detail column and option spacing).

**Response summary**

Collapsible filters, hover underline fix, full-bleed hero, equal category and best-seller grids, material swatches, before/after photos, and a two-column product page.

**Validation performed**

Each change was accepted or rejected by the student against the live page. Layout was measured in the browser, not only inferred from CSS.

### AI-011 — UI/UX design — catalogue and material photographs

| Field | Value |
| --- | --- |
| ID | AI-011 |
| Date | 13/08/2026 |
| Tool | Cursor image generation |
| Project phase | Design |
| Purpose | UI/UX design — catalogue and material photographs |
| Outcome | Modified and adopted as labelled placeholders only. |

**Prompt (as submitted)**

Generate product, material-macro, and before/after lighting photographs that match the Vision Board (warm earthy, oak / linen / opal / brass / powder). Use them on home, shop, product, and cart. Student later: 算了，先用替代字符吧先 — then reversed to using generated images as stand-ins.

**Response summary**

Wrote WebP assets under webroot/img/ (products, materials, before/after, marlow-detail-wide). These are generated images, not studio photography.

**Validation performed**

Student reviewed on the page and still flagged “AI feel” on some sections; those sections were rewritten around materials and real before/after framing. Images are not claimed as client photography in copy.

### AI-027 — UI/UX — refresh account/auth and checkout visuals without breaking Stripe or tokens

| Field | Value |
| --- | --- |
| ID | AI-027 |
| Date | 14/08/2026 |
| Tool | Cursor IDE agent (Grok 4.6) |
| Project phase | UI / UX |
| Purpose | UI/UX — refresh account/auth and checkout visuals without breaking Stripe or tokens |
| Outcome | Modified and adopted |

**Prompt (as submitted)**

Restyle the customer account navigation and checkout surfaces using existing site.css tokens. Keep Payment Element z-index rules so Pay stays clickable.

**Response summary**

account.css: pill tabs, card lift, form focus. checkout.css: section titles, alerts, .checkout-pay-actions { z-index: 2 }. Functional selectors preserved (commit 2d4f5ef).

**Validation performed**

Visual check on /account and /checkout. Pay button remains above the Stripe iframe. No site.css token changes.
