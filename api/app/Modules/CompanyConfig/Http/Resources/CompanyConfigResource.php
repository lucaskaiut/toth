<?php

namespace App\Modules\CompanyConfig\Http\Resources;

use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CompanyConfig */
class CompanyConfigResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'value' => $this->value,
            'type' => $this->type->value,
        ];
    }
}
