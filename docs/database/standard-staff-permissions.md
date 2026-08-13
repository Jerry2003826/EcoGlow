# Standard staff permissions (batch 2)

## Decision

`standard_staff` is restricted to **six** permission keys:

| Key | Why it is in the set |
| --- | --- |
| `refunds.process` | Requirement: processing refunds |
| `invoices.issue` | Requirement: sending invoices |
| `orders.dispatch` | Requirement: dispatching orders |
| `payments.record` | Requirement: recording sales / payments |
| `orders.view` | Dispatching and invoicing cannot be done without seeing the order |
| `customers.view` | Dispatching and invoicing cannot be done without seeing the customer |

Removed from the original eleven-key seed: `messages.manage`, `services.manage`, `reports.view`, `inventory.view`, `orders.create`, `orders.manage`.

The four removed extras (`messages.manage`, `services.manage`, `reports.view`, `inventory.view`) were never in the client wording. `orders.create` / `orders.manage` are Elevated/Master work: Standard records a sale through `payments.record` and `orders.dispatch`, not by opening the full order editor.

## How to change this later

The map in `AdminPermissionMap` only names keys that already exist in `permissions`. Role membership lives in `role_permissions`. If the PO decides Standard also needs, for example, `inventory.view` or `messages.manage`, **grant the row in `role_permissions` (or tick it in /admin/users)**. No PHP change is required unless a new *action* is added.

`orders.view` was added in this batch because the seeded catalogue had create/manage/dispatch but no read-only order key.

## Applied by

- Migration `20260814120000_TightenStandardStaffPermissions`
- Idempotent statements in `database/mysql/009_core_seed.sql` (so re-seeding cannot widen Standard again)
