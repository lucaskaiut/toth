# Documento Técnico do Sistema — Toth CRM (MVP)

Este documento descreve o funcionamento **completo** do MVP do CRM com WhatsApp + IA + Kanban, com foco em:

- **Visão de produto**: regras de negócio e fluxos (não técnico)
- **Visão técnica**: arquitetura, stacks, endpoints, arquivos e decisões

Referência principal de requisitos: `docs/requirements.md`.

---

## 1. Visão geral do MVP

### Objetivo

Oferecer um CRM web multiempresa para atendimento comercial via WhatsApp, com:

- criação automática de lead ao receber mensagem
- histórico completo de conversa
- Kanban por estágio do funil
- atendimento automático por IA (quando configurado)
- atualização em tempo real na interface (mensagens e movimentação de cards)

### Componentes

- **Backend**: Laravel (API)
- **Frontend**: React Router (Vite) com SPA (SSR desativado)
- **Tempo real**: Laravel Reverb + Laravel Echo (cliente)
- **Infra**: MySQL + Redis (Docker Compose)

---

## 2. Stacks e execução (Docker)

Arquivo: `docker-compose.yaml`

Serviços relevantes:

- **nginx**: expõe a API em `http://localhost:8080`
- **api**: PHP-FPM + Laravel (interno, atendido pelo nginx)
- **mysql**: banco relacional
- **redis**: cache (config por empresa) e suporte para escalabilidade
- **reverb**: WebSocket em `ws://localhost:8081`
- **web**: Vite dev server em `http://localhost:5173`

Observação importante:

- No `web/vite.config.ts`, existe proxy apenas para `"/api"` (NÃO deve haver proxy para `"/app"`).

---

## 3. Regras de negócio (RN) e como são atendidas

### RN-001 — Um lead deve possuir apenas uma conversa ativa

- Implementação: tabela `conversations` tem `unique(lead_id)`.
- Migração: `api/database/migrations/2026_05_26_000003_create_conversations_table.php`

### RN-002 — Mensagem recebida persistida antes de IA

- Implementação: o webhook persiste a mensagem (origin `customer`) dentro de transação e só depois agenda o job da IA.
- Serviço: `api/app/Modules/Conversation/Domain/Services/IncomingMessageProcessor.php`

### RN-003 — IA não envia direto ao WhatsApp

- Implementação: envio WhatsApp é sempre disparado no backend via `WhatsAppClient` (camada `Core`), nunca pelo frontend.
- Core: `api/app/Core/Whatsapp/Contracts/WhatsAppClient.php`

### RN-004 — Resposta IA persistida antes de enviar

- Implementação: `ConversationAiProcessor` salva a mensagem (`origin = ai`), atualiza resumo, move estágio e só depois chama `WhatsAppClient->send(...)`.
- Serviço: `api/app/Modules/Conversation/Domain/Services/ConversationAiProcessor.php`

### RN-005 — Movimentação automática após resposta válida

- Implementação: o lead só é movido após a resposta estruturada ser parseada e persistida.
- Serviço: `ConversationAiProcessor`

### RN-006 — Mensagens manuais entram no contexto da IA

- Implementação: mensagens origin `user` também são consideradas na reconstrução do contexto.
- Serviço: `api/app/Modules/Conversation/Domain/Services/ConversationContextBuilder.php`

### Estado de atendimento da conversa (MVP obrigatório)

O **lead** possui estágio de funil (`pipeline_stages`). A **conversa** possui estado de atendimento independente, para controlar se a IA pode responder.

Estados (`conversations.attendance_status`):

| Valor | Significado | IA responde? |
|-------|-------------|--------------|
| `ai_enabled` | Atendimento automático pela IA | Sim |
| `handoff_to_human` | Humano assumiu a conversa | Não |
| `waiting_human` | Aguardando ação do humano | Não |
| `closed` | Conversa encerrada | Não |

Regras automáticas:

