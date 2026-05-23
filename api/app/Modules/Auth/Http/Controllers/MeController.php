<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Resources\AuthUserResource;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function show(Request $request): AuthUserResource
    {
        return new AuthUserResource($request->user());
    }
}
