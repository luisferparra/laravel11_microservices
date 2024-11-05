<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Traits\HasRoles;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Exception;
use Illuminate\Support\Facades\Hash;

use App\Helpers\UsersHelper;

class InstallRolesAndPermissions extends Command
{

    protected $rolesPermissionArray = [
        'read',
        'write',
        'execute',
        'delete'
    ];

    protected $rolesArray = [
        'SuperAdmin' => [
            'read',
            'write',
            'execute',
            'delete'
        ],
        'CompanyAdmin' => [
            'read',
            'write',
            'execute',
            'delete'
        ],
        'User' => [
            'read'
        ]
    ];
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:install-roles-and-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'From Roles and Permission, initial Roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach ($this->rolesPermissionArray as $permission) {
            try {
                Permission::create(['guard_name' => 'api','name' => $permission]);
            } catch (Exception $e) {
                continue;
            }
        }
        foreach ($this->rolesArray as $role => $permissionList) {
            try {
                $roleObj = Role::create(['name' => $role]);
                $roleObj->givePermissionTo($permissionList);
            } catch (\Throwable $th) {
                continue;
            }
        }

        try {
            $user = User::create([
                'name' => 'Super Admin',
                'email' => 'superadmin@laravel.mv',
                'password' => UsersHelper::setPasswordEncrypt(env('SUPERADMIN_PASSWORD', '123456'))
            ]);
            $user->assignRole('SuperAdmin');
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
