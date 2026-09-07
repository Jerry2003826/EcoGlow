<?php
// templates/element/admin/sidebar.php

$currentController = (string)$this->request->getParam('controller');

$linkOptions = static function (bool $active): array {
    return [
        'class' => $active ? 'is-active' : '',
    ];
};
?>

<nav class="admin-sidebar">
    <div class="sidebar-group">
        <h3>Main</h3>

        <?= $this->Html->link(
            'Dashboard',
            '/admin',
            $linkOptions($currentController === 'Dashboard')
        ) ?>

        <?= $this->Html->link(
            'Orders',
            '/admin/orders',
            $linkOptions($currentController === 'Orders')
        ) ?>
    </div>

    <div class="sidebar-group">
        <h3>Operations</h3>

        <?= $this->Html->link(
            'Inventory',
            '/admin/coming-soon/inventory',
            $linkOptions($currentController === 'Inventory')
        ) ?>

        <?= $this->Html->link(
            'Suppliers',
            '/admin/coming-soon/suppliers',
            $linkOptions($currentController === 'Suppliers')
        ) ?>

        <?= $this->Html->link(
            'Customers',
            '/admin/coming-soon/customers',
            $linkOptions($currentController === 'Customers')
        ) ?>

        <?= $this->Html->link(
            'Products',
            '/admin/coming-soon/products',
            $linkOptions($currentController === 'Products')
        ) ?>

        <?= $this->Html->link(
            'Shipments',
            '/admin/coming-soon/shipments',
            $linkOptions($currentController === 'Shipments')
        ) ?>
    </div>

    <div class="sidebar-group">
        <h3>Sales</h3>

        <?= $this->Html->link(
            'Quotations',
            '/admin/coming-soon/quotations',
            $linkOptions($currentController === 'Quotations')
        ) ?>

        <?= $this->Html->link(
            'Invoices',
            '/admin/coming-soon/invoices',
            $linkOptions($currentController === 'Invoices')
        ) ?>

        <?= $this->Html->link(
            'Payments',
            '/admin/coming-soon/payments',
            $linkOptions($currentController === 'Payments')
        ) ?>

        <?= $this->Html->link(
            'Refunds',
            '/admin/coming-soon/refunds',
            $linkOptions($currentController === 'Refunds')
        ) ?>
    </div>

    <div class="sidebar-group">
        <h3>Communication</h3>

        <?= $this->Html->link(
            'Messages',
            '/admin/coming-soon/messages',
            $linkOptions($currentController === 'Messages')
        ) ?>
    </div>

    <div class="sidebar-group">
        <h3>Reporting</h3>

        <?= $this->Html->link(
            'Reports',
            '/admin/coming-soon/reports',
            $linkOptions($currentController === 'Reports')
        ) ?>
    </div>

    <div class="sidebar-group">
        <h3>Administration</h3>

        <?= $this->Html->link(
            'Access',
            '/admin/coming-soon/access',
            $linkOptions($currentController === 'Access')
        ) ?>
    </div>
</nav>
