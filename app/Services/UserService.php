<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function createUser(array $data, int $targetCompanyId): User
    {
        return DB::transaction(function () use ($data, $targetCompanyId) {
            // 1. Create Base User
            $user = User::create([
                'name' => $data['name'],
                'username' => trim($data['username']),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $targetCompanyId, // Primary Company
            ]);

            // 2. Attach Pivot company_user
            $user->companies()->syncWithoutDetaching([$targetCompanyId]);

            // 3. Assign Spatie Role
            $user->assignRole($data['role']);

            return $user;
        });
    }

    public function updateUser(User $user, array $data, ?int $targetCompanyId = null, ?string $role = null): User
    {
        return DB::transaction(function () use ($user, $data, $targetCompanyId, $role) {
            $updateData = [
                'name' => $data['name'],
                'username' => trim($data['username']),
                'email' => $data['email'],
            ];

            if (!empty($data['password'])) {
                $updateData['password'] = Hash::make($data['password']);
            }

            // Super Admin = holding "Wayang Group" (bukan tenant) → tanpa company & pivot.
            if ($role === 'super_admin') {
                $updateData['company_id'] = null;
                $user->companies()->sync([]);
            } elseif ($targetCompanyId) {
                $updateData['company_id'] = $targetCompanyId;
                $user->companies()->sync([$targetCompanyId]);
            }

            $user->update($updateData);

            // Sync Role
            if ($role) {
                $user->syncRoles([$role]);
            }

            return $user;
        }); 
    }
}
