<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'user.create', 'module' => 'Identity'],
            ['name' => 'user.read', 'module' => 'Identity'],
            ['name' => 'user.update', 'module' => 'Identity'],
            ['name' => 'user.delete', 'module' => 'Identity'],
            ['name' => 'role.manage', 'module' => 'IAM'],
            ['name' => 'permission.manage', 'module' => 'IAM'],
            ['name' => 'kyc.review', 'module' => 'Identity'],
            ['name' => 'kyc.approve', 'module' => 'Identity'],
            ['name' => 'fraud.review', 'module' => 'IAM'],
            ['name' => 'transaction.reverse', 'module' => 'Transaction'],
            ['name' => 'report.view', 'module' => 'Reporting'],
            ['name' => 'settings.manage', 'module' => 'Settings'],
            ['name' => 'admin.access', 'module' => 'Admin'],
        ];

        $now = now();
        $permissionIds = [];

        foreach ($permissions as $perm) {
            $id = Str::ulid()->toBase32();
            DB::table('permissions')->insert([
                'id' => $id,
                'name' => $perm['name'],
                'guard_name' => 'api',
                'module' => $perm['module'],
                'description' => match ($perm['name']) {
                    'user.create' => 'Create new user accounts',
                    'user.read' => 'View user account details',
                    'user.update' => 'Update user account information',
                    'user.delete' => 'Delete or deactivate user accounts',
                    'role.manage' => 'Create, edit, and delete roles',
                    'permission.manage' => 'Manage permission assignments',
                    'kyc.review' => 'Review KYC submissions',
                    'kyc.approve' => 'Approve or reject KYC submissions',
                    'fraud.review' => 'Review and investigate fraud cases',
                    'transaction.reverse' => 'Reverse or void transactions',
                    'report.view' => 'View reports and analytics',
                    'settings.manage' => 'Manage system settings',
                    'admin.access' => 'Access admin dashboard and management features',
                    default => null,
                },
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $permissionIds[$perm['name']] = $id;
        }

        $roles = [
            'super_admin' => [
                'description' => 'Full system access with all permissions',
                'permissions' => array_keys($permissionIds),
            ],
            'admin' => [
                'description' => 'Administrative access for day-to-day operations',
                'permissions' => [
                    'user.create', 'user.read', 'user.update', 'user.delete',
                    'role.manage', 'permission.manage',
                    'kyc.review', 'kyc.approve',
                    'report.view', 'settings.manage',
                    'admin.access',
                ],
            ],
            'compliance_officer' => [
                'description' => 'Review and approve KYC submissions',
                'permissions' => [
                    'user.read', 'user.update',
                    'kyc.review', 'kyc.approve',
                    'report.view',
                ],
            ],
            'fraud_analyst' => [
                'description' => 'Investigate and review fraud cases',
                'permissions' => [
                    'user.read',
                    'kyc.review',
                    'fraud.review',
                    'transaction.reverse',
                    'report.view',
                ],
            ],
            'ops_manager' => [
                'description' => 'Oversee daily operations',
                'permissions' => [
                    'user.read', 'user.update',
                    'kyc.review',
                    'fraud.review',
                    'transaction.reverse',
                    'report.view',
                ],
            ],
            'support_agent' => [
                'description' => 'Customer support with read-only access',
                'permissions' => [
                    'user.read',
                    'user.update',
                ],
            ],
            'finance_viewer' => [
                'description' => 'View financial reports only',
                'permissions' => [
                    'report.view',
                ],
            ],
        ];

        foreach ($roles as $roleName => $roleData) {
            $roleId = Str::ulid()->toBase32();
            DB::table('roles')->insert([
                'id' => $roleId,
                'name' => $roleName,
                'guard_name' => 'api',
                'description' => $roleData['description'],
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rolePermissionRows = [];
            foreach ($roleData['permissions'] as $permName) {
                $rolePermissionRows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionIds[$permName],
                ];
            }

            DB::table('role_permissions')->insert($rolePermissionRows);
        }
    }
}