- Nova conversa inicia como `ai_enabled`.
- Quando um atendente envia mensagem manual (`origin = user`), o sistema muda para `handoff_to_human` (a IA para).
- Webhook continua persistindo mensagens do cliente, mas **não agenda IA** se o estado não for `ai_enabled`.

Alteração manual (UI/API):

- `PATCH /api/conversations/{conversation}/attendance-status` permite reativar IA, marcar aguardando humano ou encerrar.

Arquivos:

- Enum: `api/app/Modules/Conversation/Domain/Enums/ConversationAttendanceStatus.php`
- Serviço: `api/app/Modules/Conversation/Domain/Services/ConversationAttendanceService.php`
- Migration: `api/database/migrations/2026_05_27_000001_add_attendance_status_to_conversations_table.php`

---

## 4. Modelo de dados (MVP)

### Tabelas principais

- `pipeline_stages`: estágios do funil por empresa (4 estágios padrão)
- `leads`: entidade de lead, por empresa, com telefone único por empresa
- `conversations`: 1 conversa por lead, armazena `summary` e `attendance_status`
- `messages`: histórico completo, com origem (`customer|ai|user`) e `sent_at`
- `company_configs`: armazenamento genérico de configs por empresa
- `integration_logs`: logs de falhas/erros das integrações (WhatsApp/IA)

Migrations:

- `api/database/migrations/2026_05_26_000001_create_pipeline_stages_table.php`
- `api/database/migrations/2026_05_26_000002_create_leads_table.php`
- `api/database/migrations/2026_05_26_000003_create_conversations_table.php`
- `api/database/migrations/2026_05_26_000004_create_messages_table.php`
- `api/database/migrations/2026_05_26_000005_create_company_configs_table.php`
- `api/database/migrations/2026_05_26_000006_create_integration_logs_table.php`

### Entities / Models

- Lead: `api/app/Modules/Lead/Domain/Models/Lead.php`
- PipelineStage: `api/app/Modules/Lead/Domain/Models/PipelineStage.php`
- Conversation: `api/app/Modules/Conversation/Domain/Models/Conversation.php`
- Message: `api/app/Modules/Conversation/Domain/Models/Message.php`
- CompanyConfig: `api/app/Modules/CompanyConfig/Domain/Models/CompanyConfig.php`
- IntegrationLog: `api/app/Modules/IntegrationLog/Domain/Models/IntegrationLog.php`

### Estágios padrão (RF-014)

Enum: `api/app/Modules/Lead/Domain/Enums/DefaultPipelineStage.php`

- `novo_lead` → "Novo Lead"
- `qualificacao` → "Qualificação"
- `proposta` → "Proposta"
- `fechado` → "Fechado"

Seed por empresa:

- Serviço: `api/app/Modules/Lead/Domain/Services/PipelineStageService.php`
- Usado em: registro de empresa, webhook e job.

---

## 5. Multiempresa e contexto da empresa

### Tenant atual

O backend já utiliza `CurrentCompany` para rotas autenticadas:

- Contexto: `api/app/Modules/Company/Domain/CurrentCompany.php`
- Middleware: `api/app/Modules/Company/Http/Middleware/InitializeCompany.php`
- Alias middleware `company`: `api/bootstrap/app.php`

Fluxo:

- Usuário autenticado via Sanctum (token)
- Middleware `company` carrega `user->company` e preenche `CurrentCompany`

### Configurações por empresa (genéricas)

Requisito: entity com `(company_id, key, value, type)` para configs arbitrárias.

Implementação:

- Model: `api/app/Modules/CompanyConfig/Domain/Models/CompanyConfig.php`
- Enum tipo: `api/app/Modules/CompanyConfig/Domain/Enums/CompanyConfigType.php`

Chaves esperadas (exemplos):

- WhatsApp Evolution:
  - `evolution.instance_name` (provisionado automaticamente no cadastro)
- IA:
  - `ai.api_key`
  - `ai.model`
  - `ai.system_prompt`

