<?php

namespace App\Modules\Knowledge\Domain\Actions;

use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceStatus;
use App\Modules\Knowledge\Domain\Jobs\IndexKnowledgeSourceJob;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;

class DispatchSourceIndexingAction
{
    public function handle(KnowledgeSource $source): void
    {
        $source->update(['status' => KnowledgeSourceStatus::Pending]);

        IndexKnowledgeSourceJob::dispatch($source->id, $source->company_id)
            ->onQueue(config('knowledge.queue', 'redis'));
    }
}
