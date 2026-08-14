<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class DropArticlesAndCategories extends BaseMigration
{
    /**
     * Up Method.
     *
     * Drops the two tables left over from the CakePHP scaffold. Neither backs a
     * user story, and their controllers were reachable through the `fallbacks()`
     * route, so they are removed together with the code that used them.
     *
     * The guards keep the migration runnable against a database where the
     * tables were already removed by hand, e.g. on the deployed host.
     *
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('articles')) {
            $this->table('articles')->drop()->save();
        }

        if ($this->hasTable('categories')) {
            $this->table('categories')->drop()->save();
        }
    }

    /**
     * Down Method.
     *
     * Recreates both tables as InitialSchema defined them. The rows are not
     * restored: the scaffold never stored anything worth keeping.
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('articles')
            ->addColumn('title', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('body', 'text', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => false,
            ])
            ->create();

        $this->table('categories')
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->create();
    }
}
