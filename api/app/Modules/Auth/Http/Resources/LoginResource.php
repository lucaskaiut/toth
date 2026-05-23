<?php

namespace App\Modules\Auth\Http\Resources;

use App\Modules\Auth\Domain\DTOs\LoginResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LoginResult */
class LoginResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'user' => new AuthUserResource($this->user),
        ];
    }
}
