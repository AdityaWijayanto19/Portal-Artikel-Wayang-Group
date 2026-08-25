<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    public function updateProfile(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $updateData = ['name' => $data['name']];

            if (! $user->isAuthor()) {
                $updateData['email'] = $data['email'];
                $updateData['username'] = trim($data['username']);
            }

            if (! empty($data['password'])) {
                $updateData['password'] = $data['password'];
            }

            $user->update($updateData);

            return $user;
        });
    }
}
