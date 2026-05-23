<?php

namespace App\Modules\Auth\Domain\Services;

use App\Modules\Auth\Domain\Contracts\RegisterChannel;
use App\Modules\Auth\Domain\DTOs\LoginResult;
use App\Modules\Auth\Domain\Exceptions\UnsupportedRegisterChannelException;

class RegisterService
{
    /**
     * @param  array<string, RegisterChannel>  $channels
     */
    public function __construct(
        private readonly array $channels,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function register(string $channel, array $data): ?LoginResult
    {
        if (! isset($this->channels[$channel])) {
            throw new UnsupportedRegisterChannelException($channel);
        }

        $user = $this->channels[$channel]->createUser($data);

        if ($user === null) {
            return null;
        }

        $token = $user->createToken('auth')->plainTextToken;

        return new LoginResult(user: $user, token: $token);
    }
}
