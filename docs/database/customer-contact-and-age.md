# Customer contact details and age

## Contact details

The brief asks for age and contact details to be treated as sensitive and restricted by role.

There is no `customers.view_contact` key in the seeded `permissions` table. Batch 2 therefore uses **`customers.view`** as the gate:

- A staff member who holds `customers.view` sees email and phone in full.
- Anyone else who can still reach a screen that shows contact details (for example an order, via `orders.view`) sees a mask such as `04** *** *89`, plus the note “Contact details are permission-protected.”

If the PO later wants a tighter split (see the customer list but not the phone number), add `customers.view_contact` as a row in `permissions` / `role_permissions`. The PHP gate is a single `has(..., 'customers.view')` check in `AdminController::canViewCustomerContact()`.

## Age

`customer_sensitive_profiles` has `date_of_birth` and `age_at_registration`. The admin UI **does not collect, display or edit age**. Collecting date of birth under the Australian Privacy Act needs a stated purpose; that decision is left with the PO.

`customers.sensitive.view` remains in the catalogue for a later screen if the PO confirms a purpose.
