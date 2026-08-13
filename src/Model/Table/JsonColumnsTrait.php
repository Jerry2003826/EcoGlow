<?php
declare(strict_types=1);

namespace App\Model\Table;

/**
 * MariaDB reports JSON columns as longtext. Map them to Cake's json type
 * so entities and fixtures can pass PHP arrays.
 */
trait JsonColumnsTrait
{
    /**
     * @param list<string> $columns Column names to map when they exist.
     * @return void
     */
    protected function mapJsonColumns(array $columns): void
    {
        $schema = $this->getSchema();
        foreach ($columns as $column) {
            if ($schema->hasColumn($column)) {
                $schema->setColumnType($column, 'json');
            }
        }
    }
}
