<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Owner Permissions
            [
                'title' => 'owner_access',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_index',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_create',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_edit',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_delete',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'report_check',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'game_type_access',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'provider_access',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'provider_create',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'provider_edit',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'provider_delete',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'provider_index',
                'group' => 'owner',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Agent Permissions
            [
                'title' => 'agent_access',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_index',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_create',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_edit',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_delete',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_change_password_access',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'transfer_log',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'make_transfer',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'bank',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'withdraw',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'deposit',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'contact',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_access',
                'group' => 'agent',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // Player Permissions
            [
                'title' => 'player_index',
                'group' => 'player',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_create',
                'group' => 'player',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_edit',
                'group' => 'player',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_delete',
                'group' => 'player',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_view',
                'group' => 'player',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            // System Wallet Permissions
            [
                'title' => 'system_wallet_access',
                'group' => 'system_wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'system_wallet_index',
                'group' => 'system_wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'system_wallet_create',
                'group' => 'system_wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'system_wallet_edit',
                'group' => 'system_wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'system_wallet_delete',
                'group' => 'system_wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'system_wallet_view',
                'group' => 'system_wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            
        ];

        Permission::insert($permissions);
    }
}
