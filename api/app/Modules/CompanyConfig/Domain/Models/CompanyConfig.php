<?php

namespace App\Modules\CompanyConfig\Domain\Models;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'type' => CompanyConfigType::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function booted(): void
    {
        $forget = static function (CompanyConfig $config): void {
            (new CompanyConfigResolver($config->company_id))->forgetCache($config->company_id);
        };

        static::saved($forget);
        static::deleted($forget);
    }
}
