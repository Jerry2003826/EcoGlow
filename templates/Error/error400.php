<?php
// templates/Error/error400.php

use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MissingControllerException;

$isForbidden =
    ($error instanceof ForbiddenException) ||
    (($error->getCode() ?? 0) === 403) ||
    ($error->getMessage() === 'Forbidden');

$isNotFound =
    ($error instanceof MissingControllerException) ||
    (($error->getCode() ?? 0) === 404);

if ($isForbidden) {
    $this->setLayout('error');
    $this->assign('title', 'Access Denied');
    ?>
    <h1>Access Denied</h1>
    <p>You do not have access to this page.</p>
    <?php
    return;
}

if ($isNotFound) {
    $this->setLayout('error');
    $this->assign('title', 'Page Not Found');
    ?>

    <h1>404 - Page Not Found</h1>
    <p>The page you are looking for does not exist.</p>

    <?php
    return;
}

echo $this->element('auto_table_warning');

if (isset($message)) {
    echo '<h1>' . h($message) . '</h1>';
}
?>
