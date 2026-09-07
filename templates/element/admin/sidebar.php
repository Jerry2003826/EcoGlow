<?php
// templates/element/admin/sidebar.php

$currentController = (string)$this->request->getParam('controller');
$pass = (array)$this->request->getParam('pass');

$comingSoonModule = $currentController === 'ComingSoon'
    ? (string)($pass[0] ?? '')
    : '';

$active = static function (bool $isActive): string {
    return 'sidebar-link' . ($isActive ? ' active' : '');
};
?>
<aside id="admin-sidebar" class="admin-sidebar">
    <div class="admin-brand">
        Eco Glow Lighting
    </div>

    <div class="admin-sidebar-scroll">
        <div class="sidebar-group">
            <div class="sidebar-group-title">Main</div>

            <?= $this->Html->link(
                'Dashboard',
                '/admin',
                ['class' => $active($currentController === 'Dashboard')]
            ) ?>

            <?= $this->Html->link(
                'Orders',
                '/admin/orders',
                ['class' => $active($currentController === 'Orders')]
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Operations</div>

            <?php
            $inventoryLabel = 'Inventory';

            if (($lowStockCount ?? 0) > 0) {
                $inventoryLabel .= ' <span class="sidebar-badge">' . (int)$lowStockCount . '</span>';
            }

            echo $this->Html->link(
                $inventoryLabel,
                '/admin/coming-soon/inventory',
                [
                    'class' => $active($comingSoonModule === 'inventory'),
                    'escape' => false,
                ]
            );
            ?>

            <?= $this->Html->link(
                'Suppliers',
                '/admin/coming-soon/suppliers',
                ['class' => $active($comingSoonModule === 'suppliers')]
            ) ?>

            <?= $this->Html->link(
                'Customers',
                '/admin/coming-soon/customers',
                ['class' => $active($comingSoonModule === 'customers')]
            ) ?>

            <?= $this->Html->link(
                'Products',
                '/admin/coming-soon/products',
                ['class' => $active($comingSoonModule === 'products')]
            ) ?>

            <?= $this->Html->link(
                'Shipments',
                '/admin/coming-soon/shipments',
                ['class' => $active($comingSoonModule === 'shipments')]
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Sales</div>

            <?= $this->Html->link(
                'Quotations',
                '/admin/coming-soon/quotations',
                ['class' => $active($comingSoonModule === 'quotations')]
            ) ?>

            <?= $this->Html->link(
                'Invoices',
                '/admin/coming-soon/invoices',
                ['class' => $active($comingSoonModule === 'invoices')]
            ) ?>

            <?= $this->Html->link(
                'Payments',
                '/admin/coming-soon/payments',
                ['class' => $active($comingSoonModule === 'payments')]
            ) ?>

            <?= $this->Html->link(
                'Refunds',
                '/admin/coming-soon/refunds',
                ['class' => $active($comingSoonModule === 'refunds')]
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Communication</div>

            <?= $this->Html->link(
                'Messages',
                '/admin/coming-soon/messages',
                ['class' => $active($comingSoonModule === 'messages')]
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Reporting</div>

            <?= $this->Html->link(
                'Reports',
                '/admin/coming-soon/reports',
                ['class' => $active($comingSoonModule === 'reports')]
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Administration</div>

            <?= $this->Html->link(
                'Access',
                '/admin/coming-soon/access',
                ['class' => $active($comingSoonModule === 'access')]
            ) ?>
        </div>
    </div>

    <div class="sidebar-footer">
        <?= $this->Html->link(
            'View storefront',
            '/',
            ['class' => 'sidebar-storefront']
        ) ?>
    </div>
</aside>
