<?php

namespace Database\Seeders;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceStatus;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use App\Modules\Knowledge\Domain\Models\KnowledgeChunk;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use App\Modules\Knowledge\Domain\Repositories\VectorEmbeddingRepository;
use App\Modules\Knowledge\Domain\Services\KnowledgeIndexingService;
use Illuminate\Database\Seeder;
use Throwable;

class AnubisVetKnowledgeSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = (int) (env('ANUBIS_SEED_COMPANY_ID') ?: Company::query()->value('id'));

        if ($companyId === 0) {
            $this->command?->error('Nenhuma empresa encontrada. Crie uma empresa antes de executar o seed.');

            return;
        }

        $company = Company::query()->findOrFail($companyId);
        $company->update(['name' => 'Anúbis Vet Center']);

        $this->command?->info("Empresa #{$companyId}: {$company->name}");

        $this->seedAiSystemPrompt($companyId);
        $this->purgeKnowledge($companyId);
        $sources = $this->createSources($companyId);

        $this->command?->info('Fontes criadas: '.count($sources));

        $indexed = $this->indexSources($sources);
        $this->command?->info("Indexadas com sucesso: {$indexed}/".count($sources));
    }

    private function seedAiSystemPrompt(int $companyId): void
    {
        $prompt = <<<'PROMPT'
Você é o atendente virtual da Anúbis Vet Center, uma clínica veterinária com pet shop especializada em atendimento humanizado para cães e gatos.

Seu papel é atuar como atendente comercial e de triagem via WhatsApp.

Você representa a empresa de forma profissional, acolhedora, objetiva e prestativa.

# Objetivo do atendimento

1. Recepcionar o cliente
2. Identificar a necessidade
3. Qualificar o atendimento
4. Direcionar corretamente o lead
5. Coletar informações relevantes
6. Manter o contexto da conversa
7. Atualizar corretamente o estágio do funil

# Estágios válidos

Os slugs e critérios semânticos de cada estágio são informados dinamicamente no contexto ("Estágios disponíveis").
Use suggested_stage apenas com um desses slugs. Omita a chave para manter o estágio atual.
Nunca retroceda no funil. Avance ou mantenha.

# Mensagens da equipe

Mensagens prefixadas com [Atendente humano] são da equipe real. Não as contradiga nem as desfaça.

# Tom de comunicação

Acolhedor, educado, natural, profissional, claro e objetivo. Evite respostas longas, linguagem robótica e múltiplas perguntas em sequência.

# Regras de atendimento

- Cumprimente quando apropriado e demonstre empatia com o pet
- Urgência médica: oriente atendimento presencial imediato, sem diagnosticar
- Valores: responda diretamente e com transparência
- Nunca invente diagnósticos, prescreva medicação ou confirme disponibilidade sem certeza

# Agendamento

Siga o bloco dinâmico de ferramentas no contexto (se presente):

- Com ferramentas de agenda: use-as para consultar disponibilidade e agendar. A clínica apresenta os horários disponíveis ao cliente — nunca peça "qual dia/horário prefere".
- Sem ferramentas de agenda: encaminhe para atendimento humano com requires_handoff: true. Informe que a equipe verificará os horários disponíveis e retornará em breve.

Em ambos os casos:
- NUNCA confirme horário sem consultar ferramenta ou equipe humana
- Use suggested_stage "proposta" quando houver intenção de agendar (ou omita se já estiver em proposta)
- NÃO use requires_handoff em triagem médica, perguntas de preço ou orientações gerais

# Formato obrigatório de resposta (JSON válido, sem markdown)

{
  "message": "texto da resposta ao cliente (vazio se should_reply for false)",
  "suggested_stage": "novo_lead|qualificacao|proposta|fechado (omitir se sem mudança)",
  "summary": "resumo curto e objetivo",
  "should_reply": true,
  "requires_handoff": false
}

requires_handoff true SOMENTE para agendamento sem ferramenta de agenda ou falha em ferramenta. NÃO use em triagem médica, dúvidas de preço ou orientações gerais.
PROMPT;

        CompanyConfig::query()->updateOrCreate(
            ['company_id' => $companyId, 'key' => 'ai.system_prompt'],
            ['value' => $prompt, 'type' => CompanyConfigType::String],
        );

        (new CompanyConfigResolver($companyId))->forgetCache($companyId);
    }

    private function purgeKnowledge(int $companyId): void
    {
        $chunkIds = KnowledgeChunk::query()
            ->where('company_id', $companyId)
            ->pluck('id')
            ->all();

        app(VectorEmbeddingRepository::class)->deleteByChunkIds($companyId, $chunkIds);
        app(VectorEmbeddingRepository::class)->deleteByCompanyId($companyId);

        KnowledgeChunk::query()->where('company_id', $companyId)->delete();
        KnowledgeSource::query()->where('company_id', $companyId)->delete();
    }

    /**
     * @return list<KnowledgeSource>
     */
    private function createSources(int $companyId): array
    {
        $sources = [];

        $sources[] = $this->source($companyId, KnowledgeSourceType::Company, 'Anúbis Vet Center — Perfil', <<<'TXT'
Nome: Anúbis Vet Center
Segmento: Clínica veterinária + pet shop
Atendimento humanizado para cães e gatos.

Especialidades: clínica geral, vacinação, consultas, exames laboratoriais, banho e tosa, venda de rações, medicamentos veterinários, acessórios e check-up preventivo.

Animais atendidos: cães e gatos.

Endereço: Av. Artemis Bastet, 1450 - Jardim Aurora

Horário de funcionamento:
Segunda a sexta: 08:00 às 19:00
Sábado: 08:00 às 16:00
Domingo: fechado

Telefone: (41) 4000-2026
WhatsApp: (41) 99888-2026
TXT, [
            'name' => 'Anúbis Vet Center',
            'segment' => 'Clínica veterinária + pet shop',
            'address' => 'Av. Artemis Bastet, 1450 - Jardim Aurora',
            'phone' => '(41) 4000-2026',
            'whatsapp' => '(41) 99888-2026',
        ]);

        foreach ($this->products() as $product) {
            $sources[] = $this->source(
                $companyId,
                KnowledgeSourceType::Product,
                $product['title'],
                $product['content'],
                $product['metadata'] ?? null,
            );
        }

        foreach ($this->faqs() as $faq) {
            $sources[] = $this->source($companyId, KnowledgeSourceType::Faq, $faq['title'], $faq['content']);
        }

        $sources[] = $this->source($companyId, KnowledgeSourceType::Policy, 'Políticas e restrições de atendimento', $this->policiesContent());
        $sources[] = $this->source($companyId, KnowledgeSourceType::Script, 'Roteiro de atendimento WhatsApp', $this->scriptsContent());
        $sources[] = $this->source($companyId, KnowledgeSourceType::FreeContext, 'Triagem e contexto operacional', $this->freeContextContent());

        return $sources;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function source(
        int $companyId,
        KnowledgeSourceType $type,
        string $title,
        string $content,
        ?array $metadata = null,
    ): KnowledgeSource {
        return KnowledgeSource::query()->create([
            'company_id' => $companyId,
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'metadata' => $metadata,
            'status' => KnowledgeSourceStatus::Pending,
        ]);
    }

    /**
     * @return list<array{title: string, content: string, metadata?: array<string, mixed>}>
     */
    private function products(): array
    {
        return [
            ['title' => 'Consulta clínica', 'content' => 'Consulta clínica veterinária: R$ 120. Avaliação geral do pet com médico veterinário.'],
            ['title' => 'Retorno de consulta', 'content' => 'Retorno de consulta (até 15 dias após a consulta original): gratuito.'],
            ['title' => 'Vacinação V10', 'content' => 'Vacinação V10 (polivalente para cães): R$ 95.'],
            ['title' => 'Vacinação antirrábica', 'content' => 'Vacinação antirrábica: R$ 70. Obrigatória conforme legislação.'],
            ['title' => 'Banho — porte pequeno', 'content' => 'Banho para porte pequeno: R$ 45. Inclui banho com produtos adequados para pets.'],
            ['title' => 'Banho — porte médio', 'content' => 'Banho para porte médio: R$ 65.'],
            ['title' => 'Banho — porte grande', 'content' => 'Banho para porte grande: R$ 95.'],
            ['title' => 'Tosa', 'content' => 'Tosa: a partir de R$ 55. Valor pode variar conforme porte, pelagem e comportamento do animal.'],
            ['title' => 'Hemograma', 'content' => 'Hemograma completo (exame laboratorial): R$ 85.'],
            ['title' => 'Ultrassom', 'content' => 'Ultrassom veterinário: R$ 180. Exame de imagem para diagnóstico assistido pelo veterinário.'],
            ['title' => 'Check-up anual', 'content' => 'Pacote check-up anual preventivo: R$ 320. Pacote de avaliação preventiva anual.'],
            [
                'title' => 'Entrega pet shop',
                'content' => 'Entrega de produtos do pet shop disponível para raio de até 8 km da clínica.',
                'metadata' => ['delivery_radius_km' => 8],
            ],
        ];
    }

    /**
     * @return list<array{title: string, content: string}>
     */
    private function faqs(): array
    {
        return [
            [
                'title' => 'Horário de funcionamento',
                'content' => 'Funcionamos de segunda a sexta das 08:00 às 19:00, sábado das 08:00 às 16:00. Domingo fechado.',
            ],
            [
                'title' => 'Quais animais são atendidos?',
                'content' => 'Atendemos exclusivamente cães e gatos na clínica e no pet shop.',
            ],
            [
                'title' => 'Onde fica a clínica?',
                'content' => 'Estamos na Av. Artemis Bastet, 1450 - Jardim Aurora. Telefone (41) 4000-2026 e WhatsApp (41) 99888-2026.',
            ],
            [
                'title' => 'Como funciona o retorno da consulta?',
                'content' => 'O retorno da consulta é gratuito quando realizado em até 15 dias após a consulta original.',
            ],
            [
                'title' => 'Vocês fazem entrega do pet shop?',
                'content' => 'Sim, entregamos produtos do pet shop em um raio de até 8 km da clínica.',
            ],
            [
                'title' => 'O que fazer em caso de urgência?',
                'content' => 'Em urgência médica, orientamos comparecer imediatamente à clínica no endereço Av. Artemis Bastet, 1450. Priorize atendimento presencial; não realizamos diagnóstico por mensagem.',
            ],
            [
                'title' => 'Valores de banho e tosa',
                'content' => 'Banho: porte pequeno R$ 45, médio R$ 65, grande R$ 95. Tosa a partir de R$ 55 conforme porte e pelagem.',
            ],
        ];
    }

    private function policiesContent(): string
    {
        return <<<'MD'
# Políticas de atendimento — Anúbis Vet Center

## Restrições (nunca fazer)

- Inventar diagnósticos ou prescrever medicação
- Dar orientações médicas complexas por mensagem
- Afirmar disponibilidade de horário sem confirmação da equipe
- Pedir ao cliente qual dia ou horário prefere para agendar
- Fornecer informações fora do contexto da clínica

## Agendamento

- A clínica informa os horários disponíveis; nunca peça ao cliente qual dia ou horário prefere
- Com ferramenta de agenda: consulte disponibilidade e ofereça horários reais ao cliente
- Sem ferramenta de agenda: encaminhe para atendimento humano
- Colete nome do pet e motivo do atendimento antes de agendar

## Quando não souber

Orientar o cliente a aguardar atendimento humano da equipe.

## Urgência médica

Priorizar atendimento presencial imediato na clínica. Não tentar diagnosticar pelo WhatsApp.

## Transparência de preços

Sempre informar valores oficiais da tabela quando o cliente perguntar.
MD;
    }

    private function scriptsContent(): string
    {
        return <<<'MD'
# Scripts comerciais — WhatsApp

## Abertura (novo lead)

"Olá! 😊 Seja bem-vindo à Anúbis Vet Center. Como podemos ajudar seu pet hoje?"

## Consulta de preço

Informe o valor diretamente e peça nome e idade do pet para seguir.

Exemplo: "A consulta clínica custa R$ 120. Pode me informar o nome e a idade do seu pet para seguirmos com o atendimento?"

## Agendamento

Com ferramenta de agenda: consulte disponibilidade e apresente horários ao cliente.
Sem ferramenta: encaminhe para a equipe humana — NÃO peça dia ou horário.

Exemplo sem ferramenta: "Perfeito! Vou encaminhar seu atendimento para nossa equipe, que verificará os horários disponíveis e retornará em breve para confirmar o agendamento."

Exemplo com ferramenta: "Encontrei estes horários disponíveis: [horários]. Qual prefere?"

## Encaminhamento humano

"Entendi. Vou encaminhar seu atendimento para nossa equipe humana o quanto antes."

## Tom

Acolhedor, educado, natural, profissional. Frases curtas. Uma pergunta por vez quando precisar de dados.
MD;
    }

    private function freeContextContent(): string
    {
        return <<<'TXT'
# Informações para triagem

Coletar quando necessário:
- Nome do tutor
- Nome do pet
- Espécie (cão ou gato)
- Idade aproximada
- Motivo do atendimento
- Urgência (sim/não)
- Porte do animal (para banho e tosa)

Não coletar preferência de dia/horário quando não houver ferramenta de agenda — a equipe humana informa os horários disponíveis.

# Agendamento

- Com ferramenta de agenda: use as ferramentas para consultar e confirmar horários
- Sem ferramenta de agenda: encaminhar para atendimento humano com requires_handoff true

# Resumo da conversa (campo summary)

Formato técnico curto. Exemplo: "Tutor João solicitou consulta para gato adulto com vômito leve. Cliente pediu valores e disponibilidade."

# Estágios do funil

novo_lead, qualificacao, proposta, fechado — usar exatamente esses valores no JSON.
TXT;
    }

    /**
     * @param  list<KnowledgeSource>  $sources
     */
    private function indexSources(array $sources): int
    {
        $indexer = app(KnowledgeIndexingService::class);
        $withVectors = filter_var(env('ANUBIS_SEED_WITH_VECTORS', false), FILTER_VALIDATE_BOOL);
        $success = 0;

        foreach ($sources as $source) {
            try {
                if ($withVectors) {
                    $indexer->indexSource($source->fresh());
                } else {
                    $indexer->indexSourceWithoutEmbeddings($source->fresh());
                }
                $success++;
                $this->command?->line("  ✓ {$source->title}");
            } catch (Throwable $exception) {
                $this->command?->warn("  ✗ {$source->title}: {$exception->getMessage()}");
            }
        }

        if (! $withVectors) {
            $this->command?->warn('Indexação sem vetores (contexto estruturado ativo). Defina ANUBIS_SEED_WITH_VECTORS=true e suba o Ollama para busca semântica.');
        }

        return $success;
    }
}
