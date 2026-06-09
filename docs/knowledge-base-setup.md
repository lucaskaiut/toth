# Base de Conhecimento (RAG)

Módulo multi-tenant de Knowledge Base com indexação assíncrona, busca semântica (pgvector) e injeção de contexto no prompt da IA.

## Arquitetura

| Camada | Tecnologia |
|--------|------------|
| Dados transacionais | MySQL (`knowledge_sources`, `knowledge_chunks`) |
| Embeddings / busca | PostgreSQL + pgvector (`knowledge_embeddings`) |
| Filas de indexação | Redis (`IndexKnowledgeSourceJob`, `ReindexTenantKnowledgeJob`) |
| Embeddings | Ollama (`nomic-embed-text`, `bge-m3`) ou OpenAI-compatible |

## Subir infraestrutura

```bash
docker compose up -d
```

O serviço `postgres-vector` expõe a porta **5433** no host.

## Variáveis de ambiente (`api/.env`)

```env
QUEUE_CONNECTION=redis

EMBEDDING_DRIVER=ollama
OLLAMA_URL=http://host.docker.internal:11434
OLLAMA_EMBEDDING_MODEL=nomic-embed-text
EMBEDDING_DIMENSIONS=768

VECTOR_DB_HOST=postgres-vector
VECTOR_DB_PORT=5432
VECTOR_DB_DATABASE=toth_vectors
VECTOR_DB_USERNAME=toth
VECTOR_DB_PASSWORD=secret

REDIS_HOST=redis
```

Para OpenAI embeddings:

```env
EMBEDDING_DRIVER=openai
OPENAI_EMBEDDING_API_KEY=sk-...
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
EMBEDDING_DIMENSIONS=1536
```

> Ajuste `EMBEDDING_DIMENSIONS` e recrie a tabela vetorial se trocar de modelo.

## Migrations

```bash
docker compose exec api php artisan migrate
docker compose exec api php artisan vector:migrate
```

## Filas

O worker `queue` no Compose processa jobs Redis. Após alterar código de jobs:

```bash
docker compose restart queue
```

## API (`/api/knowledge`)

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/sources` | Lista fontes (`?type=faq`) |
| POST | `/sources` | Cria fonte |
| PUT | `/sources/{id}` | Atualiza |
| DELETE | `/sources/{id}` | Remove |
| POST | `/sources/documents` | Upload de documento |
| POST | `/sources/{id}/reindex` | Reindexa uma fonte |
| POST | `/reindex-all` | Reindexa tenant |
| GET | `/stats` | Dashboard de indexação |
| POST | `/search` | Busca semântica |
| POST | `/context` | Contexto consolidado para LLM |

## Frontend

Rota protegida: **`/settings/knowledge`**

## Integração com IA

`KnowledgeContextBuilder` é chamado em `ConversationContextBuilder` antes de cada inferência, injetando seções estruturadas + top-K chunks relevantes no system prompt.

## Rebuild PHP (pdo_pgsql)

Após atualizar o Dockerfile:

```bash
docker compose build api queue reverb
docker compose up -d
```
