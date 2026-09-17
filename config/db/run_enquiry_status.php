<?php
// config/db/run_enquiry_status.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap.php';

use Cake\Datasource\ConnectionManager;

$connection = ConnectionManager::get('default');
$cols = $connection->execute("SHOW COLUMNS FROM Enquiry LIKE 'Status'")->fetchAll('assoc');

if ($cols === []) {
    $connection->execute(
        "ALTER TABLE Enquiry ADD COLUMN Status VARCHAR(50) NOT NULL DEFAULT 'New'"
    );
    echo "ADDED\n";
} else {
    echo "EXISTS\n";
}

$connection->execute(
    "UPDATE Enquiry SET Status = 'New' WHERE Status IS NULL OR Status = ''"
);

$rows = $connection->execute(
    'SELECT Status, COUNT(*) AS c FROM Enquiry GROUP BY Status'
)->fetchAll('assoc');

foreach ($rows as $row) {
    echo 'COUNT ' . $row['Status'] . '=' . $row['c'] . "\n";
}
