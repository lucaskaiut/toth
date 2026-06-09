<?php

namespace App\Modules\Knowledge\Domain\Jobs;

use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use App\Modules\Knowledge\Domain\Services\KnowledgeIndexingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IndexKnowledgeSourceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    public function __construct(
        public int $sourceId,
        public int $companyId,
    ) {}

    public function handle(KnowledgeIndexingService $indexingService): void
    {
        $source = KnowledgeSource::query()
            ->where('company_id', $this->companyId)
            ->find($this->sourceId);

        if ($source === null) {
            return;
        }

        $indexingService->indexSource($source);
    }
}