### Resolução global + cache

Helper:

- `company_config($key, $default = null)`
- Arquivo: `api/app/helpers.php` (carregado via `composer.json` → `autoload.files`)

Resolver cacheado:

- `CompanyConfigResolver`: `api/app/Modules/CompanyConfig/Domain/Services/CompanyConfigResolver.php`
- TTL/prefixo: `api/config/company.php`
- Driver cache: usa o `cache.default` (em produção recomenda-se `redis`)

Importante:

- Para o webhook (que não possui `CurrentCompany`), a resolução é por `company_id` (via `CompanyConfig`), com base em `evolution.instance_name`.

---

## 6. Integrações externas (padrão Core / Integrations)

### 6.1. WhatsApp (Evolution) — Multiempresa

**Core (contratos e DTOs):**

- Contrato: `api/app/Core/Whatsapp/Contracts/WhatsAppClient.php`
  - `send`, `parseWebhook`
  - `createInstance`, `connectInstance`, `getConnectionState`
- DTOs:
  - `IncomingWhatsAppMessage`
  - `OutgoingWhatsAppMessage`
  - `SendMessageResult`
  - `CreateWhatsAppInstanceData`, `WhatsAppConnectResult`, `WhatsAppConnectionStateResult`

**Integration (concreto Evolution):**

- Client: `api/app/Integrations/Whatsapp/Evolution/EvolutionWhatsAppClient.php`
- Parser webhook: `api/app/Integrations/Whatsapp/Evolution/EvolutionWebhookParser.php`

Config via `.env` (global):

- `WHATSAPP_DRIVER` (default `evolution`)
- `WHATSAPP_BASE_URL`
- `WHATSAPP_API_KEY` (chave administrativa global da Evolution)
- `WHATSAPP_TIMEOUT`
- `WHATSAPP_WEBHOOK_TOKEN` (validação do header `authorization` no webhook)

Config por empresa (banco):

- `evolution.instance_name` (gerado automaticamente: `toth_{company_id}_{hash}`)

### 6.1.1. Cadastro + provisionamento + onboarding WhatsApp

Estados da empresa (`companies.status`):

- `pending_whatsapp_connection` — cadastro concluído, aguardando QR Code
- `active` — WhatsApp conectado; CRM liberado

Fluxo:

1. `POST /api/register` com `whatsapp` (número principal, somente dígitos)
2. Backend cria empresa em `pending_whatsapp_connection`
3. Backend gera `instance_name` determinístico e chama `POST /instance/create` na Evolution
4. Webhook configurado inline: `{APP_URL}/api/webhooks/whatsapp`
5. `evolution.instance_name` salvo em `company_configs`
6. Frontend redireciona para `/setup/whatsapp`
7. `GET /api/company/whatsapp/connect` → QR Code / pairing code
8. Polling `GET /api/company/whatsapp/connection-state` (5s) até `state=open`
9. Empresa passa para `active` e usuário é redirecionado ao Kanban

Rotas protegidas do CRM exigem middleware `company.active`.

Falha no provisionamento: transação do cadastro é revertida (empresa não fica inconsistente).

### 6.2. IA (OpenAI-compatible)

**Core:**

- Contrato: `api/app/Core/AI/Contracts/AiClient.php`
- DTOs:
  - `AiChatRequest`, `AiChatMessage`
  - `AiStructuredResponse` (message + suggestedStage + summary)

**Integration:**

- Provider compatível: `api/app/Integrations/AI/OpenAICompatible/OpenAiCompatibleClient.php`

Config via `.env` (global):

- `AI_DRIVER` (default `openai_compatible`)
- `AI_BASE_URL`
- `AI_TIMEOUT`
- defaults auxiliares: `AI_DEFAULT_MODEL`, `AI_DEFAULT_SYSTEM_PROMPT`, `AI_RECENT_MESSAGES_LIMIT`

Config por empresa (banco):

- `ai.api_key`
- `ai.model`
- `ai.system_prompt`

