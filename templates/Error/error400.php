<?php
// templates/Error/error400.php

use Cake\Http\Exception\ForbiddenException;

$isForbidden =
    ($error instanceof ForbiddenException) ||
    (($error->getCode() ?? 0) === 403) ||
    ($error->getMessage() === 'Forbidden');

if ($isForbidden) {
    $this->setLayout('error');
    $this->assign('title', 'Access Denied');
    ?>
    <h1>Access Denied</h1>
    <p>You do not have access to this page.</p>
    <?php
    return;
}

echo $this->element('auto_table_warning');

if (isset($message)) {
    echo '<h1>' . h($message) . '</h1>';
}
?>
