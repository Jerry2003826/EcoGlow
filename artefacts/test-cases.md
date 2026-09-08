# Test cases — Eco Glow dest

IDs map to Project Finkel minutes and Trello Iteration 1.

| ID | Story | Steps | Expected |
|---|---|---|---|
| TC-G1 | Guest entity | Checkout without login | Row in `Guest`; `Orders.GuestId` set; no `User` created |
| TC-5A | US5 browse | `/products`, search Ashby, category Wall Lights | Matching cards only |
| TC-5B | US5 add + continue | Add to cart, stay on shop, click View Cart | Flash shown; cart has the line |
| TC-5C | US5 refresh | Change qty, refresh | Qty and total persist |
| TC-5D | US5 dummy pay | Pay 4111 then 4242 | 4111 error; 4242 → receipt |
| TC-5E | US5 receipt | Refresh `/orders/receipt/{id}` | Same guest and totals |
| TC-6A | US6 dashboard | Place order, open admin dashboard | Orders Today +1; Revenue includes payment |
| TC-6B | US6 OrderId | Place two guest orders | Distinct integer OrderId values |
| TC-7A | US7 install | Tick install on cart | +$80 per item on each line and on the order total |
| TC-8A | US8 staff | Login staff403 | Only Orders Today and Pending Shipment cards |
| TC-8B | US8 admin | Login admin | Extra Low Stock and Revenue / Recent Payments |
| TC-51 | US-5.1 | Orders filter Installation and Repair | Install orders listed; repair form writes `Service_Request` |
| TC-52 | US-5.2 | Order view update delivery date/status | `Delivery` row updates; delivered sets order status |
| TC-61 | US-6.1 | Seed stock 2 and 1 vs threshold 5 | Inventory highlights those rows; top badge > 0 |
| TC-62 | US-6.2 | Open `/admin/suppliers` | Brightline and Harbor with phone/email |
| TC-71 | US-7.1 | Create quotation | `Customer` + `Quotation` rows; view shows total |
| TC-72 | US-7.2 | Create quotation | `EmailedAt` set if mail() works; else flash + row still there |
| TC-73 | US-7.3 | Generate invoice from paid order | Invoice total = payment; GST = 10/110 of total |
| TC-81 | US-8.1 | Guest checkout | Name, email, address, phone on order |
| TC-82 | US-8.2 | Staff opens `/admin` vs Access | Staff dashboard reduced; Access still Coming Soon |
| TC-43 | US-4.3 | Submit `/enquiries/add`, open Messages | Enquiry appears and view shows body |
| TC-SEC1 | Auth | Logged-out `/admin/*` | 302 `/login` |
| TC-SEC2 | Stock | Complete checkout of qty 1 | `Inventory.Quantity` decreases by 1 |
