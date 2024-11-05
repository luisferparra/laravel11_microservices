<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;

use App\Models\User;

class UsersHelper
{
    /**
     * Creates an user in the database
     * @param array $data
     * @param string $roles
     * @param array $permissions
     * @return string|\App\Models\User
     */
    public static function createUser(array $data, $role = "", $permissions = [])
    {
        try {
            $data['password'] = (!empty($data['password'])) ? self::setPasswordEncrypt($data['password']) : self::setPasswordEncrypt('');
            $data['name'] = ucwords(strtolower($data['name']));
            $data['email'] = strtolower($data['email']);



            $user = User::create($data);
            if (!empty($role)) {
                $user->assignRole($role);
                //self::setRolePermissionsToUser($user, $role, $permissions);
            }
            return $user;
        } catch (\Exception $e) {
            return $e->getMessage();
            //return false;
        }
    }

    /**
     * Will assign a role and permissions to a user
     * @param \App\Models\User $user
     * @param string $role
     * @param array $permissions
     * @return void
     */
    public static function setRolePermissionsToUser(User $user, $role = "", $permissions = [])
    {
        if (!empty($role))
            $user->assignRole($role);
        /*if (!empty($permissions)) {
            $user->givePermissionTo($permissions);
        }*/
    }

    /**
     *
     * Function that given an email, returns if the user exists or not. True if Exists
     * @param mixed $email
     * @return bool
     */
    public static function getUserExists($email): bool
    {
        $user = User::where("email", $email)->first();

        return !empty($user->id);
    }

    /**
     *
     * Function that given a password, encrypts it using Laravel's Hash::make method
     * @param mixed $password
     * @return string
     */
    public static function setPasswordEncrypt($password): string
    {
        return  Hash::make($password);
    }
}
