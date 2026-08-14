<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPasswordResetToUsers extends BaseMigration
{
    /**
     * Up Method.
     *
     * Adds the two columns backing the "forgot password" flow. Only a SHA-256
     * hash of the reset token is ever stored here, so a leaked database dump
     * cannot be replayed against the reset endpoint. Both columns are nullable
     * because an account only carries a token while a reset is pending.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-up-method
     *
     * @return void
     */
    public function up(): void
    {
        $this->table('users')
            ->addColumn('password_reset_token', 'string', [
                'default' => null,
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('password_reset_expires', 'datetime', [
                'default' => null,
                'limit' => null,
                'null' => true,
            ])
            ->addIndex(['password_reset_token'])
            ->update();
    }

    /**
     * Down Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/migrations/5/guides/writing-migrations/migration-methods.html#the-down-method
     *
     * @return void
     */
    public function down(): void
    {
        $this->table('users')
            ->removeIndex(['password_reset_token'])
            ->update();

        $this->table('users')
            ->removeColumn('password_reset_token')
            ->removeColumn('password_reset_expires')
            ->update();
    }
}
