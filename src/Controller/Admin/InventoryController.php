<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;

class InventoryController extends AdminController
{
    public function index()
    {
        $identity = $this->request->getAttribute('identity');

        if ($identity === null) {
            return;
        }

        $userId = (int)$identity->getIdentifier();

        $permissionService = new PermissionService();

        $canView = $permissionService->hasPermission($userId, 'inventory.view');
        $canAdjust = $permissionService->hasPermission($userId, 'inventory.adjust');

        if (!$canView && !$canAdjust) {
            throw new ForbiddenException();
        }

        $connection = $this->fetchTable('SalesOrders')->getConnection();

        $items = $connection->execute(
            '
            SELECT
                product_variant_id,
                inventory_location_id,
                product_name,
                variant_name,
                sku,
                location_name,
                quantity_on_hand,
                quantity_reserved,
                quantity_available,
                reorder_point,
                reorder_quantity
            FROM v_low_stock_items
            ORDER BY quantity_available ASC
            '
        )->fetchAll('assoc');

        $this->set(compact('items'));
    }
}
