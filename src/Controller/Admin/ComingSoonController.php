<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Exception\NotFoundException;

/**
 * Placeholder for modules that land in later batches. Every sidebar entry that
 * is not built yet points here so there are no dead links.
 */
class ComingSoonController extends AdminController
{
    /**
     * Copy for each upcoming module — what it will do, not just "coming soon".
     *
     * @var array<string, array{title: string, summary: string, points: array<int, string>}>
     */
    private const MODULES = [
        'appointments' => [
            'title' => 'Appointments',
            'summary' => 'Schedule licensed installation and repairs against technician availability.',
            'points' => [
                'Book site surveys, installs and call-outs without double-booking a technician.',
                'Hold a tentative slot until the customer confirms, then lock the diary.',
                'Carry parts, notes and the related sales order onto the job card.',
            ],
        ],
        'products' => [
            'title' => 'Products',
            'summary' => 'Maintain the catalogue the storefront already reads: categories, variants and media.',
            'points' => [
                'Edit names, SKUs, GST-inclusive prices and installation flags without touching the database.',
                'Keep listing squares and 3:2 detail photographs on separate image roles.',
                'Retire a product without deleting historical order lines that snapshotted it.',
            ],
        ],
        'customers' => [
            'title' => 'Customers',
            'summary' => 'A single record for phone, email and walk-in buyers, including the 360° history.',
            'points' => [
                'Search by name, email or phone and see open orders, invoices and messages together.',
                'Store trade accounts and consent flags separately from the public registration form.',
                'Date of birth stays behind the customers.sensitive.view permission.',
            ],
        ],
        'invoices' => [
            'title' => 'Invoices',
            'summary' => 'Issue GST invoices from a recorded sale and send them to the customer.',
            'points' => [
                'Raise an invoice from an order without retyping line items or tax.',
                'Record deposits and partial payments against the balance due.',
                'Voiding an invoice will require the invoices.void permission.',
            ],
        ],
        'quotations' => [
            'title' => 'Quotations',
            'summary' => 'Versioned quotes for site visits, trade pricing and follow-up.',
            'points' => [
                'Freeze a quote as a version so later price list changes cannot rewrite what was sent.',
                'Convert an accepted quote into a sales order with the same line snapshots.',
                'Approval of high-value quotes will use quotations.approve.',
            ],
        ],
        'reports' => [
            'title' => 'Reports',
            'summary' => 'Operating and financial views already prepared in the database.',
            'points' => [
                'Daily order counts and GST from v_business_dashboard_daily.',
                'Gross profit estimates from v_order_profitability, using cost snapshots.',
                'Outstanding invoice balances from v_invoice_balances.',
            ],
        ],
        'users' => [
            'title' => 'Users & roles',
            'summary' => 'Let the Product Owner grant and revoke access without a code change.',
            'points' => [
                'Assign master, elevated staff or standard staff from the roles table.',
                'Add per-user allow or deny overrides; deny always wins.',
                'This screen will require the access.manage permission.',
            ],
        ],
        'feature-flags' => [
            'title' => 'Feature flags',
            'summary' => 'Switch optional modules without a deploy.',
            'points' => [
                'Thirteen flags are already seeded, including manual-channel orders.',
                'Staff will toggle a flag and see the matching sidebar entry appear.',
                'AI and loyalty stay off until the later batches wire them up.',
            ],
        ],
    ];

    /**
     * @param string $module Sidebar module key.
     * @return void
     */
    public function index(string $module = ''): void
    {
        if (!isset(self::MODULES[$module])) {
            throw new NotFoundException();
        }

        $this->set('comingSoon', self::MODULES[$module]);
        $this->set('moduleKey', $module);
    }
}
