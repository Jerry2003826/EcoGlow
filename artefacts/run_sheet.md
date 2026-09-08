# Eco Glow Lighting - Completed Run Sheet

## Document control

| Field | Value |
|---|---|
| Project | Eco Glow Lighting |
| Team | Team 236 |
| Environment | `https://dev.u26s2236.iedev.org/` |
| Iteration | Project Finkel Iteration 1 / Week 6 |
| Run date | 2026-09-07 |
| Source artefacts | `artefacts/test-cases.md`, `artefacts/uat-report.md`, live destination review |
| Overall result | Complete with accepted conditions |

## Run instructions

1. Open the environment in a private browser window.
2. Begin as an unauthenticated guest unless the step explicitly requires an administrator or staff account.
3. Record the generated `OrderId` from the successful checkout receipt; use it for the related admin checks.
4. Use the approved test payment flow. The application accepts the designated successful test card and rejects other card numbers.
5. Record any failure, unexpected result, or environment issue in the notes column and attach a screenshot where available.

## Completed functional run

| ID | Area | Steps | Expected result | Result | Notes |
|---|---|---|---|---|---|
| RS-01 | Landing page | Open `/` while logged out. Inspect the navbar, carousel, navigation links, and responsive menu. | Landing page loads, carousel and local imagery display, navigation remains usable at narrow widths. | PASS | Responsive navigation and carousel reviewed. |
| RS-02 | Product search | Open `/products`. Search for `Ashby`. | Matching product cards are displayed. | PASS | Search uses product name and description. |
| RS-03 | Product category filter | Select `Wall Lights` on `/products`. | Only products in the selected category are displayed. | PASS | Category filtering verified. |
| RS-04 | Product price filter | Apply each available price range. | Products outside the selected price range are excluded. | PASS | Price ranges are validated server-side. |
| RS-05 | Product card and cart | Add an in-stock product to the cart from the shop. | Card shows image, name, category, price, stock state, and action. Item is added and the user remains on the shop page. | PASS | Success flash is shown. |
| RS-06 | Cart persistence | Open the cart, change quantity, and refresh the page. | Cart quantity and totals persist in the session. | PASS | Session `CartId` retained. |
| RS-07 | Installation option | Enable installation in the cart. | Installation fee is calculated per item and included in line and order totals. | PASS | Installation service request is created during checkout. |
| RS-08 | Guest checkout validation | Proceed to checkout without logging in. Submit guest name, email, address, and phone. | Guest details validate and are stored as a guest record; no user account is created. | PASS | `Guest` record linked to the order. |
| RS-09 | Payment rejection | Submit an invalid test card number. | Payment is rejected and checkout remains available. | PASS | Dummy payment gate behaves as specified. |
| RS-10 | Payment success | Submit the approved successful test card. | Order, order items, payment, receipt, and delivery records are created. | PASS | Redirects to the receipt page. |
| RS-11 | Receipt persistence | Refresh `/orders/receipt/{id}`. | Receipt remains available with guest details, line items, installation fee, total, and `OrderId`. | PASS | Receipt survives refresh. |
| RS-12 | Inventory deduction | Complete checkout for an in-stock quantity. | Inventory decreases by the purchased quantity and does not become negative. | PASS | Inventory update verified. |
| RS-13 | Admin dashboard | Log in as admin and open `/admin`. | Dashboard displays Orders Today, Pending Shipment, Low Stock, Revenue, and Recent Payments. | PASS | Values query live SQL tables. |
| RS-14 | Admin order detail | Open the order created in RS-10. | Guest, line items, installation fee, payment, receipt, and delivery date are visible. | PASS | `OrderId` is used for lookup. |
| RS-15 | Staff permissions | Log in as staff and open `/admin`. | Staff sees the reduced dashboard and cannot access restricted finance or access modules. | PASS | Staff dashboard shows the permitted cards only. |
| RS-16 | Inventory management | Open Admin Inventory. | Low-stock products are highlighted and the sidebar badge reflects low-stock count. | PASS | Halden and Rowan are low stock in the reviewed seed data. |
| RS-17 | Supplier management | Open Admin Suppliers. | Brightline Wholesale and Harbor Electrical display with contact details. | PASS | Supplier records verified. |
| RS-18 | Customer management | Open Admin Customers after guest checkout and quotation creation. | Guest and quotation customers appear in the customer list. | PASS | Live records visible. |
| RS-19 | Enquiries and messages | Submit `/enquiries/add`, then open Admin Messages. | Enquiry appears with its message body. | PASS | Public-to-admin message flow verified. |
| RS-20 | Shipments | Open Admin Shipments and update delivery date/status on the order. | Delivery address and date display; changes save; delivered status updates the order appropriately. | PASS | Delivery workflow verified. |
| RS-21 | Orders and repairs | Filter Admin Orders by Installation and Repair. Create a repair booking. | Correct order types filter; repair booking saves with a requested datetime. | PASS | Installation and repair flows verified. |
| RS-22 | Quotations | Create and view a quotation. | Customer and quotation records save, and quotation total is displayed. | PASS WITH CONDITION | Email delivery depends on the destination server mail configuration; record persists if mail fails. |
| RS-23 | Invoices | Generate an invoice from a paid order. | Invoice total matches payment and GST-inclusive values are split correctly. | PASS | GST calculation reviewed. |
| RS-24 | Shared data lists | Open Admin Payments and Admin Products. | Lists read the same live SQL data used by storefront and checkout. | PASS | Shared table data verified. |
| RS-25 | Unauthenticated admin access | Log out and open `/admin` and an admin child route. | Request redirects to `/login`. | PASS | Authentication guard verified. |


