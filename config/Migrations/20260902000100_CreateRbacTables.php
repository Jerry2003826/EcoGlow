<?php

declare(strict_types=1);

use Migrations\BaseMigration;

final class CreateRbacTables extends BaseMigration
{
    public function change(): void
    {
        $this->table('roles', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
            ])
            ->addColumn('code', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('name', 'string', [
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['code'], ['unique' => true])
            ->create();

        $this->table('permissions', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
            ])
            ->addColumn('permission_key', 'string', [
                'limit' => 150,
                'null' => false,
            ])
            ->addColumn('description', 'string', [
                'limit' => 255,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['permission_key'], ['unique' => true])
            ->create();

        $this->table('role_permissions', [
            'id' => false,
            'primary_key' => ['role_id', 'permission_id'],
        ])
            ->addColumn('role_id', 'biginteger', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('permission_id', 'biginteger', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addForeignKey(
                'role_id',
                'roles',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            )
            ->addForeignKey(
                'permission_id',
                'permissions',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            )
            ->create();

        $userRoles = $this->table('user_roles', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('role_id', 'biginteger', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('starts_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('ends_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('revoked_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['user_id'])
            ->addIndex(['role_id'])
            ->addForeignKey(
                'role_id',
                'roles',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            );

        // This migration does not create `users`. CakePHP's test Migrator drops
        // existing tables first, so the FK can only be added when that table
        // already exists (e.g. a full application schema).
        if ($this->hasTable('users')) {
            $userRoles->addForeignKey(
                'user_id',
                'users',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            );
        }

        $userRoles->create();

        $overrides = $this->table('user_permission_overrides', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'biginteger', [
                'identity' => true,
                'signed' => false,
            ])
            ->addColumn('user_id', 'integer', [
                'null' => false,
                'signed' => true,
            ])
            ->addColumn('permission_id', 'biginteger', [
                'null' => false,
                'signed' => false,
            ])
            ->addColumn('effect', 'string', [
                'limit' => 5,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(
                ['user_id', 'permission_id'],
                ['unique' => true]
            )
            ->addForeignKey(
                'permission_id',
                'permissions',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            );

        if ($this->hasTable('users')) {
            $overrides->addForeignKey(
                'user_id',
                'users',
                'id',
                [
                    'delete' => 'CASCADE',
                    'update' => 'CASCADE',
                ]
            );
        }

        $overrides->create();
    }
}
