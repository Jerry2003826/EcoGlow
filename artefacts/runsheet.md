# Eco Glow dest runsheet

Environment: https://dev.u26s2236.iedev.org/  
Deadline this sheet covers: Week 6 Thursday (Project Finkel 08/09/26)

## Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@ecoglow.local | admin1234 |
| Staff | staff@ecoglow.local | staff1234 |

Test card: `4242424242424242` (any future expiry, any CVC). Other numbers must fail.

## Guest shop (meeting US5 + US7)

1. Open `/` logged out. Confirm olive navbar and landing carousel.
2. Open Shop `/products`. Search `Ashby`. Filter Wall Lights. Add to cart. Confirm flash and stay on shop.
3. Open Cart. Tick installation ($80 per item). Confirm each line shows $80 x qty and the running total updates without an Update Cart button.
4. Refresh the cart. Cart must still hold the items.
5. Checkout as guest: name, email, address, phone. Pay with `4111...` first — must reject. Pay with `4242...`.
6. Receipt shows guest details, lines, install fee, OrderId. Refresh the receipt — it stays.

## Admin live SQL (meeting US6 + US8)

7. Login admin. Dashboard: Orders Today, Pending Shipment, Low Stock, Revenue, Recent Payments. Numbers must match new guest order.
8. Open the new OrderId. Confirm Guest, lines, InstallFee, Payment, Receipt, Delivery date.
9. Logout. Login staff. Dashboard has only Orders Today and Pending Shipment.

## Trello Iteration 1 pages

10. Admin Inventory: Halden and Rowan are low stock. Sidebar badge matches.
11. Suppliers: Brightline Wholesale and Harbor Electrical with phone/email.
12. Customers: guest from the order appears. Quotations later create Customer rows.
13. Messages: public `/enquiries/add` row appears in `/admin/messages`.
14. Shipments: delivery address and date for the order. On the order, change date/status and save.
15. Orders filter Installation / Repair. Add a repair booking with a datetime.
16. Quotations: create one, confirm Customer + Quotation rows. Email may fail on dest mail(); record must still save.
17. Invoices: generate from the paid order. View shows GST inclusive split.
18. Payments and Products lists read the same SQL tables.
19. Unauthenticated `/admin` redirects to `/login`.

## Out of scope this week

Refunds, Reports, Access stay Coming Soon. Payment is dummy Stripe by meeting decision.
