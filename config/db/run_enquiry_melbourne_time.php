<?php
// config/db/run_enquiry_melbourne_time.php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__) . '/bootstrap.php';

use Cake\Datasource\ConnectionManager;

$connection = ConnectionManager::get('default');
$melbourne = new \DateTimeZone('Australia/Melbourne');
$pacific = new \DateTimeZone('America/Los_Angeles');
$nowMelbourne = new \DateTime('now', $melbourne);
$nowPacific = new \DateTime('now', $pacific);

$latest = $connection->execute(
    'SELECT CreatedDate FROM Enquiry WHERE CreatedDate IS NOT NULL ORDER BY EnquiryId DESC LIMIT 1'
)->fetch('assoc');

if ($latest === false) {
    echo "EMPTY\n";
    exit(0);
}

$stored = (string)$latest['CreatedDate'];
$asMelbourne = new \DateTime($stored, $melbourne);
$asPacific = new \DateTime($stored, $pacific);
$diffMelbourne = abs((int)$nowMelbourne->format('U') - (int)$asMelbourne->format('U'));
$diffPacific = abs((int)$nowPacific->format('U') - (int)$asPacific->format('U'));

if ($diffPacific + 1800 >= $diffMelbourne) {
    echo "SKIPPED\n";
    echo 'LATEST ' . $stored . "\n";
    exit(0);
}

$rows = $connection->execute(
    'SELECT EnquiryId, CreatedDate FROM Enquiry WHERE CreatedDate IS NOT NULL'
)->fetchAll('assoc');

foreach ($rows as $row) {
    $converted = (new \DateTime((string)$row['CreatedDate'], $pacific))
        ->setTimezone($melbourne)
        ->format('Y-m-d H:i:s');

    $connection->execute(
        'UPDATE Enquiry SET CreatedDate = :created WHERE EnquiryId = :id',
        [
            'created' => $converted,
            'id' => (int)$row['EnquiryId'],
        ]
    );
}

echo 'CONVERTED ' . count($rows) . "\n";
