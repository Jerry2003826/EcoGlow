<?php

$this->assign('title', 'Customers');
?>

<h1 class="page-title">Customers</h1>

<?php if (count($customers) === 0): ?>
    <div class="empty-state">
        No customers found.
    </div>
<?php else: ?>
    <div class="panel-card">
        <table class="table admin-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Suburb</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td>
                        <?= $this->Html->link(
                            trim(
                                ($customer->first_name ?? '') . ' ' .
                                ($customer->last_name ?? '')
                            ),
                            ['action' => 'view', $customer->id]
                        ) ?>
                    </td>
                    <td><?= h($customer->email) ?></td>
                    <td><?= h($customer->phone) ?></td>
                    <td><?= h($customer->suburb) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