### 6.3. Resolução por driver (Service Provider)

Bindings por configuração:

- Provider: `api/app/Providers/IntegrationServiceProvider.php`
- Registrado em: `api/bootstrap/providers.php`

---

## 7. Fluxo principal ponta-a-ponta (WhatsApp → CRM)

### 7.0. Debounce de mensagens (MVP obrigatório)

No WhatsApp é comum o cliente enviar **várias mensagens curtas em sequência** em poucos segundos. Responder a cada mensagem individualmente gera:

- múltiplas respostas “quebradas”
- contexto ruim para a IA
- ruído no histórico e no atendimento

**Comportamento esperado (debounce):**

> Ao receber mensagem do cliente, o sistema aguarda uma janela de **8–15 segundos sem novas mensagens** para então processar a IA uma única vez com o lote mais recente.

Implementação:

- O webhook sempre **persiste** cada mensagem recebida (RN-002).
- A chamada à IA é disparada por job **debounced** (único por conversa), que:
  - mantém uma chave `ai_debounce_until:{companyId}:{conversationId}` no cache
  - se executado antes do prazo, ele se “release” para rodar mais tarde
  - quando o prazo expira, monta contexto com **as últimas mensagens** e responde uma vez

Configuração por ambiente:

- `AI_DEBOUNCE_MIN_SECONDS` (default 8)
- `AI_DEBOUNCE_MAX_SECONDS` (default 15)

Arquivos:

- Agendamento no webhook: `api/app/Modules/Conversation/Domain/Services/IncomingMessageProcessor.php`
- Job: `api/app/Modules/Conversation/Domain/Jobs/DebouncedProcessConversationAiJob.php`

### 7.1. Recebimento de mensagem (Webhook)

Endpoint público:

- `POST /api/webhooks/whatsapp`
- Controller: `api/app/Modules/Whatsapp/Http/Controllers/WhatsAppWebhookController.php`

Processamento:

1. `WhatsAppClient->parseWebhook(...)` converte payload em `IncomingWhatsAppMessage`
2. resolve `company_id` via `company_configs` onde `key = evolution.instance_name` e `value = instanceName`
3. garante estágios padrão para a empresa
4. cria/encontra lead por telefone
5. cria/encontra conversa do lead
6. salva mensagem (origin `customer`)
7. dispara broadcasts de tempo real
8. agenda o job de IA (assíncrono)

Serviço principal:

- `api/app/Modules/Conversation/Domain/Services/IncomingMessageProcessor.php`

### 7.2. Job de IA

Job:

- `api/app/Modules/Conversation/Domain/Jobs/ProcessConversationAiJob.php`

Responsabilidades:

1. garante estágios padrão
2. carrega conversation + lead + stage
3. chama `ConversationAiProcessor->process(...)`

### 7.3. Processamento da IA

Serviço:

- `api/app/Modules/Conversation/Domain/Services/ConversationAiProcessor.php`

Fluxo:

1. lê `ai.api_key` por empresa; se não houver, encerra sem responder
2. monta contexto via `ConversationContextBuilder`
3. chama `AiClient->chat(...)`
4. interpreta JSON estruturado (message, suggested_stage, summary)
5. persiste mensagem (origin `ai`)
6. atualiza summary em `conversations.summary`
7. move lead para o stage sugerido
8. broadcast de:
   - `message.created`
   - `conversation.updated`
   - `lead.stage_changed` (se mudou)
9. envia WhatsApp via Evolution (se `evolution.api_key` + `evolution.instance_name` estiverem configurados)

Contexto (centralizado):

- `api/app/Modules/Conversation/Domain/Services/ConversationContextBuilder.php`
- Envia para IA:
  - prompt de sistema
  - resumo persistido
  - últimas N mensagens
  - estágio atual do lead

### 7.4. Mensagens manuais (usuário interno)

Endpoint (autenticado):

- `POST /api/conversations/{conversation}/messages`

Fluxo:

