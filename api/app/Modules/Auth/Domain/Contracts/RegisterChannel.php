<?php

namespace App\Modules\Auth\Domain\Contracts;

use App\Models\User;

interface RegisterChannel
{
    /**
     * Executa o cadastro específico do canal e retorna o usuário criado.
     * Não efetua login — apenas cria o User ou retorna null.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): ?User;
}
