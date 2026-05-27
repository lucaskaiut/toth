<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Domain\Exceptions\RegisterProvisioningException;
use App\Modules\Auth\Domain\Exceptions\UnsupportedRegisterChannelException;
use App\Modules\Auth\Domain\Services\RegisterService;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\LoginResource;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterService $registerService,
    ) {}

    public function store(RegisterRequest $request): LoginResource|JsonResponse
    {
        try {
            $result = $this->registerService->register(
                $request->validated('channel'),
                $request->validated('data') ?? [],
            );
        } catch (UnsupportedRegisterChannelException) {
            return response()->json([
                'message' => 'Canal de cadastro não suportado.',
            ], 422);
        } catch (RegisterProvisioningException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }

        if ($result === null) {
            return response()->json([
                'message' => 'Não foi possível concluir o cadastro.',
            ], 422);
        }

        return (new LoginResource($result))
            ->response()
            ->setStatusCode(201);
    }
}