1. salva mensagem origin `user` no histórico
2. broadcast em tempo real
3. envia WhatsApp via Evolution (se configurado)

Serviço:

- `api/app/Modules/Conversation/Domain/Services/OutgoingMessageService.php`

---

## 8. Kanban (RF-013 a RF-017)

### Backend (dados e movimentação)

Endpoints:

- `GET /api/pipeline/stages` (semeia estágios padrão se necessário)
- `GET /api/leads`
- `PATCH /api/leads/{lead}/stage` (movimentação manual)

Arquivos:

- Controller: `api/app/Modules/Lead/Http/Controllers/LeadController.php`
- Stage controller: `api/app/Modules/Lead/Http/Controllers/PipelineStageController.php`
- Service: `api/app/Modules/Lead/Domain/Services/LeadService.php`

### Frontend (UI)

Rotas:

- `/kanban` → `web/app/routes/_app.kanban.tsx`

Componentes:

- `web/app/features/kanban/components/KanbanBoard.tsx`
- `web/app/features/kanban/components/KanbanColumn.tsx`
- `web/app/features/kanban/components/LeadCard.tsx`

Drag-and-drop:

- Lib: `@dnd-kit/*`
- Estratégia: atualização **otimista** no React Query ao mover stage e sync posterior.

---

## 9. Inbox / Atendimento (RF-026 a RF-028)

### Backend

Endpoints:

- `GET /api/conversations`
- `GET /api/conversations/{conversation}/messages`
- `POST /api/conversations/{conversation}/messages` (envio manual)

Arquivos:

- Controller: `api/app/Modules/Conversation/Http/Controllers/ConversationController.php`

### Frontend

Rotas:

- `/inbox` → `web/app/routes/_app.inbox.tsx`

Componentes:

- `web/app/features/inbox/components/InboxLayout.tsx`
- `web/app/features/inbox/components/ConversationList.tsx`
- `web/app/features/inbox/components/ChatPanel.tsx`
- `web/app/features/inbox/components/MessageBubble.tsx`

---

## 10. Tempo real (WebSocket)

### Backend (Broadcast)

Canal privado por empresa:

- `company.{companyId}`
- Autorização: `api/routes/channels.php` valida `user.company_id == companyId`

Eventos:

- `message.created`: `api/app/Modules/Realtime/Events/MessageCreated.php`
- `conversation.updated`: `api/app/Modules/Realtime/Events/ConversationUpdated.php`
- `lead.stage_changed`: `api/app/Modules/Realtime/Events/LeadStageChanged.php`

Auth de broadcasting:

- Rotas geradas via `Broadcast::routes()` dentro do grupo autenticado (`auth:sanctum`, `company`)
- Definido em `api/routes/api.php`

### Frontend (Echo)

Echo/Reverb:

- `web/app/lib/realtime/echo.ts`

Provider:

- `web/app/providers/RealtimeProvider.tsx`
- Assina `private-company.{companyId}` e atualiza caches do React Query.

Env no frontend:

- `VITE_REVERB_APP_KEY`
- `VITE_REVERB_HOST`
- `VITE_REVERB_PORT`
- `VITE_REVERB_SCHEME`

---

## 11. Endpoints (resumo)

### Públicos

- `POST /api/login`
- `POST /api/register`
- `POST /api/webhooks/whatsapp`

### Autenticados (token Sanctum + company)

- `GET /api/me`
- `GET /api/pipeline/stages`
- `GET /api/leads`
- `GET /api/leads/{lead}`
- `PUT /api/leads/{lead}`
- `PATCH /api/leads/{lead}/stage`
- `GET /api/conversations`
- `GET /api/conversations/{conversation}`
- `GET /api/conversations/{conversation}/messages`
- `POST /api/conversations/{conversation}/messages`
- `PATCH /api/conversations/{conversation}/attendance-status`
- `GET /api/company-configs`
- `PUT /api/company-configs`
- `GET|POST /api/broadcasting/auth`

