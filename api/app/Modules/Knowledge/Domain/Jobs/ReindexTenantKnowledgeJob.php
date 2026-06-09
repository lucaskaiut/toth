<?php

namespace App\Modules\Knowledge\Domain\Jobs;

use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ReindexTenantKnowledgeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(
        public int $companyId,
    ) {}

    public function handle(): void
    {
        $queue = (string) config('knowledge.queue', 'default');

        KnowledgeSource::query()
            ->where('company_id', $this->companyId)
            ->orderBy('id')
            ->each(function (KnowledgeSource $source) use ($queue): void {
                IndexKnowledgeSourceJob::dispatch($source->id, $source->company_id)
                    ->onQueue($queue);
            });
    }
}
