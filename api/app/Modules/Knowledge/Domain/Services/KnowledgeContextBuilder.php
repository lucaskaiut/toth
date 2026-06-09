<?php

namespace App\Modules\Knowledge\Domain\Services;

use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;

class KnowledgeContextBuilder
{
    public function __construct(
        private readonly RetrievalService $retrievalService,
    ) {}

    public function build(int $companyId, string $userQuery): string
    {
        $sections = [];

        $companyInfo = $this->sectionByType($companyId, KnowledgeSourceType::Company, '[INFORMAÇÕES DA EMPRESA]');
        if ($companyInfo !== '') {
            $sections[] = $companyInfo;
        }

        $products = $this->sectionByType($companyId, KnowledgeSourceType::Product, '[PRODUTOS]');
        if ($products !== '') {
            $sections[] = $products;
        }

        $faq = $this->sectionByType($companyId, KnowledgeSourceType::Faq, '[FAQ]');
        if ($faq !== '') {
            $sections[] = $faq;
        }

        $policies = $this->sectionByType($companyId, KnowledgeSourceType::Policy, '[POLÍTICAS]');
        if ($policies !== '') {
            $sections[] = $policies;
        }

        $scripts = $this->sectionByType($companyId, KnowledgeSourceType::Script, '[SCRIPTS COMERCIAIS]');
        if ($scripts !== '') {
            $sections[] = $scripts;
        }

        $freeContext = $this->sectionByType($companyId, KnowledgeSourceType::FreeContext, '[CONTEXTO LIVRE]');
        if ($freeContext !== '') {
            $sections[] = $freeContext;
        }

        $relevant = trim($this->retrievalService->buildContext(
            $userQuery,
            (int) config('knowledge.context_top_k', 8),
            $companyId,
        ));
        if ($relevant !== '') {
            $sections[] = $relevant;
        }

        return implode("\n\n", $sections);
    }

    private function sectionByType(int $companyId, KnowledgeSourceType $type, string $header): string
    {
        $sources = KnowledgeSource::query()
            ->where('company_id', $companyId)
            ->where('type', $type->value)
            ->where('status', 'indexed')
            ->orderBy('title')
            ->get();

        if ($sources->isEmpty()) {
            return '';
        }

        $lines = [$header];

        foreach ($sources as $source) {
            $content = trim((string) $source->content);
            if ($content === '') {
                continue;
            }

            $lines[] = "### {$source->title}\n{$content}";
        }

        return count($lines) > 1 ? implode("\n\n", $lines) : '';
    }
}
