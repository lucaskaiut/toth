<?php

namespace App\Modules\Auth\Domain\Contracts;

use App\Models\User;

interface LoginChannel
{
    /**
     * Verifica se as credenciais do canal permitem autenticação e retorna o usuário.
     * Não efetua login — apenas resolve o User ou null.
     *
     * @param  array<string, mixed>  $data
     */
    public function resolveUser(array $data): ?User;
}
