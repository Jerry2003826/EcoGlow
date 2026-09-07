# UAT report — Eco Glow dest

| Field | Value |
|---|---|
| Product | Eco Glow Lighting |
| Environment | dest `https://dev.u26s2236.iedev.org/` |
| Build | GitLab `dev` after I1 live-module wiring |
| Date | 2026-09-07 |
| Reviewers | Team 236 (Dev, Muthu, Ethan, Natasha, Jiarui) |
| Source of truth | Trello UGIE 26S2 Team 236 + Project Finkel minutes 07/09/26 |

## Result summary

| Area | Result | Notes |
|---|---|---|
| Guest entity | Pass | Unauthenticated checkout writes `Guest`, not `User` |
| US5 browse / cart / receipt | Pass | Session `CartId`; receipt survives refresh |
| US5 dummy Stripe | Pass | Only `4242424242424242` accepted |
| US6 dashboard SQL + OrderId | Pass | `Orders.OrderId` auto increment; widgets query live tables |
| US7 install $200 | Pass | Cart checkbox, fee stored on `Shopping_Cart` |
| US8 staff vs admin dashboard | Pass | Staff: 2 cards. Admin: + low stock + finance |
| US-5.1 / 5.2 orders + delivery | Pass | Orders list types; delivery date editable; repair bookings saved |
| US-6.1 / 6.2 stock + suppliers | Pass | Inventory page + badge; supplier directory |
| US-7.1 / 7.2 / 7.3 quote + invoice | Conditional | Quote and invoice persist. Dest PHP `mail()` may not deliver |
| US-8.1 guest details | Pass | Name, email, address, phone stored |
| US-8.2 role restriction | Pass | Staff cannot see finance widgets or Access/Reports/Refunds |
| US-4.3 messages | Pass | Enquiry form → `/admin/messages` |
| Artefacts | Pass | This folder: runsheet, UAT, test cases |

## Defects still accepted

1. Payment is a test-card gate, not Stripe.js. Required by the 07/09 minutes.
2. Quote email depends on dest MTA. Record is still created if send fails.
3. Refunds / Reports / Access remain Coming Soon (not in this week’s stories).
4. Product images on shop use category photos; seed filenames are not all in `webroot/img`.

## Sign-off

| Name | Role | Sign |
|---|---|---|
| Natasha Katsikaronis | Minutes / PO side | |
| Muthu Wijesekera | Team | |
| Ethan Yeh | Team | |
| Jiarui Li | Team | |
| Dev Singh | Team | |
