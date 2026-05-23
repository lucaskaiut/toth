<?php

namespace App\Modules\Auth\Domain\Channels;

use App\Models\User;
use App\Modules\Auth\Domain\Contracts\RegisterChannel;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Support\Facades\DB;

class InternalRegisterChannel implements RegisterChannel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): ?User
    {
        $companyName = $data['company_name'] ?? null;
        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (
            ! is_string($companyName)
            || ! is_string($name)
            || ! is_string($email)
            || ! is_string($password)
        ) {
            return null;
        }

        if (User::query()->where('email', $email)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($companyName, $name, $email, $password) {
            $company = Company::query()->create([
                'name' => trim($companyName),
            ]);

            return $company->users()->create([
                'name' => trim($name),
                'email' => $email,
                'password' => $password,
            ]);
        });
    }
}