Arquivo de rotas:

- `api/routes/api.php`

---

## 12. Decisões técnicas (por que assim)

### Core vs Integrations

Motivo:

- manter **domínio independente de fornecedor**
- permitir trocar driver por `.env` sem refatorar regras

### Config por empresa no banco

Motivo:

- Evolution exige `API_KEY` e `INSTANCE_NAME` por empresa (multiempresa)
- IA também varia por empresa (modelo, prompt, chave)

### Contexto da IA centralizado

Motivo:

- evitar lógica distribuída em controllers
- garantir consistência do prompt e do formato estruturado

### Tempo real via Reverb

Motivo:

- requisito RNF-002 (atualização sem refresh)
- integra bem com Laravel Broadcast + canais privados por empresa

### SPA no frontend

Motivo:

- autenticação via token em `localStorage`
- SSR complicava o bootstrap e redirecionamentos (tela branca)

---

## 13. Observabilidade e logs (RNF-004)

Erros de integração são registrados em:

- tabela: `integration_logs`
- service: `api/app/Modules/IntegrationLog/Domain/Services/IntegrationLogService.php`

Também escreve no log do Laravel (`Log::error(...)`).

---

## 14. Como configurar uma empresa (passo a passo)

1. Definir no `.env` da API:

- `WHATSAPP_BASE_URL`, `WHATSAPP_API_KEY`
- `WHATSAPP_WEBHOOK_TOKEN` (mesmo valor enviado no header do webhook Evolution)
- `APP_URL` (URL pública usada no webhook)

2. Registrar usuário/empresa:

- `POST /api/register` (channel `internal`, campo `whatsapp` obrigatório)
- instância Evolution criada automaticamente

3. Conectar WhatsApp (onboarding):

- Acessar `/setup/whatsapp` no frontend
- Escanear QR Code retornado por `GET /api/company/whatsapp/connect`

4. Configurar IA (opcional, manual):

- `PUT /api/company-configs` com `ai.api_key` e `ai.model`

Sem `ai.api_key`:

- lead/conversa/mensagem são persistidos normalmente
- job encerra e **não responde** via IA

---

## 15. Arquivos mais importantes (mapa rápido)

Backend:

- Webhook: `api/app/Modules/Whatsapp/Http/Controllers/WhatsAppWebhookController.php`
- Entrada webhook → domínio: `api/app/Modules/Conversation/Domain/Services/IncomingMessageProcessor.php`
- Job IA: `api/app/Modules/Conversation/Domain/Jobs/ProcessConversationAiJob.php`
- Processador IA: `api/app/Modules/Conversation/Domain/Services/ConversationAiProcessor.php`
- Contexto IA: `api/app/Modules/Conversation/Domain/Services/ConversationContextBuilder.php`
- Kanban/Lead: `api/app/Modules/Lead/**`
- Config por empresa: `api/app/Modules/CompanyConfig/**`
- Broadcast events: `api/app/Modules/Realtime/Events/**`
- Canais: `api/routes/channels.php`

Frontend:

- Guards: `web/app/features/auth/guards/*`
- Onboarding WhatsApp: `web/app/routes/_setup.whatsapp.tsx`, `web/app/features/setup/**`
- Kanban: `web/app/features/kanban/**`
- Inbox: `web/app/features/inbox/**`
- Realtime: `web/app/providers/RealtimeProvider.tsx`, `web/app/lib/realtime/echo.ts`
- API client: `web/app/lib/api/client.ts`

---

## 16. Limitações atuais do MVP

- Não há UI dedicada para editar `company_configs` no frontend (apenas endpoint; WhatsApp é provisionado no cadastro).
- Não há tratamento avançado de mídia WhatsApp (áudio/imagem), apenas texto.
- Não há filas robustas configuradas (o job usa o driver padrão; em produção recomenda-se queue + workers).
- IA assume retorno JSON; providers podem exigir hardening adicional (validação/retentativas).

