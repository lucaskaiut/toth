<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Knowledge\Domain\Actions\DispatchSourceIndexingAction;
use App\Modules\Knowledge\Domain\DTOs\CreateKnowledgeSourceData;
use App\Modules\Knowledge\Domain\DTOs\UpdateKnowledgeSourceData;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use App\Modules\Knowledge\Domain\Jobs\ReindexTenantKnowledgeJob;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use App\Modules\Knowledge\Domain\Services\KnowledgeSourceService;
use App\Modules\Knowledge\Http\Requests\StoreKnowledgeDocumentRequest;
use App\Modules\Knowledge\Http\Requests\StoreKnowledgeSourceRequest;
use App\Modules\Knowledge\Http\Requests\UpdateKnowledgeSourceRequest;
use App\Modules\Knowledge\Http\Resources\KnowledgeSourceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KnowledgeSourceController extends Controller
{
    public function __construct(
        private readonly KnowledgeSourceService $sourceService,
        private readonly DispatchSourceIndexingAction $dispatchIndexing,
        private readonly CurrentCompany $currentCompany,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $type = $request->query('type');
        $enumType = is_string($type) ? KnowledgeSourceType::tryFrom($type) : null;

        return KnowledgeSourceResource::collection($this->sourceService->all($enumType));
    }

    public function store(StoreKnowledgeSourceRequest $request): KnowledgeSourceResource
    {
        $source = $this->sourceService->create(new CreateKnowledgeSourceData(
            type: KnowledgeSourceType::from($request->validated('type')),
            title: $request->validated('title'),
            content: $request->validated('content'),
            metadata: $request->validated('metadata'),
        ));

        return new KnowledgeSourceResource($source);
    }

    public function update(UpdateKnowledgeSourceRequest $request, KnowledgeSource $knowledgeSource): KnowledgeSourceResource
    {
        $source = $this->sourceService->update(
            $knowledgeSource,
            new UpdateKnowledgeSourceData(
                title: $request->validated('title'),
                content: $request->has('content') ? $request->validated('content') : null,
                metadata: $request->validated('metadata'),
            ),
        );

        return new KnowledgeSourceResource($source);
    }

    public function destroy(KnowledgeSource $knowledgeSource): JsonResponse
    {
        $this->sourceService->delete($knowledgeSource);

        return response()->json(['message' => 'Fonte removida com sucesso.']);
    }

    public function storeDocument(StoreKnowledgeDocumentRequest $request): KnowledgeSourceResource
    {
        $source = $this->sourceService->storeDocument(
            $request->file('file'),
            $request->validated('title'),
        );

        return new KnowledgeSourceResource($source);
    }

    public function reindex(KnowledgeSource $knowledgeSource): KnowledgeSourceResource
    {
        $this->dispatchIndexing->handle($knowledgeSource);

        return new KnowledgeSourceResource($knowledgeSource->fresh());
    }

    public function reindexAll(): JsonResponse
    {
        ReindexTenantKnowledgeJob::dispatch($this->currentCompany->id())
            ->onQueue(config('knowledge.queue', 'redis'));

        return response()->json(['message' => 'Reindexação completa enfileirada.']);
    }

    public function stats(): JsonResponse
    {
        return response()->json(['data' => $this->sourceService->stats()]);
    }
}
