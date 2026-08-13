<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use Authentication\Identity;

/**
 * Shared login helper and fixture list for staff-console tests.
 */
trait AdminAuthTrait
{
    /**
     * Fixtures required to exercise RBAC plus the order/inventory path.
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Users',
        'app.Roles',
        'app.Permissions',
        'app.RolePermissions',
        'app.UserRoles',
        'app.UserPermissionOverrides',
        'app.ContactMessages',
        'app.Customers',
        'app.Products',
        'app.ProductVariants',
        'app.InventoryLocations',
        'app.InventoryBalances',
        'app.ReorderRules',
        'app.SalesOrders',
        'app.SalesOrderItems',
        'app.OrderStatusHistory',
        'app.OrderNotes',
        'app.StockReservations',
        'app.InventoryMovements',
    ];

    /**
     * Log in a fixture user via the session.
     *
     * @param int $userId UsersFixture id.
     * @return void
     */
    protected function loginAs(int $userId): void
    {
        $this->session([
            'Auth' => new Identity(
                $this->fetchTable('Users')->get($userId),
            ),
        ]);
    }
}
