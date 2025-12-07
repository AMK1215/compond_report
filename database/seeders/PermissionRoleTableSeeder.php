<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PermissionRoleTableSeeder extends Seeder
{
    private const ROLE_PERMISSIONS = [
        'Owner' => [
            'owner_access',
            'owner_index',
            'owner_create',
            'owner_edit',
            'owner_delete',
            'agent_index',
            'agent_create',
            'agent_edit',
            'agent_delete',
            'transfer_log',
            'make_transfer',
            'game_type_access',
            'provider_access',
            'provider_create',
            'provider_edit',
            'provider_delete',
            'provider_index',
            'report_check',
            'player_access',
        ],
        'Agent' => [
            'agent_access',
            'agent_index',
            'agent_create',
            'agent_edit',
            'agent_delete',
            'transfer_log',
            'make_transfer',
            'player_index',
            'player_create',
            'player_edit',
            'player_delete',
            'player_view',
            'deposit',
            'withdraw',
            'bank',
            'contact',
            'player_access',
            'report_check',
        ],
        'Player' => [
            'player_view',
            'withdraw',
            'deposit',
            'bank',
            'contact',
        ],
        'SystemWallet' => [
            'system_wallet_access',
            'system_wallet_index',
            'system_wallet_create',
            'system_wallet_edit',
            'system_wallet_delete',
            'owner_access',
            'owner_index',
            'owner_create',
            'owner_edit',
            'owner_delete',
            'agent_index',
            'agent_create',
            'agent_edit',
            'agent_delete',
        ],
    ];

    private const ROLE_IDS = [
        'Owner' => 1,
        'Agent' => 2,
        'Player' => 3,
        'SystemWallet' => 4,
    ];

    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Validate roles exist
            $this->validateRoles();

            // Validate permissions exist
            $this->validatePermissions();

            // Clean up existing permission assignments
            $this->cleanupExistingAssignments();

            // Assign permissions to roles
            foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
                $roleId = self::ROLE_IDS[$roleName];
                $permissionIds = Permission::whereIn('title', $permissions)
                    ->pluck('id')
                    ->toArray();

                $this->assignPermissions($roleId, $permissionIds, $roleName);
            }

            // Verify permission assignments
            $this->verifyPermissionAssignments();

            DB::commit();
            Log::info('Permission assignments completed successfully');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in PermissionRoleTableSeeder: '.$e->getMessage());
            throw $e;
        }
    }

    private function validateRoles(): void
    {
        $existingRoles = Role::whereIn('id', array_values(self::ROLE_IDS))->pluck('id')->toArray();
        $missingRoles = array_diff(array_values(self::ROLE_IDS), $existingRoles);

        if (! empty($missingRoles)) {
            throw new \RuntimeException('Missing required roles with IDs: '.implode(', ', $missingRoles));
        }
    }

    private function validatePermissions(): void
    {
        $allPermissions = array_merge(...array_values(self::ROLE_PERMISSIONS));
        $existingPermissions = Permission::whereIn('title', $allPermissions)->pluck('title')->toArray();
        $missingPermissions = array_diff($allPermissions, $existingPermissions);

        if (! empty($missingPermissions)) {
            throw new \RuntimeException('Missing required permissions: '.implode(', ', $missingPermissions));
        }
    }

    private function cleanupExistingAssignments(): void
    {
        try {
            DB::table('permission_role')->delete();
            Log::info('Cleaned up existing permission assignments');
        } catch (\Exception $e) {
            Log::error('Failed to cleanup existing permission assignments: '.$e->getMessage());
            throw $e;
        }
    }

    private function assignPermissions(int $roleId, array $permissions, string $roleName): void
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->permissions()->sync($permissions);
            Log::info('Assigned '.count($permissions)." permissions to {$roleName} role");
        } catch (\Exception $e) {
            Log::error("Failed to assign permissions to {$roleName} role: ".$e->getMessage());
            throw $e;
        }
    }

    private function verifyPermissionAssignments(): void
    {
        foreach (self::ROLE_PERMISSIONS as $roleName => $expectedPermissions) {
            $roleId = self::ROLE_IDS[$roleName];
            $role = Role::findOrFail($roleId);
            $assignedPermissions = $role->permissions()->pluck('title')->toArray();
            $missingPermissions = array_diff($expectedPermissions, $assignedPermissions);

            if (! empty($missingPermissions)) {
                throw new \RuntimeException(
                    "Role '{$roleName}' is missing permissions: ".implode(', ', $missingPermissions)
                );
            }
        }
    }
}
