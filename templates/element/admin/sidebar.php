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
                '/admin/inventory',
                [
                    'class' => $active($currentController === 'Inventory'),
                    'escape' => false,
                ]
            );
            ?>

            <?= $this->Html->link(
                'Suppliers',
                '/admin/suppliers',
                ['class' => $active($currentController === 'Suppliers')]
            ) ?>

            <?= $this->Html->link(
                'Customers',
                '/admin/customers',
                ['class' => $active($currentController === 'Customers')]
            ) ?>

            <?= $this->Html->link(
                'Products',
                '/admin/products',
                ['class' => $active($currentController === 'Products')]
            ) ?>

            <?= $this->Html->link(
                'Shipments',
                '/admin/shipments',
                ['class' => $active($currentController === 'Shipments')]
            ) ?>
        </div>

        <div class="sidebar-group">
            <div class="sidebar-group-title">Sales</div>

            <?= $this->Html->link(
                'Quotations',
                '/admin/quotations',
                ['class' => $active($currentController === 'Quotations')]
            ) ?>

            <?= $this->Html->link(
                'Invoices',
                '/admin/invoices',
                ['class' => $active($currentController === 'Invoices')]
            ) ?>

            <?= $this->Html->link(
                'Payments',
                '/admin/payments',
                ['class' => $active($currentController === 'Payments')]
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
                '/admin/messages',
                ['class' => $active($currentController === 'Messages')]
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
