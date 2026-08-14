<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Checkout holds, session revocation, MFA, email verification, and alerts.
 */
final class AddSecurityHardeningColumns extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        if ($this->hasTable('users')) {
            $users = $this->table('users');
            if (!$users->hasColumn('auth_version')) {
                $users->addColumn('auth_version', 'integer', [
                    'default' => 1,
                    'null' => false,
                    'after' => 'status',
                ]);
            }
            if (!$users->hasColumn('email_verified_at')) {
                $users->addColumn('email_verified_at', 'datetime', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('email_verification_token')) {
                $users->addColumn('email_verification_token', 'string', [
                    'default' => null,
                    'limit' => 64,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('email_verification_expires')) {
                $users->addColumn('email_verification_expires', 'datetime', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('pending_email')) {
                $users->addColumn('pending_email', 'string', [
                    'default' => null,
                    'limit' => 255,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('pending_email_token')) {
                $users->addColumn('pending_email_token', 'string', [
                    'default' => null,
                    'limit' => 64,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('pending_email_expires')) {
                $users->addColumn('pending_email_expires', 'datetime', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('mfa_secret')) {
                $users->addColumn('mfa_secret', 'string', [
                    'default' => null,
                    'limit' => 512,
                    'null' => true,
                ]);
            }
            if (!$users->hasColumn('mfa_enabled')) {
                $users->addColumn('mfa_enabled', 'boolean', [
                    'default' => false,
                    'null' => false,
                ]);
            }
            if (!$users->hasColumn('mfa_confirmed_at')) {
                $users->addColumn('mfa_confirmed_at', 'datetime', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            $users->update();
        }

        if ($this->hasTable('sales_orders')) {
            $orders = $this->table('sales_orders');
            if (!$orders->hasColumn('checkout_attempt_id')) {
                $orders->addColumn('checkout_attempt_id', 'string', [
                    'default' => null,
                    'limit' => 36,
                    'null' => true,
                ]);
                $orders->addIndex(['checkout_attempt_id'], [
                    'unique' => true,
                    'name' => 'uq_sales_orders_checkout_attempt_id',
                ]);
            }
            if (!$orders->hasColumn('hold_expires_at')) {
                $orders->addColumn('hold_expires_at', 'datetime', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            if (!$orders->hasColumn('cart_id')) {
                $orders->addColumn('cart_id', 'biginteger', [
                    'default' => null,
                    'null' => true,
                    'signed' => false,
                ]);
            }
            $orders->update();
        }

        if ($this->hasTable('carts')) {
            $carts = $this->table('carts');
            if (!$carts->hasColumn('checkout_attempt_id')) {
                $carts->addColumn('checkout_attempt_id', 'string', [
                    'default' => null,
                    'limit' => 36,
                    'null' => true,
                ]);
            }
            $carts->update();
        }

        if ($this->hasTable('stock_reservations')) {
            $reservations = $this->table('stock_reservations');
            if (!$reservations->hasColumn('expires_at')) {
                $reservations->addColumn('expires_at', 'datetime', [
                    'default' => null,
                    'null' => true,
                ]);
            }
            $reservations->update();
        }

        if ($this->hasTable('payments') && !$this->table('payments')->hasIndex(['provider', 'provider_payment_id'])) {
            $this->table('payments')
                ->addIndex(['provider', 'provider_payment_id'], [
                    'unique' => true,
                    'name' => 'uq_payments_provider_payment_id',
                ])
                ->update();
        }

        if (!$this->hasTable('payment_reconciliation_alerts')) {
            $this->table('payment_reconciliation_alerts')
                ->addColumn('event_id', 'string', ['limit' => 64, 'null' => false])
                ->addColumn('provider_payment_id', 'string', ['limit' => 64, 'null' => true])
                ->addColumn('sales_order_id', 'biginteger', ['null' => true, 'signed' => false])
                ->addColumn('reason', 'string', ['limit' => 64, 'null' => false])
                ->addColumn('detail', 'text', ['null' => true])
                ->addColumn('payload_digest', 'string', ['limit' => 64, 'null' => true])
                ->addColumn('created', 'datetime', ['null' => false])
                ->addIndex(['event_id'], ['name' => 'idx_payment_alerts_event_id'])
                ->create();
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        throw new RuntimeException('Irreversible additive security migration.');
    }
}
