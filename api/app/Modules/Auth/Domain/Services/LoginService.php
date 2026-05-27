<?php

namespace App\Modules\Auth\Domain\Services;

use App\Modules\Auth\Domain\Contracts\LoginChannel;
use App\Modules\Auth\Domain\DTOs\LoginResult;
use App\Modules\Auth\Domain\Exceptions\UnsupportedLoginChannelException;

class LoginService
{
    /**
     * @param  array<string, LoginChannel>  $channels
     */
    public function __construct(
        private readonly array $channels,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function login(string $channel, array $data): ?LoginResult
    {
        if (! isset($this->channels[$channel])) {
            throw new UnsupportedLoginChannelException($channel);
        }

        $user = $this->channels[$channel]->resolveUser($data);

        if ($user === null) {
            return null;
        }

        $token = $user->createToken('auth')->plainTextToken;

        return new LoginResult(user: $user->load('company'), token: $token);
    }
}
