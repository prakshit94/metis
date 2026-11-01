<?php

namespace App\Services;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Guard;

class NotificationRecipients
{
    public function forRoles(array $names): Collection
    {
        $user = new User;
        $roleClass = $user->getRoleClass();
        $roles = [];
        foreach ($names as $name) {
            try {
                // Use Spatie's existing guard/team-aware role resolution.
                $roles[] = $roleClass::findByName($name, Guard::getDefaultName($user));
            } catch (RoleDoesNotExist) {
                // Optional role names must not suppress alerts to valid admins.
            }
        }

        return User::role($roles)->get();
    }
}
