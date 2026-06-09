<?php

namespace App\Modules\Knowledge\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Knowledge\Domain\Services\KnowledgeContextBuilder;
use App\Modules\Knowledge\Domain\Services\RetrievalService;
use App\Modules\Knowledge\Http\Requests\KnowledgeSearchRequest;
use App\Modules\Knowledge\Http\Resources\KnowledgeSearchResultResource;
use Illuminate\Http\JsonResponse;

class KnowledgeSearchController extends Controller
{
    public function __construct(
        private readonly RetrievalService $retrievalService,
        private readonly KnowledgeContextBuilder $contextBuilder,
    ) {}

    public function search(KnowledgeSearchRequest $request): JsonResponse
    {
        $results = $this->retrievalService->search(
            $request->validated('query'),
            $request->integer('limit') ?: null,
        );

        return response()->json([
            'data' => collect($results)->map(
                static fn ($result) => (new KnowledgeSearchResultResource($result))->resolve()
            )->values(),
        ]);
    }

    public function context(KnowledgeSearchRequest $request): JsonResponse
    {
        $companyId = $request->user()?->company_id;

        if ($companyId === null) {
            abort(403);
        }

        $context = $this->contextBuilder->build($companyId, $request->validated('query'));

        return response()->json([
            'data' => [
                'context' => $context,
            ],
        ]);
    }
}
