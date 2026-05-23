<?php

namespace App\Modules\Auth\Domain\Channels;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\LoginChannel;
use Illuminate\Support\Facades\Hash;

class InternalLoginChannel implements LoginChannel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function resolveUser(array $data): ?User
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (! is_string($email) || ! is_string($password)) {
            return null;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
