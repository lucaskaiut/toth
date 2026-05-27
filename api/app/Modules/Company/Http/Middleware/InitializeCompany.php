<?php

namespace App\Modules\Company\Http\Middleware;

use App\Modules\Company\Domain\CurrentCompany;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeCompany
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return $next($request);
        }

        $user->loadMissing('company');

        if ($user->company === null) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        $this->currentCompany->set($user->company);

        return $next($request);
    }
}
