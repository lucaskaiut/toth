<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('company.{companyId}', function (User $user, int $companyId) {
    return (int) $user->company_id === $companyId;
});
