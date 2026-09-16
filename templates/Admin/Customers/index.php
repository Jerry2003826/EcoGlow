<?php
// templates/Admin/Customers/index.php

$this->assign('title', 'Customers');
?>

<h1 class="page-title">Customers</h1>

<div class="panel-card">
    <h2>Registered customers</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($customers) === 0): ?>
                <tr>
                    <td colspan="5">No registered customers yet. Guests appear below.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($customers as $customer): ?>
                <tr
                    class="admin-clickable-row"
                    data-href="/admin/customers/edit/<?= (int)$customer->CustomerId ?>"
                >
                    <td><?= h($customer->CustomerName) ?></td>
                    <td><?= h($customer->Email) ?></td>
                    <td><?= h($customer->Phone) ?></td>
                    <td><?= h($customer->Address) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel-card">
    <h2>Guests</h2>
    <div class="admin-table-wrap">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($guests as $guest): ?>
                <tr
                    class="admin-clickable-row"
                    data-href="/admin/customers/editGuest/<?= (int)$guest->GuestId ?>"
                >
                    <td><?= h($guest->GuestName) ?></td>
                    <td><?= h($guest->Email) ?></td>
                    <td><?= h($guest->Phone) ?></td>
                    <td><?= h($guest->Address) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
