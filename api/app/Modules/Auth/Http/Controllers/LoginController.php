<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Domain\Exceptions\UnsupportedLoginChannelException;
use App\Modules\Auth\Domain\Services\LoginService;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\LoginResource;
use Illuminate\Http\JsonResponse;

class LoginController extends Controller
{
    public function __construct(
        private readonly LoginService $loginService,
    ) {}

    public function store(LoginRequest $request): LoginResource|JsonResponse
    {
        try {
            $result = $this->loginService->login(
                $request->validated('channel'),
                $request->validated('data'),
            );
        } catch (UnsupportedLoginChannelException) {
            return response()->json([
                'message' => 'Canal de login não suportado.',
            ], 422);
        }

        if ($result === null) {
            return response()->json([
                'message' => 'Credenciais inválidas.',
            ], 401);
        }

        return new LoginResource($result);
    }
}
